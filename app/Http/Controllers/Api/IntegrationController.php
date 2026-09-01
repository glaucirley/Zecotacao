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
        // 1. Validate incoming payload
        $validator = Validator::make($request->all(), [
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

        $data = $request->all();

        // Check if quote exists and lock status checks
        $existingQuote = Cotacao::where('numero', $data['numero'])->first();
        if ($existingQuote && in_array($existingQuote->status, ['FINALIZADA_COM_PEDIDO', 'FATURADA', 'PERDIDA'])) {
            return response()->json([
                'error' => 'Locked quote',
                'message' => "The quote {$data['numero']} is in status {$existingQuote->status} and cannot be modified."
            ], 422);
        }

        try {
            $quote = DB::transaction(function () use ($data, $existingQuote) {
                
                // 2. Resolve Representative (Vendedor)
                $repData = $data['representante'];
                $representative = User::where('codigo_sankhya', $repData['codigo_sankhya'])->first();
                if (!$representative) {
                    $sankhyaDb = resolve(\App\Services\SankhyaDatabaseService::class);
                    $representative = $sankhyaDb->syncRepresentativeByCode($repData['codigo_sankhya']);
                    if (!$representative) {
                        throw new \Exception("Representante com código Sankhya {$repData['codigo_sankhya']} não encontrado.");
                    }
                } else {
                    $representative->update([
                        'nome' => $repData['nome'],
                        'email' => $repData['email'],
                    ]);
                }

                // 3. Resolve Partner (Cliente)
                $partnerData = $data['parceiro'];
                $partner = Parceiro::where('codigo_sankhya', $partnerData['codigo_sankhya'])->first();
                if (!$partner) {
                    $sankhyaDb = resolve(\App\Services\SankhyaDatabaseService::class);
                    $partner = $sankhyaDb->syncPartnerByCode($partnerData['codigo_sankhya']);
                    if (!$partner) {
                        throw new \Exception("Parceiro com código Sankhya {$partnerData['codigo_sankhya']} não encontrado.");
                    }
                }

                // 4. Resolve Products and map to local IDs
                $productIdsMap = [];
                foreach ($data['itens'] as $item) {
                    $product = Produto::where('codigo_sankhya', $item['codigo_sankhya'])->first();
                    if (!$product) {
                        $sankhyaDb = resolve(\App\Services\SankhyaDatabaseService::class);
                        $product = $sankhyaDb->syncProductByCode($item['codigo_sankhya']);
                        if (!$product) {
                            \App\Models\ProdutoNaoEncontrado::registrar(
                                $item['codigo_sankhya'],
                                $item['descricao'],
                                ($partner ? $partner->razao_social : 'Cliente Desconhecido')
                            );
                            throw new \Exception("Produto com código Sankhya {$item['codigo_sankhya']} não encontrado.");
                        }
                    }
                    $productIdsMap[$item['codigo_sankhya']] = $product->id;
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
                    $quote = Cotacao::create([
                        'numero' => $data['numero'],
                        'parceiro_id' => $partner->id,
                        'representante_id' => $representative->id,
                        'status' => 'EM_CRIACAO',
                        'origem' => 'whatsapp_ia',
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
                    'link' => url("/cotacoes/token/{$quote->token_representante}"),
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
}
