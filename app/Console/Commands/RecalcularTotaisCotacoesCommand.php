<?php

namespace App\Console\Commands;

use App\Models\Cotacao;
use App\Models\CotacaoHistorico;
use Illuminate\Console\Command;

class RecalcularTotaisCotacoesCommand extends Command
{
    protected $signature = 'cotacoes:recalcular-totais
                            {--cotacao= : Número ou ID da cotação específica (ex: COT-2026-000088 ou COT-088)}
                            {--dry-run : Apenas simula sem salvar no banco de dados}';

    protected $description = 'Recalcula o subtotal, desconto e total de todas as cotações ou de uma cotação específica, excluindo itens bloqueados e não encontrados';

    public function handle()
    {
        $cotacaoFiltro = $this->option('cotacao');
        $isDryRun = (bool)$this->option('dry-run');

        $this->info("================================================================================");
        $this->info(" RECALCULO DE TOTAIS DAS COTAÇÕES (EXCLUINDO ITENS BLOQUEADOS/NÃO COTÁVEIS)");
        if ($isDryRun) {
            $this->warn(" MODO SIMULAÇÃO (--dry-run ATIVO): Nenhuma alteração será salva no banco.");
        }
        $this->info("================================================================================");

        $query = Cotacao::with(['itens.produto', 'parceiro']);

        if (!empty($cotacaoFiltro)) {
            if (is_numeric($cotacaoFiltro)) {
                $query->where('id', $cotacaoFiltro);
            } else {
                // Suporta busca exata ou abreviada (ex: COT-088 ou COT-2026-000088)
                $query->where(function ($q) use ($cotacaoFiltro) {
                    $q->where('numero', $cotacaoFiltro)
                      ->orWhere('numero', 'like', "%{$cotacaoFiltro}%");
                });
            }
        }

        $quotes = $query->orderBy('id', 'desc')->get();

        if ($quotes->isEmpty()) {
            $this->warn("Nenhuma cotação encontrada para recálculo.");
            return Command::SUCCESS;
        }

        $cotacoesAlteradas = 0;
        $detalhes = [];

        foreach ($quotes as $quote) {
            $totalAntigo = (float)$quote->total;
            $subtotalAntigo = (float)$quote->subtotal;
            $descontoAntigo = (float)$quote->desconto;

            $blockedCount = $quote->itens()->where('restricao_efeito', 'B')->count();
            $unfoundCount = $quote->itens()->where('nao_encontrado', true)->count();

            if (!$isDryRun) {
                $quote->recalcularTotais();
                $quote->refresh();
                $totalNovo = (float)$quote->total;
            } else {
                // Modo simulação: calcula o total sem salvar
                $totalNovo = 0.00;
                foreach ($quote->itens as $item) {
                    if ($item->status_item === 'recusado' || (bool)$item->nao_encontrado || $item->isBloqueado()) {
                        continue;
                    }
                    $qtd = (float)($item->qtd ?? 0);
                    $prop = (float)($item->preco_unit_proposto ?? 0);
                    $sug = (float)($item->preco_unit_sugerido ?? 0);
                    $unit = $prop > 0 ? $prop : $sug;
                    $itemSub = ceil(round(round($qtd * $unit, 4) * 100, 2)) / 100;
                    $totalNovo += $itemSub;
                }
                $totalNovo = ceil(round(round($totalNovo, 4) * 100, 2)) / 100;
            }

            $diff = round($totalNovo - $totalAntigo, 2);

            if (abs($diff) > 0.009) {
                $cotacoesAlteradas++;

                $motivos = [];
                if ($blockedCount > 0) {
                    $motivos[] = "{$blockedCount} item(ns) bloqueado(s)";
                }
                if ($unfoundCount > 0) {
                    $motivos[] = "{$unfoundCount} item(ns) não encontrado(s)";
                }
                $motivoStr = !empty($motivos) ? implode(', ', $motivos) : 'Ajuste de arredondamento';

                if (!$isDryRun) {
                    CotacaoHistorico::create([
                        'cotacao_id' => $quote->id,
                        'evento' => 'TOTAIS_RECALCULADOS',
                        'usuario_id' => auth()->id() ?? null,
                        'papel' => 'sistema',
                        'condicao' => "Total recalculado: R$ " . number_format($totalAntigo, 2, ',', '.') . " -> R$ " . number_format($totalNovo, 2, ',', '.') . " (Diferença: R$ " . number_format($diff, 2, ',', '.') . ") devido a {$motivoStr}.",
                    ]);
                }

                $detalhes[] = [
                    'cotacao'      => $quote->numero,
                    'cliente'      => substr($quote->parceiro?->razao_social ?? '', 0, 25),
                    'total_antigo' => 'R$ ' . number_format($totalAntigo, 2, ',', '.'),
                    'total_novo'   => 'R$ ' . number_format($totalNovo, 2, ',', '.'),
                    'diferenca'    => ($diff > 0 ? '+' : '') . 'R$ ' . number_format($diff, 2, ',', '.'),
                    'motivo'       => $motivoStr,
                ];
            }
        }

        $this->line("\n================================================================================");
        $this->info(" RESUMO FINAL");
        $this->info("================================================================================");
        $this->line(" Cotações analisadas     : <comment>" . $quotes->count() . "</comment>");
        $this->line(" Cotações com ajuste     : <error>{$cotacoesAlteradas}</error>");
        $this->line("--------------------------------------------------------------------------------");

        if (!empty($detalhes)) {
            $this->warn("\nCOTAÇÕES CORRIGIDAS:");
            $this->table(['Cotação', 'Cliente', 'Total Gravado (Antes)', 'Total Corrigido (Depois)', 'Diferença', 'Motivo'], $detalhes);
        } else {
            $this->info("\nTodas as cotações já estão com os totais 100% corretos!");
        }

        return Command::SUCCESS;
    }
}
