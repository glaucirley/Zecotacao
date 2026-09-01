<?php

namespace App\Services;

use App\Models\Cotacao;
use App\Models\ParametroSistema;
use App\Models\CotacaoHistorico;

class QuoteWorkflowService
{
    /**
     * Evaluate quotation items and route the status accordingly.
     *
     * @param Cotacao $quote
     * @return array
     */
    public function evaluateAndRoute(Cotacao $quote): array
    {
        $quote->refresh();
        $quote->load(['itens', 'representante.equipe.gestor', 'anexos', 'justificativas.anexos']);

        if ($quote->itens->isEmpty()) {
            return [
                'success' => false,
                'error' => 'NO_ITEMS',
                'message' => 'Cannot submit a quote with no items.'
            ];
        }

        // Calculate totals for priority assessment
        $totalQuantity = 0;
        $totalNetRevenue = 0.00;
        $totalCost = 0.00;
        foreach ($quote->itens as $item) {
            if ($item->status_item === 'recusado') {
                continue;
            }
            $proposedPrice = (float)($item->preco_unit_proposto ?? 0);
            $cost = (float)($item->custo ?? 0);
            $tax = (float)($item->imposto ?? 0);
            $qty = (int)($item->qtd ?? 1);

            $totalNetRevenue += $qty * $proposedPrice * (1 - ($tax / 100));
            $totalCost += $qty * $cost;
            $totalQuantity += $qty;
        }

        $overallMargin = 0.00;
        if ($totalNetRevenue > 0) {
            $overallMargin = (($totalNetRevenue - $totalCost) / $totalNetRevenue) * 100;
        }

        // Grande Conta check
        $grandeContaValor = (float)ParametroSistema::getVal('ALCADA_GRANDE_CONTA_VALOR', 10000.00);
        $grandeContaQtd = (int)ParametroSistema::getVal('ALCADA_GRANDE_CONTA_QTD', 100);
        $grandeContaMargem = (float)ParametroSistema::getVal('ALCADA_GRANDE_CONTA_MARGEM', 15.00);

        $isPriority = false;
        $reasons = [];

        if ((float)$quote->total >= $grandeContaValor) {
            $isPriority = true;
            $reasons[] = sprintf('Valor proposto (R$ %.2f) >= Limite (R$ %.2f)', $quote->total, $grandeContaValor);
        }

        if ($totalQuantity >= $grandeContaQtd) {
            $isPriority = true;
            $reasons[] = sprintf('Qtd itens (%d) >= Limite (%d)', $totalQuantity, $grandeContaQtd);
        }

        if ($totalNetRevenue > 0 && $overallMargin <= $grandeContaMargem) {
            $isPriority = true;
            $reasons[] = sprintf('Margem geral (%.2f%%) <= Limite (%.2f%%)', $overallMargin, $grandeContaMargem);
        }

        if ($isPriority && !$quote->prioridade) {
            $quote->update(['prioridade' => true]);

            CotacaoHistorico::create([
                'cotacao_id' => $quote->id,
                'evento' => 'CLASSIFICADA_GRANDE_CONTA',
                'usuario_id' => $quote->representante_id,
                'papel' => 'sistema',
                'condicao' => 'Cotacao identificada como Grande Conta / Prioritaria: ' . implode('; ', $reasons),
            ]);

            // Notify Rep
            \App\Models\Notificacao::create([
                'usuario_id' => $quote->representante_id,
                'titulo' => '⚡ Cotacao Prioritaria (Grande Conta)',
                'mensagem' => "A cotacao no. {$quote->numero} foi classificada como Grande Conta / Prioritaria devido a limites comerciais atingidos.",
                'link' => "/cotacoes/token/{$quote->token_representante}",
                'lida' => false
            ]);

            // Notify Gestor (if exists)
            $gestorId = $quote->representante->equipe?->gestor_id;
            if ($gestorId) {
                \App\Models\Notificacao::create([
                    'usuario_id' => $gestorId,
                    'titulo' => '🔥 ALERTA: Grande Conta na Fila',
                    'mensagem' => "A cotacao no. {$quote->numero} do vendedor {$quote->representante->nome} exige atencao prioritaria.",
                    'link' => "/aprovacoes/{$quote->id}",
                    'lida' => false
                ]);
            }

            // Notify Directors
            $directors = \App\Models\User::where('papel', 'diretor')->get();
            foreach ($directors as $dir) {
                \App\Models\Notificacao::create([
                    'usuario_id' => $dir->id,
                    'titulo' => '🔥 ALERTA: Grande Conta na Fila',
                    'mensagem' => "A cotacao no. {$quote->numero} do vendedor {$quote->representante->nome} exige atencao prioritaria.",
                    'link' => "/aprovacoes/{$quote->id}",
                    'lida' => false
                ]);
            }
        } elseif (!$isPriority && $quote->prioridade) {
            $quote->update(['prioridade' => false]);
        }

        // Check the reenvio parcial mode parameter
        $reenvioModo = ParametroSistema::getVal('REENVIO_PARCIAL_MODO', 'RECALCULA_TUDO');

        if ($reenvioModo === 'RECALCULA_TUDO') {
            // Reset all items to pending so they are all re-evaluated together
            foreach ($quote->itens as $item) {
                $item->update(['status_item' => 'pendente']);
            }
            $quote->refresh();
        }

        // 1. Check if any item is proposed below its minimum price (skipping already approved items if SO_ITENS_ALTERADOS)
        $hasItemsBelowMin = false;
        foreach ($quote->itens as $item) {
            if ($reenvioModo === 'SO_ITENS_ALTERADOS' && $item->status_item === 'aprovado') {
                continue;
            }
            if ((float)$item->preco_unit_proposto < (float)$item->preco_minimo) {
                $hasItemsBelowMin = true;
                break;
            }
        }

        // 2. If no items are below the minimum, approve automatically
        if (!$hasItemsBelowMin) {
            $quote->update(['status' => 'PDF_GERADO']);

            // Set remaining non-approved items status to approved
            foreach ($quote->itens as $item) {
                if ($item->status_item !== 'aprovado') {
                    $item->update(['status_item' => 'aprovado']);
                }
            }

            CotacaoHistorico::create([
                'cotacao_id' => $quote->id,
                'evento' => 'APROVADA_AUTOMATICAMENTE',
                'usuario_id' => $quote->representante_id,
                'papel' => 'sistema',
                'condicao' => 'Todos os itens ativos estao dentro do preco minimo. Status alterado para PDF_GERADO.',
            ]);

            return [
                'success' => true,
                'status' => 'PDF_GERADO',
                'message' => 'Quote approved automatically. PDF is ready for generation.'
            ];
        }

        // 3. Since at least one item is below minimum, check justification requirements
        $exigeAnexo = ParametroSistema::getVal('EXIGE_ANEXO_JUSTIFICATIVA', true);
        
        // Count attachments linked to the quote or its justifications
        $hasAttachments = $quote->anexos()->exists() || 
            $quote->justificativas->contains(fn($j) => $j->anexos()->exists());

        if ($exigeAnexo && !$hasAttachments) {
            return [
                'success' => false,
                'error' => 'JUSTIFICATION_ATTACHMENT_REQUIRED',
                'message' => 'One or more items are below the minimum price. A justification with at least one file attachment is required.'
            ];
        }

        // 4. Calculate discount based on DESCONTO_AVALIACAO_MODO
        $modoAvaliacao = ParametroSistema::getVal('DESCONTO_AVALIACAO_MODO', 'ITEM_A_ITEM');
        $gestorLimit = (float)($quote->representante->equipe?->gestor?->limite_desconto_percentual ?? 0.00);
        $routeToDirector = false;
        $calculatedDiscount = 0.00;

        if ($modoAvaliacao === 'ITEM_A_ITEM') {
            // Find the maximum discount percentage among items below suggested price (skipping approved if SO_ITENS_ALTERADOS)
            $maxDiscount = 0.00;
            foreach ($quote->itens as $item) {
                if ($reenvioModo === 'SO_ITENS_ALTERADOS' && $item->status_item === 'aprovado') {
                    continue;
                }
                $precoSugerido = (float)$item->preco_unit_sugerido;
                $precoProposto = (float)$item->preco_unit_proposto;

                if ($precoSugerido > 0 && $precoProposto < $precoSugerido) {
                    $discount = (($precoSugerido - $precoProposto) / $precoSugerido) * 100;
                    if ($discount > $maxDiscount) {
                        $maxDiscount = $discount;
                    }
                }
            }
            $calculatedDiscount = $maxDiscount;
            if ($calculatedDiscount > $gestorLimit) {
                $routeToDirector = true;
            }
        } else {
            // MEDIA_TOTAL mode
            $totalSugerido = 0.00;
            $totalProposto = 0.00;

            foreach ($quote->itens as $item) {
                if ($reenvioModo === 'SO_ITENS_ALTERADOS' && $item->status_item === 'aprovado') {
                    continue;
                }
                $totalSugerido += $item->qtd * (float)$item->preco_unit_sugerido;
                $totalProposto += $item->qtd * (float)$item->preco_unit_proposto;
            }

            if ($totalSugerido > 0) {
                $calculatedDiscount = (($totalSugerido - $totalProposto) / $totalSugerido) * 100;
            }

            if ($calculatedDiscount > $gestorLimit) {
                $routeToDirector = true;
            }
        }

        // 5. Update quote status and log history
        if ($routeToDirector) {
            $quote->update(['status' => 'COM_DIRETOR']);
            
            CotacaoHistorico::create([
                'cotacao_id' => $quote->id,
                'evento' => 'ENVIADA_AO_DIRETOR',
                'usuario_id' => $quote->representante_id,
                'papel' => 'representante',
                'condicao' => sprintf(
                    'Desconto calculado (%s: %.2f%%) excede o limite do gestor (%.2f%%). Enviado para a diretoria.',
                    $modoAvaliacao,
                    $calculatedDiscount,
                    $gestorLimit
                )
            ]);

            return [
                'success' => true,
                'status' => 'COM_DIRETOR',
                'message' => 'Quote sent to Director for approval. Discount limit exceeded.'
            ];
        } else {
            $quote->update(['status' => 'AGUARDANDO_GESTOR']);

            CotacaoHistorico::create([
                'cotacao_id' => $quote->id,
                'evento' => 'ENVIADA_AO_GESTOR',
                'usuario_id' => $quote->representante_id,
                'papel' => 'representante',
                'condicao' => sprintf(
                    'Desconto calculado (%s: %.2f%%) esta dentro do limite do gestor (%.2f%%). Aguardando gestor.',
                    $modoAvaliacao,
                    $calculatedDiscount,
                    $gestorLimit
                )
            ]);

            return [
                'success' => true,
                'status' => 'AGUARDANDO_GESTOR',
                'message' => 'Quote sent to Manager for approval.'
            ];
        }
    }
}
