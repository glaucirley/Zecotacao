<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Parceiro;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PartnerController extends Controller
{
    /**
     * List partners/clients. Restricted to Administrators. Limited to 20 items by default.
     */
    public function index(Request $request)
    {
        $query = Parceiro::query();

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $terms = array_filter(explode(' ', $search));
            if (!empty($terms)) {
                $query->where(function ($q) use ($terms) {
                    foreach ($terms as $term) {
                        $q->where(function ($subQ) use ($term) {
                            $subQ->where('codigo_sankhya', 'like', "%{$term}%")
                                 ->orWhere('razao_social', 'like', "%{$term}%")
                                 ->orWhere('nome_fantasia', 'like', "%{$term}%")
                                 ->orWhere('cnpj', 'like', "%{$term}%")
                                 ->orWhere('cidade', 'like', "%{$term}%")
                                 ->orWhere('bairro', 'like', "%{$term}%");

                            $cleanDigits = preg_replace('/[^0-9]/', '', $term);
                            if (strlen($cleanDigits) >= 3) {
                                $subQ->orWhere('cnpj', 'like', "%{$cleanDigits}%");
                            }
                        });
                    }
                });
            }
        }

        if ($request->filled('status')) {
            $st = $request->input('status');
            if ($st === 'ativo') {
                $query->where('ativo', true);
            } elseif ($st === 'inativo') {
                $query->where('ativo', false);
            }
        }

        $limit = $request->filled('limit') ? min((int)$request->input('limit'), 100) : 20;

        $partners = $query->orderBy('razao_social')->take($limit)->get();

        return response()->json([
            'success' => true,
            'data' => $partners
        ]);
    }

    /**
     * Store a new partner.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user->isAdministrador()) {
            return response()->json(['error' => 'Forbidden.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'codigo_sankhya' => 'required|string|max:100|unique:parceiros,codigo_sankhya',
            'razao_social' => 'required|string|max:255',
            'nome_fantasia' => 'nullable|string|max:255',
            'cnpj' => 'nullable|string|max:50|unique:parceiros,cnpj',
            'telefone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:150',
            'endereco' => 'nullable|string|max:255',
            'cidade' => 'nullable|string|max:100',
            'uf' => 'nullable|string|max:2',
            'cep' => 'nullable|string|max:20',
            'ativo' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation error', 'messages' => $validator->errors()], 422);
        }

        try {
            $partner = Parceiro::create($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Cliente criado com sucesso.',
                'data' => $partner
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Database error',
                'message' => 'Falha ao criar cliente: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update an existing partner.
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->isAdministrador()) {
            return response()->json(['error' => 'Forbidden.'], 403);
        }

        $partner = Parceiro::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'codigo_sankhya' => ['required', 'string', 'max:100', Rule::unique('parceiros', 'codigo_sankhya')->ignore($partner->id)],
            'razao_social' => 'required|string|max:255',
            'nome_fantasia' => 'nullable|string|max:255',
            'cnpj' => ['nullable', 'string', 'max:50', Rule::unique('parceiros', 'cnpj')->ignore($partner->id)],
            'telefone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:150',
            'endereco' => 'nullable|string|max:255',
            'cidade' => 'nullable|string|max:100',
            'uf' => 'nullable|string|max:2',
            'cep' => 'nullable|string|max:20',
            'ativo' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation error', 'messages' => $validator->errors()], 422);
        }

        try {
            $partner->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Cliente atualizado com sucesso.',
                'data' => $partner
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Database error',
                'message' => 'Falha ao atualizar cliente: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete an existing partner.
     */
    public function destroy($id)
    {
        $user = Auth::user();
        if (!$user->isAdministrador()) {
            return response()->json(['error' => 'Forbidden.'], 403);
        }

        $partner = Parceiro::findOrFail($id);

        try {
            $partner->delete();
            return response()->json([
                'success' => true,
                'message' => 'Cliente excluído com sucesso.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Database error',
                'message' => 'Falha ao excluir cliente. Pode haver cotações vinculadas. Tente desativá-lo.'
            ], 500);
        }
    }

    /**
     * Import partners from an uploaded CSV file.
     */
    public function importFile(Request $request, \App\Services\PartnerImportService $importService)
    {
        $user = Auth::user();
        if (!$user->isAdministrador()) {
            return response()->json(['error' => 'Forbidden.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'arquivo' => 'required|file|max:51200', // max 50MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Arquivo inválido. Selecione um arquivo CSV ou TXT de até 50MB.',
                'errors'  => $validator->errors()
            ], 422);
        }

        $file = $request->file('arquivo');
        $path = $file->getRealPath();

        try {
            $result = $importService->importFromCsv($path);

            return response()->json([
                'success'     => true,
                'message'     => "Importação concluída: {$result['criados']} novos clientes cadastrados e {$result['atualizados']} atualizados de um total de {$result['total']} registros.",
                'total'       => $result['total'],
                'criados'     => $result['criados'],
                'atualizados' => $result['atualizados'],
                'erros'       => $result['erros'],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao processar o arquivo: ' . $e->getMessage()
            ], 500);
        }
    }
}

