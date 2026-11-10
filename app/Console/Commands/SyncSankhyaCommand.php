<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SankhyaDatabaseService;
use App\Models\Produto;
use App\Models\Parceiro;
use App\Models\User;
use Illuminate\Support\Str;

class SyncSankhyaCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sankhya:sync {--type=all : The type of sync (all, products, partners, reps, prices, conditions, validades, estoque, campanhas)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize catalog database (Products, Clients, Vendedores) directly from Sankhya Oracle DB';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->option('type');
        $this->info("Starting Sankhya Direct Sync (type: {$type})...");

        try {
            $db = resolve(SankhyaDatabaseService::class);
            
            if (in_array($type, ['all', 'products'])) {
                $this->info("Fetching products from Oracle...");
                $products = $db->fetchProducts();
                $this->output->progressStart(count($products));
                $prodSuccess = 0;
                foreach ($products as $p) {
                    try {
                        $db->saveProductFromRow($p);
                        $prodSuccess++;
                    } catch (\Throwable $e) {
                        // Log row failure and continue
                    }
                    $this->output->progressAdvance();
                }
                $this->output->progressFinish();
                $this->info("Synced {$prodSuccess}/" . count($products) . " products successfully.");
            }

            if (in_array($type, ['all', 'partners'])) {
                $this->info("Fetching partners (clients) from Oracle...");
                $partners = $db->fetchPartners();
                $this->output->progressStart(count($partners));
                $partnerSuccess = 0;
                $partnerErrors = 0;
                foreach ($partners as $pa) {
                    try {
                        $db->savePartnerFromRow($pa);
                        $partnerSuccess++;
                    } catch (\Throwable $e) {
                        $partnerErrors++;
                    }
                    $this->output->progressAdvance();
                }
                $this->output->progressFinish();
                $this->info("Synced {$partnerSuccess}/" . count($partners) . " partners successfully" . ($partnerErrors > 0 ? " ({$partnerErrors} skipped due to row errors)." : "."));
            }

            if (in_array($type, ['all', 'reps'])) {
                $this->info("Fetching representatives (sellers) from Oracle...");
                $reps = $db->fetchRepresentatives();
                $this->output->progressStart(count($reps));
                $repSuccess = 0;
                $activeCodes = [];
                foreach ($reps as $r) {
                    try {
                        $codRaw = trim((string)($r['CODVEND'] ?? $r['codvend'] ?? ''));
                        if ($codRaw !== '') {
                            $activeCodes[] = $codRaw;
                            if (is_numeric($codRaw)) {
                                $activeCodes[] = (string)((int)$codRaw);
                            }
                        }

                        $u = $db->saveRepresentativeFromRow($r);
                        if (!empty($u->codigo_sankhya)) {
                            $activeCodes[] = (string)$u->codigo_sankhya;
                            if (is_numeric($u->codigo_sankhya)) {
                                $activeCodes[] = (string)((int)$u->codigo_sankhya);
                            }
                        }
                        $repSuccess++;
                    } catch (\Throwable $e) {
                        $this->warn("Aviso ao salvar vendedor ({$codRaw}): " . $e->getMessage());
                    }
                    $this->output->progressAdvance();
                }
                $this->output->progressFinish();
                $this->info("Synced {$repSuccess}/" . count($reps) . " representatives successfully.");

                if (!empty($activeCodes)) {
                    $activeCodes = array_values(array_unique(array_filter($activeCodes)));
                    $deactivated = User::where('papel', 'representante')
                        ->whereNotNull('codigo_sankhya')
                        ->whereNotIn('codigo_sankhya', $activeCodes)
                        ->where('ativo', true)
                        ->update(['ativo' => false]);
                    if ($deactivated > 0) {
                        $this->warn("Inativados {$deactivated} representante(s) que não possuem AD_ATVCOT = 'S' ou ATIVO = 'S'.");
                    }
                }
            }

            if (in_array($type, ['all', 'prices', 'tabelas'])) {
                $this->info("Fetching price tables from Oracle (VGF_PRECOAPP)...");
                $priceRows = $db->fetchPriceTablesFromView();
                $count = $db->savePriceTablesFromViewRows($priceRows);
                $this->info("Synced {$count} price table items successfully.");
            }

            if (in_array($type, ['all', 'conditions', 'condicoes', 'pagamento'])) {
                $this->info("Fetching payment conditions from Oracle (TIPONEG_APP / VGF_TIPONEG_APP / TGFTPV)...");
                $condRows = $db->fetchPaymentConditionsFromView();
                if (empty($condRows)) {
                    $this->warn("Aviso: Nenhuma linha retornada para condições de pagamento nas views consultadas (TIPONEG_APP, VGF_TIPONEG_APP, TGFTPV).");
                } else {
                    $count = $db->savePaymentConditionsFromViewRows($condRows);
                    $this->info("Synced {$count} payment conditions successfully.");
                }
            }

            if (in_array($type, ['all', 'validades', 'validade', 'estoque'])) {
                $this->info("Fetching product stock and expiration dates from Oracle (VGF_VALIDADEAPP)...");
                $validadeRows = $db->fetchValidadesFromView();

                if (empty($validadeRows)) {
                    $this->warn("Aviso: Nenhuma linha retornada da view VGF_VALIDADEAPP.");
                } else {
                    $cols = array_keys($validadeRows[0]);
                    $this->info("Colunas encontradas na view: [" . implode(', ', $cols) . "]");
                    $sample = $validadeRows[0];
                    $sampleClean = [];
                    foreach ($sample as $sk => $sv) {
                        $sampleClean[$sk] = is_string($sv) && strlen($sv) > 40 ? substr($sv, 0, 40) . '...' : $sv;
                    }
                    $this->line("Exemplo do 1º registro: " . json_encode($sampleClean));
                }

                $stats = [];
                $count = $db->saveValidadesFromViewRows($validadeRows, $stats);
                $this->info("Validades atualizadas: {$stats['validade']} | Estoques identificados e atualizados: {$stats['estoque']} (Total afetado: {$count}).");

                // Se a view VGF_VALIDADEAPP não possuir coluna de estoque (ou tiver 0 estoques atualizados),
                // busca o saldo automaticamente da tabela nativa de estoque do Sankhya (TGFEST / VGF_ESTOQUE)
                if (($stats['estoque'] ?? 0) === 0) {
                    $this->warn("Aviso: Nenhum valor de estoque foi identificado nas colunas da view VGF_VALIDADEAPP.");
                    $this->info("Buscando saldos de estoque diretamente nas tabelas de estoque do Sankhya (TGFEST / VGF_ESTOQUE)...");
                    $estoqueRows = $db->fetchEstoqueFromSankhyaTable();
                    if (!empty($estoqueRows)) {
                        $this->info("Encontrados " . count($estoqueRows) . " produtos com saldo de estoque.");
                        $syncedEst = $db->saveEstoqueRows($estoqueRows);
                        $this->info("Sucesso! {$syncedEst} saldos de estoque foram sincronizados para os produtos cadastrados.");
                    } else {
                        $this->error("Aviso: Não foi possível obter estoque das tabelas do Sankhya (TGFEST/VGF_ESTOQUE). Verifique as permissões do usuário do banco Oracle.");
                    }
                }
            }

            if (in_array($type, ['all', 'campanhas', 'campanha'])) {
                $this->info("Fetching product campaigns from Oracle (VGF_CAMPANHA_APP)...");
                $campanhaRows = $db->fetchCampanhasFromView();
                $count = $db->saveCampanhasFromViewRows($campanhaRows, true);
                $this->info("Synced {$count} product campaign records successfully.");
            }

            $this->info("Sankhya sync process completed successfully.");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Sankhya sync failed: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
