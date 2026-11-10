<?php

namespace App\Console\Commands;

use App\Models\Cotacao;
use App\Models\CotacaoItem;
use App\Models\CotacaoHistorico;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class LimparItensDuplicadosCommand extends Command
{
    protected $signature = 'cotacoes:limpar-itens-duplicados
                            {--cotacao= : Número ou ID da cotação específica (ex: COT-2026-000083)}
                            {--dry-run : Apenas simula a limpeza sem alterar o banco de dados}';

    protected $description = 'Detecta e remove itens duplicados em cotações (ex: causados por requisições concorrentes do N8N) e recalcula os totais';

    public function handle()
    {
        $cotacaoFiltro = $this->option('cotacao');
        $isDryRun = (bool)$this->option('dry-run');

        $this->info("================================================================================");
        $this->info(" DETECÇÃO E LIMPEZA DE ITENS DUPLICADOS NAS COTAÇÕES");
        if ($isDryRun) {
            $this->warn(" MODO SIMULAÇÃO (--dry-run ATIVO): Nenhuma alteração será salva no banco.");
        }
        $this->info("================================================================================");

        $query = Cotacao::with(['itens.produto', 'parceiro']);

        if (!empty($cotacaoFiltro)) {
            if (is_numeric($cotacaoFiltro)) {
                $query->where('id', $cotacaoFiltro);
            } else {
                $query->where('numero', $cotacaoFiltro);
            }
        }

        $quotes = $query->orderBy('id', 'desc')->get();

        if ($quotes->isEmpty()) {
            $this->warn("Nenhuma cotação encontrada para análise.");
            return Command::SUCCESS;
        }

        $cotacoesAfetadas = 0;
        $totalItensRemovidos = 0;
        $detalhes = [];

        foreach ($quotes as $quote) {
            $itens = $quote->itens()->orderBy('id', 'asc')->get();
            if ($itens->count() < 2) {
                continue;
            }

            $vistos = [];
            $itensDuplicadosIds = [];

            foreach ($itens as $item) {
                $prodKey = $item->produto_id ? "PROD_{$item->produto_id}" : "SEM_PROD";
                $descKey = mb_strtolower(trim((string)($item->descricao_original ?: $item->descricao ?: '')));
                $qtdKey = number_format((float)$item->qtd, 4, '.', '');
                $sugKey = number_format((float)$item->preco_unit_sugerido, 4, '.', '');
                $propKey = number_format((float)$item->preco_unit_proposto, 4, '.', '');

                $sig = "{$prodKey}|{$descKey}|{$qtdKey}|{$sugKey}|{$propKey}";

                if (isset($vistos[$sig])) {
                    // É um item duplicado do já visto anteriormente nesta cotação
                    $itensDuplicadosIds[] = $item->id;
                } else {
                    $vistos[$sig] = $item->id;
                }
            }

            if (!empty($itensDuplicadosIds)) {
                $cotacoesAfetadas++;
                $qtdRemover = count($itensDuplicadosIds);
                $totalItensRemovidos += $qtdRemover;

                $totalAntigo = (float)$quote->total;
                $itensTotalAntes = $itens->count();
                $itensTotalDepois = $itensTotalAntes - $qtdRemover;

                $this->line("\n--------------------------------------------------------------------------------");
                $this->warn(" Cotação {$quote->numero} (ID: {$quote->id}) - Cliente: {$quote->parceiro?->razao_social}");
                $this->line(" Itens: <comment>{$itensTotalAntes}</comment> -> <info>{$itensTotalDepois}</info> (removendo {$qtdRemover} duplicados)");

                if (!$isDryRun) {
                    DB::transaction(function () use ($quote, $itensDuplicadosIds, $totalAntigo, $qtdRemover) {
                        // Deleta os itens duplicados
                        CotacaoItem::whereIn('id', $itensDuplicadosIds)->delete();

                        // Recalcula os totais da cotação
                        $quote->recalcularTotais();
                        $quote->refresh();

                        // Registra no histórico de auditoria
                        CotacaoHistorico::create([
                            'cotacao_id' => $quote->id,
                            'evento' => 'ITENS_DUPLICADOS_REMOVIDOS',
                            'usuario_id' => auth()->id() ?? null,
                            'papel' => 'sistema',
                            'condicao' => "Removidos {$qtdRemover} itens duplicados por importação simultânea. Total corrigido de R$ " . number_format($totalAntigo, 2, ',', '.') . " para R$ " . number_format($quote->total, 2, ',', '.'),
                        ]);
                    });

                    $this->info(" ✔ Itens duplicados removidos com sucesso. Total corrigido: R$ " . number_format($totalAntigo, 2, ',', '.') . " -> <info>R$ " . number_format($quote->total, 2, ',', '.') . "</info>");
                } else {
                    $this->comment(" [SIMULAÇÃO] Itens IDs a remover: " . implode(', ', $itensDuplicadosIds));
                }

                $detalhes[] = [
                    'cotacao' => $quote->numero,
                    'cliente' => substr($quote->parceiro?->razao_social ?? '', 0, 30),
                    'antes'   => $itensTotalAntes . ' itens',
                    'depois'  => $itensTotalDepois . ' itens',
                    'duplicados' => $qtdRemover,
                    'valor_antes' => 'R$ ' . number_format($totalAntigo, 2, ',', '.'),
                ];
            }
        }

        $this->line("\n================================================================================");
        $this->info(" RESUMO FINAL");
        $this->info("================================================================================");
        $this->line(" Cotações analisadas         : <comment>" . $quotes->count() . "</comment>");
        $this->line(" Cotações com duplicidades   : <error>{$cotacoesAfetadas}</error>");
        $this->line(" Total de itens duplicados   : <error>{$totalItensRemovidos}</error>");
        $this->line("--------------------------------------------------------------------------------");

        if (!empty($detalhes)) {
            $this->table(['Cotação', 'Cliente', 'Qtd Antes', 'Qtd Depois', 'Itens Removidos', 'Valor Antes'], $detalhes);
        } else {
            $this->info("Nenhuma cotação com itens duplicados foi encontrada!");
        }

        return Command::SUCCESS;
    }
}
