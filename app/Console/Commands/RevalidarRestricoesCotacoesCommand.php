<?php

namespace App\Console\Commands;

use App\Models\Cotacao;
use App\Services\RestricaoComercialService;
use Illuminate\Console\Command;

class RevalidarRestricoesCotacoesCommand extends Command
{
    protected $signature = 'cotacoes:revalidar-restricoes
                            {--cotacao= : Número da cotação específica (ex: COT-2026-000083)}
                            {--id= : ID da cotação específica}
                            {--canal=ZC : Canal de consulta (ZC ou N8N)}
                            {--limite=200 : Limite máximo de cotações a revalidar}';

    protected $description = 'Revalida as restrições comerciais (Oracle AD_PKG_ZC_REGRAS) de todas as cotações ou de uma cotação específica';

    public function handle(RestricaoComercialService $restricaoService)
    {
        $cotacaoNum = $this->option('cotacao');
        $cotacaoId = $this->option('id');
        $canal = (string)$this->option('canal') ?: 'ZC';
        $limite = (int)$this->option('limite') ?: 200;

        $query = Cotacao::with(['parceiro', 'representante', 'itens.produto'])
            ->orderBy('id', 'desc');

        if (!empty($cotacaoId)) {
            $query->where('id', $cotacaoId);
        } elseif (!empty($cotacaoNum)) {
            $query->where('numero', $cotacaoNum);
        } else {
            $query->limit($limite);
        }

        $quotes = $query->get();

        if ($quotes->isEmpty()) {
            $this->warn("Nenhuma cotação encontrada para revalidação.");
            return Command::SUCCESS;
        }

        $this->info("================================================================================");
        $this->info(" REVALIDAÇÃO DE RESTRIÇÕES COMERCIAIS (AD_PKG_ZC_REGRAS)");
        $this->info(" Processando " . $quotes->count() . " cotação(ões)...");
        $this->info("================================================================================");

        $totalItens = 0;
        $totalBloqueados = 0;
        $totalLiberados = 0;
        $cotacoesComBloqueio = 0;
        $falhasConexao = 0;

        $bar = $this->output->createProgressBar($quotes->count());
        $bar->start();

        $detalhesBloqueados = [];

        foreach ($quotes as $quote) {
            $res = $restricaoService->validarCotacao($quote, $canal);

            if ($res['erro_conexao'] ?? false) {
                $falhasConexao++;
            }

            $itensQuote = $quote->itens;
            foreach ($itensQuote as $item) {
                if ($item->nao_encontrado && empty($item->produto_id)) {
                    continue;
                }
                $totalItens++;
                if ($item->restricao_efeito === 'B') {
                    $totalBloqueados++;
                    $detalhesBloqueados[] = [
                        'cotacao'   => $quote->numero,
                        'cliente'   => $quote->parceiro?->codigo_sankhya . ' - ' . substr($quote->parceiro?->razao_social ?? '', 0, 25),
                        'codprod'   => $item->produto?->codigo_sankhya,
                        'descricao' => substr($item->produto?->descricao ?? $item->descricao_original ?? '', 0, 30),
                        'fonte'     => $item->restricao_fonte,
                        'motivo'    => substr($item->restricao_msg_vendedor ?? '', 0, 35),
                    ];
                } else {
                    $totalLiberados++;
                }
            }

            if (!empty($res['bloqueados'])) {
                $cotacoesComBloqueio++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->line("\n");

        $this->info("================================================================================");
        $this->info(" RESUMO DO PROCESSAMENTO");
        $this->info("================================================================================");
        $this->line(" Cotações Processadas : <comment>" . $quotes->count() . "</comment>");
        $this->line(" Total de Itens       : <comment>{$totalItens}</comment>");
        $this->line(" Itens Bloqueados (B) : <error>{$totalBloqueados}</error>");
        $this->line(" Itens Liberados (L)  : <info>{$totalLiberados}</info>");
        $this->line(" Cotações c/ Bloqueio : <comment>{$cotacoesComBloqueio}</comment>");
        if ($falhasConexao > 0) {
            $this->error(" Falhas de Conexão    : {$falhasConexao}");
        }
        $this->line("--------------------------------------------------------------------------------");

        if (!empty($detalhesBloqueados)) {
            $this->warn("\nITENS BLOQUEADOS IDENTIFICADOS:");
            $this->table(['Cotação', 'Cliente', 'Cód Prod', 'Descrição', 'Fonte', 'Motivo'], array_slice($detalhesBloqueados, 0, 20));
            if (count($detalhesBloqueados) > 20) {
                $this->comment("... e mais " . (count($detalhesBloqueados) - 20) . " itens bloqueados.");
            }
        } else {
            $this->info("\nNenhum item bloqueado entre os processados.");
        }

        return Command::SUCCESS;
    }
}
