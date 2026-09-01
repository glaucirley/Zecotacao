<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Equipe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * List all users and teams for dropdowns. Restricted to Directors.
     */
    public function index(Request $request)
    {
        $currentUser = Auth::user();
        if (!$currentUser->isAdministrador()) {
            return response()->json(['error' => 'Forbidden. Only administrators can manage users.'], 403);
        }

        $users = User::with('equipe')->orderBy('nome')->get();
        $teams = Equipe::orderBy('nome')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'users' => $users,
                'teams' => $teams
            ]
        ]);
    }

    /**
     * Create a new user.
     */
    public function store(Request $request)
    {
        $currentUser = Auth::user();
        if (!$currentUser->isAdministrador()) {
            return response()->json(['error' => 'Forbidden. Only administrators can manage users.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'nome' => 'required|string|max:255',
            'email' => 'required|email|unique:usuarios,email',
            'password' => 'required|string|min:6',
            'papel' => 'required|in:diretor,gestor,representante,faturamento,administrador',
            'telefone' => 'nullable|string|max:50',
            'limite_desconto_percentual' => 'nullable|numeric|min:0|max:100',
            'codigo_sankhya' => 'nullable|string|max:100|unique:usuarios,codigo_sankhya',
            'equipe_id' => 'nullable|exists:equipes,id',
            'ativo' => 'required|boolean',
            'permissoes_dashboard' => 'nullable|array',
            'acesso_chat' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation error', 'messages' => $validator->errors()], 422);
        }

        try {
            $user = User::create([
                'nome' => $request->input('nome'),
                'email' => $request->input('email'),
                'senha_hash' => Hash::make($request->input('password')),
                'papel' => $request->input('papel'),
                'telefone' => $request->input('telefone'),
                'limite_desconto_percentual' => $request->input('limite_desconto_percentual', 0.00),
                'codigo_sankhya' => $request->input('codigo_sankhya'),
                'equipe_id' => $request->input('equipe_id'),
                'ativo' => $request->input('ativo', true),
                'permissoes_dashboard' => $request->input('permissoes_dashboard'),
                'acesso_chat' => $request->input('acesso_chat', false),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User created successfully.',
                'data' => $user
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Database error',
                'message' => 'Failed to create user. ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update an existing user.
     */
    public function update(Request $request, $id)
    {
        $currentUser = Auth::user();
        if (!$currentUser->isAdministrador()) {
            return response()->json(['error' => 'Forbidden. Only administrators can manage users.'], 403);
        }

        $user = User::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'nome' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('usuarios', 'email')->ignore($user->id)],
            'password' => 'nullable|string|min:6',
            'papel' => 'required|in:diretor,gestor,representante,faturamento,administrador',
            'telefone' => 'nullable|string|max:50',
            'limite_desconto_percentual' => 'nullable|numeric|min:0|max:100',
            'codigo_sankhya' => ['nullable', 'string', 'max:100', Rule::unique('usuarios', 'codigo_sankhya')->ignore($user->id)],
            'equipe_id' => 'nullable|exists:equipes,id',
            'ativo' => 'required|boolean',
            'permissoes_dashboard' => 'nullable|array',
            'acesso_chat' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation error', 'messages' => $validator->errors()], 422);
        }

        try {
            $updateData = [
                'nome' => $request->input('nome'),
                'email' => $request->input('email'),
                'papel' => $request->input('papel'),
                'telefone' => $request->input('telefone'),
                'limite_desconto_percentual' => $request->input('limite_desconto_percentual', 0.00),
                'codigo_sankhya' => $request->input('codigo_sankhya'),
                'equipe_id' => $request->input('equipe_id'),
                'ativo' => $request->input('ativo'),
                'permissoes_dashboard' => $request->input('permissoes_dashboard'),
                'acesso_chat' => $request->input('acesso_chat', false),
            ];

            // Only update password if filled
            if ($request->filled('password')) {
                $updateData['senha_hash'] = Hash::make($request->input('password'));
            }

            $user->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully.',
                'data' => $user
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Database error',
                'message' => 'Failed to update user. ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a user.
     */
    public function destroy($id)
    {
        $currentUser = Auth::user();
        if (!$currentUser->isAdministrador()) {
            return response()->json(['error' => 'Forbidden.'], 403);
        }

        $user = User::findOrFail($id);

        // Prevent self deletion
        if ($user->id === $currentUser->id) {
            return response()->json(['error' => 'Conflict', 'message' => 'You cannot delete yourself.'], 409);
        }

        try {
            $user->delete();
            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Database error',
                'message' => 'Failed to delete user. Maybe they have associated quotes? Try deactivating them instead.'
            ], 500);
        }
    }
}
