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
                'message' => 'Não é possível enviar uma cotação sem itens.'
            ];
        }

        // Calculate totals for priority assessment and overall margin
        $totalQuantity = 0;
        $totalNetRevenue = 0.00;
        $totalCost = 0.00;
        $itensComDadosValidos = 0;

        foreach ($quote->itens as $item) {
            if ($item->status_item === 'recusado') {
                continue;
            }

            $qty = (int)($item->qtd ?? 1);
            $totalQuantity += $qty;

            $proposedPrice = (float)($item->preco_unit_proposto ?? 0);
            $cost = (float)($item->custo ?? 0);
            $tax = (float)($item->imposto ?? 0);
            $suggested = (float)($item->preco_unit_sugerido ?? 0);
            $minPrice = (float)($item->preco_minimo ?? 0);

            // Ignorar itens com dados cadastrais ou numéricos inconsistentes no cálculo da margem:
            // 1. Preço mínimo maior que o sugerido/tabela (inconsistência cadastral, ex: item com dados invertidos)
            // 2. Custo inválido (zerado, negativo ou maior que o preço de referência)
            // 3. Alíquota de imposto inválida (< 0% ou >= 100%)
            // 4. Preço proposto zerado ou negativo
            $hasInconsistentData = ($suggested > 0 && $minPrice > $suggested)
                || ($cost <= 0 || ($suggested > 0 && $cost > $suggested))
                || ($tax < 0 || $tax >= 100)
                || ($proposedPrice <= 0);

            if ($hasInconsistentData) {
                continue;
            }

            $totalNetRevenue += $qty * $proposedPrice * (1 - ($tax / 100));
            $totalCost += $qty * $cost;
            $itensComDadosValidos++;
        }

        $overallMargin = 0.00;
        if ($totalNetRevenue > 0 && $itensComDadosValidos > 0) {
            $overallMargin = (($totalNetRevenue - $totalCost) / $totalNetRevenue) * 100;
        }

        // Grande Conta check
        $grandeContaValor = (float)ParametroSistema::getVal('ALCADA_GRANDE_CONTA_VALOR', 10000.00);
        $grandeContaQtd = (int)ParametroSistema::getVal('ALCADA_GRANDE_CONTA_QTD', 100);
        $grandeContaMargem = (float)ParametroSistema::getVal('ALCADA_GRANDE_CONTA_MARGEM', 15.00);

        // REGRA ESTRITA: Cotação só é classificada como Grande Conta se atingir o valor ou a quantidade relevante.
        // A margem baixa NÃO classifica como Grande Conta (gera apenas alerta comercial à parte).
        $isGrandeConta = false;
        $reasons = [];

        if ((float)$quote->total >= $grandeContaValor) {
            $isGrandeConta = true;
            $reasons[] = sprintf('Valor proposto (R$ %.2f) >= Limite de Grande Conta (R$ %.2f)', $quote->total, $grandeContaValor);
        }

        if ($totalQuantity >= $grandeContaQtd) {
            $isGrandeConta = true;
            $reasons[] = sprintf('Qtd itens (%d) >= Limite de Grande Conta (%d)', $totalQuantity, $grandeContaQtd);
        }

        if ($isGrandeConta && !$quote->prioridade) {
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
        } elseif (!$isGrandeConta && $quote->prioridade) {
            $quote->update(['prioridade' => false]);
        }

        // Alerta à parte para margem baixa (não confunde com Grande Conta)
        if ($itensComDadosValidos > 0 && $totalNetRevenue > 0 && $overallMargin <= $grandeContaMargem) {
            $jaAlertouMargem = CotacaoHistorico::where('cotacao_id', $quote->id)
                ->where('evento', 'ALERTA_MARGEM_BAIXA')
                ->exists();

            if (!$jaAlertouMargem) {
                CotacaoHistorico::create([
                    'cotacao_id' => $quote->id,
                    'evento' => 'ALERTA_MARGEM_BAIXA',
                    'usuario_id' => $quote->representante_id,
                    'papel' => 'sistema',
                    'condicao' => sprintf(
                        'Alerta comercial: Margem líquida estimada da cotação (%.2f%%) está abaixo ou igual ao limite de atenção comercial (%.2f%%).',
                        $overallMargin,
                        $grandeContaMargem
                    ),
                ]);

                // Notificar Gestor
                $gestorId = $quote->representante->equipe?->gestor_id;
                if ($gestorId) {
                    \App\Models\Notificacao::create([
                        'usuario_id' => $gestorId,
                        'titulo' => '⚠️ Alerta: Margem Comercial Baixa',
                        'mensagem' => sprintf('A cotação nº %s do vendedor %s possui margem estimada de %.2f%% (limite de atenção: %.2f%%).', $quote->numero, $quote->representante->nome, $overallMargin, $grandeContaMargem),
                        'link' => "/aprovacoes/{$quote->id}",
                        'lida' => false
                    ]);
                }

                // Notificar Diretores
                $directors = \App\Models\User::where('papel', 'diretor')->get();
                foreach ($directors as $dir) {
                    \App\Models\Notificacao::create([
                        'usuario_id' => $dir->id,
                        'titulo' => '⚠️ Alerta: Margem Comercial Baixa',
                        'mensagem' => sprintf('A cotação nº %s do vendedor %s possui margem estimada de %.2f%% (limite de atenção: %.2f%%).', $quote->numero, $quote->representante->nome, $overallMargin, $grandeContaMargem),
                        'link' => "/aprovacoes/{$quote->id}",
                        'lida' => false
                    ]);
                }
            }
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
        // e identificar itens com inconsistência cadastral (preço mínimo > preço sugerido)
        $hasItemsBelowMin = false;
        $inconsistentItems = [];

        foreach ($quote->itens as $item) {
            $suggested = (float)$item->preco_unit_sugerido;
            $minPrice = (float)$item->preco_minimo;
            $proposedPrice = (float)$item->preco_unit_proposto;

            // Detectar inconsistência cadastral (mínimo cadastral maior que o sugerido de tabela)
            $isInconsistent = ($suggested > 0 && $minPrice > $suggested);
            if ($isInconsistent) {
                $inconsistentItems[] = $item;
                if (!$item->inconsistente) {
                    $item->update(['inconsistente' => true]);
                }
            }

            if ($reenvioModo === 'SO_ITENS_ALTERADOS' && $item->status_item === 'aprovado') {
                continue;
            }

            // Preço mínimo efetivo: quando o mínimo cadastral for maior que o sugerido,
            // a referência para exigir justificativa de piso é o preço sugerido (vendendo pelo sugerido nunca exige justificativa).
            $effectiveMin = $isInconsistent ? $suggested : $minPrice;

            if ($proposedPrice < $effectiveMin) {
                $hasItemsBelowMin = true;
            }
        }

        // Avisar administradores se houver itens com cadastro inconsistente
        if (!empty($inconsistentItems)) {
            static::notifyAdminsAboutInconsistentItems($quote, $inconsistentItems);
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
                'message' => 'Cotação aprovada automaticamente. O PDF está disponível para geração.'
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
                    'message' => 'Um ou mais itens estão abaixo do preço mínimo. É obrigatório fornecer justificativa e anexar pelo menos um arquivo comprovatório.'
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
                'message' => 'Cotação enviada para aprovação da Diretoria.'
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
                'message' => 'Cotação enviada para aprovação do Gestor.'
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

        // Reconcile Grande Conta / Priority quotes below thresholds
        self::reconcileGrandeContaPriorities();

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

    /**
     * Remove priority / Big Account flag from quotes that are below the value and quantity thresholds.
     */
    public static function reconcileGrandeContaPriorities(): int
    {
        $valorLimite = (float) ParametroSistema::getVal('ALCADA_GRANDE_CONTA_VALOR', 10000.00);
        $qtdLimite = (int) ParametroSistema::getVal('ALCADA_GRANDE_CONTA_QTD', 100);

        $cotacoesPrioritarias = Cotacao::where('prioridade', true)
            ->where('total', '<', $valorLimite)
            ->with('itens')
            ->get();

        $count = 0;
        foreach ($cotacoesPrioritarias as $c) {
            $totalQtd = $c->itens->where('status_item', '!=', 'recusado')->sum('qtd');
            if ($totalQtd < $qtdLimite) {
                $c->update(['prioridade' => false]);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Notify administrators about items with inconsistent cadastral data (min price > suggested price)
     */
    public static function notifyAdminsAboutInconsistentItems(Cotacao $quote, array $items): void
    {
        $admins = \App\Models\User::where('papel', 'administrador')->where('ativo', true)->get();
        if ($admins->isEmpty()) {
            return;
        }

        foreach ($items as $item) {
            $prodDesc = $item->produto->descricao ?? ('Produto #' . $item->produto_id);
            $prodCode = $item->produto->codigo_sankhya ?? ('ID:' . $item->produto_id);
            $minVal = number_format((float)$item->preco_minimo, 2, ',', '.');
            $sugVal = number_format((float)$item->preco_unit_sugerido, 2, ',', '.');

            // Evitar duplicações de histórico e notificação para o mesmo item
            $alreadyLogged = CotacaoHistorico::where('cotacao_id', $quote->id)
                ->where('evento', 'ALERTA_ITEM_INCONSISTENTE')
                ->where('cotacao_item_id', $item->id)
                ->exists();

            if (!$alreadyLogged) {
                CotacaoHistorico::create([
                    'cotacao_id' => $quote->id,
                    'cotacao_item_id' => $item->id,
                    'evento' => 'ALERTA_ITEM_INCONSISTENTE',
                    'usuario_id' => null,
                    'papel' => 'sistema',
                    'condicao' => "Inconsistência cadastral: Preço Mínimo (R$ {$minVal}) é maior que o Sugerido (R$ {$sugVal}) para '{$prodDesc}' ({$prodCode}). Administradores notificados para conferência no cadastro/Sankhya.",
                ]);

                foreach ($admins as $admin) {
                    \App\Models\Notificacao::create([
                        'usuario_id' => $admin->id,
                        'titulo' => '⚠️ Preço Mínimo Maior que Sugerido',
                        'mensagem' => "O produto '{$prodDesc}' ({$prodCode}) na cotação nº {$quote->numero} possui Preço Mínimo (R$ {$minVal}) maior que o Sugerido (R$ {$sugVal}). Favor conferir no cadastro.",
                        'link' => "/cotacoes/id/{$quote->id}",
                        'lida' => false,
                    ]);
                }
            }
        }
    }
}
