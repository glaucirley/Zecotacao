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
        // 1. Unauthenticated JSON handling
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'error' => 'Unauthenticated.',
                    'code' => 'AUTH_UNAUTHENTICATED',
                    'message' => 'Sessão expirada. Por favor, faça login novamente.'
                ], 401);
            }
        });

        // 2. Database QueryException (sanitized response, full log)
        $exceptions->render(function (\Illuminate\Database\QueryException $e, \Illuminate\Http\Request $request) {
            \Illuminate\Support\Facades\Log::error("Database QueryException on {$request->method()} {$request->path()}: " . $e->getMessage(), [
                'sql' => $e->getSql(),
                'bindings' => $e->getBindings(),
                'url' => $request->fullUrl(),
                'user_id' => auth()->id() ?? null,
            ]);

            if ($request->is('api/*') || $request->expectsJson() || $request->wantsJson() || $request->ajax()) {
                if (config('app.debug')) {
                    return response()->json([
                        'error' => 'Erro no banco de dados (Debug Mode)',
                        'code' => 'DATABASE_QUERY_ERROR',
                        'message' => $e->getMessage(),
                    ], 500);
                }

                return response()->json([
                    'error' => 'Erro interno do servidor',
                    'code' => 'DATABASE_ERROR',
                    'message' => 'Ocorreu um erro no banco de dados ao processar sua solicitação. Os detalhes foram registrados nos logs do servidor.'
                ], 500);
            }

            if (!config('app.debug')) {
                return response()->make('Ocorreu um erro interno ao processar a solicitação.', 500);
            }
        });

        // 3. Database PDOException (sanitized response, full log)
        $exceptions->render(function (\PDOException $e, \Illuminate\Http\Request $request) {
            \Illuminate\Support\Facades\Log::error("PDOException on {$request->method()} {$request->path()}: " . $e->getMessage(), [
                'url' => $request->fullUrl(),
                'user_id' => auth()->id() ?? null,
            ]);

            if ($request->is('api/*') || $request->expectsJson() || $request->wantsJson() || $request->ajax()) {
                if (config('app.debug')) {
                    return response()->json([
                        'error' => 'Falha de conexão com banco de dados (Debug Mode)',
                        'code' => 'DATABASE_CONNECTION_ERROR',
                        'message' => $e->getMessage(),
                    ], 500);
                }

                return response()->json([
                    'error' => 'Erro interno do servidor',
                    'code' => 'DATABASE_CONNECTION_ERROR',
                    'message' => 'Ocorreu uma falha de conexão com o banco de dados. Os detalhes foram registrados nos logs do servidor.'
                ], 500);
            }

            if (!config('app.debug')) {
                return response()->make('Ocorreu uma falha de conexão com o banco de dados.', 500);
            }
        });

        // 4. Global fallback for any unhandled Throwable in production (APP_DEBUG=false)
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            if ($e instanceof \Illuminate\Auth\AuthenticationException
                || $e instanceof \Illuminate\Validation\ValidationException
                || $e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                return null;
            }

            \Illuminate\Support\Facades\Log::error("Unhandled exception [{$e->getCode()}] on {$request->method()} {$request->path()}: " . $e->getMessage(), [
                'exception' => $e,
                'url' => $request->fullUrl(),
                'user_id' => auth()->id() ?? null,
            ]);

            if (!config('app.debug')) {
                if ($request->is('api/*') || $request->expectsJson() || $request->wantsJson() || $request->ajax()) {
                    return response()->json([
                        'error' => 'Erro interno do servidor',
                        'code' => 'INTERNAL_SERVER_ERROR',
                        'message' => 'Ocorreu um erro interno no servidor. Os detalhes foram registrados nos logs.'
                    ], 500);
                }

                return response()->make('Ocorreu um erro interno no servidor.', 500);
            }
        });
    })->create();
