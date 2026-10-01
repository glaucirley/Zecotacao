<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CleanQuotesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cotacoes:limpar {--force : Executa sem pedir confirmação}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpa todos os dados de cotações, conversas (chat), histórico/aprovações, faturamento e notificações, mantendo usuários, clientes e produtos.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->option('force') && !$this->confirm('ATENÇÃO: Esta ação apagarátodas as cotações, conversas, histórico de aprovações e faturamento. Deseja continuar?')) {
            $this->info('Operação cancelada.');
            return 0;
        }

        $this->info('Limpando base de cotações e conversas...');

        Schema::disableForeignKeyConstraints();

        $tables = [
            'cotacao_anexos',
            'cotacao_justificativas',
            'cotacao_vinculo_itens',
            'cotacao_historico',
            'cotacao_itens',
            'pedidos_externos',
            'cotacoes',
            'chat_mensagens',
            'notificacoes',
            'checkins',
            'produtos_nao_encontrados',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->truncate();
                $this->line("  ✓ Tabela '{$table}' limpa.");
            }
        }

        Schema::enableForeignKeyConstraints();

        $this->info('Base de dados de cotações limpa com sucesso!');
        return 0;
    }
}
