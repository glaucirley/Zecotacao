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
        if (array_key_exists('subtotal', $rawInput)) $rawInput['subtotal'] = $this->sanitizeFloat($rawInput['subtotal']);
        if (array_key_exists('desconto', $rawInput)) $rawInput['desconto'] = $this->sanitizeFloat($rawInput['desconto']);
        if (array_key_exists('total', $rawInput))    $rawInput['total']    = $this->sanitizeFloat($rawInput['total']);

        if (isset($rawInput['itens']) && is_array($rawInput['itens'])) {
            foreach ($rawInput['itens'] as $idx => $item) {
                if (array_key_exists('qtd', $item))                  $rawInput['itens'][$idx]['qtd']                  = (int)$this->sanitizeFloat($item['qtd']);
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
            'subtotal' => 'required|numeric',
            'desconto' => 'required|numeric',
            'total' => 'required|numeric',
            
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
            'itens.*.codigo_sankhya' => 'required|string',
            'itens.*.descricao' => 'required|string',
            'itens.*.unidade' => 'required|string',
            'itens.*.qtd' => 'required|integer|min:1',
            'itens.*.preco_unit_sugerido' => 'required|numeric',
            'itens.*.preco_minimo' => 'required|numeric',
            'itens.*.preco_unit_proposto' => 'required|numeric',
            'itens.*.ajuste_percentual' => 'nullable|numeric',
            'itens.*.subtotal' => 'required|numeric',
            'itens.*.margem_calculada' => 'nullable|numeric',
            'itens.*.custo' => 'nullable|numeric',
            'itens.*.imposto' => 'nullable|numeric',
            'itens.*.campanha_id' => 'nullable|string',
            'itens.*.mostrar_selo_campanha' => 'nullable|boolean',
            'itens.*.ordem_exibicao' => 'nullable|integer',
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

                foreach ($data['itens'] as $item) {
                    $itemCode = (string)$item['codigo_sankhya'];

                    if (isset($resolvedProductsCache[$itemCode])) {
                        $product = $resolvedProductsCache[$itemCode];
                    } else {
                        // A. Check local DB first for instant response
                        $product = $localProducts[$itemCode] ?? null;

                        // B. If not in local DB, attempt Sankhya sync
                        if (!$product) {
                            try {
                                $sankhyaDb = resolve(\App\Services\SankhyaDatabaseService::class);
                                $product = $sankhyaDb->syncProductByCode($itemCode);
                            } catch (\Throwable $e) {
                                $product = null;
                            }
                        }

                        // C. If product code does NOT exist anywhere, register in ProdutoNaoEncontrado and create local fallback
                        if (!$product) {
                            if (!isset($registeredMissingCodes[$itemCode])) {
                                \App\Models\ProdutoNaoEncontrado::registrar(
                                    $itemCode,
                                    $item['descricao'] ?? ('Produto ' . $itemCode),
                                    ($partner ? $partner->razao_social : 'Cliente Desconhecido')
                                );
                                $registeredMissingCodes[$itemCode] = true;
                            }

                            $productData = [
                                'codigo_sankhya'  => $itemCode,
                                'descricao'       => $item['descricao'] ?? ('Produto ' . $itemCode),
                                'unidade'         => $item['unidade'] ?? 'UN',
                                'ativo'           => true,
                            ];
                            if (\Illuminate\Support\Facades\Schema::hasColumn('produtos', 'custo_variavel')) {
                                $productData['custo_variavel'] = (float)($item['custo'] ?? 0);
                            }
                            $product = Produto::create($productData);
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
                        'subtotal' => $data['subtotal'],
                        'desconto' => $data['desconto'],
                        'total' => $data['total'],
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
                        'subtotal' => $data['subtotal'],
                        'desconto' => $data['desconto'],
                        'total' => $data['total'],
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
                foreach ($data['itens'] as $item) {
                    CotacaoItem::create([
                        'cotacao_id' => $quote->id,
                        'produto_id' => $productIdsMap[$item['codigo_sankhya']],
                        'qtd' => $item['qtd'],
                        'preco_unit_sugerido' => $item['preco_unit_sugerido'],
                        'preco_minimo' => $item['preco_minimo'],
                        'preco_unit_proposto' => $item['preco_unit_proposto'],
                        'ajuste_percentual' => $item['ajuste_percentual'] ?? 0.00,
                        'subtotal' => $item['subtotal'],
                        'status_item' => 'pendente',
                        'margem_calculada' => $item['margem_calculada'] ?? null,
                        'custo' => $item['custo'] ?? null,
                        'imposto' => $item['imposto'] ?? null,
                        'campanha_id' => $item['campanha_id'] ?? null,
                        'mostrar_selo_campanha' => $item['mostrar_selo_campanha'] ?? false,
                        'ordem_exibicao' => $item['ordem_exibicao'] ?? 0,
                    ]);
                }

                // 7. Write Audit Trail (Historico)
                $event = $existingQuote ? 'ATUALIZADA_VIA_INTEGRACAO' : 'CRIADA_VIA_INTEGRACAO';
                $cond = $existingQuote ? 'Cotação atualizada via N8N payload' : 'Cotação importada com sucesso via N8N';
                
                CotacaoHistorico::create([
                    'cotacao_id' => $quote->id,
                    'evento' => $event,
                    'usuario_id' => null,
                    'papel' => 'sistema',
                    'condicao' => $cond,
                ]);

                // Create Notification for Representative
                \App\Models\Notificacao::create([
                    'usuario_id' => $representative->id,
                    'titulo' => 'Nova Cotação Recebida!',
                    'mensagem' => "A cotação {$quote->numero} para " . ($partner->razao_social ?? 'Cliente') . " foi criada via WhatsApp e está pronta para edição.",
                    'link' => url("/cotacoes/id/{$quote->id}"),
                    'lida' => false,
                ]);

                return $quote;
            });

            return response()->json([
                'success' => true,
                'message' => 'Quote imported successfully.',
                'data' => [
                    'id' => $quote->id,
                    'numero' => $quote->numero,
                    'status' => $quote->status,
                    'token' => $quote->token_representante,
                    'url_acesso' => url("/cotacoes/token/{$quote->token_representante}")
                ]
            ], 201);

        } catch (\Exception $e) {
            $code = (strpos($e->getMessage(), 'não encontrado') !== false) ? 422 : 500;
            return response()->json([
                'error' => 'Import error',
                'message' => 'Failed to import quote. ' . $e->getMessage()
            ], $code);
        }
    }

    /**
     * Convert strings with PT-BR comma or 3-decimal period formatting (e.g. "12.000" or "12,000" or "1.234,56") to clean float values.
     */
    private function sanitizeFloat($value)
    {
        if (is_null($value) || $value === '') {
            return 0.0;
        }
        if (is_int($value) || is_float($value)) {
            return (float)$value;
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

        return (float)$str;
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

