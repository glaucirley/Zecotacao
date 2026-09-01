<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Authenticate session users (gestor, diretor, faturamento, representative fallback).
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            $user = Auth::user();

            if (!$user->ativo) {
                Auth::logout();
                return response()->json(['error' => 'User account is inactive.'], 403);
            }

            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'nome' => $user->nome,
                    'email' => $user->email,
                    'papel' => $user->papel,
                ]
            ]);
        }

        return response()->json([
            'error' => 'Invalid email or password.'
        ], 401);
    }

    /**
     * Log out session users.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['success' => true]);
    }

    /**
     * Get details of currently logged in session user.
     */
    public function me()
    {
        if (Auth::check()) {
            $user = Auth::user();
            return response()->json([
                'logged_in' => true,
                'user' => [
                    'id' => $user->id,
                    'nome' => $user->nome,
                    'email' => $user->email,
                    'papel' => $user->papel,
                ]
            ]);
        }

        return response()->json(['logged_in' => false], 401);
    }
}
