<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PublicProposalController;
use App\Http\Controllers\RepresentativeLinkController;
use App\Http\Controllers\Web\WebAuthController;
use App\Http\Controllers\Web\WebViewController;

// Public Customer Proposal Routes (Accessed via link generated for WhatsApp / Web Share)
Route::get('/p/{token}', [PublicProposalController::class, 'show'])->name('public.proposal');
Route::get('/p/{token}/pdf', [PublicProposalController::class, 'downloadPdf'])->name('public.proposal.pdf');
Route::post('/p/{token}/track', [PublicProposalController::class, 'track'])->name('public.proposal.track');

// 1. Root & Auth Routes
Route::redirect('/', '/login');

Route::get('/login', [WebAuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [WebAuthController::class, 'login'])->middleware('throttle:login');
Route::post('/logout', [WebAuthController::class, 'logout'])->name('logout');

// 2. Web routes (Representative Links)
Route::get('/r/{token}', [RepresentativeLinkController::class, 'show'])->name('representative.link');
Route::get('/cotacoes/token/{token}', [RepresentativeLinkController::class, 'redirectLegacyToken']);

// 3. Protected Views
Route::middleware('auth')->group(function () {
    // Master Quotations List (All authenticated users)
    Route::get('/cotacoes', [WebViewController::class, 'quotes']);

    // Quotation Sales Funnel (Gestor, Diretor, Faturamento & Administrador)
    Route::get('/funil', [WebViewController::class, 'funnel']);

    // Approvals (Gestor & Diretor)
    Route::get('/aprovacoes', [WebViewController::class, 'approvals']);
    Route::get('/aprovacoes/{id}', [WebViewController::class, 'approvalDetail']);

    // Billing (Faturamento, Diretor & Administrador)
    Route::get('/faturamento', [WebViewController::class, 'billing']);
    Route::get('/faturamento/{id}', [WebViewController::class, 'billingDetail']);

    // Parameters (Administrador and Diretor)
    Route::get('/parametros', [WebViewController::class, 'parameters']);

    // User Management (Administrador only)
    Route::get('/usuarios', [WebViewController::class, 'users']);

    // Client Management (Administrador only)
    Route::get('/clientes', [WebViewController::class, 'partners']);

    // Product Catalog Management (Administrador only)
    Route::get('/produtos', [WebViewController::class, 'products']);

    // Representative Mobile Dashboard
    Route::get('/painel-representante', [WebViewController::class, 'representativeDashboard']);

    // General Business Intelligence Dashboard
    Route::get('/dashboard', [WebViewController::class, 'dashboard']);

    // Chat Logs Auditor (WhatsApp Web style)
    Route::get('/conversas', [WebViewController::class, 'conversations']);

    // Quote Audit logs dedicated page
    Route::get('/cotacoes/{id}/auditoria', [WebViewController::class, 'quoteAudit']);

    // Authenticated View/Edit Quote by ID
    Route::get('/cotacoes/id/{id}', [WebViewController::class, 'quoteDetail']);
});
