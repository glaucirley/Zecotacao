<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\QuoteWorkflowService;

class ExpireQuotesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cotacoes:expirar';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expirar cotações cuja validade tenha sido atingida';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Iniciando verificação de cotações expiradas...");

        $count = QuoteWorkflowService::checkAndExpireQuotes();

        $this->info("Verificação concluída. {$count} cotação(ões) expirada(s) com sucesso.");

        return Command::SUCCESS;
    }
}
