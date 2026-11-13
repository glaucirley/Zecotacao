<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SankhyaDatabaseService;

class SyncTelemarketingCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sankhya:sync-telemarketing';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza os vendedores de telemarketing (AD_VENDTLMKT) da TGFPAR do Sankhya para os clientes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Iniciando sincronização direta de Vendedores Telemarketing (AD_VENDTLMKT)...");

        try {
            $db = resolve(SankhyaDatabaseService::class);
            $result = $db->syncAllPartnerTelemarketing();

            if ($result['success']) {
                $this->info("✓ Sucesso!");
                $this->info("  Total de registros com AD_VENDTLMKT no Oracle: {$result['total_oracle']}");
                $this->info("  Total de clientes atualizados no Zé Cotação: {$result['count']}");

                if (!empty($result['amostra'])) {
                    $this->line("\nAmostra dos primeiros dados recebidos do Sankhya:");
                    foreach ($result['amostra'] as $item) {
                        $p = $item['CODPARC'] ?? $item['codparc'] ?? '?';
                        $v = $item['AD_VENDTLMKT'] ?? $item['ad_vendtlmkt'] ?? '?';
                        $this->line("  - Cliente Sankhya {$p} -> Vendedor Telemarketing: {$v}");
                    }
                }

                $totalComVendedor6 = \App\Models\Parceiro::whereNotNull('vendedor_6_codigo')->where('vendedor_6_codigo', '<>', '')->count();
                $this->info("\nTotal atual de clientes com vendedor 6 cadastrado: {$totalComVendedor6}");

                return Command::SUCCESS;
            }

            $this->error("Falha ao consultar AD_VENDTLMKT no Oracle:");
            $this->error($result['error'] ?? 'Erro desconhecido');

            if (!empty($result['sugestoes'])) {
                $this->warn("\nColunas encontradas na tabela TGFPAR que podem corresponder a telemarketing/vendedor:");
                foreach ($result['sugestoes'] as $col) {
                    $this->line("  * {$col}");
                }
            }

            return Command::FAILURE;
        } catch (\Throwable $e) {
            $this->error("Exceção durante a execução: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
