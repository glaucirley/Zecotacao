<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\IntegrationController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\QuoteController;
use App\Http\Controllers\Api\ApprovalController;
use App\Http\Controllers\Api\BillingController;
use App\Http\Controllers\Api\ParameterController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\PartnerController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ChatLogController;

Route::prefix('v1')->group(function () {
    // 1. Webhook N8N API Route
    Route::middleware('api.key')->group(function () {
        Route::post('/cotacoes', [IntegrationController::class, 'store']);
        Route::post('/chat/logs', [ChatLogController::class, 'store']);
    });

    // 2. Session-based Auth Routes
    Route::middleware([
        \Illuminate\Cookie\Middleware\EncryptCookies::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        \Illuminate\Session\Middleware\StartSession::class,
    ])->group(function () {
        Route::post('/auth/login', [AuthController::class, 'login']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);
        
        // Protected by session auth (Gestor, Diretor, Faturamento)
        Route::middleware('auth')->group(function () {
            // 4. Approval Routes (Gestor / Diretor)
            Route::get('/aprovacoes', [ApprovalController::class, 'index']);
            Route::get('/aprovacoes/{cotacao_id}', [ApprovalController::class, 'show']);
            Route::get('/cotacoes/todas', [QuoteController::class, 'listAll']);
            Route::get('/cotacoes/meta', [QuoteController::class, 'getMeta']);
            Route::post('/cotacoes/manual', [QuoteController::class, 'storeManual']);
            Route::get('/produtos', [QuoteController::class, 'listProducts']);
            Route::delete('/cotacoes/{id}', [QuoteController::class, 'destroy']);
            Route::patch('/aprovacoes/{cotacao_id}/itens/{item_id}', [ApprovalController::class, 'simulatePrice']);
            Route::post('/aprovacoes/{cotacao_id}/vincular', [ApprovalController::class, 'bindItems']);
            Route::post('/aprovacoes/{cotacao_id}/decisao', [ApprovalController::class, 'decide']);

            // 5. Billing Routes (Faturamento)
            Route::get('/faturamento/fila', [BillingController::class, 'index']);
            Route::get('/faturamento/{cotacao_id}', [BillingController::class, 'show']);
            Route::post('/faturamento/{cotacao_id}/pedido', [BillingController::class, 'createExternalOrder']);
            Route::patch('/faturamento/{cotacao_id}/conferencia', [BillingController::class, 'updateConference']);
            Route::post('/faturamento/{cotacao_id}/divergencia', [BillingController::class, 'logDivergence']);
            Route::post('/faturamento/{cotacao_id}/faturar', [BillingController::class, 'bill']);

            // 10. Notification Routes
            Route::get('/notificacoes', [NotificationController::class, 'index']);
            Route::get('/notificacoes/unread-count', [NotificationController::class, 'unreadCount']);
            Route::patch('/notificacoes/{id}/ler', [NotificationController::class, 'markAsRead']);
            Route::post('/notificacoes/ler-tudo', [NotificationController::class, 'markAllAsRead']);

            // 11. Dashboard Route
            Route::get('/dashboard/stats', [DashboardController::class, 'getStats']);

            // 12. WhatsApp Chat Logs Routes
            Route::get('/chat/contatos', [ChatLogController::class, 'listContacts']);
            Route::get('/chat/historico/{telefone}', [ChatLogController::class, 'getHistory']);

            // 6. System Parameters Routes (Director only)
            Route::get('/parametros', [ParameterController::class, 'index']);
            Route::patch('/parametros/{chave}', [ParameterController::class, 'update']);
            Route::post('/parametros/sankhya/conexao', [ParameterController::class, 'saveSankhyaSettings']);
            Route::post('/parametros/sankhya/testar', [ParameterController::class, 'testSankhyaConnection']);
            Route::post('/parametros/sankhya/sincronizar', [ParameterController::class, 'syncSankhyaCatalog']);

            // 7. User Management Routes (Administrator only)
            Route::get('/usuarios', [UserController::class, 'index']);
            Route::post('/usuarios', [UserController::class, 'store']);
            Route::patch('/usuarios/{id}', [UserController::class, 'update']);
            Route::delete('/usuarios/{id}', [UserController::class, 'destroy']);

            // 13. Representative Check-in Routes
            Route::post('/checkin', [\App\Http\Controllers\Api\CheckinController::class, 'store']);
            Route::get('/checkin/recentes', [\App\Http\Controllers\Api\CheckinController::class, 'listRecents']);
            Route::get('/checkin/todos', [\App\Http\Controllers\Api\CheckinController::class, 'listAll']);

            // 8. Client Management Routes (Administrator only)
            Route::get('/clientes', [PartnerController::class, 'index']);
            Route::post('/clientes', [PartnerController::class, 'store']);
            Route::patch('/clientes/{id}', [PartnerController::class, 'update']);
            Route::delete('/clientes/{id}', [PartnerController::class, 'destroy']);

            // 9. Product Catalog CRUD Routes (Administrator only)
            Route::get('/produtos-admin', [ProductController::class, 'index']);
            Route::post('/produtos-admin', [ProductController::class, 'store']);
            Route::patch('/produtos-admin/{id}', [ProductController::class, 'update']);
            Route::delete('/produtos-admin/{id}', [ProductController::class, 'destroy']);
        });
    });

    // 3. Representative Public Token Routes
    Route::middleware(['token.auth'])->group(function () {
        Route::get('/cotacoes/token/{token}', [QuoteController::class, 'show']);
        Route::patch('/cotacoes/token/{token}', [QuoteController::class, 'update']);
        Route::post('/cotacoes/token/{token}/itens', [QuoteController::class, 'addItem']);
        Route::delete('/cotacoes/token/{token}/itens/{item_id}', [QuoteController::class, 'removeItem']);
        Route::post('/cotacoes/token/{token}/justificativa', [QuoteController::class, 'addJustification']);
        Route::post('/cotacoes/token/{token}/enviar', [QuoteController::class, 'submit']);
        Route::post('/cotacoes/token/{token}/perdida', [QuoteController::class, 'markAsLost']);
        Route::post('/cotacoes/token/{token}/faturar', [QuoteController::class, 'releaseForBilling']);
        Route::get('/cotacoes/token/{token}/pdf', [QuoteController::class, 'generatePdf']);
        Route::get('/cotacoes/token/{token}/produtos', [QuoteController::class, 'listProducts']);
    });
});
