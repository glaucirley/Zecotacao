<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;

class WebAuthController extends Controller
{
    /**
     * Display login form or redirect authenticated user.
     */
    public function showLoginForm(Request $request)
    {
        if (Auth::check()) {
            $redirect = $request->query('redirect');
            if ($redirect && str_starts_with($redirect, '/') && !str_starts_with($redirect, '//')) {
                return redirect($redirect);
            }
            $user = Auth::user();
            if ($user->isRepresentante()) {
                return redirect('/painel-representante');
            }
            if ($user->isSuporte()) {
                return redirect('/cotacoes');
            }
            return redirect('/dashboard');
        }
        return view('auth.login');
    }

    /**
     * Authenticate web session user.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $remember = $request->boolean('remember', true);

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();
            $user = Auth::user();

            if (!$user->ativo) {
                Auth::logout();
                return back()->withErrors(['email' => 'Conta inativa.']);
            }

            $request->session()->put('zecotacao_internal_user', true);
            Cookie::queue('zecotacao_internal', '1', 60 * 24 * 30);

            // Safe internal redirect if provided
            $redirect = $request->input('redirect');
            if ($redirect && str_starts_with($redirect, '/') && !str_starts_with($redirect, '//')) {
                return redirect($redirect);
            }

            // Intended URL if set by auth middleware
            if ($intended = redirect()->getIntendedUrl()) {
                return redirect()->intended();
            }

            if ($user->isRepresentante()) {
                return redirect('/painel-representante');
            }
            if ($user->isSuporte()) {
                return redirect('/cotacoes');
            }
            return redirect('/dashboard');
        }

        return back()->withErrors(['email' => 'E-mail ou senha incorretos.']);
    }

    /**
     * Terminate web session.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        Cookie::queue(Cookie::forget('zecotacao_internal'));
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}
