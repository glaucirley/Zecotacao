<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Parceiro;
use App\Models\Produto;
use App\Models\Cotacao;
use App\Models\CotacaoItem;
use App\Models\CotacaoHistorico;
use App\Models\ParametroSistema;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class IntegrationController extends Controller
{
    public function store(Request $request)
    {
        $rawInput = $request->all();

        // Pre-process numeric fields to handle PT-BR commas and 3-decimal strings from n8n / Sankhya
        $rawInput['subtotal'] = array_key_exists('subtotal', $rawInput) ? $this->sanitizeFloat($rawInput['subtotal']) : 0.00;
        $rawInput['desconto'] = array_key_exists('desconto', $rawInput) ? $this->sanitizeFloat($rawInput['desconto']) : 0.00;
        $rawInput['total']    = array_key_exists('total', $rawInput)    ? $this->sanitizeFloat($rawInput['total'])    : 0.00;

        if (!isset($rawInput['itens']) || !is_array($rawInput['itens'])) {
            $rawInput['itens'] = [];
        }

        // Merge any root-level unfound items arrays sent by n8n / AI into $rawInput['itens']
        $unfoundRootKeys = ['itens_nao_encontrados', 'produtos_nao_encontrados', 'nao_encontrados', 'itens_pendentes'];
        foreach ($unfoundRootKeys as $rootKey) {
            if (!empty($rawInput[$rootKey]) && is_array($rawInput[$rootKey])) {
                foreach ($rawInput[$rootKey] as $rawUnfound) {
                    if (is_string($rawUnfound) && trim($rawUnfound) !== '') {
                        $rawInput['itens'][] = [
                            'descricao' => trim($rawUnfound),
                            'descricao_original' => trim($rawUnfound),
                            'qtd' => 1,
                            'unidade' => 'UN',
                            'nao_encontrado' => true,
                            'observacao_ia' => 'Item solicitado pelo cliente não encontrado automaticamente pela IA no catálogo.',
                        ];
                    } elseif (is_array($rawUnfound)) {
                        $descOrig = $rawUnfound['descricao_original']
                            ?? $rawUnfound['descricao']
                            ?? $rawUnfound['produto']
                            ?? $rawUnfound['termo_buscado']
                            ?? $rawUnfound['termo']
                            ?? $rawUnfound['nome']
                            ?? $rawUnfound['item']
                            ?? 'Item solicitado pelo cliente';
                        $qtdUnfound = $rawUnfound['qtd'] ?? $rawUnfound['quantidade'] ?? 1;
                        $obsIa = $rawUnfound['observacao_ia']
                            ?? $rawUnfound['observacao']
                            ?? $rawUnfound['motivo']
                            ?? 'Item solicitado pelo cliente não encontrado automaticamente pela IA no catálogo.';

                        $rawInput['itens'][] = array_merge($rawUnfound, [
                            'descricao' => $rawUnfound['descricao'] ?? $descOrig,
                            'descricao_original' => $descOrig,
                            'qtd' => max(1, (int)round($this->sanitizeFloat($qtdUnfound))),
                            'unidade' => $rawUnfound['unidade'] ?? 'UN',
                            'nao_encontrado' => true,
                            'observacao_ia' => $obsIa,
                        ]);
                    }
                }
            }
        }

        if (isset($rawInput['itens']) && is_array($rawInput['itens'])) {
            foreach ($rawInput['itens'] as $idx => $item) {
                if (!is_array($item)) {
                    continue;
                }

                // Fallback description and quantity fields if AI used alternative keys
                if (empty($item['descricao'])) {
                    $item['descricao'] = $item['descricao_original']
                        ?? $item['produto']
                        ?? $item['nome']
                        ?? $item['termo_buscado']
                        ?? $item['item']
                        ?? 'Item solicitado pelo cliente';
                    $rawInput['itens'][$idx]['descricao'] = $item['descricao'];
                }
                if (!isset($item['qtd']) && isset($item['quantidade'])) {
                    $item['qtd'] = $item['quantidade'];
                }

                $rawCode = trim((string)($item['codigo_sankhya'] ?? ''));
                $isCodeMissing = ($rawCode === '' || $rawCode === '0' || strtolower($rawCode) === 'null' || strtolower($rawCode) === 'n/a' || strtolower($rawCode) === 'nao_encontrado' || str_starts_with($rawCode, 'SEM_CODIGO'));
                $isNaoEncontrado = !empty($item['nao_encontrado'])
                    || !empty($item['item_nao_encontrado'])
                    || !empty($item['produto_nao_encontrado'])
                    || !empty($item['nao_localizado'])
                    || $isCodeMissing;

                if ($isNaoEncontrado) {
                    $rawInput['itens'][$idx]['nao_encontrado'] = true;
                    if ($isCodeMissing) {
                        $rawInput['itens'][$idx]['codigo_sankhya'] = 'SEM_CODIGO_' . substr(md5(($item['descricao_original'] ?? $item['descricao']) ?? Str::random(8)), 0, 8);
                    }
                    if (empty($rawInput['itens'][$idx]['unidade'])) {
                        $rawInput['itens'][$idx]['unidade'] = 'UN';
                    }
                    if (!isset($rawInput['itens'][$idx]['preco_unit_sugerido'])) {
                        $rawInput['itens'][$idx]['preco_unit_sugerido'] = 0.00;
                    }
                    if (!isset($rawInput['itens'][$idx]['preco_minimo'])) {
                        $rawInput['itens'][$idx]['preco_minimo'] = 0.00;
                    }
                    if (!isset($rawInput['itens'][$idx]['preco_unit_proposto'])) {
                        $rawInput['itens'][$idx]['preco_unit_proposto'] = 0.00;
                    }
                    if (!isset($rawInput['itens'][$idx]['subtotal'])) {
                        $rawInput['itens'][$idx]['subtotal'] = 0.00;
                    }
                    if (empty($rawInput['itens'][$idx]['descricao_original'])) {
                        $rawInput['itens'][$idx]['descricao_original'] = $item['descricao'] ?? 'Item solicitado pelo cliente';
                    }
                    if (empty($rawInput['itens'][$idx]['observacao_ia'])) {
                        $rawInput['itens'][$idx]['observacao_ia'] = $item['observacao'] ?? $item['motivo'] ?? 'Item não identificado automaticamente no catálogo pela IA';
                    }
                }

                $rawInput['itens'][$idx]['qtd'] = isset($item['qtd']) ? max(1, (int)round($this->sanitizeFloat($item['qtd']))) : 1;
                if (array_key_exists('preco_unit_sugerido', $item)) $rawInput['itens'][$idx]['preco_unit_sugerido'] = $this->sanitizeFloat($item['preco_unit_sugerido']);
                if (array_key_exists('preco_minimo', $item))        $rawInput['itens'][$idx]['preco_minimo']        = $this->sanitizeFloat($item['preco_minimo']);
                if (array_key_exists('preco_unit_proposto', $item)) $rawInput['itens'][$idx]['preco_unit_proposto'] = $this->sanitizeFloat($item['preco_unit_proposto']);
                if (array_key_exists('ajuste_percentual', $item))   $rawInput['itens'][$idx]['ajuste_percentual']   = $this->sanitizeFloat($item['ajuste_percentual']);
                if (array_key_exists('subtotal', $item))            $rawInput['itens'][$idx]['subtotal']            = $this->sanitizeFloat($item['subtotal']);
                if (array_key_exists('margem_calculada', $item))    $rawInput['itens'][$idx]['margem_calculada']    = $this->sanitizeFloat($item['margem_calculada']);
                if (array_key_exists('custo', $item))               $rawInput['itens'][$idx]['custo']               = $this->sanitizeFloat($item['custo']);
                if (array_key_exists('imposto', $item))             $rawInput['itens'][$idx]['imposto']             = $this->sanitizeFloat($item['imposto']);
            }
        }

        // 1. Validate incoming payload
        $validator = Validator::make($rawInput, [
            'numero' => 'required|string',
            'data_emissao' => 'nullable|string',
            'validade_horas' => 'nullable|integer',
            'forma_pagamento' => 'nullable|string',
            'prazo_entrega' => 'nullable|string',
            'frete_tipo' => 'nullable|in:CIF,FOB',
            'transportadora' => 'nullable|string',
            'observacao_cliente' => 'nullable|string',
            'observacao_interna' => 'nullable|string',
            'subtotal' => 'nullable|numeric',
            'desconto' => 'nullable|numeric',
            'total' => 'nullable|numeric',
            
            // Representative (Vendedor)
            'representante' => 'required|array',
            'representante.codigo_sankhya' => 'required|string',
            'representante.nome' => 'required|string',
            'representante.email' => 'required|email',
            'representante.telefone' => 'nullable|string',

            // Partner (Cliente)
            'parceiro' => 'required|array',
            'parceiro.codigo_sankhya' => 'required|string',
            'parceiro.razao_social' => 'required|string',
            'parceiro.nome_fantasia' => 'nullable|string',
            'parceiro.cnpj' => 'nullable|string',
            'parceiro.telefone' => 'nullable|string',
            'parceiro.email' => 'nullable|string',
            'parceiro.endereco' => 'nullable|string',
            'parceiro.cidade' => 'nullable|string',
            'parceiro.uf' => 'nullable|string|max:2',
            'parceiro.cep' => 'nullable|string',

            // Items
            'itens' => 'required|array|min:1',
            'itens.*.codigo_sankhya' => 'nullable|string',
            'itens.*.descricao' => 'required|string',
            'itens.*.unidade' => 'nullable|string',
            'itens.*.qtd' => 'required|numeric|min:1',
            'itens.*.preco_unit_sugerido' => 'nullable|numeric',
            'itens.*.preco_minimo' => 'nullable|numeric',
            'itens.*.preco_unit_proposto' => 'nullable|numeric',
            'itens.*.ajuste_percentual' => 'nullable|numeric',
            'itens.*.subtotal' => 'nullable|numeric',
            'itens.*.margem_calculada' => 'nullable|numeric',
            'itens.*.custo' => 'nullable|numeric',
            'itens.*.imposto' => 'nullable|numeric',
            'itens.*.campanha_id' => 'nullable|string',
            'itens.*.mostrar_selo_campanha' => 'nullable|boolean',
            'itens.*.ordem_exibicao' => 'nullable|integer',
            'itens.*.nao_encontrado' => 'nullable|boolean',
            'itens.*.descricao_original' => 'nullable|string|max:500',
            'itens.*.observacao_ia' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation error',
                'messages' => $validator->errors()
            ], 422);
        }

        $data = $rawInput;

        // Check if quote exists and lock status checks
        $existingQuote = Cotacao::where('numero', $data['numero'])->first();
        if ($existingQuote && in_array($existingQuote->status, ['FINALIZADA_COM_PEDIDO', 'FATURADA', 'PERDIDA', 'EXPIRADA'])) {
            return response()->json([
                'error' => 'Locked quote',
                'message' => "The quote {$data['numero']} is in status {$existingQuote->status} and cannot be modified."
            ], 422);
        }

        try {
            $quote = DB::transaction(function () use ($data, $existingQuote) {
                
                // 2. Resolve Representative (Vendedor) with payload auto-provisioning fallback
                $repData = $data['representante'];
                $representative = User::where('codigo_sankhya', $repData['codigo_sankhya'])->first();
                if (!$representative) {
                    try {
                        $sankhyaDb = resolve(\App\Services\SankhyaDatabaseService::class);
                        $representative = $sankhyaDb->syncRepresentativeByCode($repData['codigo_sankhya']);
                    } catch (\Throwable $e) {
                        $representative = null;
                    }

                    if (!$representative) {
                        $representative = User::create([
                            'nome'           => $repData['nome'] ?? ('Vendedor ' . $repData['codigo_sankhya']),
                            'papel'          => 'representante',
                            'email'          => $repData['email'] ?? ('vendedor_' . $repData['codigo_sankhya'] . '@zecotacao.com.br'),
                            'telefone'       => $repData['telefone'] ?? null,
                            'codigo_sankhya' => (string)$repData['codigo_sankhya'],
                            'senha_hash'     => bcrypt(Str::random(16)),
                            'ativo'          => true,
                        ]);
                    }
                } else {
                    $representative->update([
                        'nome'  => $repData['nome'] ?? $representative->nome,
                        'email' => $repData['email'] ?? $representative->email,
                    ]);
                }

                // 3. Resolve Partner (Cliente) with payload auto-provisioning fallback
                $partnerData = $data['parceiro'];
                $partner = Parceiro::where('codigo_sankhya', $partnerData['codigo_sankhya'])->first();
                if (!$partner) {
                    try {
                        $sankhyaDb = resolve(\App\Services\SankhyaDatabaseService::class);
                        $partner = $sankhyaDb->syncPartnerByCode($partnerData['codigo_sankhya']);
                    } catch (\Throwable $e) {
                        $partner = null;
                    }

                    if (!$partner) {
                        $cleanCnpj = preg_replace('/[^0-9]/', '', $partnerData['cnpj'] ?? '');
                        $cleanCep = preg_replace('/[^0-9]/', '', $partnerData['cep'] ?? '');

                        $partner = Parceiro::create([
                            'codigo_sankhya'    => (string)$partnerData['codigo_sankhya'],
                            'razao_social'      => $partnerData['razao_social'] ?? ('Cliente ' . $partnerData['codigo_sankhya']),
                            'nome_fantasia'     => $partnerData['nome_fantasia'] ?? $partnerData['razao_social'] ?? null,
                            'cnpj'              => $cleanCnpj ?: null,
                            'telefone'          => $partnerData['telefone'] ?? null,
                            'email'             => $partnerData['email'] ?? null,
                            'endereco'          => $partnerData['endereco'] ?? null,
                            'cidade'            => $partnerData['cidade'] ?? null,
                            'uf'                => $partnerData['uf'] ?? null,
                            'cep'               => $cleanCep ?: null,
                            'vendedor_1_codigo' => (string)$repData['codigo_sankhya'],
                            'ativo'             => true,
                        ]);
                    }
                }

                // 4. Resolve Products: Bulk pre-fetch local products to prevent slow remote queries and 504 Gateway Time-out
                $itemCodes = array_values(array_unique(array_filter(array_map(function ($i) {
                    return isset($i['codigo_sankhya']) ? (string)$i['codigo_sankhya'] : null;
                }, $data['itens']))));

                $localProducts = Produto::whereIn('codigo_sankhya', $itemCodes)
                    ->get()
                    ->keyBy('codigo_sankhya');

                $productIdsMap = [];
                $resolvedProductsCache = [];
                $registeredMissingCodes = [];
                $unfoundItemCodes = [];

                foreach ($data['itens'] as $item) {
                    $itemCode = (string)($item['codigo_sankhya'] ?? '');
                    if (empty($itemCode) || str_starts_with($itemCode, 'SEM_CODIGO')) {
                        $itemCode = 'SEM_CODIGO_' . substr(md5(($item['descricao_original'] ?? $item['descricao']) ?? Str::random(8)), 0, 8);
                        $unfoundItemCodes[$itemCode] = true;
                    }

                    if (!empty($item['nao_encontrado'])) {
                        $unfoundItemCodes[$itemCode] = true;
                    }

                    if (isset($resolvedProductsCache[$itemCode])) {
                        $product = $resolvedProductsCache[$itemCode];
                    } else {
                        $product = null;

                        // A. Check local DB first if not explicitly marked as not found
                        if (empty($unfoundItemCodes[$itemCode])) {
                            $product = $localProducts[$itemCode] ?? null;

                            // Check if this local product was previously created as a fallback for a missing code
                            if ($product && !$product->ativo && \App\Models\ProdutoNaoEncontrado::where('codigo_sankhya', $itemCode)->exists()) {
                                $unfoundItemCodes[$itemCode] = true;
                            }

                            // B. If not in local DB, attempt Sankhya sync
                            if (!$product) {
                                try {
                                    $sankhyaDb = resolve(\App\Services\SankhyaDatabaseService::class);
                                    $product = $sankhyaDb->syncProductByCode($itemCode);
                                } catch (\Throwable $e) {
                                    $product = null;
                                }
                            }
                        }

                        // C. If product code does NOT exist anywhere (or is marked unfound), register in ProdutoNaoEncontrado and create local fallback
                        if (!$product || !empty($unfoundItemCodes[$itemCode])) {
                            $unfoundItemCodes[$itemCode] = true;
                            if (!isset($registeredMissingCodes[$itemCode])) {
                                \App\Models\ProdutoNaoEncontrado::registrar(
                                    $itemCode,
                                    $item['descricao_original'] ?? ($item['descricao'] ?? ('Produto ' . $itemCode)),
                                    ($partner ? $partner->razao_social : 'Cliente Desconhecido')
                                );
                                $registeredMissingCodes[$itemCode] = true;
                            }

                            if (!$product) {
                                $productData = [
                                    'codigo_sankhya'  => $itemCode,
                                    'descricao'       => $item['descricao'] ?? ('Produto ' . $itemCode),
                                    'unidade'         => $item['unidade'] ?? 'UN',
                                    'ativo'           => false,
                                ];
                                if (\Illuminate\Support\Facades\Schema::hasColumn('produtos', 'custo_variavel')) {
                                    $productData['custo_variavel'] = (float)($item['custo'] ?? 0);
                                }
                                $product = Produto::firstOrCreate(['codigo_sankhya' => $itemCode], $productData);
                            }
                        }

                        $resolvedProductsCache[$itemCode] = $product;
                    }

                    $productIdsMap[$itemCode] = $product->id;
                }

                // 5. Create or Update Quotation
                $validityHours = $data['validade_horas'] ?? (int) ParametroSistema::getVal('VALIDADE_PADRAO_HORAS', 24);
                $emissionDate = !empty($data['data_emissao']) ? \Carbon\Carbon::parse($data['data_emissao']) : now();
                $validityDate = $emissionDate->copy()->addHours($validityHours);

                if ($existingQuote) {
                    // Update existing
                    $existingQuote->update([
                        'parceiro_id' => $partner->id,
                        'representante_id' => $representative->id,
                        'status' => 'EM_CRIACAO', // goes back to EM_CRIACAO upon update via webhook
                        'data_emissao' => $emissionDate,
                        'validade_horas' => $validityHours,
                        'data_validade' => $validityDate,
                        'subtotal' => $data['subtotal'] ?? 0.00,
                        'desconto' => $data['desconto'] ?? 0.00,
                        'total' => $data['total'] ?? 0.00,
                        'forma_pagamento' => $data['forma_pagamento'] ?? $existingQuote->forma_pagamento,
                        'prazo_entrega' => $data['prazo_entrega'] ?? $existingQuote->prazo_entrega,
                        'frete_tipo' => $data['frete_tipo'] ?? $existingQuote->frete_tipo,
                        'transportadora' => $data['transportadora'] ?? $existingQuote->transportadora,
                        'observacao_cliente' => $data['observacao_cliente'] ?? $existingQuote->observacao_cliente,
                        'observacao_interna' => $data['observacao_interna'] ?? $existingQuote->observacao_interna,
                    ]);
                    $quote = $existingQuote;
                    
                    // Clear old items to recreate them
                    $quote->itens()->delete();
                } else {
                    // Create new
                    $quoteToken = Str::random(40);
                    $quoteNum = (!empty($data['numero']) && preg_match('/^COT-\d{4}-\d{6}$/', $data['numero']))
                        ? $data['numero']
                        : Cotacao::generateNextNumero();

                    $quote = Cotacao::create([
                        'numero' => $quoteNum,
                        'parceiro_id' => $partner->id,
                        'representante_id' => $representative->id,
                        'status' => 'EM_CRIACAO',
                        'origem' => $data['origem'] ?? 'whatsapp_ia',
                        'data_emissao' => $emissionDate,
                        'validade_horas' => $validityHours,
                        'data_validade' => $validityDate,
                        'subtotal' => $data['subtotal'] ?? 0.00,
                        'desconto' => $data['desconto'] ?? 0.00,
                        'total' => $data['total'] ?? 0.00,
                        'forma_pagamento' => $data['forma_pagamento'] ?? null,
                        'prazo_entrega' => $data['prazo_entrega'] ?? null,
                        'frete_tipo' => $data['frete_tipo'] ?? 'CIF',
                        'transportadora' => $data['transportadora'] ?? null,
                        'observacao_cliente' => $data['observacao_cliente'] ?? null,
                        'observacao_interna' => $data['observacao_interna'] ?? null,
                        'token_representante' => $quoteToken,
                        'token_acesso_em' => null,
                    ]);
                }

                // 6. Recreate Quote Items
                $unfoundCount = 0;
                foreach ($data['itens'] as $item) {
                    $itemCode = (string)($item['codigo_sankhya'] ?? '');
                    if (empty($itemCode) || str_starts_with($itemCode, 'SEM_CODIGO') || !isset($productIdsMap[$itemCode])) {
                        $itemCode = 'SEM_CODIGO_' . substr(md5(($item['descricao_original'] ?? $item['descricao']) ?? Str::random(8)), 0, 8);
                    }

                    $isUnfound = !empty($item['nao_encontrado']) || isset($unfoundItemCodes[$itemCode]) || str_starts_with($itemCode, 'SEM_CODIGO');
                    if ($isUnfound) {
                        $unfoundCount++;
                    }

                    $qtd = (float)($item['qtd'] ?? 0);
                    $precoSugerido = (float)($item['preco_unit_sugerido'] ?? 0.00);
                    $precoMinimo = (float)($item['preco_minimo'] ?? 0.00);
                    $precoProposto = (float)($item['preco_unit_proposto'] ?? 0.00);
                    $itemSubtotal = (isset($item['subtotal']) && (float)$item['subtotal'] > 0)
                        ? (float)$item['subtotal']
                        : round($qtd * ($precoProposto > 0 ? $precoProposto : $precoSugerido), 2);

                    CotacaoItem::create([
                        'cotacao_id' => $quote->id,
                        'produto_id' => $productIdsMap[$itemCode],
                        'qtd' => $qtd,
                        'preco_unit_sugerido' => $precoSugerido,
                        'preco_minimo' => $precoMinimo,
                        'preco_unit_proposto' => $precoProposto,
                        'ajuste_percentual' => $item['ajuste_percentual'] ?? 0.00,
                        'subtotal' => $itemSubtotal,
                        'status_item' => 'pendente',
                        'margem_calculada' => $item['margem_calculada'] ?? null,
                        'custo' => $item['custo'] ?? null,
                        'imposto' => $item['imposto'] ?? null,
                        'campanha_id' => $item['campanha_id'] ?? null,
                        'mostrar_selo_campanha' => $item['mostrar_selo_campanha'] ?? false,
                        'ordem_exibicao' => $item['ordem_exibicao'] ?? 0,
                        'nao_encontrado' => $isUnfound,
                        'descricao_original' => $item['descricao_original'] ?? ($isUnfound ? ($item['descricao'] ?? null) : null),
                        'observacao_ia' => $item['observacao_ia'] ?? ($isUnfound ? 'Item não identificado automaticamente no catálogo Sankhya' : null),
                    ]);
                }

                // Recalculate quotation totals based on inserted items
                $quote->recalcularTotais();

                // 7. Write Audit Trail (Historico)
                $event = $existingQuote ? 'ATUALIZADA_VIA_INTEGRACAO' : 'CRIADA_VIA_INTEGRACAO';
                $cond = $existingQuote ? 'Cotação atualizada via N8N payload' : 'Cotação importada com sucesso via N8N';
                if ($unfoundCount > 0) {
                    $cond .= " ({$unfoundCount} item(ns) não encontrado(s) pela IA aguardando inclusão manual)";
                }
                
                CotacaoHistorico::create([
                    'cotacao_id' => $quote->id,
                    'evento' => $event,
                    'usuario_id' => null,
                    'papel' => 'sistema',
                    'condicao' => $cond,
                ]);

                // Create Notification for Representative
                $notifMsg = "A cotação {$quote->numero} para " . ($partner->razao_social ?? 'Cliente') . " foi criada via WhatsApp e está pronta para edição.";
                if ($unfoundCount > 0) {
                    $notifMsg .= " Atenção: {$unfoundCount} item(ns) solicitado(s) pelo cliente não foram encontrados pela IA e aguardam sua inclusão manual.";
                }
                \App\Models\Notificacao::create([
                    'usuario_id' => $representative->id,
                    'titulo' => $unfoundCount > 0 ? '⚠️ Nova Cotação (Itens não encontrados pela IA)' : 'Nova Cotação Recebida!',
                    'mensagem' => $notifMsg,
                    'link' => url("/cotacoes/id/{$quote->id}"),
                    'lida' => false,
                ]);

                return $quote;
            });

            return response()->json([
                'success' => true,
                'message' => 'Cotação importada com sucesso.',
                'data' => [
                    'id' => $quote->id,
                    'numero' => $quote->numero,
                    'status' => $quote->status,
                    'token' => $quote->token_representante,
                    'url_acesso' => url("/cotacoes/token/{$quote->token_representante}")
                ]
            ], 201);

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Falha ao importar cotação via API/Webhook: " . $e->getMessage(), [
                'exception' => $e,
                'request' => $request->all()
            ]);

            $isNotFound = strpos($e->getMessage(), 'não encontrado') !== false;
            $code = $isNotFound ? 422 : 500;
            $errorCode = $isNotFound ? 'IMPORT_VALIDATION_ERROR' : 'IMPORT_DATABASE_ERROR';

            $message = ($code === 422 || config('app.debug'))
                ? 'Falha ao importar cotação: ' . $e->getMessage()
                : 'Ocorreu um erro interno ao processar a cotação. Os detalhes foram registrados nos logs do servidor.';

            return response()->json([
                'error' => $code === 422 ? 'Erro de validação' : 'Erro interno do servidor',
                'code' => $errorCode,
                'message' => $message
            ], $code);
        }
    }

    /**
     * Convert strings with PT-BR comma or 3-decimal period formatting (e.g. "12.000" or "12,000" or "1.234,56") to clean float values.
     */
    private function sanitizeFloat($value, int $decimals = 2): float
    {
        if (is_null($value) || $value === '') {
            return 0.0;
        }
        if (is_int($value) || is_float($value)) {
            return round((float)$value, $decimals);
        }

        $str = trim((string)$value);

        // If string has both thousand '.' and decimal ',' (e.g. "1.234,56" or "1.234,500")
        if (strpos($str, '.') !== false && strpos($str, ',') !== false) {
            $str = str_replace('.', '', $str);
            $str = str_replace(',', '.', $str);
        } elseif (strpos($str, ',') !== false) {
            // Only comma present (e.g. "12,000" or "12,50")
            $str = str_replace(',', '.', $str);
        }

        return round((float)$str, $decimals);
    }

    /**
     * Bulk import/sync partners from N8N.
     */
    public function importPartnersBatch(Request $request, \App\Services\PartnerImportService $importService)
    {
        $raw = $request->input('clientes') ?? $request->input('parceiros') ?? $request->all();

        if (!is_array($raw)) {
            return response()->json([
                'success' => false,
                'message' => 'O payload deve conter uma lista de clientes/parceiros em JSON.'
            ], 422);
        }

        // If payload is wrapped in key or is a direct array
        $rows = isset($raw[0]) ? $raw : (array_values($raw)[0] ?? []);
        if (!is_array($rows) || empty($rows)) {
            $rows = [$raw];
        }

        $result = $importService->importFromRows($rows);

        return response()->json([
            'success'     => true,
            'message'     => "Lote processado com sucesso: {$result['criados']} novos clientes, {$result['atualizados']} atualizados.",
            'total'       => $result['total'],
            'criados'     => $result['criados'],
            'atualizados' => $result['atualizados'],
            'erros'       => $result['erros'],
        ]);
    }
}

