<?php

namespace App\Services;

use App\Models\Cotacao;
use App\Models\ParametroSistema;
use App\Models\CotacaoHistorico;
use App\Models\User;
use App\Models\Notificacao;

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
                'link' => "/cotacoes/id/{$quote->id}",
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

        // 2. Calculate discount percentage based on DESCONTO_AVALIACAO_MODO
        $modoAvaliacao = ParametroSistema::getVal('DESCONTO_AVALIACAO_MODO', 'ITEM_A_ITEM');
        $calculatedDiscount = 0.00;

        if ($modoAvaliacao === 'ITEM_A_ITEM') {
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
            $calculatedDiscount = round($maxDiscount, 2);
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

            if ($totalSugerido > 0 && $totalProposto < $totalSugerido) {
                $calculatedDiscount = round((($totalSugerido - $totalProposto) / $totalSugerido) * 100, 2);
            }
        }

        // 3. Representative limits and rule evaluation
        $representante = $quote->representante;
        $repLimit = (float)($representante?->limite_desconto_percentual ?? 0.00);
        $regraLiberacao = ParametroSistema::getVal('REGRA_LIBERACAO_DESCONTO', 'ALCADA_REPRESENTANTE');

        $canAutoApprove = false;
        $autoApproveReason = '';

        if (!$hasItemsBelowMin) {
            if ($regraLiberacao === 'PRECO_MINIMO') {
                $canAutoApprove = true;
                $autoApproveReason = sprintf(
                    'Liberada automaticamente pelo sistema: todos os itens respeitam o preço mínimo (Regra: PRECO_MINIMO. Desconto aplicado: %.2f%%).',
                    $calculatedDiscount
                );
            } else {
                // Default: ALCADA_REPRESENTANTE (Regra A)
                if ($calculatedDiscount <= $repLimit) {
                    $canAutoApprove = true;
                    $autoApproveReason = $calculatedDiscount > 0
                        ? sprintf(
                            'Liberada automaticamente pelo sistema: todos os itens respeitam o preço mínimo e o desconto aplicado (%.2f%%) está dentro da alçada comercial do representante (%s: %.2f%%).',
                            $calculatedDiscount,
                            $representante?->nome ?? 'Representante',
                            $repLimit
                        )
                        : sprintf(
                            'Liberada automaticamente pelo sistema: cotação sem desconto adicional nos itens (preço sugerido/tabela mantido) e acima do preço mínimo (Alçada do representante %s: %.2f%%).',
                            $representante?->nome ?? 'Representante',
                            $repLimit
                        );
                }
            }
        }

        // 4. Auto-approve if permitted
        if ($canAutoApprove) {
            $quote->update(['status' => 'APROVADA']);

            // Set remaining non-approved items status to approved
            foreach ($quote->itens as $item) {
                if ($item->status_item !== 'aprovado') {
                    $item->update(['status_item' => 'aprovado']);
                }
            }

            CotacaoHistorico::create([
                'cotacao_id' => $quote->id,
                'evento' => 'LIBERADA_AUTOMATICAMENTE',
                'usuario_id' => null,
                'papel' => 'sistema',
                'condicao' => $autoApproveReason,
            ]);

            return [
                'success' => true,
                'status' => 'APROVADA',
                'message' => 'Quote approved automatically. PDF is ready for generation.'
            ];
        }

        // 5. Quote requires manual approval! Check justification requirements if below min
        if ($hasItemsBelowMin) {
            $exigeAnexo = ParametroSistema::getVal('EXIGE_ANEXO_JUSTIFICATIVA', true);
            $hasAttachments = $quote->anexos()->exists() || 
                $quote->justificativas->contains(fn($j) => $j->anexos()->exists());

            if ($exigeAnexo && !$hasAttachments) {
                return [
                    'success' => false,
                    'error' => 'JUSTIFICATION_ATTACHMENT_REQUIRED',
                    'message' => 'One or more items are below the minimum price. A justification with at least one file attachment is required.'
                ];
            }
        }

        // 6. Check team and active manager availability
        $equipe = $representante?->equipe;
        $gestor = $equipe?->gestor;
        $hasActiveGestor = ($gestor && $gestor->ativo);
        $semGestorAcao = ParametroSistema::getVal('SEM_GESTOR_ACAO', 'BLOQUEAR');

        if (!$hasActiveGestor) {
            if ($semGestorAcao === 'BLOQUEAR') {
                $motivoBloqueio = $hasItemsBelowMin
                    ? 'Não é possível enviar a cotação: existem itens abaixo do preço mínimo exigindo aprovação de gestor, mas o representante não possui equipe ou gestor ativo cadastrado. Entre em contato com a administração.'
                    : sprintf(
                        'Não é possível enviar a cotação: o desconto aplicado (%.2f%%) excede a alçada do representante (%s: %.2f%%) e requer aprovação de gestor, mas seu usuário não possui equipe ou gestor ativo cadastrado. Entre em contato com a administração.',
                        $calculatedDiscount,
                        $representante?->nome ?? 'Representante',
                        $repLimit
                    );

                return [
                    'success' => false,
                    'error' => 'SEM_GESTOR_ATIVO',
                    'message' => $motivoBloqueio
                ];
            }
        }

        // 7. Route to Director or Manager
        $gestorLimit = $hasActiveGestor ? (float)$gestor->limite_desconto_percentual : 0.00;
        $routeToDirector = !$hasActiveGestor; // If no active manager and action is DIRETORIA, escalates directly

        if ($hasActiveGestor) {
            if ($calculatedDiscount > $gestorLimit) {
                $routeToDirector = true;
            }
        }

        // 8. Update quote status and log history
        if ($routeToDirector) {
            $quote->update(['status' => 'COM_DIRETOR']);

            $condicaoHistorico = '';
            if (!$hasActiveGestor) {
                $condicaoHistorico = sprintf(
                    'Desconto aplicado (%.2f%%) excede a alçada do representante (%s: %.2f%%). Encaminhada diretamente para a diretoria por ausência de gestor ativo vinculado (SEM_GESTOR_ACAO = DIRETORIA).',
                    $calculatedDiscount,
                    $representante?->nome ?? 'Representante',
                    $repLimit
                );
            } else {
                $condicaoHistorico = sprintf(
                    'Desconto aplicado (%s: %.2f%%) excede o limite do gestor (%s: %.2f%%). Encaminhada para aprovação da diretoria.',
                    $modoAvaliacao,
                    $calculatedDiscount,
                    $gestor->nome,
                    $gestorLimit
                );
            }

            CotacaoHistorico::create([
                'cotacao_id' => $quote->id,
                'evento' => 'ENVIADA_AO_DIRETOR',
                'usuario_id' => $quote->representante_id,
                'papel' => 'representante',
                'condicao' => $condicaoHistorico
            ]);

            // Notificar diretores e administradores
            $diretores = User::whereIn('papel', ['diretor', 'administrador'])->where('ativo', true)->get();
            foreach ($diretores as $dir) {
                \App\Models\Notificacao::create([
                    'usuario_id' => $dir->id,
                    'titulo' => '📋 Cotação aguardando Diretoria',
                    'mensagem' => "A cotação {$quote->numero} de " . ($quote->representante?->nome ?? 'Representante') . " requer aprovação da diretoria.",
                    'link' => "/cotacoes/id/{$quote->id}",
                    'lida' => false,
                ]);
            }

            return [
                'success' => true,
                'status' => 'COM_DIRETOR',
                'message' => 'Quote sent to Director for approval.'
            ];
        } else {
            $quote->update(['status' => 'AGUARDANDO_GESTOR']);

            $condicaoHistorico = sprintf(
                'Desconto aplicado (%s: %.2f%%) excede a alçada permitida do representante (%s: %.2f%%), mas está dentro do limite do gestor (%s: %.2f%%). Encaminhada para aprovação do gestor.',
                $modoAvaliacao,
                $calculatedDiscount,
                $representante?->nome ?? 'Representante',
                $repLimit,
                $gestor->nome,
                $gestorLimit
            );

            CotacaoHistorico::create([
                'cotacao_id' => $quote->id,
                'evento' => 'ENVIADA_AO_GESTOR',
                'usuario_id' => $quote->representante_id,
                'papel' => 'representante',
                'condicao' => $condicaoHistorico
            ]);

            if ($gestor && $gestor->ativo) {
                \App\Models\Notificacao::create([
                    'usuario_id' => $gestor->id,
                    'titulo' => '📋 Cotação aguardando sua Aprovação',
                    'mensagem' => "A cotação {$quote->numero} de {$quote->representante?->nome} foi enviada para sua aprovação.",
                    'link' => "/cotacoes/id/{$quote->id}",
                    'lida' => false,
                ]);
            }

            return [
                'success' => true,
                'status' => 'AGUARDANDO_GESTOR',
                'message' => 'Quote sent to Manager for approval.'
            ];
        }
    }

    /**
     * Check and expire quotes whose validity has passed.
     * Also reconciles any quotes orphaned in AGUARDANDO_GESTOR without an active manager.
     *
     * @return int Number of expired quotes processed
     */
    public static function checkAndExpireQuotes(): int
    {
        // Reconcile quotes orphaned without manager
        self::reconcileOrphanedQuotes();

        $activeStatuses = ['EM_CRIACAO', 'DEVOLVIDA', 'AGUARDANDO_GESTOR', 'COM_DIRETOR', 'APROVADA', 'PDF_GERADO', 'AGUARDANDO_PEDIDO'];

        $now = now();

        $expiredQuotes = Cotacao::whereIn('status', $activeStatuses)
            ->where(function ($query) use ($now) {
                $query->where(function ($q1) use ($now) {
                    $q1->whereNotNull('data_validade')
                       ->where('data_validade', '<', $now);
                })->orWhere(function ($q2) use ($now) {
                    $q2->whereNull('data_validade')
                       ->whereRaw('DATE_ADD(COALESCE(data_emissao, created_at), INTERVAL COALESCE(validade_horas, 24) HOUR) < ?', [$now]);
                });
            })
            ->get();

        $count = 0;
        foreach ($expiredQuotes as $quote) {
            $quote->update(['status' => 'EXPIRADA']);

            $validityLabel = $quote->data_validade ? $quote->data_validade->format('d/m/Y H:i') : ($quote->validade_horas . 'h');

            CotacaoHistorico::create([
                'cotacao_id' => $quote->id,
                'evento' => 'COTACAO_EXPIRADA',
                'usuario_id' => null,
                'papel' => 'sistema',
                'condicao' => "Cotação expirada automaticamente por atingir o limite de validade ({$validityLabel}).",
            ]);

            if ($quote->representante_id) {
                \App\Models\Notificacao::create([
                    'usuario_id' => $quote->representante_id,
                    'titulo' => '⏰ Cotação Expirada!',
                    'mensagem' => "A cotação {$quote->numero} expirou por ter ultrapassado a validade configurada.",
                    'link' => "/cotacoes/id/{$quote->id}",
                    'lida' => false,
                ]);
            }

            $count++;
        }

        return $count;
    }

    /**
     * Reconcile quotes stuck in AGUARDANDO_GESTOR when the representative has no team or the team has no active manager.
     *
     * @return int Number of quotes reconciled
     */
    public static function reconcileOrphanedQuotes(): int
    {
        $orphanedQuotes = Cotacao::where('status', 'AGUARDANDO_GESTOR')
            ->with(['representante.equipe.gestor'])
            ->get();

        $semGestorAcao = ParametroSistema::getVal('SEM_GESTOR_ACAO', 'BLOQUEAR');
        $count = 0;

        foreach ($orphanedQuotes as $quote) {
            $gestor = $quote->representante?->equipe?->gestor;
            $hasActiveGestor = ($gestor && $gestor->ativo);

            if (!$hasActiveGestor) {
                if ($semGestorAcao === 'DIRETORIA') {
                    $quote->update(['status' => 'COM_DIRETOR']);

                    CotacaoHistorico::create([
                        'cotacao_id' => $quote->id,
                        'evento' => 'ENVIADA_AO_DIRETOR',
                        'usuario_id' => null,
                        'papel' => 'sistema',
                        'condicao' => 'Cotação órfã em Aguardando Gestor reencaminhada automaticamente para a Diretoria por ausência de gestor ativo na equipe (SEM_GESTOR_ACAO = DIRETORIA).'
                    ]);

                    $recipients = User::whereIn('papel', ['diretor', 'administrador'])->where('ativo', true)->get();
                    foreach ($recipients as $recipient) {
                        \App\Models\Notificacao::create([
                            'usuario_id' => $recipient->id,
                            'titulo' => '📋 Cotação Reencaminhada para Diretoria',
                            'mensagem' => "A cotação {$quote->numero} estava sem gestor responsável e foi reencaminhada para a Diretoria.",
                            'link' => "/cotacoes/id/{$quote->id}",
                            'lida' => false,
                        ]);
                    }
                } else {
                    // BLOQUEAR / DEVOLVER ao representante para ajuste de alçada/equipe
                    $quote->update(['status' => 'DEVOLVIDA']);

                    CotacaoHistorico::create([
                        'cotacao_id' => $quote->id,
                        'evento' => 'COTACAO_DEVOLVIDA',
                        'usuario_id' => null,
                        'papel' => 'sistema',
                        'condicao' => 'Cotação devolvida pelo sistema: representante não possui equipe vinculada ou gestor ativo para aprovação (SEM_GESTOR_ACAO = BLOQUEAR).'
                    ]);

                    if ($quote->representante_id) {
                        \App\Models\Notificacao::create([
                            'usuario_id' => $quote->representante_id,
                            'titulo' => '⚠️ Cotação Devolvida pelo Sistema',
                            'mensagem' => "A cotação {$quote->numero} foi devolvida pois seu usuário não possui gestor ativo cadastrado para aprovação. Entre em contato com a administração.",
                            'link' => "/cotacoes/id/{$quote->id}",
                            'lida' => false,
                        ]);
                    }
                }
                $count++;
            }
        }

        return $count;
    }
}
