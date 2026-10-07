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
            return response()->json(['error' => 'Não autenticado.'], 401);
        }

        // Trigger automatic expiration check for overdue quotes
        \App\Services\QuoteWorkflowService::checkAndExpireQuotes();

        // Lightweight eager loading for listing performance
        $query = Cotacao::with([
            'parceiro:id,codigo_sankhya,razao_social,nome_fantasia,cnpj',
            'representante:id,nome,email,equipe_id',
            'representante.equipe:id,nome'
        ])->orderBy('created_at', 'desc');

        // Access Control
        if ($user->isGestor()) {
            $teamIds = $user->equipesGerenciadas->pluck('id');
            $query->whereHas('representante', function ($q) use ($teamIds) {
                $q->whereIn('equipe_id', $teamIds);
            });
        } elseif ($user->isRepresentante()) {
            $query->where('representante_id', $user->id);
        } elseif (!$user->isDiretor() && !$user->isAdministrador() && !$user->isFaturamento()) {
            return response()->json(['error' => 'Acesso não autorizado.'], 403);
        }

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('origem')) {
            $query->where('origem', $request->input('origem'));
        }

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $terms = array_filter(explode(' ', $search));
            if (!empty($terms)) {
                $query->where(function ($q) use ($terms) {
                    foreach ($terms as $term) {
                        $q->where(function ($subQ) use ($term) {
                            $subQ->where('numero', 'like', "%{$term}%")
                                 ->orWhereHas('parceiro', function ($p) use ($term) {
                                     $p->where('razao_social', 'like', "%{$term}%")
                                       ->orWhere('nome_fantasia', 'like', "%{$term}%")
                                       ->orWhere('cnpj', 'like', "%{$term}%");
                                 })
                                 ->orWhereHas('representante', function ($r) use ($term) {
                                     $r->where('nome', 'like', "%{$term}%");
                                 });
                        });
                    }
                });
            }
        }

        if ($request->filled('data_inicio')) {
            $query->whereDate('created_at', '>=', $request->input('data_inicio'));
        }
        if ($request->filled('data_fim')) {
            $query->whereDate('created_at', '<=', $request->input('data_fim'));
        }

        // Server-side Pagination & Light Payload
        if ($request->has('page') || $request->has('per_page')) {
            $perPage = max(1, (int)$request->input('per_page', 15));
            $paginated = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $paginated->items(),
                'meta' => [
                    'current_page' => $paginated->currentPage(),
                    'last_page' => $paginated->lastPage(),
                    'per_page' => $paginated->perPage(),
                    'total' => $paginated->total(),
                ]
            ]);
        }

        $quotes = $query->get();

        return response()->json([
            'success' => true,
            'data' => $quotes,
            'meta' => [
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => $quotes->count(),
                'total' => $quotes->count(),
            ]
        ]);
    }

    /**
     * Get metadata for manual creation (partners, representatives, products).
     */
    public function getMeta(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['error' => 'Não autenticado.'], 401);
        }

        $partners = \App\Models\Parceiro::where('ativo', true)->orderBy('razao_social')->take(50)->get();
        $products = \App\Models\Produto::where('ativo', true)->orderBy('descricao')->take(50)->get();

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
            return response()->json(['error' => 'Não autenticado.'], 401);
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
            return response()->json(['error' => 'Erro de validação', 'messages' => $validator->errors()], 422);
        }

        // Access check: representatives can only create quotes for themselves
        $repId = $request->input('representante_id');
        if ($user->isRepresentante() && $repId != $user->id) {
            return response()->json(['error' => 'Acesso não autorizado. Representantes só podem criar cotações para si mesmos.'], 403);
        }

        try {
            return DB::transaction(function () use ($request, $repId, $user) {
                // Generate sequential number: COT-YYYY-XXXXXX
                $num = Cotacao::generateNextNumero();
                
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
                    'message' => 'Cotação criada manualmente com sucesso.',
                    'data' => $quote->fresh(['itens.produto', 'parceiro', 'representante'])
                ], 201);
            });

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro no banco de dados',
                'message' => 'Falha ao criar cotação: ' . $e->getMessage()
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
            return response()->json(['error' => 'Não autenticado.'], 401);
        }

        $quote = Cotacao::findOrFail($id);

        // Access control
        if ($user->isAdministrador()) {
            // Admins can delete anything
        } elseif ($user->isRepresentante() && $quote->representante_id == $user->id) {
            // Representative can delete only if status is EM_CRIACAO
            if ($quote->status !== 'EM_CRIACAO') {
                return response()->json(['error' => 'Acesso não autorizado.', 'message' => 'Você só pode excluir cotações em rascunho.'], 403);
            }
        } elseif ($user->isGestor()) {
            // Gestor can delete their team's quotes only if in draft
            $teamIds = $user->equipesGerenciadas->pluck('id');
            if (!in_array($quote->representante->equipe_id, $teamIds->toArray()) || $quote->status !== 'EM_CRIACAO') {
                return response()->json(['error' => 'Acesso não autorizado.', 'message' => 'Você só pode excluir cotações em rascunho da sua equipe.'], 403);
            }
        } else {
            return response()->json(['error' => 'Acesso não autorizado.'], 403);
        }

        try {
            $quote->delete();
            return response()->json([
                'success' => true,
                'message' => 'Cotação excluída com sucesso.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro no banco de dados',
                'message' => 'Falha ao excluir cotação: ' . $e->getMessage()
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

        if (!in_array($quote->status, ['EM_CRIACAO', 'DEVOLVIDA'])) {
            return response()->json([
                'error' => 'Status bloqueado',
                'message' => "Esta cotação está com status {$quote->status} e não permite alterações."
            ], 422);
        }

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
            return response()->json(['error' => 'Erro de validação', 'messages' => $validator->errors()], 422);
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

                            $precoProposto = round((float)$itemData['preco_unit_proposto'], 2);
                            $precoSugerido = round((float)$item->preco_unit_sugerido, 2);
                            $ajuste = $precoSugerido > 0 ? round((($precoProposto - $precoSugerido) / $precoSugerido) * 100, 2) : 0.00;
                            $qtd = (int)$itemData['qtd'];

                            $item->update([
                                'qtd' => $qtd,
                                'preco_unit_proposto' => $precoProposto,
                                'ajuste_percentual' => $ajuste,
                                'subtotal' => round($qtd * $precoProposto, 2),
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
                'message' => 'Cotação atualizada com sucesso.',
                'data' => $quote->fresh(['itens.produto'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro no banco de dados',
                'message' => 'Falha ao atualizar cotação: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add a new item to the quotation.
     */
    public function addItem(Request $request)
    {
        $quote = $request->cotacao;

        if (!in_array($quote->status, ['EM_CRIACAO', 'DEVOLVIDA'])) {
            return response()->json([
                'error' => 'Status bloqueado',
                'message' => "Esta cotação está com status {$quote->status} e não permite inclusão de itens."
            ], 422);
        }

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
            return response()->json(['error' => 'Erro de validação', 'messages' => $validator->errors()], 422);
        }

        try {
            DB::transaction(function () use ($request, $quote) {
                $precoProposto = round((float)$request->input('preco_unit_proposto'), 2);
                $precoSugerido = round((float)$request->input('preco_unit_sugerido'), 2);
                $precoMinimo = round((float)$request->input('preco_minimo'), 2);
                $isInconsistente = ($precoSugerido > 0 && $precoMinimo > $precoSugerido);
                $ajuste = $precoSugerido > 0 ? round((($precoProposto - $precoSugerido) / $precoSugerido) * 100, 2) : 0.00;
                $qtd = (int)$request->input('qtd');

                // Determine display order (add to end)
                $maxOrder = CotacaoItem::where('cotacao_id', $quote->id)->max('ordem_exibicao') ?? 0;

                $item = CotacaoItem::create([
                    'cotacao_id' => $quote->id,
                    'produto_id' => $request->input('produto_id'),
                    'qtd' => $qtd,
                    'preco_unit_sugerido' => $precoSugerido,
                    'preco_minimo' => $precoMinimo,
                    'preco_unit_proposto' => $precoProposto,
                    'ajuste_percentual' => $ajuste,
                    'subtotal' => round($qtd * $precoProposto, 2),
                    'status_item' => 'pendente',
                    'inconsistente' => $isInconsistente,
                    'margem_calculada' => $request->filled('margem_calculada') ? round((float)$request->input('margem_calculada'), 2) : null,
                    'custo' => $request->filled('custo') ? round((float)$request->input('custo'), 2) : null,
                    'imposto' => $request->filled('imposto') ? round((float)$request->input('imposto'), 2) : null,
                    'campanha_id' => $request->input('campanha_id'),
                    'mostrar_selo_campanha' => $request->input('mostrar_selo_campanha', false),
                    'ordem_exibicao' => $maxOrder + 1,
                ]);

                if ($isInconsistente) {
                    \App\Services\QuoteWorkflowService::notifyAdminsAboutInconsistentItems($quote, [$item]);
                }

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
                'message' => 'Item adicionado com sucesso.',
                'data' => $quote->fresh(['itens.produto'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro no banco de dados',
                'message' => 'Falha ao adicionar item: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove an item from the quotation.
     */
    public function removeItem(Request $request, $token, $item_id)
    {
        $quote = $request->cotacao;

        if (!in_array($quote->status, ['EM_CRIACAO', 'DEVOLVIDA'])) {
            return response()->json([
                'error' => 'Status bloqueado',
                'message' => "Esta cotação está com status {$quote->status} e não permite exclusão de itens."
            ], 422);
        }

        $item = CotacaoItem::where('cotacao_id', $quote->id)->where('id', $item_id)->first();

        if (!$item) {
            return response()->json(['error' => 'Item não encontrado.'], 404);
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
                'message' => 'Item removido com sucesso.',
                'data' => $quote->fresh(['itens.produto'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro no banco de dados',
                'message' => 'Falha ao remover item: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add a justification (and upload files/audio) for a discount.
     */
    public function addJustification(Request $request)
    {
        $quote = $request->cotacao;

        if (!in_array($quote->status, ['EM_CRIACAO', 'DEVOLVIDA'])) {
            return response()->json([
                'error' => 'Status bloqueado',
                'message' => "Esta cotação está com status {$quote->status} e não permite envio de justificativa."
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'texto' => 'nullable|string',
            'audio' => 'nullable|file|mimes:audio/mpeg,mp3,wav,ogg,m4a,application/octet-stream|max:10240', // 10MB limit
            'cotacao_item_id' => 'nullable|exists:cotacao_itens,id',
            'anexos' => 'nullable|array',
            'anexos.*' => [
                'file',
                'max:10240', // 10MB limit per file
                'mimes:pdf,jpeg,png,jpg,webp,gif,doc,docx,xls,xlsx,csv,ppt,pptx,odt,ods,odp',
            ],
        ], [
            'anexos.*.file' => 'O arquivo enviado não é válido.',
            'anexos.*.max' => 'Cada anexo deve ter no máximo 10MB.',
            'anexos.*.mimes' => 'Formato não permitido. São aceitos apenas arquivos PDF, imagens (JPG, PNG, WEBP) e documentos do Office (Word, Excel, PowerPoint).',
            'audio.file' => 'O arquivo de áudio enviado não é válido.',
            'audio.max' => 'O arquivo de áudio deve ter no máximo 10MB.',
            'audio.mimes' => 'Formato de áudio não suportado.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Erro de validação',
                'message' => $validator->errors()->first(),
                'messages' => $validator->errors()
            ], 422);
        }

        // Additional deep inspection on real file content, extensions, and real MIME types
        if ($request->hasFile('anexos')) {
            $allowedExtensions = ['pdf', 'png', 'jpg', 'jpeg', 'webp', 'gif', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'ppt', 'pptx', 'odt', 'ods', 'odp'];
            $blockedExtensions = ['exe', 'zip', 'rar', '7z', 'tar', 'gz', 'bat', 'cmd', 'sh', 'com', 'scr', 'msi', 'vbs', 'js', 'bin', 'phtml', 'php', 'apk', 'jar'];

            $allowedMimePrefixesOrTypes = [
                'application/pdf',
                'image/',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'text/csv',
                'text/plain',
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'application/vnd.oasis.opendocument.',
            ];

            $blockedMimePatterns = [
                'application/x-dosexec',
                'application/x-executable',
                'application/x-msdownload',
                'application/x-msdos-program',
                'application/zip',
                'application/x-zip-compressed',
                'application/x-rar-compressed',
                'application/x-7z-compressed',
                'application/x-tar',
                'application/gzip',
            ];

            foreach ($request->file('anexos') as $file) {
                $origName = $file->getClientOriginalName();
                $ext = strtolower($file->getClientOriginalExtension());

                // 1. Extension inspection
                if (in_array($ext, $blockedExtensions) || !in_array($ext, $allowedExtensions)) {
                    return response()->json([
                        'error' => 'Extensão de arquivo inválida',
                        'message' => "O arquivo '{$origName}' possui extensão não permitida (.{$ext}). Apenas PDF, imagens e documentos do Office são aceitos.",
                        'messages' => ['anexos' => ["O arquivo '{$origName}' possui extensão não permitida (.{$ext})."]]
                    ], 422);
                }

                // 2. File size inspection (10MB)
                if ($file->getSize() > 10 * 1024 * 1024) {
                    return response()->json([
                        'error' => 'Arquivo muito grande',
                        'message' => "O arquivo '{$origName}' ultrapassa o limite máximo de 10 MB.",
                        'messages' => ['anexos' => ["O arquivo '{$origName}' ultrapassa o limite máximo de 10 MB."]]
                    ], 422);
                }

                // 3. Real content MIME inspection
                $realMime = $file->getMimeType();
                if (!$realMime && function_exists('finfo_open')) {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $realMime = finfo_file($finfo, $file->getRealPath());
                    finfo_close($finfo);
                }
                $realMime = strtolower($realMime ?: '');

                // Check blocked MIME patterns
                foreach ($blockedMimePatterns as $blockedMime) {
                    if (str_starts_with($realMime, $blockedMime) || Str::contains($realMime, $blockedMime)) {
                        return response()->json([
                            'error' => 'Tipo de arquivo bloqueado',
                            'message' => "O arquivo '{$origName}' foi recusado por conter formato ou conteúdo não permitido ({$realMime}).",
                            'messages' => ['anexos' => ["O arquivo '{$origName}' possui conteúdo não permitido ({$realMime})."]]
                        ], 422);
                    }
                }

                // Check allowed MIME patterns
                $mimeAllowed = false;
                foreach ($allowedMimePrefixesOrTypes as $allowedType) {
                    if (str_starts_with($realMime, $allowedType)) {
                        $mimeAllowed = true;
                        break;
                    }
                }

                if (!$mimeAllowed) {
                    return response()->json([
                        'error' => 'Tipo MIME não suportado',
                        'message' => "O conteúdo real do arquivo '{$origName}' ({$realMime}) não corresponde aos formatos aceitos (PDF, imagens ou Office).",
                        'messages' => ['anexos' => ["O conteúdo real do arquivo '{$origName}' ({$realMime}) não é permitido."]]
                    ], 422);
                }
            }
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
                        $realMime = strtolower($file->getMimeType() ?: '');
                        $ext = strtolower($file->getClientOriginalExtension());

                        $type = 'documento';
                        if (Str::startsWith($realMime, 'image/')) {
                            $type = 'imagem';
                        } elseif ($realMime === 'application/pdf' || $ext === 'pdf') {
                            $type = 'pdf';
                        } elseif (Str::contains($realMime, 'spreadsheet') || Str::contains($realMime, 'excel') || in_array($ext, ['xls', 'xlsx', 'csv'])) {
                            $type = 'planilha';
                        }

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
                'message' => 'Justificativa salva com sucesso.',
                'data' => $justification->load('anexos')
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro no banco de dados',
                'message' => 'Falha ao salvar justificativa: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark the quotation as lost (perdida).
     */
    public function markAsLost(Request $request)
    {
        $quote = $request->cotacao;

        if (in_array($quote->status, ['AGUARDANDO_GESTOR', 'COM_DIRETOR', 'FINALIZADA_COM_PEDIDO', 'FATURADA', 'PERDIDA'])) {
            return response()->json([
                'error' => 'Status bloqueado',
                'message' => "Cotação no status {$quote->status} não pode ser marcada como perdida."
            ], 422);
        }

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
                'message' => 'Cotação marcada como perdida com sucesso.',
                'status' => 'PERDIDA'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro no banco de dados',
                'message' => 'Falha ao marcar como perdida: ' . $e->getMessage()
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
                'error' => 'Status inválido',
                'message' => "Não é permitido enviar uma cotação com status {$quote->status}."
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

    public function generatePdf(Request $request, \App\Services\PdfService $pdfService, $tokenOrId = null)
    {
        $quote = $request->cotacao;

        if (!$quote && $tokenOrId) {
            $quote = Cotacao::where('token_representante', $tokenOrId)->orWhere('id', $tokenOrId)->first();
        }

        if (!$quote && $request->route('id')) {
            $quote = Cotacao::find($request->route('id'));
        }

        if (!$quote) {
            return response()->json([
                'error' => 'Não encontrado',
                'message' => 'Cotação não encontrada.'
            ], 404);
        }

        // Sequence requirement: PDF can only be generated after quotation approval!
        $allowedStatuses = ['APROVADA', 'PDF_GERADO', 'AGUARDANDO_PEDIDO', 'FINALIZADA_COM_PEDIDO', 'FATURADA'];
        if (!in_array($quote->status, $allowedStatuses)) {
            if (in_array($quote->status, ['PERDIDA', 'EXPIRADA'])) {
                return response()->json([
                    'error' => 'Status bloqueado',
                    'message' => 'Não é permitido gerar PDF para uma cotação perdida ou expirada.'
                ], 422);
            }
            return response()->json([
                'error' => 'Status bloqueado',
                'message' => "O PDF só pode ser gerado após a aprovação da cotação (status atual: {$quote->status})."
            ], 422);
        }

        try {
            $pdf = $pdfService->generateQuotePdf($quote);

            // If quote was in APROVADA (Pendente PDF), advance status to PDF_GERADO
            if ($quote->status === 'APROVADA') {
                $quote->update(['status' => 'PDF_GERADO']);

                CotacaoHistorico::create([
                    'cotacao_id' => $quote->id,
                    'evento' => 'PDF_GERADO',
                    'usuario_id' => auth()->id() ?? $quote->representante_id,
                    'papel' => auth()->user()?->papel ?? 'representante',
                    'condicao' => 'PDF da cotação gerado com sucesso com itens aprovados. Cotação pronta para liberação de faturamento.',
                ]);
            }

            return $pdf->stream("cotacao_{$quote->numero}.pdf");
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro na geração do PDF',
                'message' => $e->getMessage()
            ], 422);
        }
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

        $subtotal = round($subtotal, 2);
        $total = round($total, 2);
        $desconto = round($subtotal - $total, 2);

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
            return response()->json(['error' => 'Conflito', 'message' => 'Esta cotação já foi faturada.'], 422);
        }

        if ($quote->status === 'FINALIZADA_COM_PEDIDO') {
            return response()->json(['error' => 'Conflito', 'message' => 'Esta cotação já possui pedido externo registrado.'], 422);
        }

        if ($quote->status === 'APROVADA') {
            return response()->json([
                'error' => 'PDF obrigatório',
                'message' => 'É necessário gerar o PDF da cotação antes de liberá-la para faturamento.'
            ], 422);
        }

        if ($quote->status !== 'PDF_GERADO') {
            return response()->json([
                'error' => 'Status inválido',
                'message' => "Esta cotação está com status {$quote->status} e não pode ser liberada para faturamento."
            ], 422);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'numero_pedido_externo' => 'required|string|max:100',
            'valor_pedido' => 'required|numeric|min:0.01',
            'tipo_faturamento' => 'required|in:total,parcial',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Erro de validação', 'messages' => $validator->errors()], 422);
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
                'error' => 'Erro no banco de dados',
                'message' => 'Falha ao liberar para faturamento. ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * List active products for quotation addition (limited to 50 by default, with live search & price lookup).
     */
    public function listProducts(Request $request)
    {
        $query = Produto::where(function($q) {
            $q->where('ativo', true)
              ->orWhere('ativo', 1)
              ->orWhereNull('ativo');
        });

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $terms = array_filter(explode(' ', $search));
            if (!empty($terms)) {
                $query->where(function ($q) use ($terms) {
                    foreach ($terms as $term) {
                        $q->where(function ($subQ) use ($term) {
                            $subQ->where('codigo_sankhya', 'like', "%{$term}%")
                                 ->orWhere('descricao', 'like', "%{$term}%")
                                 ->orWhere('marca', 'like', "%{$term}%")
                                 ->orWhere('ncm', 'like', "%{$term}%");
                        });
                    }
                });
            }
        }

        $limit = $request->filled('limit') ? min((int)$request->input('limit'), 100) : 50;
        $products = $query->orderBy('descricao')->take($limit)->get();

        // Attach price info from TabelaPrecoItem if present, or provide standard fallback
        foreach ($products as $p) {
            $priceItem = \App\Models\TabelaPrecoItem::where('produto_id', $p->id)->first()
                      ?? \App\Models\TabelaPrecoItem::where('codigo_sankhya_produto', $p->codigo_sankhya)->first();
            
            if ($priceItem && (float)$priceItem->preco_venda > 0) {
                $p->preco_sugerido = round((float)$priceItem->preco_venda, 2);
                $p->preco_minimo = (float)$priceItem->preco_minimo > 0 ? round((float)$priceItem->preco_minimo, 2) : round((float)$priceItem->preco_venda * 0.90, 2);
                $p->custo = round((float)$priceItem->custo_variavel, 2);
            } else {
                $p->preco_sugerido = 100.00;
                $p->preco_minimo = 90.00;
                $p->custo = 60.00;
            }
            $p->inconsistente = ($p->preco_sugerido > 0 && $p->preco_minimo > $p->preco_sugerido);
            $p->preco_minimo_efetivo = $p->inconsistente ? $p->preco_sugerido : $p->preco_minimo;
        }

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }
}
