<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (app()->environment('production') || config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        // Auto-reparo de integridade: garantir que cotacao_itens.produto_id seja NULL para itens não encontrados
        try {
            if (!\Illuminate\Support\Facades\Cache::has('schema_cotacao_itens_produto_id_nullable')) {
                $col = \Illuminate\Support\Facades\DB::selectOne("
                    SELECT IS_NULLABLE 
                    FROM INFORMATION_SCHEMA.COLUMNS 
                    WHERE TABLE_SCHEMA = DATABASE() 
                      AND TABLE_NAME = 'cotacao_itens' 
                      AND COLUMN_NAME = 'produto_id'
                ");
                if ($col && strtoupper((string)$col->IS_NULLABLE) === 'NO') {
                    \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0');
                    \Illuminate\Support\Facades\DB::statement('ALTER TABLE cotacao_itens MODIFY COLUMN produto_id BIGINT UNSIGNED NULL');
                    \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1');
                }
                \Illuminate\Support\Facades\Cache::put('schema_cotacao_itens_produto_id_nullable', true, now()->addDays(7));
            }
        } catch (\Throwable $th) {}

        // Rate limiter for authentication endpoints (prevent brute-force attacks)
        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->input('email');
            return Limit::perMinute(5)->by($email . '|' . $request->ip())->response(function () {
                if (request()->is('api/*') || request()->expectsJson() || request()->wantsJson() || request()->ajax()) {
                    return response()->json([
                        'success' => false,
                        'error' => 'TOO_MANY_ATTEMPTS',
                        'message' => 'Muitas tentativas de login consecutivas. Por favor, aguarde 60 segundos antes de tentar novamente.',
                    ], 429);
                }
                return back()->withErrors([
                    'email' => 'Muitas tentativas de login consecutivas. Por favor, aguarde 60 segundos antes de tentar novamente.'
                ])->withInput(request()->except('password'));
            });
        });
    }
}
