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

            $validadeLock = storage_path('framework/schema_validade_2026_10_18.lock');
            if (!is_file($validadeLock)) {
                try {
                    if (Schema::hasTable('produtos') && !Schema::hasColumn('produtos', 'validade_proxima')) {
                        Schema::table('produtos', function (\Illuminate\Database\Schema\Blueprint $table) {
                            $table->date('validade_proxima')->nullable()->after('unidade');
                        });
                    }
                    if (Schema::hasTable('cotacao_itens') && !Schema::hasColumn('cotacao_itens', 'validade')) {
                        Schema::table('cotacao_itens', function (\Illuminate\Database\Schema\Blueprint $table) {
                            $table->date('validade')->nullable()->after('observacao_ia');
                        });
                    }
                    if (Schema::hasColumn('cotacao_itens', 'validade') && Schema::hasColumn('produtos', 'validade_proxima')) {
                        @file_put_contents($validadeLock, date('c'));
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

            $restoreApprovalLock = storage_path('framework/restore_expired_in_approval_2026_10_18.lock');
            if (!is_file($restoreApprovalLock)) {
                try {
                    $expiredQuotes = \App\Models\Cotacao::where('status', 'EXPIRADA')
                        ->with('historico')
                        ->get();
                    $defaultHours = (int) \App\Models\ParametroSistema::getVal('VALIDADE_PADRAO_HORAS', 24);

                    foreach ($expiredQuotes as $eq) {
                        $history = $eq->historico->sortBy('id')->values();
                        $lastStatusEvent = null;
                        foreach ($history as $h) {
                            if ($h->evento === 'COTACAO_EXPIRADA') {
                                continue;
                            }
                            if (in_array($h->evento, [
                                'ENVIADA_AO_DIRETOR',
                                'ESCALADA_PARA_DIRETORIA',
                                'ENVIADA_AO_GESTOR',
                                'APROVADA_DIRETOR',
                                'APROVADA_GESTOR',
                                'APROVADA_ADMINISTRADOR',
                                'LIBERADA_AUTOMATICAMENTE',
                                'DEVOLVIDA_AO_REPRESENTANTE',
                                'COTACAO_DEVOLVIDA',
                                'TODOS_ITENS_RECUSADOS',
                                'PDF_GERADO',
                            ])) {
                                $lastStatusEvent = $h->evento;
                            }
                        }

                        if (in_array($lastStatusEvent, ['ENVIADA_AO_DIRETOR', 'ESCALADA_PARA_DIRETORIA', 'ENVIADA_AO_GESTOR'])) {
                            $restoredStatus = ($lastStatusEvent === 'ENVIADA_AO_GESTOR') ? 'AGUARDANDO_GESTOR' : 'COM_DIRETOR';
                            $hoursToAdd = max(1, (int)($eq->validade_horas ?: $defaultHours));
                            $newVal = now()->addHours($hoursToAdd);

                            $eq->update([
                                'status' => $restoredStatus,
                                'data_validade' => $newVal,
                            ]);

                            \App\Models\CotacaoHistorico::create([
                                'cotacao_id' => $eq->id,
                                'evento' => 'VALIDADE_ESTENDIDA_EM_APROVACAO',
                                'usuario_id' => null,
                                'papel' => 'sistema',
                                'condicao' => "Cotação restaurada para {$restoredStatus} pois havia expirado enquanto aguardava avaliação. Validade estendida até " . $newVal->format('d/m/Y H:i') . ".",
                            ]);
                        }
                    }
                    @file_put_contents($restoreApprovalLock, date('c'));
                } catch (\Throwable $e) {
                    // Fail silently if DB is not ready
                }
            }
        }
    }
}
