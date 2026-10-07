<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->alias([
            'api.key' => \App\Http\Middleware\ApiKeyMiddleware::class,
            'token.auth' => \App\Http\Middleware\TokenAuthenticationMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return response()->json([
                    'error' => 'Unauthenticated.',
                    'message' => 'Sessão expirada. Por favor, faça login novamente.'
                ], 401);
            }
        });

        $exceptions->render(function (\Illuminate\Database\QueryException $e, \Illuminate\Http\Request $request) {
            \Illuminate\Support\Facades\Log::error("Database QueryException on {$request->method()} {$request->path()}: " . $e->getMessage(), [
                'sql' => $e->getSql(),
                'bindings' => $e->getBindings(),
                'url' => $request->fullUrl(),
                'user_id' => auth()->id() ?? null,
            ]);

            if ($request->is('api/*') || $request->wantsJson()) {
                return response()->json([
                    'error' => 'Erro interno do servidor',
                    'message' => 'Ocorreu um erro no banco de dados ao processar sua solicitação. Os detalhes foram registrados nos logs do servidor.'
                ], 500);
            }
        });

        $exceptions->render(function (\PDOException $e, \Illuminate\Http\Request $request) {
            \Illuminate\Support\Facades\Log::error("PDOException on {$request->method()} {$request->path()}: " . $e->getMessage(), [
                'url' => $request->fullUrl(),
                'user_id' => auth()->id() ?? null,
            ]);

            if ($request->is('api/*') || $request->wantsJson()) {
                return response()->json([
                    'error' => 'Erro interno do servidor',
                    'message' => 'Ocorreu uma falha de conexão com o banco de dados. Os detalhes foram registrados nos logs do servidor.'
                ], 500);
            }
        });
    })->create();
