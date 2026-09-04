<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cotacao;
use App\Models\CotacaoItem;
use App\Models\CotacaoJustificativa;
use App\Models\CotacaoAnexo;
use App\Models\CotacaoHistorico;
use App\Models\Produto;
use App\Models\ParametroSistema;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class QuoteController extends Controller
{
    /**
     * List all quotations in the system with filters.
     */
    public function listAll(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $query = Cotacao::with(['parceiro', 'representante.equipe'])
            ->orderBy('created_at', 'desc');

        // Access Control
        if ($user->isGestor()) {
            $teamIds = $user->equipesGerenciadas->pluck('id');
            $query->whereHas('representante', function ($q) use ($teamIds) {
                $q->whereIn('equipe_id', $teamIds);
            });
        } elseif ($user->isRepresentante()) {
            $query->where('representante_id', $user->id);
        } elseif (!$user->isDiretor() && !$user->isAdministrador() && !$user->isFaturamento()) {
            return response()->json(['error' => 'Forbidden.'], 403);
        }

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('numero', 'like', "%{$search}%")
                  ->orWhereHas('parceiro', function ($p) use ($search) {
                      $p->where('razao_social', 'like', "%{$search}%")
                        ->orWhere('nome_fantasia', 'like', "%{$search}%");
                  });
            });
        }

        $quotes = $query->get();

        return response()->json([
            'success' => true,
            'data' => $quotes
        ]);
    }

    /**
     * Get metadata for manual creation (partners, representatives, products).
     */
    public function getMeta(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $partners = \App\Models\Parceiro::where('ativo', true)->orderBy('razao_social')->get();
        $products = \App\Models\Produto::where('ativo', true)->orderBy('descricao')->get();

        // Representatives based on role
        if ($user->isAdministrador() || $user->isDiretor() || $user->isFaturamento()) {
            $representatives = User::where('papel', 'representante')->where('ativo', true)->orderBy('nome')->get();
        } elseif ($user->isGestor()) {
            $teamIds = $user->equipesGerenciadas->pluck('id');
            $representatives = User::where('papel', 'representante')
                ->whereIn('equipe_id', $teamIds)
                ->where('ativo', true)
                ->orderBy('nome')
                ->get();
        } else {
            $representatives = User::where('id', $user->id)->get();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'partners' => $partners,
                'representatives' => $representatives,
                'products' => $products
            ]
        ]);
    }

    /**
     * Create a quotation manually.
     */
    public function storeManual(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $validator = Validator::make($request->all(), [
            'parceiro_id' => 'required|exists:parceiros,id',
            'representante_id' => 'required|exists:usuarios,id',
            'forma_pagamento' => 'nullable|string',
            'prazo_entrega' => 'nullable|string',
            'frete_tipo' => 'nullable|in:CIF,FOB',
            'observacao_cliente' => 'nullable|string',
            'observacao_interna' => 'nullable|string',
            'itens' => 'required|array|min:1',
            'itens.*.produto_id' => 'required|exists:produtos,id',
            'itens.*.qtd' => 'required|integer|min:1',
            'itens.*.preco_unit_proposto' => 'required|numeric|min:0.01'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation error', 'messages' => $validator->errors()], 422);
        }

        // Access check: representatives can only create quotes for themselves
        $repId = $request->input('representante_id');
        if ($user->isRepresentante() && $repId != $user->id) {
            return response()->json(['error' => 'Forbidden. Representatives can only create quotes for themselves.'], 403);
        }

        try {
            return DB::transaction(function () use ($request, $repId, $user) {
                // Generate unique number
                $num = 'COT-M-' . strtoupper(Str::random(6)) . '-' . time();
                
                // Expiry time (read from parameters or default 24h)
                $hoursParam = \App\Models\ParametroSistema::where('chave', 'VALIDADE_PADRAO_HORAS')->first();
                $hours = $hoursParam ? (int)$hoursParam->valor : 24;

                $quote = Cotacao::create([
                    'numero' => $num,
                    'parceiro_id' => $request->input('parceiro_id'),
                    'representante_id' => $repId,
                    'status' => 'EM_CRIACAO',
                    'origem' => 'plataforma_manual',
                    'data_emissao' => now(),
                    'validade_horas' => $hours,
                    'data_validade' => now()->addHours($hours),
                    'forma_pagamento' => $request->input('forma_pagamento', 'A combinar'),
                    'prazo_entrega' => $request->input('prazo_entrega', '3 dias'),
                    'frete_tipo' => $request->input('frete_tipo', 'CIF'),
                    'observacao_cliente' => $request->input('observacao_cliente'),
                    'observacao_interna' => $request->input('observacao_interna'),
                    'token_representante' => Str::random(40),
                    'token_acesso_em' => now()
                ]);

                $subtotal = 0;
                $total = 0;

                // Create items
                foreach ($request->input('itens') as $itemData) {
                    $prod = \App\Models\Produto::findOrFail($itemData['produto_id']);
                    $qtd = (int)$itemData['qtd'];
                    $precoProposto = (float)$itemData['preco_unit_proposto'];

                    // Pricing rules lookup (based on seeded product codes)
                    $sugerido = $precoProposto;
                    $minimo = $precoProposto * 0.90;
                    $custo = $precoProposto * 0.60;
                    $imposto = 18.00;

                    if ($prod->codigo_sankhya === 'PROD001') {
                        $sugerido = 85.00;
                        $minimo = 75.00;
                        $custo = 45.00;
                        $imposto = 18.00;
                    } elseif ($prod->codigo_sankhya === 'PROD002') {
                        $sugerido = 280.00;
                        $minimo = 250.00;
                        $custo = 160.00;
                        $imposto = 12.00;
                    } elseif ($prod->codigo_sankhya === 'PROD003') {
                        $sugerido = 45.00;
                        $minimo = 40.00;
                        $custo = 22.00;
                        $imposto = 18.00;
                    }

                    $ajuste = $sugerido > 0 ? (($precoProposto - $sugerido) / $sugerido) * 100 : 0;
                    $itemSubtotal = $qtd * $precoProposto;

                    // Margin
                    $margem = null;
                    if ($precoProposto > 0 && $custo > 0) {
                        $impostoFator = 1 - ($imposto / 100);
                        $margem = (($precoProposto * $impostoFator - $custo) / ($precoProposto * $impostoFator)) * 100;
                    }

                    CotacaoItem::create([
                        'cotacao_id' => $quote->id,
                        'produto_id' => $prod->id,
                        'qtd' => $qtd,
                        'preco_unit_sugerido' => $sugerido,
                        'preco_minimo' => $minimo,
                        'preco_unit_proposto' => $precoProposto,
                        'ajuste_percentual' => $ajuste,
                        'subtotal' => $itemSubtotal,
                        'status_item' => 'pendente',
                        'custo' => $custo,
                        'imposto' => $imposto,
                        'margem_calculada' => $margem
                    ]);

                    $subtotal += $qtd * max($sugerido, $precoProposto);
                    $total += $itemSubtotal;
                }

                // Update quote totals
                $desconto = $subtotal - $total;
                $quote->update([
                    'subtotal' => $subtotal,
                    'desconto' => $desconto,
                    'total' => $total
                ]);

                // Audit history log
                CotacaoHistorico::create([
                    'cotacao_id' => $quote->id,
                    'evento' => 'CRIACAO_MANUAL',
                    'usuario_id' => $user->id,
                    'papel' => $user->papel,
                    'condicao' => 'Cotacao inserida manualmente no painel.'
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Manually created quotation successfully.',
                    'data' => $quote->fresh(['itens.produto', 'parceiro', 'representante'])
                ], 201);
            });

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Database error',
                'message' => 'Failed to create quotation. ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a quotation.
     */
    public function destroy($id)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $quote = Cotacao::findOrFail($id);

        // Access control
        if ($user->isAdministrador()) {
            // Admins can delete anything
        } elseif ($user->isRepresentante() && $quote->representante_id == $user->id) {
            // Representative can delete only if status is EM_CRIACAO
            if ($quote->status !== 'EM_CRIACAO') {
                return response()->json(['error' => 'Forbidden. You can only delete quotations in draft state.'], 403);
            }
        } elseif ($user->isGestor()) {
            // Gestor can delete their team's quotes only if in draft
            $teamIds = $user->equipesGerenciadas->pluck('id');
            if (!in_array($quote->representante->equipe_id, $teamIds->toArray()) || $quote->status !== 'EM_CRIACAO') {
                return response()->json(['error' => 'Forbidden. You can only delete draft quotations of your team.'], 403);
            }
        } else {
            return response()->json(['error' => 'Forbidden.'], 403);
        }

        try {
            $quote->delete();
            return response()->json([
                'success' => true,
                'message' => 'Quotation deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Database error',
                'message' => 'Failed to delete quotation. ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show quote details.
     */
    public function show(Request $request)
    {
        $quote = $request->cotacao;
        $quote->load([
            'parceiro',
            'representante.equipe',
            'itens.produto',
            'justificativas.anexos',
            'anexos',
            'historico.usuario'
        ]);

        return response()->json([
            'success' => true,
            'data' => $quote
        ]);
    }

    /**
     * Update quotation conditions, commercial terms, and batch update items.
     */
    public function update(Request $request)
    {
        $quote = $request->cotacao;

        $validator = Validator::make($request->all(), [
            'forma_pagamento' => 'nullable|string',
            'prazo_entrega' => 'nullable|string',
            'frete_tipo' => 'nullable|in:CIF,FOB',
            'transportadora' => 'nullable|string',
            'observacao_cliente' => 'nullable|string',
            'observacao_interna' => 'nullable|string',
            
            'itens' => 'nullable|array',
            'itens.*.id' => 'required_with:itens|integer',
            'itens.*.qtd' => 'required_with:itens|integer|min:1',
            'itens.*.preco_unit_proposto' => 'required_with:itens|numeric|min:0.01',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation error', 'messages' => $validator->errors()], 422);
        }

        try {
            DB::transaction(function () use ($request, $quote) {
                // Update quote fields
                $quote->update($request->only([
                    'forma_pagamento',
                    'prazo_entrega',
                    'frete_tipo',
                    'transportadora',
                    'observacao_cliente',
                    'observacao_interna',
                ]));

                // Update items if passed
                if ($request->has('itens')) {
                    foreach ($request->input('itens') as $itemData) {
                        $item = CotacaoItem::where('cotacao_id', $quote->id)
                            ->where('id', $itemData['id'])
                            ->first();

                        if ($item) {
                            // If price or quantity is changed, the item goes back to 'pendente' for review
                            $status = $item->status_item;
                            $hasChanged = (float)$item->preco_unit_proposto !== (float)$itemData['preco_unit_proposto'] 
                                       || (int)$item->qtd !== (int)$itemData['qtd'];

                            if ($hasChanged && in_array($status, ['recusado', 'aprovado'])) {
                                $status = 'pendente';
                            }

                            $precoProposto = (float)$itemData['preco_unit_proposto'];
                            $precoSugerido = (float)$item->preco_unit_sugerido;
                            $ajuste = $precoSugerido > 0 ? (($precoProposto - $precoSugerido) / $precoSugerido) * 100 : 0;

                            $item->update([
                                'qtd' => $itemData['qtd'],
                                'preco_unit_proposto' => $precoProposto,
                                'ajuste_percentual' => $ajuste,
                                'subtotal' => $itemData['qtd'] * $precoProposto,
                                'status_item' => $status,
                            ]);
                        }
                    }
                }

                // Recalculate totals
                $this->recalculateQuoteTotals($quote);

                // Log history
                CotacaoHistorico::create([
                    'cotacao_id' => $quote->id,
                    'evento' => 'EDITADA_PELO_REPRESENTANTE',
                    'usuario_id' => $quote->representante_id,
                    'papel' => 'representante',
                    'condicao' => 'Cotacao alterada pelo representante comercial.',
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Quote updated successfully.',
                'data' => $quote->fresh(['itens.produto'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Database error',
                'message' => 'Failed to update quote. ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add a new item to the quotation.
     */
    public function addItem(Request $request)
    {
        $quote = $request->cotacao;

        $validator = Validator::make($request->all(), [
            'produto_id' => 'required|exists:produtos,id',
            'qtd' => 'required|integer|min:1',
            'preco_unit_proposto' => 'required|numeric|min:0.01',
            'preco_unit_sugerido' => 'required|numeric|min:0.01',
            'preco_minimo' => 'required|numeric|min:0.01',
            'custo' => 'nullable|numeric',
            'imposto' => 'nullable|numeric',
            'campanha_id' => 'nullable|string',
            'mostrar_selo_campanha' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation error', 'messages' => $validator->errors()], 422);
        }

        try {
            DB::transaction(function () use ($request, $quote) {
                $precoProposto = (float)$request->input('preco_unit_proposto');
                $precoSugerido = (float)$request->input('preco_unit_sugerido');
                $ajuste = $precoSugerido > 0 ? (($precoProposto - $precoSugerido) / $precoSugerido) * 100 : 0;
                $qtd = (int)$request->input('qtd');

                // Determine display order (add to end)
                $maxOrder = CotacaoItem::where('cotacao_id', $quote->id)->max('ordem_exibicao') ?? 0;

                CotacaoItem::create([
                    'cotacao_id' => $quote->id,
                    'produto_id' => $request->input('produto_id'),
                    'qtd' => $qtd,
                    'preco_unit_sugerido' => $precoSugerido,
                    'preco_minimo' => $request->input('preco_minimo'),
                    'preco_unit_proposto' => $precoProposto,
                    'ajuste_percentual' => $ajuste,
                    'subtotal' => $qtd * $precoProposto,
                    'status_item' => 'pendente',
                    'margem_calculada' => $request->input('margem_calculada'),
                    'custo' => $request->input('custo'),
                    'imposto' => $request->input('imposto'),
                    'campanha_id' => $request->input('campanha_id'),
                    'mostrar_selo_campanha' => $request->input('mostrar_selo_campanha', false),
                    'ordem_exibicao' => $maxOrder + 1,
                ]);

                $this->recalculateQuoteTotals($quote);

                CotacaoHistorico::create([
                    'cotacao_id' => $quote->id,
                    'evento' => 'ITEM_ADICIONADO',
                    'usuario_id' => $quote->representante_id,
                    'papel' => 'representante',
                    'condicao' => 'Item adicionado a cotacao.',
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Item added successfully.',
                'data' => $quote->fresh(['itens.produto'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Database error',
                'message' => 'Failed to add item. ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove an item from the quotation.
     */
    public function removeItem(Request $request, $token, $item_id)
    {
        $quote = $request->cotacao;

        $item = CotacaoItem::where('cotacao_id', $quote->id)->where('id', $item_id)->first();

        if (!$item) {
            return response()->json(['error' => 'Item not found.'], 404);
        }

        try {
            DB::transaction(function () use ($quote, $item) {
                $item->delete();
                $this->recalculateQuoteTotals($quote);

                CotacaoHistorico::create([
                    'cotacao_id' => $quote->id,
                    'evento' => 'ITEM_REMOVIDO',
                    'usuario_id' => $quote->representante_id,
                    'papel' => 'representante',
                    'condicao' => "Item ID {$item->id} removido da cotacao.",
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Item removed successfully.',
                'data' => $quote->fresh(['itens.produto'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Database error',
                'message' => 'Failed to remove item. ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add a justification (and upload files/audio) for a discount.
     */
    public function addJustification(Request $request)
    {
        $quote = $request->cotacao;

        $validator = Validator::make($request->all(), [
            'texto' => 'nullable|string',
            'audio' => 'nullable|file|mimes:audio/mpeg,mp3,wav,ogg,m4a,application/octet-stream|max:10240', // 10MB limit
            'cotacao_item_id' => 'nullable|exists:cotacao_itens,id',
            'anexos' => 'nullable|array',
            'anexos.*' => 'file|max:10240', // 10MB limit per file
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation error', 'messages' => $validator->errors()], 422);
        }

        try {
            $justification = DB::transaction(function () use ($request, $quote) {
                // Handle audio upload
                $audioUrl = null;
                if ($request->hasFile('audio')) {
                    $path = $request->file('audio')->store('justificativas/audios', 'public');
                    $audioUrl = Storage::disk('public')->url($path);
                }

                // Create justification
                $justification = CotacaoJustificativa::create([
                    'cotacao_id' => $quote->id,
                    'cotacao_item_id' => $request->input('cotacao_item_id'),
                    'texto' => $request->input('texto'),
                    'audio_url' => $audioUrl,
                    'criado_por' => $quote->representante_id,
                ]);

                // Handle file attachments (anexos)
                if ($request->hasFile('anexos')) {
                    foreach ($request->file('anexos') as $file) {
                        $path = $file->store('justificativas/anexos', 'public');
                        $url = Storage::disk('public')->url($path);
                        $mime = $file->getClientMimeType();
                        $type = Str::contains($mime, 'image') ? 'imagem' : 'documento';

                        CotacaoAnexo::create([
                            'cotacao_id' => $quote->id,
                            'justificativa_id' => $justification->id,
                            'tipo' => $type,
                            'arquivo_url' => $url,
                        ]);
                    }
                }

                return $justification;
            });

            return response()->json([
                'success' => true,
                'message' => 'Justification added successfully.',
                'data' => $justification->load('anexos')
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Database error',
                'message' => 'Failed to save justification. ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark the quotation as lost (perdida).
     */
    public function markAsLost(Request $request)
    {
        $quote = $request->cotacao;

        $request->validate([
            'justificativa' => 'required|string|min:5'
        ]);

        try {
            DB::transaction(function () use ($request, $quote) {
                $quote->update(['status' => 'PERDIDA']);

                // Log the justification
                CotacaoJustificativa::create([
                    'cotacao_id' => $quote->id,
                    'texto' => $request->input('justificativa'),
                    'criado_por' => $quote->representante_id,
                ]);

                CotacaoHistorico::create([
                    'cotacao_id' => $quote->id,
                    'evento' => 'MARCADA_COMO_PERDIDA',
                    'usuario_id' => $quote->representante_id,
                    'papel' => 'representante',
                    'condicao' => 'Cotacao marcada como perdida pelo representante. Motivo: ' . $request->input('justificativa'),
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Quote marked as lost.',
                'status' => 'PERDIDA'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Database error',
                'message' => 'Failed to mark as lost. ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Submit quote to workflow (Phase 4 engine placeholder).
     */
    public function submit(Request $request, \App\Services\QuoteWorkflowService $workflowService)
    {
        $quote = $request->cotacao;

        // Verify that the quote is in a state that allows submission
        if (!in_array($quote->status, ['EM_CRIACAO', 'DEVOLVIDA'])) {
            return response()->json([
                'error' => 'Invalid status',
                'message' => "Cannot submit a quotation in status {$quote->status}."
            ], 422);
        }

        $result = $workflowService->evaluateAndRoute($quote);

        if (!$result['success']) {
            return response()->json([
                'error' => $result['error'],
                'message' => $result['message']
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'status' => $result['status']
        ]);
    }

    public function generatePdf(Request $request, \App\Services\PdfService $pdfService)
    {
        $quote = $request->cotacao;

        // Check if the quotation has been approved or is ready for PDF generation
        $allowedStatuses = ['PDF_GERADO', 'AGUARDANDO_PEDIDO', 'FINALIZADA_COM_PEDIDO', 'FATURADA'];
        if (!in_array($quote->status, $allowedStatuses)) {
            return response()->json([
                'error' => 'PDF not ready',
                'message' => "PDF can only be generated for quotations in status PDF_GERADO or finalized. Current status: {$quote->status}."
            ], 422);
        }

        $pdf = $pdfService->generateQuotePdf($quote);

        return $pdf->stream("cotacao_{$quote->numero}.pdf");
    }

    /**
     * Helper to recalculate quote subtotals and discounts.
     */
    private function recalculateQuoteTotals(Cotacao $quote)
    {
        $subtotal = 0; // sum of original suggested totals
        $total = 0;    // sum of proposed totals

        // Refresh items from DB to get the most updated list
        $quote->refresh();

        foreach ($quote->itens as $item) {
            if ($item->status_item === 'recusado') {
                continue;
            }
            $subtotal += $item->qtd * max((float)$item->preco_unit_sugerido, (float)$item->preco_unit_proposto);
            $total += (float)$item->subtotal;
        }

        $desconto = $subtotal - $total;

        $quote->update([
            'subtotal' => $subtotal,
            'desconto' => $desconto,
            'total' => $total,
        ]);
    }

    /**
     * Release quote for faturamento (registered by representative).
     */
    public function releaseForBilling(Request $request, $token)
    {
        $quote = $request->cotacao;

        if ($quote->status === 'FATURADA') {
            return response()->json(['error' => 'Conflict', 'message' => 'Esta cotação já foi faturada.'], 422);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'numero_pedido_externo' => 'required|string|max:100',
            'valor_pedido' => 'required|numeric|min:0.01',
            'tipo_faturamento' => 'required|in:total,parcial',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation error', 'messages' => $validator->errors()], 422);
        }

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($request, $quote) {
                // Register PedidoExterno
                $pedido = \App\Models\PedidoExterno::updateOrCreate(
                    ['cotacao_id' => $quote->id],
                    [
                        'numero_pedido_externo' => $request->input('numero_pedido_externo'),
                        'valor_pedido' => $request->input('valor_pedido'),
                        'status_conferencia' => 'pendente'
                    ]
                );

                // Update quote status to FINALIZADA_COM_PEDIDO
                $quote->update(['status' => 'FINALIZADA_COM_PEDIDO']);

                // Log audit history
                \App\Models\CotacaoHistorico::create([
                    'cotacao_id' => $quote->id,
                    'evento' => 'PEDIDO_EXTERNO_REGISTRADO',
                    'usuario_id' => $quote->representante_id,
                    'papel' => 'representante',
                    'condicao' => sprintf(
                        'Pedido externo Nº %s registrado pelo representante no valor de R$ %s (%s). Status alterado para FINALIZADA_COM_PEDIDO.',
                        $pedido->numero_pedido_externo,
                        number_format($pedido->valor_pedido, 2, ',', '.'),
                        $request->input('tipo_faturamento') === 'parcial' ? 'Faturamento Parcial' : 'Faturamento Total'
                    )
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Cotação liberada para faturamento com sucesso.',
                'status' => 'FINALIZADA_COM_PEDIDO'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Database error',
                'message' => 'Falha ao liberar para faturamento. ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * List all active products.
     */
    public function listProducts()
    {
        $products = Produto::where(function($q) {
            $q->where('ativo', true)
              ->orWhere('ativo', 1)
              ->orWhereNull('ativo');
        })->orderBy('descricao')->get();

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }
}
