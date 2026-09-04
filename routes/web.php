<?php

use Illuminate\Support\Facades\Route;

// 1. Root & Auth
Route::get('/', function () {
    return redirect('/login');
});

Route::get('/login', function () {
    if (auth()->check()) {
        $user = auth()->user();
        if ($user->isRepresentante()) {
            return redirect('/painel-representante');
        }
        return redirect('/dashboard');
    }
    return view('auth.login');
})->name('login');

// 2. Session-protected & Session-enabled routes
Route::middleware([
    \Illuminate\Cookie\Middleware\EncryptCookies::class,
    \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
    \Illuminate\Session\Middleware\StartSession::class,
    \Illuminate\View\Middleware\ShareErrorsFromSession::class,
    \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
])->group(function () {
    
    // Representative Public URL (Accessed via Token Link - needs session for CSRF)
    Route::get('/cotacoes/token/{token}', function ($token) {
        return view('quote.representative', ['token' => $token]);
    });

    // Auth route to handle standard POST submit from login form
    Route::post('/login', function (\Illuminate\Http\Request $request) {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (auth()->attempt($credentials)) {
            $request->session()->regenerate();
            $user = auth()->user();
            
            if (!$user->ativo) {
                auth()->logout();
                return back()->withErrors(['email' => 'Conta inativa.']);
            }
            
            if ($user->isRepresentante()) {
                return redirect('/painel-representante');
            }
            return redirect('/dashboard');
        }

        return back()->withErrors(['email' => 'E-mail ou senha incorretos.']);
    });

    Route::post('/logout', function (\Illuminate\Http\Request $request) {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    });

    // Protected Views
    Route::middleware('auth')->group(function () {
        
        // Master Quotations List (All authenticated users)
        Route::get('/cotacoes', function () {
            if (auth()->user()->isRepresentante()) {
                return redirect('/painel-representante');
            }
            return view('admin.quotes');
        });

        // Quotation Sales Funnel (Gestor, Diretor, Faturamento & Administrador)
        Route::get('/funil', function () {
            if (auth()->user()->isRepresentante()) {
                return redirect('/painel-representante');
            }
            return view('admin.funnel');
        });

        // Approvals (Gestor & Diretor)
        Route::get('/aprovacoes', function () {
            if (auth()->user()->isFaturamento()) {
                return redirect('/faturamento');
            }
            return view('admin.approvals');
        });
        
        Route::get('/aprovacoes/{id}', function ($id) {
            if (auth()->user()->isFaturamento()) {
                return redirect('/faturamento');
            }
            return view('admin.approval_detail', ['id' => $id]);
        });

        // Billing (Faturamento, Diretor & Administrador)
        Route::get('/faturamento', function () {
            if (!auth()->user()->isFaturamento() && !auth()->user()->isDiretor() && !auth()->user()->isAdministrador()) {
                return redirect('/aprovacoes');
            }
            return view('admin.billing');
        });
        
        Route::get('/faturamento/{id}', function ($id) {
            if (!auth()->user()->isFaturamento() && !auth()->user()->isDiretor() && !auth()->user()->isAdministrador()) {
                return redirect('/aprovacoes');
            }
            return view('admin.billing_detail', ['id' => $id]);
        });

        // Parameters (Administrador and Diretor)
        Route::get('/parametros', function () {
            if (!auth()->user()->isAdministrador() && !auth()->user()->isDiretor()) {
                return redirect('/aprovacoes');
            }
            return view('admin.parameters');
        });

        // User Management (Administrador only)
        Route::get('/usuarios', function () {
            if (!auth()->user()->isAdministrador()) {
                return redirect('/aprovacoes');
            }
            return view('admin.users');
        });

        // Client Management (Administrador only)
        Route::get('/clientes', function () {
            if (!auth()->user()->isAdministrador()) {
                return redirect('/aprovacoes');
            }
            return view('admin.partners');
        });

        // Product Catalog Management (Administrador only)
        Route::get('/produtos', function () {
            if (!auth()->user()->isAdministrador()) {
                return redirect('/aprovacoes');
            }
            return view('admin.products');
        });

        // Representative Mobile Dashboard
        Route::get('/painel-representante', function () {
            if (!auth()->user()->isRepresentante()) {
                return redirect('/aprovacoes');
            }
            return view('representative.dashboard');
        });

        // General Business Intelligence Dashboard
        Route::get('/dashboard', function () {
            return view('admin.dashboard');
        });

        // Chat Logs Auditor (WhatsApp Web style)
        Route::get('/conversas', function () {
            if (!auth()->user()->hasChatAccess()) {
                return redirect('/dashboard');
            }
            return view('admin.chat_audit');
        });

        // Quote Audit logs dedicated page
        Route::get('/cotacoes/{id}/auditoria', function ($id) {
            return view('admin.quote_audit', ['id' => $id]);
        });
        
    });
});
