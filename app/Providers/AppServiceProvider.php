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
