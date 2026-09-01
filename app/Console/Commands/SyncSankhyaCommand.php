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
                    Produto::updateOrCreate(
                        ['codigo_sankhya' => (string)$p['CODPROD']],
                        [
                            'descricao' => $p['DESCRPROD'],
                            'unidade' => 'UN',
                            'ativo' => $p['ATIVO'] === 'S'
                        ]
                    );
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
                    Parceiro::updateOrCreate(
                        ['codigo_sankhya' => (string)$pa['CODPARC']],
                        [
                            'razao_social' => $pa['NOMEPARC'],
                            'nome_fantasia' => $pa['NOMEPARC'],
                            'cnpj' => preg_replace('/[^0-9]/', '', $pa['CGC_CPF'] ?? ''),
                            'telefone' => $pa['TELEFONE'] ?? '',
                            'email' => $pa['EMAIL'] ?? '',
                            'cep' => preg_replace('/[^0-9]/', '', $pa['CEP'] ?? ''),
                            'ativo' => true,
                        ]
                    );
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
                    $user = User::where('codigo_sankhya', (string)$r['CODVEND'])->first();
                    if ($user) {
                        $user->update([
                            'nome' => $r['APELIDO'],
                            'email' => $r['EMAIL'] ?? $user->email,
                        ]);
                    } else {
                        User::create([
                            'nome' => $r['APELIDO'],
                            'papel' => 'representante',
                            'email' => $r['EMAIL'] ?? ('vendedor' . $r['CODVEND'] . '@zecotacao.com.br'),
                            'senha_hash' => bcrypt(Str::random(16)),
                            'codigo_sankhya' => (string)$r['CODVEND'],
                            'ativo' => true,
                        ]);
                    }
                    $this->output->progressAdvance();
                }
                $this->output->progressFinish();
                $this->info("Synced " . count($reps) . " representatives successfully.");
            }

            $this->info("Sankhya sync process completed successfully.");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Sankhya sync failed: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
