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
            $iaSchemaLock = storage_path('framework/schema_ia_unfound_2026_10_18.lock');
            if (!is_file($iaSchemaLock)) {
                try {
                    if (Schema::hasTable('cotacao_itens')) {
                        Schema::table('cotacao_itens', function (\Illuminate\Database\Schema\Blueprint $table) {
                            if (!Schema::hasColumn('cotacao_itens', 'nao_encontrado')) {
                                $table->boolean('nao_encontrado')->default(false)->after('status_item');
                            }
                            if (!Schema::hasColumn('cotacao_itens', 'descricao_original')) {
                                $table->string('descricao_original', 500)->nullable()->after('nao_encontrado');
                            }
                            if (!Schema::hasColumn('cotacao_itens', 'observacao_ia')) {
                                $table->text('observacao_ia')->nullable()->after('descricao_original');
                            }
                        });
                    }
                    if (!Schema::hasTable('produtos_nao_encontrados')) {
                        Schema::create('produtos_nao_encontrados', function (\Illuminate\Database\Schema\Blueprint $table) {
                            $table->id();
                            $table->string('codigo_sankhya')->unique();
                            $table->string('descricao');
                            $table->integer('requisicoes')->default(1);
                            $table->string('ultimo_solicitante')->nullable();
                            $table->timestamps();
                        });
                    }
                    if (Schema::hasColumn('cotacao_itens', 'nao_encontrado')) {
                        @file_put_contents($iaSchemaLock, date('c'));
                    }
                } catch (\Throwable $e) {
                    // Fail silently if DB cannot be altered right now
                }
            }

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
