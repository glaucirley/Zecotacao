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
     * List all partners/clients. Restricted to Administrators.
     */
    public function index(Request $request)
    {
        $partners = Parceiro::orderBy('razao_social')->get();

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
                'message' => 'Client created successfully.',
                'data' => $partner
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Database error',
                'message' => 'Failed to create client. ' . $e->getMessage()
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
                'message' => 'Client updated successfully.',
                'data' => $partner
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Database error',
                'message' => 'Failed to update client. ' . $e->getMessage()
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
                'message' => 'Client deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Database error',
                'message' => 'Failed to delete client. They might have associated quotes. Try deactivating them instead.'
            ], 500);
        }
    }
}
