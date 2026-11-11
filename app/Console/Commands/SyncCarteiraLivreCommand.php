<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SankhyaDatabaseService;

class SyncCarteiraLivreCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sankhya:sync-carteira-livre';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza o status de Carteira Livre (AD_CARTLIVRE) de todos os clientes do ERP Sankhya';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Iniciando sincronização de Carteira Livre (AD_CARTLIVRE) do Sankhya...");

        try {
            $db = resolve(SankhyaDatabaseService::class);
            $count = $db->syncAllPartnerCarteiraLivre();

            $totalLivres = \App\Models\Parceiro::where('carteira_livre', true)->count();
            $totalParceiros = \App\Models\Parceiro::count();

            $this->info("Sucesso! {$count} parceiro(s) tiveram o status de Carteira Livre atualizado.");
            $this->info("Total de clientes em Carteira Livre agora: {$totalLivres} de {$totalParceiros}.");

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Erro ao sincronizar Carteira Livre: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
