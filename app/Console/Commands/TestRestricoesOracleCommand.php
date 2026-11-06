<?php

namespace App\Console\Commands;

use App\Services\RestricaoComercialService;
use App\Services\SankhyaDatabaseService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class TestRestricoesOracleCommand extends Command
{
    protected $signature = 'sankhya:test-restricoes 
                            {codvend : Código Sankhya do Vendedor (ex: 104)} 
                            {codparc : Código Sankhya do Parceiro/Cliente (ex: 12345)} 
                            {--canal=ZC : Canal da consulta (default: ZC)} 
                            {--sem-cache : Ignora e limpa o cache do Laravel para forçar consulta no Oracle}';

    protected $description = 'Mede o tempo de execução de AD_PKG_ZC_REGRAS.PRODUTOS_RESTRITOS no Oracle (teste 3.7 do pacote de regras Sankhya).';

    public function handle(RestricaoComercialService $restricaoService, SankhyaDatabaseService $sankhyaDb)
    {
        $codvend = (string)$this->argument('codvend');
        $codparc = (string)$this->argument('codparc');
        $canal = (string)$this->option('canal') ?: 'ZC';
        $semCache = (bool)$this->option('sem-cache');

        $this->info("================================================================================");
        $this->info(" Teste 3.7 do Pacote de Regras Sankhya: AD_PKG_ZC_REGRAS.PRODUTOS_RESTRITOS");
        $this->info("================================================================================");
        $this->line(" Vendedor : <comment>{$codvend}</comment>");
        $this->line(" Parceiro : <comment>{$codparc}</comment>");
        $this->line(" Canal    : <comment>{$canal}</comment>");
        $this->line("--------------------------------------------------------------------------------");

        $cacheKey = $restricaoService->getCacheKey($codvend, $codparc);

        if ($semCache) {
            Cache::forget($cacheKey);
            $this->warn("Cache do Laravel limpo para '{$cacheKey}'. Consulta direta forçada no Oracle.");
        }

        // 1. Medição via Oracle direto
        $this->line("Consultando Oracle: SELECT ... FROM TABLE(AD_PKG_ZC_REGRAS.PRODUTOS_RESTRITOS(:v, :p, :canal))...");
        $t0 = microtime(true);
        $rows = [];
        try {
            $conn = $sankhyaDb->connect();
            $sql = "SELECT CODPROD, FONTE, MSG_VENDEDOR FROM TABLE(AD_PKG_ZC_REGRAS.PRODUTOS_RESTRITOS(:v, :p, :canal))";
            try {
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    ':v' => $codvend,
                    ':p' => $codparc,
                    ':canal' => $canal,
                ]);
                $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
            } catch (\PDOException $e1) {
                $sqlPrefix = "SELECT CODPROD, FONTE, MSG_VENDEDOR FROM TABLE(SANKHYA.AD_PKG_ZC_REGRAS.PRODUTOS_RESTRITOS(:v, :p, :canal))";
                $stmt = $conn->prepare($sqlPrefix);
                $stmt->execute([
                    ':v' => $codvend,
                    ':p' => $codparc,
                    ':canal' => $canal,
                ]);
                $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
            }
            $elapsedOracle = round(microtime(true) - $t0, 3);
            $totalCount = count($rows);

            $this->info(" Tempo de execução no Oracle : {$elapsedOracle}s");
            $this->info(" Total de produtos restritos  : {$totalCount}");

            if ($totalCount > 0) {
                $this->line("");
                $sample = array_slice($rows, 0, 15);
                $this->table(['CODPROD', 'FONTE', 'MSG_VENDEDOR'], $sample);
                if ($totalCount > 15) {
                    $this->comment("... e outros " . ($totalCount - 15) . " produtos com restrição.");
                }
            } else {
                $this->comment("Nenhuma restrição encontrada para este par de vendedor e cliente.");
            }
        } catch (\Throwable $e) {
            $elapsedOracle = round(microtime(true) - $t0, 3);
            $this->error("Falha ao consultar Oracle após {$elapsedOracle}s: " . $e->getMessage());
        }

        // 2. Medição com Cache do Serviço
        $this->line("\n--------------------------------------------------------------------------------");
        $this->line("Testando RestricaoComercialService (com Cache):");
        $t1 = microtime(true);
        $cachedRows = $restricaoService->produtosRestritos($codvend, $codparc, $canal);
        $elapsedService = round(microtime(true) - $t1, 4);
        $this->info(" Tempo com cache ativo: {$elapsedService}s | Restrições recuperadas: " . count($cachedRows));
        $this->info("================================================================================");

        return Command::SUCCESS;
    }
}
