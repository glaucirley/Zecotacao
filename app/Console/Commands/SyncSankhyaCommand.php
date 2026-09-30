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
    protected $signature = 'sankhya:sync {--type=all : The type of sync (all, products, partners, reps)}';

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
                foreach ($products as $p) {
                    $db->saveProductFromRow($p);
                    $this->output->progressAdvance();
                }
                $this->output->progressFinish();
                $this->info("Synced " . count($products) . " products successfully.");
            }

            if (in_array($type, ['all', 'partners'])) {
                $this->info("Fetching partners (clients) from Oracle...");
                $partners = $db->fetchPartners();
                $this->output->progressStart(count($partners));
                foreach ($partners as $pa) {
                    $db->savePartnerFromRow($pa);
                    $this->output->progressAdvance();
                }
                $this->output->progressFinish();
                $this->info("Synced " . count($partners) . " partners successfully.");
            }

            if (in_array($type, ['all', 'reps'])) {
                $this->info("Fetching representatives (sellers) from Oracle...");
                $reps = $db->fetchRepresentatives();
                $this->output->progressStart(count($reps));
                foreach ($reps as $r) {
                    $db->saveRepresentativeFromRow($r);
                    $this->output->progressAdvance();
                }
                $this->output->progressFinish();
                $this->info("Synced " . count($reps) . " representatives successfully.");
            }

            if (in_array($type, ['all', 'prices', 'tabelas'])) {
                $this->info("Fetching price tables from Oracle (VGF_PRECOAPP)...");
                $priceRows = $db->fetchPriceTablesFromView();
                $count = $db->savePriceTablesFromViewRows($priceRows);
                $this->info("Synced {$count} price table items successfully.");
            }

            if (in_array($type, ['all', 'conditions', 'pagamento'])) {
                $this->info("Fetching payment conditions from Oracle (TIPONEG_APP)...");
                $condRows = $db->fetchPaymentConditionsFromView();
                $count = $db->savePaymentConditionsFromViewRows($condRows);
                $this->info("Synced {$count} payment conditions successfully.");
            }

            $this->info("Sankhya sync process completed successfully.");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Sankhya sync failed: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
