<?php

namespace App\Console\Commands;

use App\Services\RestricaoComercialService;
use App\Services\SankhyaDatabaseService;
use App\Models\Parceiro;
use App\Models\Produto;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class TestRegraItemOracleCommand extends Command
{
    protected $signature = 'sankhya:test-regra-item
                            {codparc=107791 : Código Sankhya do Parceiro/Cliente (ex: 107791)}
                            {--codprod= : Código Sankhya do Produto a testar (opcional; se omitido busca produto da marca)}
                            {--marca=BOEHRINGER : Marca para busca de produto caso codprod não seja informado}
                            {--codvend= : Código Sankhya do Vendedor (opcional; se omitido busca vendedor vinculado ao cliente)}
                            {--canal=ZC : Canal de teste (ZC ou N8N)}
                            {--sem-cache : Força limpeza de cache antes do teste}';

    protected $description = 'Executa o Teste 3.6 do pacote de regras Sankhya (AD_PKG_ZC_REGRAS.VALIDA / VALIDA_ITEM) para um cliente e produto (ex: 107791 e Boehringer)';

    public function handle(RestricaoComercialService $restricaoService, SankhyaDatabaseService $sankhyaDb)
    {
        $codparc = (string)$this->argument('codparc');
        $codprod = $this->option('codprod') ? (string)$this->option('codprod') : null;
        $marcaBusca = (string)$this->option('marca') ?: 'BOEHRINGER';
        $codvend = $this->option('codvend') ? (string)$this->option('codvend') : null;
        $canal = (string)$this->option('canal') ?: 'ZC';
        $semCache = (bool)$this->option('sem-cache');

        $this->info("================================================================================");
        $this->info(" TESTE 3.6 DO PACOTE DE REGRAS SANKHYA (AD_PKG_ZC_REGRAS.VALIDA)");
        $this->info(" Validação de Restrição Comercial: Cliente {$codparc} com Produto/Marca {$marcaBusca}");
        $this->info("================================================================================");

        // 1. Conexão Oracle
        try {
            $conn = $sankhyaDb->connect();
            $this->info("✔ Conexão com Oracle Sankhya: OK");
        } catch (\Throwable $e) {
            $this->error("✖ Falha na conexão com Oracle Sankhya: " . $e->getMessage());
            return Command::FAILURE;
        }

        // 2. Resolver Dados do Cliente / Parceiro e Vendedor
        $razaoSocial = '';
        $cnpjCpf = '';
        try {
            $stmtParc = $conn->prepare("SELECT CODPARC, RAZAOSOCIAL, CGC_CPF, CODVEND FROM TGFPAR WHERE CODPARC = :p");
            $stmtParc->execute([':p' => $codparc]);
            $rowParc = $stmtParc->fetch(\PDO::FETCH_ASSOC);
            if ($rowParc) {
                $razaoSocial = $rowParc['RAZAOSOCIAL'] ?? '';
                $cnpjCpf = $rowParc['CGC_CPF'] ?? '';
                if (empty($codvend) && !empty($rowParc['CODVEND'])) {
                    $codvend = (string)$rowParc['CODVEND'];
                }
            }
        } catch (\Throwable $e) {
            $this->warn("Aviso ao buscar parceiro no Oracle: " . $e->getMessage());
        }

        if (empty($codvend)) {
            $localParc = Parceiro::where('codigo_sankhya', $codparc)->first();
            if ($localParc) {
                $codvend = $localParc->vendedor_1_codigo;
                $razaoSocial = $razaoSocial ?: $localParc->razao_social;
            }
        }

        $codvend = $codvend ?: '104'; // Fallback padrão caso vendedor não seja encontrado

        $this->line(" Cliente       : <comment>{$codparc}</comment> - {$razaoSocial} (CNPJ/CPF: {$cnpjCpf})");
        $this->line(" Vendedor (V)  : <comment>{$codvend}</comment>");
        $this->line(" Canal         : <comment>{$canal}</comment>");

        // 3. Resolver Produto (por código ou buscando na marca)
        $descrProd = '';
        $marcaProd = '';
        if (empty($codprod)) {
            $this->line("\nBuscando produto da marca '{$marcaBusca}' no Oracle TGFPRO...");
            try {
                $stmtProds = $conn->prepare("SELECT CODPROD, DESCRPROD, MARCA, ATIVO FROM TGFPRO WHERE (UPPER(MARCA) LIKE :m OR UPPER(DESCRPROD) LIKE :m) AND ATIVO = 'S' AND ROWNUM <= 5");
                $stmtProds->execute([':m' => '%' . strtoupper($marcaBusca) . '%']);
                $foundProds = $stmtProds->fetchAll(\PDO::FETCH_ASSOC) ?: [];
                if (!empty($foundProds)) {
                    $this->table(['CODPROD', 'DESCRPROD', 'MARCA', 'ATIVO'], $foundProds);
                    $codprod = (string)$foundProds[0]['CODPROD'];
                    $descrProd = (string)$foundProds[0]['DESCRPROD'];
                    $marcaProd = (string)($foundProds[0]['MARCA'] ?? '');
                }
            } catch (\Throwable $e) {
                $this->warn("Aviso ao buscar produtos na TGFPRO: " . $e->getMessage());
            }

            if (empty($codprod)) {
                $localProd = Produto::where('marca', 'like', "%{$marcaBusca}%")->orWhere('descricao', 'like', "%{$marcaBusca}%")->first();
                if ($localProd) {
                    $codprod = (string)$localProd->codigo_sankhya;
                    $descrProd = $localProd->descricao;
                    $marcaProd = $localProd->marca ?? '';
                }
            }
        } else {
            // Busca dados do produto especificado
            try {
                $stmtP = $conn->prepare("SELECT CODPROD, DESCRPROD, MARCA FROM TGFPRO WHERE CODPROD = :prod");
                $stmtP->execute([':prod' => $codprod]);
                $rowP = $stmtP->fetch(\PDO::FETCH_ASSOC);
                if ($rowP) {
                    $descrProd = $rowP['DESCRPROD'] ?? '';
                    $marcaProd = $rowP['MARCA'] ?? '';
                }
            } catch (\Throwable $e) {}
        }

        if (empty($codprod)) {
            $this->error("Nenhum produto encontrado para a marca '{$marcaBusca}'. Forneça --codprod=XXXX explicitamente.");
            return Command::FAILURE;
        }

        $this->line(" Produto (PROD): <comment>{$codprod}</comment> - {$descrProd} (Marca: {$marcaProd})");
        $this->line("--------------------------------------------------------------------------------");

        if ($semCache) {
            $cacheKey = $restricaoService->getCacheKey($codvend, $codparc);
            Cache::forget($cacheKey);
            $this->warn("Cache do Laravel limpo para '{$cacheKey}'.");
        }

        // 4. Consulta Tabelas de Exceção Diretas no Oracle (AD_EXCPRODUTO e AD_EXCMARCA)
        $this->line("\n[Passo 1] Verificação Direta nas Tabelas de Restrição do Sankhya:");
        try {
            $stmtExcProd = $conn->prepare("SELECT * FROM AD_EXCPRODUTO WHERE (CODPARC = :p OR CODVEND = :v) AND (CODPROD = :prod OR CODPROD IS NULL)");
            $stmtExcProd->execute([':p' => $codparc, ':v' => $codvend, ':prod' => $codprod]);
            $excProdRows = $stmtExcProd->fetchAll(\PDO::FETCH_ASSOC) ?: [];
            $this->line("  - AD_EXCPRODUTO (Exceções por Produto): " . count($excProdRows) . " registro(s)");
            if (!empty($excProdRows)) {
                $this->table(array_keys($excProdRows[0]), array_slice($excProdRows, 0, 5));
            }

            $stmtExcMarca = $conn->prepare("SELECT * FROM AD_EXCMARCA WHERE (CODPARC = :p OR CODVEND = :v) AND (UPPER(MARCA) LIKE :m OR MARCA IS NULL)");
            $stmtExcMarca->execute([':p' => $codparc, ':v' => $codvend, ':m' => '%' . strtoupper($marcaBusca) . '%']);
            $excMarcaRows = $stmtExcMarca->fetchAll(\PDO::FETCH_ASSOC) ?: [];
            $this->line("  - AD_EXCMARCA (Exceções por Marca): " . count($excMarcaRows) . " registro(s)");
            if (!empty($excMarcaRows)) {
                $this->table(array_keys($excMarcaRows[0]), array_slice($excMarcaRows, 0, 5));
            }
        } catch (\Throwable $eExc) {
            $this->comment("  (Consulta direta em AD_EXCPRODUTO/AD_EXCMARCA: " . $eExc->getMessage() . ")");
        }

        // 5. Teste 3.6: Oracle Function AD_PKG_ZC_REGRAS.VALIDA
        $this->line("\n[Passo 2] Teste 3.6 via Function Oracle AD_PKG_ZC_REGRAS.VALIDA(:v, :p, :prod, :canal):");
        $t0 = microtime(true);
        $retFunc = null;
        try {
            $sqlFunc = "SELECT AD_PKG_ZC_REGRAS.VALIDA(:v, :p, :prod, :canal) AS RET FROM DUAL";
            try {
                $stmtF = $conn->prepare($sqlFunc);
                $stmtF->execute([
                    ':v' => $codvend,
                    ':p' => $codparc,
                    ':prod' => $codprod,
                    ':canal' => $canal,
                ]);
                $rowF = $stmtF->fetch(\PDO::FETCH_ASSOC);
                $retFunc = $rowF['RET'] ?? null;
            } catch (\PDOException $e1) {
                $sqlPrefix = "SELECT SANKHYA.AD_PKG_ZC_REGRAS.VALIDA(:v, :p, :prod, :canal) AS RET FROM DUAL";
                $stmtF = $conn->prepare($sqlPrefix);
                $stmtF->execute([
                    ':v' => $codvend,
                    ':p' => $codparc,
                    ':prod' => $codprod,
                    ':canal' => $canal,
                ]);
                $rowF = $stmtF->fetch(\PDO::FETCH_ASSOC);
                $retFunc = $rowF['RET'] ?? null;
            }

            $elapsedF = round(microtime(true) - $t0, 4);
            $this->info("  Tempo: {$elapsedF}s");
            $this->line("  Retorno Bruto: <comment>" . var_export($retFunc, true) . "</comment>");

            if ($retFunc !== null) {
                $parts = explode('|', (string)$retFunc);
                $efeito = trim($parts[0] ?? '') ?: 'L';
                $fonte = trim($parts[1] ?? '');
                $regra = trim($parts[2] ?? '');
                $msgVend = trim($parts[3] ?? '');
                $msgCli = trim($parts[4] ?? '');

                $colorTag = ($efeito === 'B') ? 'error' : 'info';
                $this->line("  Efeito       : <{$colorTag}>" . ($efeito === 'B' ? 'BLOQUEADO [B]' : 'LIBERADO [L]') . "</{$colorTag}>");
                $this->line("  Fonte        : {$fonte}");
                $this->line("  Regra ID     : {$regra}");
                $this->line("  Msg Vendedor : {$msgVend}");
                $this->line("  Msg Cliente  : {$msgCli}");
            }
        } catch (\Throwable $eF) {
            $this->error("  ✖ Falha na function VALIDA: " . $eF->getMessage());
        }

        // 6. Teste 3.6: Oracle Procedure AD_PKG_ZC_REGRAS.VALIDA_ITEM
        $this->line("\n[Passo 3] Teste 3.6 via Procedure Oracle AD_PKG_ZC_REGRAS.VALIDA_ITEM:");
        try {
            $efeitoOut = 'L';
            $fonteOut = '';
            $msgvOut = '';
            $msgcOut = '';

            $sqlProc = "BEGIN AD_PKG_ZC_REGRAS.VALIDA_ITEM(:v, :p, :prod, :canal, :efeito, :fonte, :msgv, :msgc); END;";
            try {
                $stmtP = $conn->prepare($sqlProc);
                $stmtP->bindParam(':v', $codvend);
                $stmtP->bindParam(':p', $codparc);
                $stmtP->bindParam(':prod', $codprod);
                $stmtP->bindParam(':canal', $canal);
                $stmtP->bindParam(':efeito', $efeitoOut, \PDO::PARAM_STR | \PDO::PARAM_INPUT_OUTPUT, 4);
                $stmtP->bindParam(':fonte', $fonteOut, \PDO::PARAM_STR | \PDO::PARAM_INPUT_OUTPUT, 50);
                $stmtP->bindParam(':msgv', $msgvOut, \PDO::PARAM_STR | \PDO::PARAM_INPUT_OUTPUT, 500);
                $stmtP->bindParam(':msgc', $msgcOut, \PDO::PARAM_STR | \PDO::PARAM_INPUT_OUTPUT, 500);
                $stmtP->execute();
            } catch (\PDOException $eP1) {
                $sqlProcPrefix = "BEGIN SANKHYA.AD_PKG_ZC_REGRAS.VALIDA_ITEM(:v, :p, :prod, :canal, :efeito, :fonte, :msgv, :msgc); END;";
                $stmtP = $conn->prepare($sqlProcPrefix);
                $stmtP->bindParam(':v', $codvend);
                $stmtP->bindParam(':p', $codparc);
                $stmtP->bindParam(':prod', $codprod);
                $stmtP->bindParam(':canal', $canal);
                $stmtP->bindParam(':efeito', $efeitoOut, \PDO::PARAM_STR | \PDO::PARAM_INPUT_OUTPUT, 4);
                $stmtP->bindParam(':fonte', $fonteOut, \PDO::PARAM_STR | \PDO::PARAM_INPUT_OUTPUT, 50);
                $stmtP->bindParam(':msgv', $msgvOut, \PDO::PARAM_STR | \PDO::PARAM_INPUT_OUTPUT, 500);
                $stmtP->bindParam(':msgc', $msgcOut, \PDO::PARAM_STR | \PDO::PARAM_INPUT_OUTPUT, 500);
                $stmtP->execute();
            }

            $efeitoOut = trim((string)$efeitoOut) ?: 'L';
            $colorTagP = ($efeitoOut === 'B') ? 'error' : 'info';
            $this->line("  Efeito       : <{$colorTagP}>" . ($efeitoOut === 'B' ? 'BLOQUEADO [B]' : 'LIBERADO [L]') . "</{$colorTagP}>");
            $this->line("  Fonte        : {$fonteOut}");
            $this->line("  Msg Vendedor : {$msgvOut}");
            $this->line("  Msg Cliente  : {$msgcOut}");
        } catch (\Throwable $eProc) {
            $this->comment("  (Procedure VALIDA_ITEM retornou: " . $eProc->getMessage() . ")");
        }

        // 7. Teste 3.7: Oracle Pipelined Function PRODUTOS_RESTRITOS
        $this->line("\n[Passo 4] Teste 3.7 via Table Function AD_PKG_ZC_REGRAS.PRODUTOS_RESTRITOS(:v, :p, :canal):");
        try {
            $sqlTbl = "SELECT CODPROD, FONTE, MSG_VENDEDOR FROM TABLE(AD_PKG_ZC_REGRAS.PRODUTOS_RESTRITOS(:v, :p, :canal))";
            try {
                $stmtT = $conn->prepare($sqlTbl);
                $stmtT->execute([':v' => $codvend, ':p' => $codparc, ':canal' => $canal]);
                $tblRows = $stmtT->fetchAll(\PDO::FETCH_ASSOC) ?: [];
            } catch (\PDOException $eT1) {
                $sqlTblPrefix = "SELECT CODPROD, FONTE, MSG_VENDEDOR FROM TABLE(SANKHYA.AD_PKG_ZC_REGRAS.PRODUTOS_RESTRITOS(:v, :p, :canal))";
                $stmtT = $conn->prepare($sqlTblPrefix);
                $stmtT->execute([':v' => $codvend, ':p' => $codparc, ':canal' => $canal]);
                $tblRows = $stmtT->fetchAll(\PDO::FETCH_ASSOC) ?: [];
            }

            $totalRestritos = count($tblRows);
            $this->line("  Total de produtos retornados em PRODUTOS_RESTRITOS: <comment>{$totalRestritos}</comment>");
            
            $foundInBatch = false;
            foreach ($tblRows as $r) {
                if ((string)($r['CODPROD'] ?? '') === (string)$codprod) {
                    $foundInBatch = true;
                    $this->error("  ✔ PRODUTO {$codprod} CONSTA NO RETORNO DE PRODUTOS_RESTRITOS (Fonte: {$r['FONTE']})");
                    break;
                }
            }

            if (!$foundInBatch) {
                if ($totalRestritos > 0) {
                    $this->warn("  ⚠ Produto {$codprod} NÃO consta no lote de PRODUTOS_RESTRITOS (mesmo havendo {$totalRestritos} outros produtos restritos).");
                } else {
                    $this->warn("  ⚠ PRODUTOS_RESTRITOS retornou 0 produtos para VEND={$codvend}, PARC={$codparc}.");
                }
            }
        } catch (\Throwable $eTbl) {
            $this->error("  ✖ Falha ao executar PRODUTOS_RESTRITOS: " . $eTbl->getMessage());
        }

        // 8. Teste via RestricaoComercialService (Laravel)
        $this->line("\n[Passo 5] Teste via RestricaoComercialService (código Laravel):");
        $resItem = $restricaoService->validarItem($codvend, $codparc, $codprod, $canal);
        $this->line("  validarItem() -> Efeito: <comment>{$resItem['efeito']}</comment> | Sucesso: " . ($resItem['sucesso_consulta'] ? 'SIM' : 'NÃO') . " | Fonte: {$resItem['fonte']} | Msg: {$resItem['msg_vendedor']}");

        // 9. Veredito Final do Teste 3.6
        $this->info("\n================================================================================");
        $oracleEfeito = null;
        if ($retFunc !== null) {
            $oracleEfeito = trim(explode('|', (string)$retFunc)[0] ?? '');
        }

        if ($oracleEfeito === 'B') {
            if ($resItem['efeito'] === 'B') {
                $this->info(" RESULTADO: [PASS] SUCESSO!");
                $this->info(" O Oracle retornou 'B' (Bloqueado) e o serviço Zé Cotação barrou o item corretamente.");
            } else {
                $this->error(" RESULTADO: [FAIL] FALHA DE INTEGRAÇÃO IDENTIFICADA!");
                $this->error(" O Oracle retornou 'B' (Bloqueado), mas o serviço Zé Cotação retornou '{$resItem['efeito']}'.");
            }
        } elseif ($oracleEfeito === 'L') {
            $this->warn(" RESULTADO: [INFO] LIBERADO NO ORACLE");
            $this->warn(" O próprio Oracle retornou 'L' (Liberado) para o cliente {$codparc} com produto {$codprod}.");
            $this->line(" Se este item deveria estar bloqueado, verifique o cadastro de restrições em AD_EXCMARCA / AD_EXCPRODUTO no Sankhya.");
        } else {
            $this->error(" RESULTADO: [INCONCLUSIVO]");
            $this->error(" Não foi possível obter retorno válido do Oracle.");
        }
        $this->info("================================================================================");

        return Command::SUCCESS;
    }
}
