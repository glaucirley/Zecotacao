<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Cotacao;
use App\Models\CotacaoItem;
use App\Models\CotacaoJustificativa;
use App\Models\CotacaoVinculoItem;
use App\Models\CotacaoHistorico;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ApprovalController extends Controller
{
    /**
     * List quotations awaiting approval for the logged-in user.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        if ($user->isGestor()) {
            // Find quotes waiting for gestor where representative belongs to the manager's team
            $teamIds = $user->equipesGerenciadas->pluck('id');
            $quotes = Cotacao::where('status', 'AGUARDANDO_GESTOR')
                ->whereHas('representante', function ($q) use ($teamIds) {
                    $q->whereIn('equipe_id', $teamIds);
                })
                ->with(['parceiro', 'representante'])
                ->get();

            return response()->json(['success' => true, 'data' => $quotes]);
        } 
        
        if ($user->isDiretor()) {
            // Directors see all quotes escalated to the director
            $quotes = Cotacao::where('status', 'COM_DIRETOR')
                ->with(['parceiro', 'representante'])
                ->get();

            return response()->json(['success' => true, 'data' => $quotes]);
        }

        if ($user->isAdministrador()) {
            // Admin sees both manager pending and director pending quotes
            $quotes = Cotacao::whereIn('status', ['AGUARDANDO_GESTOR', 'COM_DIRETOR'])
                ->with(['parceiro', 'representante'])
                ->get();

            return response()->json(['success' => true, 'data' => $quotes]);
        }

        return response()->json(['error' => 'Forbidden. Only managers, directors, and administrators can access approvals.'], 403);
    }

    /**
     * Show quotation detail item by item.
     */
    public function show($cotacao_id)
    {
        $quote = Cotacao::with([
            'parceiro',
            'representante.equipe',
            'itens.produto',
            'justificativas.anexos',
            'anexos',
            'historico.usuario'
        ])->findOrFail($cotacao_id);

        $user = Auth::user();

        // Authorization check: gestor can only view their team's quotes. Director can view everything.
        if ($user->isGestor()) {
            if ($quote->representante->equipe?->gestor_id !== $user->id) {
                return response()->json(['error' => 'Forbidden. This quotation is not from your team.'], 403);
            }
        } elseif (!$user->isDiretor() && !$user->isAdministrador()) {
            return response()->json(['error' => 'Forbidden.'], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $quote
        ]);
    }

    /**
     * Simulate/edit proposed price of a specific item.
     */
    public function simulatePrice(Request $request, $cotacao_id, $item_id)
    {
        $quote = Cotacao::findOrFail($cotacao_id);
        $item = CotacaoItem::where('cotacao_id', $quote->id)->findOrFail($item_id);
        $user = Auth::user();

        // Authorization
        if ($user->isGestor()) {
            if ($quote->representante->equipe?->gestor_id !== $user->id) {
                return response()->json(['error' => 'Forbidden. This quotation is not from your team.'], 403);
            }
        } elseif (!$user->isDiretor() && !$user->isAdministrador()) {
            return response()->json(['error' => 'Forbidden.'], 403);
        }

        // Validate status (must be pending approval)
        if (!in_array($quote->status, ['AGUARDANDO_GESTOR', 'COM_DIRETOR'])) {
            return response()->json(['error' => 'Invalid state', 'message' => 'Cannot edit prices of a quote that is not in approval stage.'], 422);
        }

        $request->validate([
            'preco_unit_proposto' => 'required|numeric|min:0.01'
        ]);

        try {
            DB::transaction(function () use ($request, $quote, $item, $user) {
                $precoProposto = (float)$request->input('preco_unit_proposto');
                $precoSugerido = (float)$item->preco_unit_sugerido;
                $ajuste = $precoSugerido > 0 ? (($precoProposto - $precoSugerido) / $precoSugerido) * 100 : 0;

                $item->update([
                    'preco_unit_proposto' => $precoProposto,
                    'ajuste_percentual' => $ajuste,
                    'subtotal' => $item->qtd * $precoProposto,
                ]);

                // Recalculate quote totals
                $subtotal = 0;
                $total = 0;
                $quote->refresh();
                foreach ($quote->itens as $it) {
                    if ($it->status_item === 'recusado') {
                        continue;
                    }
                    $subtotal += $it->qtd * max((float)$it->preco_unit_sugerido, (float)$it->preco_unit_proposto);
                    $total += (float)$it->subtotal;
                }
                $desconto = $subtotal - $total;
                $quote->update([
                    'subtotal' => $subtotal,
                    'desconto' => $desconto,
                    'total' => $total,
                ]);

                // Log audit history
                CotacaoHistorico::create([
                    'cotacao_id' => $quote->id,
                    'cotacao_item_id' => $item->id,
                    'evento' => 'SIMULACAO_PRECO_APROVADOR',
                    'usuario_id' => $user->id,
                    'papel' => $user->papel,
                    'condicao' => "Preco proposto do item alterado para R$ {$precoProposto} durante revisao.",
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Price updated in simulation successfully.',
                'data' => $quote->fresh(['itens.produto'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Database error',
                'message' => 'Failed to simulate price. ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bind multiple items of a quotation together under a single vinculo group.
     */
    public function bindItems(Request $request, $cotacao_id)
    {
        $quote = Cotacao::findOrFail($cotacao_id);
        $user = Auth::user();

        // Authorization
        if ($user->isGestor()) {
            if ($quote->representante->equipe?->gestor_id !== $user->id) {
                return response()->json(['error' => 'Forbidden. This quotation is not from your team.'], 403);
            }
        } elseif (!$user->isDiretor() && !$user->isAdministrador()) {
            return response()->json(['error' => 'Forbidden.'], 403);
        }

        $request->validate([
            'grupo_vinculo' => 'required|string|max:50',
            'item_ids' => 'required|array',
            'item_ids.*' => 'exists:cotacao_itens,id'
        ]);

        try {
            DB::transaction(function () use ($request, $quote, $cotacao_id, $user) {
                $itemIds = $request->input('item_ids');
                $grupoVinculo = $request->input('grupo_vinculo');

                // Ensure items belong to this quote
                $validItemsCount = CotacaoItem::where('cotacao_id', $quote->id)
                    ->whereIn('id', $itemIds)
                    ->count();

                if ($validItemsCount !== count($itemIds)) {
                    throw new \Exception('One or more item IDs do not belong to this quotation.');
                }

                // Delete previous vinculos for these items in this quote
                CotacaoVinculoItem::where('cotacao_id', $quote->id)
                    ->whereIn('cotacao_item_id', $itemIds)
                    ->delete();

                // Create new vinculo records
                foreach ($itemIds as $itemId) {
                    CotacaoVinculoItem::create([
                        'cotacao_id' => $quote->id,
                        'cotacao_item_id' => $itemId,
                        'grupo_vinculo' => $grupoVinculo
                    ]);
                }

                // Log audit
                CotacaoHistorico::create([
                    'cotacao_id' => $quote->id,
                    'evento' => 'ITENS_VINCULADOS',
                    'usuario_id' => $user->id,
                    'papel' => $user->papel,
                    'condicao' => "Itens (" . implode(', ', $itemIds) . ") vinculados ao grupo: '{$grupoVinculo}'.",
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Items bound successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Database error',
                'message' => 'Failed to bind items. ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Submit decision: either item-by-item decisions or quote-level actions (devolver/escalar).
     */
    public function decide(Request $request, $cotacao_id)
    {
        $quote = Cotacao::findOrFail($cotacao_id);
        $user = Auth::user();

        // Authorization
        if ($user->isGestor()) {
            if ($quote->representante->equipe?->gestor_id !== $user->id) {
                return response()->json(['error' => 'Forbidden. This quotation is not from your team.'], 403);
            }
        } elseif (!$user->isDiretor() && !$user->isAdministrador()) {
            return response()->json(['error' => 'Forbidden.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'acao' => 'required|in:decidir_itens,devolver,escalar',
            'justificativa' => 'required_if:acao,devolver,escalar|string',
            
            'itens' => 'required_if:acao,decidir_itens|array',
            'itens.*.id' => 'required_with:itens|exists:cotacao_itens,id',
            'itens.*.status' => 'required_with:itens|in:aprovado,recusado',
            'itens.*.justificativa' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation error', 'messages' => $validator->errors()], 422);
        }

        $acao = $request->input('acao');

        // Manual item-level checks for rejected justifications
        if ($acao === 'decidir_itens') {
            foreach ($request->input('itens', []) as $index => $itemDec) {
                if (isset($itemDec['status']) && $itemDec['status'] === 'recusado') {
                    if (empty($itemDec['justificativa'])) {
                        return response()->json([
                            'error' => 'Validation error',
                            'messages' => [
                                "itens.{$index}.justificativa" => ["O motivo da recusa é obrigatório para itens rejeitados."]
                            ]
                        ], 422);
                    }
                }
            }
        }

        try {
            DB::transaction(function () use ($request, $quote, $acao, $user) {
                if ($acao === 'devolver') {
                    $quote->update(['status' => 'DEVOLVIDA']);

                    // Clear items approvals and set back to pendente
                    CotacaoItem::where('cotacao_id', $quote->id)
                        ->update(['status_item' => 'pendente']);

                    CotacaoHistorico::create([
                        'cotacao_id' => $quote->id,
                        'evento' => 'DEVOLVIDA_AO_REPRESENTANTE',
                        'usuario_id' => $user->id,
                        'papel' => $user->papel,
                        'condicao' => 'Cotacao devolvida para correcoes. Motivo: ' . $request->input('justificativa'),
                    ]);

                    // Create Notification for Representative
                    \App\Models\Notificacao::create([
                        'usuario_id' => $quote->representante_id,
                        'titulo' => 'Cotação Devolvida!',
                        'mensagem' => "A cotação {$quote->numero} foi devolvida pelo avaliador para correções.",
                        'link' => url("/cotacoes/token/{$quote->token_representante}"),
                        'lida' => false,
                    ]);
                } 
                elseif ($acao === 'escalar') {
                    $quote->update(['status' => 'COM_DIRETOR']);

                    CotacaoHistorico::create([
                        'cotacao_id' => $quote->id,
                        'evento' => 'ESCALADA_PARA_DIRETORIA',
                        'usuario_id' => $user->id,
                        'papel' => $user->papel,
                        'condicao' => 'Cotacao escalada para decisao da diretoria. Motivo: ' . $request->input('justificativa'),
                    ]);
                } 
                elseif ($acao === 'decidir_itens') {
                    foreach ($request->input('itens') as $itemDec) {
                        $item = CotacaoItem::where('cotacao_id', $quote->id)
                            ->where('id', $itemDec['id'])
                            ->first();

                        if ($item) {
                            $item->update(['status_item' => $itemDec['status']]);

                            // Log item status in history
                            CotacaoHistorico::create([
                                'cotacao_id' => $quote->id,
                                'cotacao_item_id' => $item->id,
                                'evento' => $itemDec['status'] === 'aprovado' ? 'ITEM_APROVADO' : 'ITEM_RECUSADO',
                                'usuario_id' => $user->id,
                                'papel' => $user->papel,
                                'condicao' => $itemDec['status'] === 'aprovado' 
                                    ? 'Item aprovado pelo avaliador.' 
                                    : 'Item recusado. Motivo: ' . $itemDec['justificativa'],
                            ]);

                            // Save justification for rejected items
                            if ($itemDec['status'] === 'recusado') {
                                CotacaoJustificativa::create([
                                    'cotacao_id' => $quote->id,
                                    'cotacao_item_id' => $item->id,
                                    'texto' => $itemDec['justificativa'],
                                    'criado_por' => $user->id,
                                ]);
                            }
                        }
                    }

                    // Check if all items in this quote have a decision (no longer 'pendente')
                    $pendingItemsCount = CotacaoItem::where('cotacao_id', $quote->id)
                        ->where('status_item', 'pendente')
                        ->count();

                    if ($pendingItemsCount === 0) {
                        // All items decided!
                        // Calculate final approved totals to exclude rejected items
                        $approvedItems = CotacaoItem::where('cotacao_id', $quote->id)
                            ->where('status_item', 'aprovado')
                            ->get();

                        $finalSubtotal = 0;
                        $finalTotal = 0;

                        foreach ($approvedItems as $it) {
                            $finalSubtotal += $it->qtd * max((float)$it->preco_unit_sugerido, (float)$it->preco_unit_proposto);
                            $finalTotal += (float)$it->subtotal;
                        }

                        $desconto = $finalSubtotal - $finalTotal;

                        // Transition quote status to PDF_GERADO and update to final totals
                        $quote->update([
                            'status' => 'PDF_GERADO',
                            'subtotal' => $finalSubtotal,
                            'desconto' => $desconto,
                            'total' => $finalTotal,
                        ]);

                        $roleUpper = strtoupper($user->papel);
                        CotacaoHistorico::create([
                            'cotacao_id' => $quote->id,
                            'evento' => "APROVADA_{$roleUpper}",
                            'usuario_id' => $user->id,
                            'papel' => $user->papel,
                            'condicao' => 'Todos os itens foram avaliados. Status alterado para PDF_GERADO com totais recalculados apenas sobre itens aprovados.',
                        ]);

                        // Create Notification for Representative
                        \App\Models\Notificacao::create([
                            'usuario_id' => $quote->representante_id,
                            'titulo' => 'Cotação Aprovada!',
                            'mensagem' => "A cotação {$quote->numero} foi revisada e liberada! PDF pronto para faturamento.",
                            'link' => url("/cotacoes/token/{$quote->token_representante}"),
                            'lida' => false,
                        ]);
                    }
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Decision processed successfully.',
                'status' => $quote->fresh()->status
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Database error',
                'message' => 'Failed to submit decision. ' . $e->getMessage()
            ], 500);
        }
    }
}
