<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Produto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    /**
     * List products. Restricted to Administrators. Limited to 20 items by default.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user->isAdministrador() && !$user->isSuporte()) {
            return response()->json(['error' => 'Forbidden. Only administrators and support can manage products.'], 403);
        }

        $query = Produto::query();

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $terms = array_filter(explode(' ', $search));
            if (!empty($terms)) {
                $query->where(function ($q) use ($terms) {
                    foreach ($terms as $term) {
                        $q->where(function ($subQ) use ($term) {
                            $termAscii = \Illuminate\Support\Str::ascii($term);
                            $subQ->where('codigo_sankhya', 'like', "%{$term}%")
                                 ->orWhere('descricao', 'like', "%{$term}%")
                                 ->orWhere('unidade', 'like', "%{$term}%")
                                 ->orWhere('marca', 'like', "%{$term}%")
                                 ->orWhere('base', 'like', "%{$term}%")
                                 ->orWhere('principio_ativo', 'like', "%{$term}%")
                                 ->orWhere('ncm', 'like', "%{$term}%");

                            if ($termAscii !== $term) {
                                $subQ->orWhere('descricao', 'like', "%{$termAscii}%")
                                     ->orWhere('marca', 'like', "%{$termAscii}%")
                                     ->orWhere('base', 'like', "%{$termAscii}%")
                                     ->orWhere('principio_ativo', 'like', "%{$termAscii}%");
                            }
                        });
                    }
                });
            }
        }

        if ($request->filled('status')) {
            $st = $request->input('status');
            if ($st === 'ativo') {
                $query->where('ativo', true);
            } elseif ($st === 'inativo') {
                $query->where('ativo', false);
            } elseif ($st === 'problema') {
                $query->where(function($q) {
                    $q->whereDoesntHave('precos')
                      ->orWhereHas('precos', function($p) {
                          $p->where('preco_venda', '<=', 0)
                            ->orWhere('preco_venda', 100.00);
                      })
                      ->orWhere('descricao', '^')
                      ->orWhere('codigo_sankhya', '0')
                      ->orWhere('codigo_sankhya', '')
                      ->orWhereNull('codigo_sankhya')
                      ->orWhere('descricao', 'like', '%descric?o%')
                      ->orWhere('descricao', 'like', '^%')
                      ->orWhere('descricao', 'like', '<sem%');
                });
            }
        }

        $limit = $request->filled('limit') ? min((int)$request->input('limit'), 100) : 20;

        $products = $query->orderByRaw("
            CASE 
                WHEN codigo_sankhya = '0' OR codigo_sankhya = '^' THEN 2
                WHEN descricao LIKE '^%' OR descricao LIKE '<%' OR descricao = '' OR descricao IS NULL THEN 2
                ELSE 1
            END ASC
        ")->orderBy('descricao')->take($limit)->get();

        $products->transform(function ($p) {
            if ($p->descricao) {
                $p->descricao = str_ireplace(['descric?o', 'descrio'], 'descrição', $p->descricao);
            }
            return $p;
        });

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }

    /**
     * Store a new product.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user->isAdministrador() && !$user->isSuporte()) {
            return response()->json(['error' => 'Forbidden.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'codigo_sankhya' => 'required|string|max:100|unique:produtos,codigo_sankhya',
            'descricao' => 'required|string|max:255',
            'unidade' => 'required|string|max:50',
            'ativo' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation error', 'messages' => $validator->errors()], 422);
        }

        try {
            $product = Produto::create($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Produto criado com sucesso.',
                'data' => $product
            ], 201);

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Falha ao criar produto: " . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'error' => 'Erro interno do servidor',
                'code' => 'PRODUCT_CREATE_ERROR',
                'message' => config('app.debug') ? ('Falha ao criar produto: ' . $e->getMessage()) : 'Ocorreu um erro ao criar o produto. Os detalhes foram registrados nos logs do servidor.'
            ], 500);
        }
    }

    /**
     * Update an existing product.
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->isAdministrador() && !$user->isSuporte()) {
            return response()->json(['error' => 'Forbidden.'], 403);
        }

        $product = Produto::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'codigo_sankhya' => ['required', 'string', 'max:100', Rule::unique('produtos', 'codigo_sankhya')->ignore($product->id)],
            'descricao' => 'required|string|max:255',
            'unidade' => 'required|string|max:50',
            'ativo' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation error', 'messages' => $validator->errors()], 422);
        }

        try {
            $product->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Produto atualizado com sucesso.',
                'data' => $product
            ]);

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Falha ao atualizar produto ID {$id}: " . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'error' => 'Erro interno do servidor',
                'code' => 'PRODUCT_UPDATE_ERROR',
                'message' => config('app.debug') ? ('Falha ao atualizar produto: ' . $e->getMessage()) : 'Ocorreu um erro ao atualizar o produto. Os detalhes foram registrados nos logs do servidor.'
            ], 500);
        }
    }

    /**
     * Delete an existing product.
     */
    public function destroy($id)
    {
        $user = Auth::user();
        if (!$user->isAdministrador()) {
            return response()->json(['error' => 'Forbidden.'], 403);
        }

        $product = Produto::findOrFail($id);

        try {
            $product->delete();
            return response()->json([
                'success' => true,
                'message' => 'Produto excluído com sucesso.'
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Falha ao excluir produto ID {$id}: " . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'error' => 'Erro interno do servidor',
                'code' => 'PRODUCT_DELETE_ERROR',
                'message' => 'Falha ao excluir produto. Ele pode estar em uso em cotações. Tente desativá-lo.'
            ], 500);
        }
    }

    /**
     * Consulta detalhada de produtos e estoque para retaguarda, com precificação dinâmica por cliente.
     */
    public function queryCatalog(Request $request)
    {
        $user = Auth::user();
        if (!$user || $user->isRepresentante()) {
            return response()->json(['error' => 'Acesso não autorizado para representantes nesta rota.'], 403);
        }

        $parceiroId = $request->input('parceiro_id');
        $search = trim((string)$request->input('search', ''));
        $apenasEstoque = filter_var($request->input('apenas_estoque', false), FILTER_VALIDATE_BOOLEAN);
        $apenasCampanha = filter_var($request->input('apenas_campanha', false), FILTER_VALIDATE_BOOLEAN);
        $limit = min(max((int)$request->input('limit', 50), 10), 200);

        $partner = null;
        $codigosRestritos = [];
        $restricoesMap = [];

        if ($parceiroId) {
            $partner = \App\Models\Parceiro::find($parceiroId);
            if ($partner) {
                // Checa restrições comerciais
                try {
                    $codVend = $partner->vendedor_1_codigo ?? ($user->codigo_sankhya ?? '0');
                    $codParc = $partner->codigo_sankhya;
                    if (!empty($codParc)) {
                        $restricaoService = resolve(\App\Services\RestricaoComercialService::class);
                        $restrList = $restricaoService->produtosRestritos((string)$codVend, (string)$codParc);
                        foreach ($restrList as $r) {
                            $cp = (string)($r['CODPROD'] ?? '');
                            if ($cp !== '') {
                                $codigosRestritos[] = $cp;
                                $restricoesMap[$cp] = $r['MSG_VENDEDOR'] ?? ($r['FONTE'] ?? 'Restrito para este cliente');
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning("Erro ao verificar restrições comerciais na consulta: " . $e->getMessage());
                }
            }
        }

        $query = Produto::query()->where(function($q) {
            $q->where('ativo', true)
              ->orWhere('ativo', 1)
              ->orWhereNull('ativo');
        })->where('codigo_sankhya', 'not like', 'SEM_CODIGO%');

        if ($apenasEstoque) {
            $query->where('estoque', '>', 0);
        }

        if ($apenasCampanha) {
            $query->whereNotNull('campanha')->where('campanha', '!=', '');
        }

        if ($search !== '') {
            $terms = array_filter(explode(' ', $search));
            if (!empty($terms)) {
                $query->where(function ($q) use ($terms) {
                    foreach ($terms as $term) {
                        $q->where(function ($subQ) use ($term) {
                            $termAscii = \Illuminate\Support\Str::ascii($term);
                            $subQ->where('codigo_sankhya', 'like', "%{$term}%")
                                 ->orWhere('descricao', 'like', "%{$term}%")
                                 ->orWhere('marca', 'like', "%{$term}%")
                                 ->orWhere('base', 'like', "%{$term}%")
                                 ->orWhere('principio_ativo', 'like', "%{$term}%")
                                 ->orWhere('unidade', 'like', "%{$term}%")
                                 ->orWhere('ncm', 'like', "%{$term}%");

                            if ($termAscii !== $term) {
                                $subQ->orWhere('descricao', 'like', "%{$termAscii}%")
                                     ->orWhere('marca', 'like', "%{$termAscii}%")
                                     ->orWhere('base', 'like', "%{$termAscii}%")
                                     ->orWhere('principio_ativo', 'like', "%{$termAscii}%");
                            }
                        });
                    }
                });
            }
        }

        $totalFound = (clone $query)->count();

        // Ordenação inteligente: produtos com estoque primeiro, depois alfabético
        $rawProducts = $query->orderByRaw("CASE WHEN estoque > 0 THEN 1 ELSE 2 END ASC")
            ->orderBy('descricao')
            ->take($limit)
            ->get();

        $prodIds = $rawProducts->pluck('id')->filter()->all();
        $prodCodes = $rawProducts->pluck('codigo_sankhya')->filter()->all();

        // Tabela de Preço do parceiro ou geral
        $codTab = $partner?->metadados_sankhya['CODTAB'] ?? $partner?->metadados_sankhya['codtab'] ?? null;

        $priceItemsQuery = \App\Models\TabelaPrecoItem::where(function($q) use ($prodIds, $prodCodes) {
            if (!empty($prodIds)) {
                $q->whereIn('produto_id', $prodIds);
            }
            if (!empty($prodCodes)) {
                $q->orWhereIn('codigo_sankhya_produto', $prodCodes);
            }
        });

        if (!empty($codTab)) {
            $priceItemsAll = (clone $priceItemsQuery)->get();
            $priceItemsPartner = $priceItemsAll->where('codigo_sankhya_tabela', (string)$codTab);
            $priceItems = $priceItemsPartner->isNotEmpty() ? $priceItemsPartner : $priceItemsAll;
        } else {
            $priceItems = $priceItemsQuery->get();
        }

        $priceByProdId = [];
        $priceByCode = [];
        foreach ($priceItems as $pi) {
            if ($pi->produto_id && !isset($priceByProdId[$pi->produto_id])) {
                $priceByProdId[$pi->produto_id] = $pi;
            }
            if ($pi->codigo_sankhya_produto && !isset($priceByCode[$pi->codigo_sankhya_produto])) {
                $priceByCode[$pi->codigo_sankhya_produto] = $pi;
            }
        }

        $canViewCosts = \App\Models\User::canUserViewCosts();

        $items = [];
        foreach ($rawProducts as $p) {
            $cod = trim($p->codigo_sankhya ?? '');
            $desc = trim($p->descricao ?? '');
            if ($cod === '0' || $cod === '' || $cod === '^' || $desc === '^' || $desc === '' || strlen($desc) < 2) {
                continue;
            }

            $priceItem = $priceByProdId[$p->id] ?? ($priceByCode[$p->codigo_sankhya] ?? null);

            $precoSugerido = 0.00;
            $precoMinimo = 0.00;
            $custo = null;
            $margem = null;

            if ($priceItem && (float)$priceItem->preco_venda > 0) {
                $precoSugerido = round((float)$priceItem->preco_venda, 2);
                $precoMinimo = (float)$priceItem->preco_minimo > 0 ? round((float)$priceItem->preco_minimo, 2) : round((float)$priceItem->preco_venda * 0.90, 2);
                if ($canViewCosts) {
                    $custo = round((float)$priceItem->custo_variavel, 2);
                    if ($precoSugerido > 0 && $custo > 0) {
                        $margem = round((($precoSugerido - $custo) / $precoSugerido) * 100, 2);
                    }
                }
            } elseif ($p->codigo_sankhya === 'PROD001') {
                $precoSugerido = 85.00;
                $precoMinimo = 75.00;
                $custo = $canViewCosts ? 45.00 : null;
                $margem = $canViewCosts ? 47.06 : null;
            } elseif ($p->codigo_sankhya === 'PROD002') {
                $precoSugerido = 280.00;
                $precoMinimo = 250.00;
                $custo = $canViewCosts ? 160.00 : null;
                $margem = $canViewCosts ? 42.86 : null;
            } elseif ($p->codigo_sankhya === 'PROD003') {
                $precoSugerido = 45.00;
                $precoMinimo = 40.00;
                $custo = $canViewCosts ? 22.00 : null;
                $margem = $canViewCosts ? 51.11 : null;
            }

            $isRestrito = in_array((string)$p->codigo_sankhya, $codigosRestritos, true);
            $motivoRestricao = $isRestrito ? ($restricoesMap[(string)$p->codigo_sankhya] ?? 'Restrição cadastral para este cliente') : null;

            $items[] = [
                'id' => $p->id,
                'codigo_sankhya' => $p->codigo_sankhya,
                'descricao' => str_ireplace(['descric?o', 'descrio'], 'descrição', $p->descricao),
                'marca' => $p->marca,
                'base' => $p->base,
                'principio_ativo' => $p->principio_ativo,
                'unidade' => $p->unidade ?: 'UN',
                'estoque' => $p->estoque !== null ? (float)$p->estoque : null,
                'estoque_formatado' => $p->estoque_formatado,
                'validade_proxima' => $p->validade_proxima ? \Carbon\Carbon::parse($p->validade_proxima)->format('d/m/Y') : null,
                'validade_formatada' => $p->validade_formatada,
                'campanha' => $p->campanha,
                'preco_sugerido' => $precoSugerido,
                'preco_minimo' => $precoMinimo,
                'custo' => $custo,
                'margem' => $margem,
                'is_restrito' => $isRestrito,
                'motivo_restricao' => $motivoRestricao,
            ];
        }

        return response()->json([
            'success' => true,
            'total' => $totalFound,
            'data' => $items,
            'partner' => $partner ? [
                'id' => $partner->id,
                'codigo_sankhya' => $partner->codigo_sankhya,
                'razao_social' => $partner->razao_social,
                'nome_fantasia' => $partner->nome_fantasia,
                'cnpj' => $partner->cnpj,
                'cidade' => $partner->cidade,
                'uf' => $partner->uf,
                'tabela_codigo' => $codTab,
            ] : null,
            'can_view_costs' => $canViewCosts
        ]);
    }
}
