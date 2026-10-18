<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Schema;

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

        if (Schema::hasTable('parametros_sistema')) {
            $lockFile = storage_path('framework/cleanup_2026_10_18_v1.lock');
            if (!is_file($lockFile)) {
                try {
                    $migrationFile = database_path('migrations/2026_10_18_131500_clean_test_openings_and_fix_audit_roles.php');
                    if (is_file($migrationFile)) {
                        $migration = require $migrationFile;
                        if (is_object($migration) && method_exists($migration, 'runCleanup')) {
                            $migration::runCleanup();
                        }
                    }
                    @file_put_contents($lockFile, date('c'));
                } catch (\Throwable $e) {
                    // Fail silently if DB is not ready
                }
            }
        }
    }
}
