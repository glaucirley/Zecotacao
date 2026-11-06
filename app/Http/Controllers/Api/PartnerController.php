<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Parceiro;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PartnerController extends Controller
{
    /**
     * List partners/clients. Restricted to Administrators. Limited to 20 items by default.
     * High-performance query with index scans, terms filtering and query cache.
     */
    public function index(Request $request)
    {
        $search = trim((string)$request->input('search', ''));
        $status = $request->input('status');
        $limit = $request->filled('limit') ? min((int)$request->input('limit'), 100) : 20;

        $cacheKey = 'partners_search_' . md5(mb_strtolower($search) . '_' . ($status ?? '') . '_' . $limit);

        $partners = Cache::remember($cacheKey, 300, function () use ($search, $status, $limit) {
            $query = Parceiro::query()->select([
                'id', 'codigo_sankhya', 'razao_social', 'nome_fantasia',
                'cnpj', 'cidade', 'uf', 'bairro', 'ativo'
            ]);

            if ($search !== '') {
                $cleanDigits = preg_replace('/[^0-9]/', '', $search);
                $terms = array_filter(explode(' ', $search));
                $significantTerms = array_values(array_filter($terms, fn($t) => mb_strlen($t) >= 2));
                if (empty($significantTerms)) {
                    $significantTerms = $terms;
                }

                $query->where(function ($q) use ($significantTerms, $cleanDigits, $search) {
                    if (strlen($cleanDigits) >= 3) {
                        $q->where('cnpj', 'like', "%{$cleanDigits}%")
                          ->orWhere('codigo_sankhya', 'like', "%{$search}%");
                    }

                    $q->orWhere(function ($allTermsQ) use ($significantTerms) {
                        foreach ($significantTerms as $term) {
                            $allTermsQ->where(function ($subQ) use ($term) {
                                $subQ->where('razao_social', 'like', "%{$term}%")
                                     ->orWhere('nome_fantasia', 'like', "%{$term}%")
                                     ->orWhere('codigo_sankhya', 'like', "%{$term}%");
                            });
                        }
                    });
                });

                $safeSearch = addslashes($search);
                $query->orderByRaw("
                    CASE 
                        WHEN codigo_sankhya = '{$safeSearch}' THEN 1
                        WHEN razao_social LIKE '{$safeSearch}%' OR nome_fantasia LIKE '{$safeSearch}%' THEN 2
                        ELSE 3
                    END ASC,
                    razao_social ASC
                ");
            } else {
                $query->orderBy('razao_social');
            }

            if ($status === 'ativo') {
                $query->where('ativo', true);
            } elseif ($status === 'inativo') {
                $query->where('ativo', false);
            }

            return $query->take($limit)->get();
        });

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

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Falha ao criar cliente: " . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'error' => 'Erro interno do servidor',
                'code' => 'PARTNER_CREATE_ERROR',
                'message' => config('app.debug') ? ('Falha ao criar cliente: ' . $e->getMessage()) : 'Ocorreu um erro ao criar o cliente. Os detalhes foram registrados nos logs do servidor.'
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

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Falha ao atualizar cliente ID {$id}: " . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'error' => 'Erro interno do servidor',
                'code' => 'PARTNER_UPDATE_ERROR',
                'message' => config('app.debug') ? ('Falha ao atualizar cliente: ' . $e->getMessage()) : 'Ocorreu um erro ao atualizar o cliente. Os detalhes foram registrados nos logs do servidor.'
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
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Falha ao excluir cliente ID {$id}: " . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'error' => 'Erro interno do servidor',
                'code' => 'PARTNER_DELETE_ERROR',
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
            \Illuminate\Support\Facades\Log::error("Erro ao importar CSV de clientes: " . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'error' => 'Erro interno do servidor',
                'code' => 'PARTNER_IMPORT_ERROR',
                'message' => config('app.debug') ? ('Erro ao processar o arquivo: ' . $e->getMessage()) : 'Erro ao processar o arquivo de clientes. Os detalhes foram registrados nos logs do servidor.'
            ], 500);
        }
    }
}

