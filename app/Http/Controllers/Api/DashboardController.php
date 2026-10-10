<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cotacao;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Get aggregated stats for the dashboard based on user permissions.
     */
    public function getStats(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        // 1. Date filters
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        if (!$startDate) {
            $startDate = now()->subDays(30)->toDateString();
        }
        if (!$endDate) {
            $endDate = now()->toDateString();
        }

        $parsedStart = Carbon::parse($startDate)->startOfDay();
        $parsedEnd = Carbon::parse($endDate)->endOfDay();

        // 2. Base Query and Scoping
        $baseQuery = Cotacao::whereBetween('created_at', [$parsedStart, $parsedEnd]);

        if ($user->isRepresentante()) {
            // Representative only sees their own quotes
            $baseQuery->where('representante_id', $user->id);
        } elseif ($user->isGestor()) {
            // Gestor only sees their team's quotes
            $teamIds = $user->equipesGerenciadas->pluck('id');
            $baseQuery->whereHas('representante', function ($q) use ($teamIds) {
                $q->whereIn('equipe_id', $teamIds);
            });
        }

        $data = [
            'summary' => null,
            'status_distribution' => null,
            'top_sellers' => null,
            'top_partners' => null,
            'timeline' => null,
            'permissions' => [
                'ver_kpis' => $user->hasDashPermission('ver_kpis'),
                'ver_evolucao_temporal' => $user->hasDashPermission('ver_evolucao_temporal'),
                'ver_status_dist' => $user->hasDashPermission('ver_status_dist'),
                'ver_ranking_vendedores' => $user->hasDashPermission('ver_ranking_vendedores'),
                'ver_top_clientes' => $user->hasDashPermission('ver_top_clientes'),
            ]
        ];

        // 3. Populate metrics based on granular permissions
        
        // A. General KPIs Card
        if ($user->hasDashPermission('ver_kpis')) {
            $totalQuotes = (clone $baseQuery)->count();
            
            $totalBilled = (clone $baseQuery)
                ->where('status', 'FATURADA')
                ->sum('total');

            $sumSubtotal = (clone $baseQuery)->sum('subtotal');
            $sumDesconto = (clone $baseQuery)->sum('desconto');

            $descontoMedio = 0.0;
            if ($sumSubtotal > 0 && $sumDesconto > 0) {
                $descontoMedio = round(($sumDesconto / $sumSubtotal) * 100, 2);
            } else {
                $totalPrecoSugerido = (clone $baseQuery)->sum('subtotal');
                $totalPrecoProposto = (clone $baseQuery)->sum('total');
                if ($totalPrecoSugerido > 0 && $totalPrecoSugerido > $totalPrecoProposto) {
                    $descontoMedio = round((($totalPrecoSugerido - $totalPrecoProposto) / $totalPrecoSugerido) * 100, 2);
                }
            }

            $convertedCount = (clone $baseQuery)
                ->whereIn('status', ['FINALIZADA_COM_PEDIDO', 'FATURADA'])
                ->count();
            $conversaoRate = $totalQuotes > 0 ? ($convertedCount / $totalQuotes) * 100 : 0;

            // Previous period comparison
            $daysDiff = max(1, $parsedStart->diffInDays($parsedEnd));
            $prevStart = (clone $parsedStart)->subDays($daysDiff);
            $prevEnd = (clone $parsedStart)->subSecond();

            $prevQuery = Cotacao::whereBetween('created_at', [$prevStart, $prevEnd]);
            if ($user->isRepresentante()) {
                $prevQuery->where('representante_id', $user->id);
            } elseif ($user->isGestor()) {
                $teamIds = $user->equipesGerenciadas->pluck('id');
                $prevQuery->whereHas('representante', function ($q) use ($teamIds) {
                    $q->whereIn('equipe_id', $teamIds);
                });
            }

            $prevTotalQuotes = (clone $prevQuery)->count();
            $prevTotalBilled = (clone $prevQuery)->where('status', 'FATURADA')->sum('total');
            $prevConverted = (clone $prevQuery)->whereIn('status', ['FINALIZADA_COM_PEDIDO', 'FATURADA'])->count();
            $prevConversaoRate = $prevTotalQuotes > 0 ? ($prevConverted / $prevTotalQuotes) * 100 : 0;

            $diffQuotes = $prevTotalQuotes > 0 ? round((($totalQuotes - $prevTotalQuotes) / $prevTotalQuotes) * 100, 1) : 0;
            $diffBilled = $prevTotalBilled > 0 ? round((($totalBilled - $prevTotalBilled) / $prevTotalBilled) * 100, 1) : 0;
            $diffConv = round($conversaoRate - $prevConversaoRate, 1);

            $data['summary'] = [
                'total_quotes' => $totalQuotes,
                'total_billed' => (float)$totalBilled,
                'conversao_rate' => (float)$conversaoRate,
                'desconto_medio' => (float)$descontoMedio,
                'comparisons' => [
                    'quotes_diff' => $diffQuotes,
                    'billed_diff' => $diffBilled,
                    'conversao_diff' => $diffConv,
                ]
            ];
        }

        // B. Status Distribution
        if ($user->hasDashPermission('ver_status_dist')) {
            $statusCounts = (clone $baseQuery)
                ->select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->get()
                ->pluck('count', 'status')
                ->toArray();

            $allStatuses = ['EM_CRIACAO', 'DEVOLVIDA', 'AGUARDANDO_GESTOR', 'COM_DIRETOR', 'APROVADA', 'PDF_GERADO', 'FINALIZADA_COM_PEDIDO', 'FATURADA', 'PERDIDA', 'EXPIRADA'];
            $distribution = [];
            foreach ($allStatuses as $st) {
                $distribution[$st] = $statusCounts[$st] ?? 0;
            }
            $data['status_distribution'] = $distribution;
        }

        // C. Top Sellers
        if ($user->hasDashPermission('ver_ranking_vendedores')) {
            $topSellersQuery = (clone $baseQuery)
                ->select(
                    'representante_id',
                    DB::raw('count(*) as total_quotes'),
                    DB::raw('sum(total) as value_quotes'),
                    DB::raw('sum(case when status in (\'FINALIZADA_COM_PEDIDO\', \'FATURADA\') then total else 0 end) as value_billed'),
                    DB::raw('sum(case when status in (\'FINALIZADA_COM_PEDIDO\', \'FATURADA\') then 1 else 0 end) as count_billed')
                )
                ->groupBy('representante_id')
                ->with('representante.equipe')
                ->get();

            $topSellers = $topSellersQuery->map(function ($s) {
                return [
                    'name' => $s->representante->nome ?? 'N/A',
                    'team' => $s->representante->equipe->nome ?? 'Sem Equipe',
                    'total_quotes' => (int)$s->total_quotes,
                    'value_quotes' => (float)$s->value_quotes,
                    'value_billed' => (float)$s->value_billed,
                    'conversao' => $s->total_quotes > 0 ? (float)(($s->count_billed / $s->total_quotes) * 100) : 0.0
                ];
            })->sortByDesc('value_billed')->values()->take(5);

            $data['top_sellers'] = $topSellers;
        }

        // D. Top Partners (Clientes)
        if ($user->hasDashPermission('ver_top_clientes')) {
            $topPartnersQuery = (clone $baseQuery)
                ->select(
                    'parceiro_id',
                    DB::raw('count(*) as total_quotes'),
                    DB::raw('sum(total) as value_quotes')
                )
                ->groupBy('parceiro_id')
                ->with('parceiro')
                ->get();

            $topPartners = $topPartnersQuery->map(function ($p) {
                return [
                    'name' => $p->parceiro->razao_social ?? 'N/A',
                    'code' => $p->parceiro->codigo_sankhya ?? 'N/A',
                    'total_quotes' => (int)$p->total_quotes,
                    'value' => (float)$p->value_quotes
                ];
            })->sortByDesc('value')->values()->take(5);

            $data['top_partners'] = $topPartners;
        }

        // E. Timeline (Type-safe numbers)
        if ($user->hasDashPermission('ver_evolucao_temporal')) {
            $timelineRaw = (clone $baseQuery)
                ->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('count(*) as count'),
                    DB::raw('sum(total) as value'),
                    DB::raw('sum(case when status in (\'FINALIZADA_COM_PEDIDO\', \'FATURADA\') then total else 0 end) as value_billed')
                )
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            $data['timeline'] = $timelineRaw->map(function ($t) {
                return [
                    'date' => (string)$t->date,
                    'count' => (int)$t->count,
                    'value' => (float)$t->value,
                    'value_billed' => (float)$t->value_billed,
                ];
            })->values();
        }

        // F. SLA Alerts for stuck quotations
        $slaAlerts = [];
        $stuckQuotes = (clone $baseQuery)
            ->whereIn('status', ['EM_CRIACAO', 'DEVOLVIDA', 'AGUARDANDO_GESTOR', 'COM_DIRETOR'])
            ->with(['parceiro:id,razao_social', 'representante:id,nome'])
            ->get();

        foreach ($stuckQuotes as $sq) {
            $hoursStuck = $sq->updated_at ? $sq->updated_at->diffInHours(now()) : $sq->created_at->diffInHours(now());
            $threshold = 24;
            if ($sq->status === 'AGUARDANDO_GESTOR') $threshold = 4;
            if ($sq->status === 'COM_DIRETOR') $threshold = 12;

            if ($hoursStuck >= $threshold) {
                $slaAlerts[] = [
                    'id' => $sq->id,
                    'numero' => $sq->numero,
                    'status' => $sq->status,
                    'hours_stuck' => (int)$hoursStuck,
                    'threshold' => (int)$threshold,
                    'client' => $sq->parceiro->razao_social ?? 'N/A',
                    'rep' => $sq->representante->nome ?? 'N/A',
                ];
            }
        }
        $data['sla_alerts'] = $slaAlerts;

        // F. Product Analysis for Purchase and Sales Planning
        $data['product_analysis'] = [
            'most_quoted' => [],
            'high_discounts' => [],
            'high_rejections' => [],
            'missing_products' => []
        ];

        try {
            $baseItemsQuery = DB::table('cotacao_itens')
                ->join('cotacoes', 'cotacao_itens.cotacao_id', '=', 'cotacoes.id')
                ->join('produtos', 'cotacao_itens.produto_id', '=', 'produtos.id')
                ->whereBetween('cotacoes.created_at', [$parsedStart, $parsedEnd]);

            if ($user->isRepresentante()) {
                $baseItemsQuery->where('cotacoes.representante_id', $user->id);
            } elseif ($user->isGestor()) {
                $teamIds = $user->equipesGerenciadas->pluck('id')->toArray();
                $baseItemsQuery->join('usuarios', 'cotacoes.representante_id', '=', 'usuarios.id')
                    ->whereIn('usuarios.equipe_id', $teamIds);
            }

            // 1. Most Quoted Products (Product Demand)
            $data['product_analysis']['most_quoted'] = (clone $baseItemsQuery)
                ->select(
                    'produtos.descricao',
                    'produtos.codigo_sankhya',
                    DB::raw('COUNT(DISTINCT cotacoes.id) as total_quotes'),
                    DB::raw('SUM(cotacao_itens.qtd) as total_qty'),
                    DB::raw('SUM(cotacao_itens.qtd * cotacao_itens.preco_unit_proposto) as total_val')
                )
                ->groupBy('produtos.id', 'produtos.descricao', 'produtos.codigo_sankhya')
                ->orderByDesc('total_quotes')
                ->limit(5)
                ->get()
                ->map(fn($item) => [
                    'name' => $item->descricao,
                    'code' => $item->codigo_sankhya,
                    'quotes' => (int)$item->total_quotes,
                    'qty' => (float)$item->total_qty,
                    'value' => (float)$item->total_val
                ])
                ->values()
                ->toArray();

            // 2. High Discount Requests (Margin Pressure)
            $data['product_analysis']['high_discounts'] = (clone $baseItemsQuery)
                ->whereRaw('cotacao_itens.preco_unit_proposto < cotacao_itens.preco_minimo')
                ->select(
                    'produtos.descricao',
                    'produtos.codigo_sankhya',
                    DB::raw('COUNT(cotacao_itens.id) as discount_requests_count'),
                    DB::raw('AVG((cotacao_itens.preco_unit_sugerido - cotacao_itens.preco_unit_proposto) / cotacao_itens.preco_unit_sugerido * 100) as avg_discount_percent')
                )
                ->groupBy('produtos.id', 'produtos.descricao', 'produtos.codigo_sankhya')
                ->orderByDesc('discount_requests_count')
                ->limit(5)
                ->get()
                ->map(fn($item) => [
                    'name' => $item->descricao,
                    'code' => $item->codigo_sankhya,
                    'requests' => (int)$item->discount_requests_count,
                    'avg_discount' => round((float)$item->avg_discount_percent, 2)
                ])
                ->values()
                ->toArray();

            // 3. High Rejections / Lost Quotes (Product Rejection Rate)
            $data['product_analysis']['high_rejections'] = (clone $baseItemsQuery)
                ->where(function($q) {
                    $q->where('cotacao_itens.status_item', 'recusado')
                      ->orWhere('cotacoes.status', 'PERDIDA');
                })
                ->select(
                    'produtos.descricao',
                    'produtos.codigo_sankhya',
                    DB::raw('COUNT(DISTINCT cotacoes.id) as lost_quotes_count'),
                    DB::raw('SUM(cotacao_itens.qtd) as lost_qty')
                )
                ->groupBy('produtos.id', 'produtos.descricao', 'produtos.codigo_sankhya')
                ->orderByDesc('lost_quotes_count')
                ->limit(5)
                ->get()
                ->map(fn($item) => [
                    'name' => $item->descricao,
                    'code' => $item->codigo_sankhya,
                    'lost_quotes' => (int)$item->lost_quotes_count,
                    'lost_qty' => (float)$item->lost_qty
                ])
                ->values()
                ->toArray();

            // 4. Missing Products Demand (Gaps in Catalog)
            if (!$user->isRepresentante()) {
                $data['product_analysis']['missing_products'] = \App\Models\ProdutoNaoEncontrado::orderByDesc('requisicoes')
                    ->orderByDesc('updated_at')
                    ->limit(10)
                    ->get()
                    ->map(fn($item) => [
                        'code' => $item->codigo_sankhya,
                        'name' => $item->descricao,
                        'requests' => (int)$item->requisicoes,
                        'last_requester' => $item->ultimo_solicitante,
                        'updated_at' => $item->updated_at ? $item->updated_at->toDateTimeString() : null
                    ])
                    ->values()
                    ->toArray();
            }

        } catch (\Exception $e) {
            // Fail-safe to prevent API errors if tables are empty or querying issue
        }

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Get queue statistics and action items for the "Minha Fila" view.
     */
    public function getQueue(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        // 1. Approvals waiting for user
        $approvalQuery = Cotacao::whereIn('status', ['AGUARDANDO_GESTOR', 'COM_DIRETOR']);
        if ($user->isGestor()) {
            $teamIds = $user->equipesGerenciadas->pluck('id');
            $approvalQuery->where('status', 'AGUARDANDO_GESTOR')
                ->whereHas('representante', fn($q) => $q->whereIn('equipe_id', $teamIds));
        } elseif ($user->isDiretor()) {
            $approvalQuery->whereIn('status', ['AGUARDANDO_GESTOR', 'COM_DIRETOR']);
        } elseif (!$user->isAdministrador()) {
            $approvalQuery->whereRaw('1 = 0');
        }
        $pendingApprovals = $approvalQuery->count();

        // 2. Billing orders to check
        $billingQuery = Cotacao::where(function($q) {
            $q->where('status', 'FINALIZADA_COM_PEDIDO')
              ->orWhere(function($sub) {
                  $sub->whereNotNull('numero_pedido_externo')
                      ->where('status', '!=', 'FATURADA');
              });
        });
        if ($user->isRepresentante()) {
            $billingQuery->where('representante_id', $user->id);
        }
        $pendingBilling = $billingQuery->count();

        // 3. Quotes expiring today or in < 24h
        $expiringQuery = Cotacao::whereIn('status', ['EM_CRIACAO', 'AGUARDANDO_GESTOR', 'COM_DIRETOR', 'APROVADA', 'PDF_GERADO'])
            ->where(function($q) {
                $q->whereDate('data_validade', '<=', now()->toDateString())
                  ->orWhere('created_at', '<=', now()->subHours(24));
            });
        if ($user->isRepresentante()) {
            $expiringQuery->where('representante_id', $user->id);
        } elseif ($user->isGestor()) {
            $teamIds = $user->equipesGerenciadas->pluck('id');
            $expiringQuery->whereHas('representante', fn($q) => $q->whereIn('equipe_id', $teamIds));
        }
        $expiringToday = $expiringQuery->count();

        // 4. Catalog & user health
        $productsWithoutPrice = \App\Models\Produto::whereDoesntHave('tabelasPreco')->count();
        $usersWithoutTeam = User::where('ativo', true)->where('papel', 'REPRESENTANTE')->whereNull('equipe_id')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'approvals' => $pendingApprovals,
                'billing' => $pendingBilling,
                'expiring_today' => $expiringToday,
                'catalog_health' => [
                    'products_without_price' => $productsWithoutPrice,
                    'users_without_team' => $usersWithoutTeam,
                ]
            ]
        ]);
    }

    /**
     * Fast global search across quotations, partners and products for Ctrl+K modal.
     */
    public function globalSearch(Request $request)
    {
        $q = trim($request->input('q', ''));
        if (strlen($q) < 1) {
            return response()->json(['success' => true, 'results' => []]);
        }

        $results = [];

        // 1. Search quotes
        $quotes = Cotacao::where(function($query) use ($q) {
                $query->where('numero', 'like', "%{$q}%");
                if (is_numeric($q)) {
                    $query->orWhere('id', (int)$q);
                }
                $query->orWhereHas('pedidoExterno', function($p) use ($q) {
                    $p->where('numero_pedido_externo', 'like', "%{$q}%");
                })
                ->orWhereHas('parceiro', function($p) use ($q) {
                    $p->where('razao_social', 'like', "%{$q}%")
                      ->orWhere('nome_fantasia', 'like', "%{$q}%")
                      ->orWhere('cnpj', 'like', "%{$q}%");
                })
                ->orWhereHas('representante', function($r) use ($q) {
                    $r->where('nome', 'like', "%{$q}%");
                });
            })
            ->with(['parceiro', 'pedidoExterno', 'representante'])
            ->orderByDesc('id')
            ->limit(6)
            ->get();

        foreach ($quotes as $quote) {
            $orderNum = $quote->pedidoExterno ? $quote->pedidoExterno->numero_pedido_externo : null;
            $clientName = $quote->parceiro ? ($quote->parceiro->nome_fantasia ?: $quote->parceiro->razao_social) : 'Cliente não informado';
            $repName = $quote->representante ? $quote->representante->nome : null;

            $subtitleParts = [];
            $subtitleParts[] = 'Status: ' . $quote->status;
            $subtitleParts[] = 'Total: R$ ' . number_format($quote->total, 2, ',', '.');
            if ($repName) $subtitleParts[] = 'Vendedor: ' . $repName;
            if ($orderNum) $subtitleParts[] = 'Pedido: ' . $orderNum;

            $results[] = [
                'type' => 'cotacao',
                'badge' => 'Cotação',
                'title' => $quote->numero . ' — ' . $clientName,
                'subtitle' => implode(' · ', $subtitleParts),
                'url' => url('/cotacoes/id/' . $quote->id)
            ];
        }

        // 2. Search partners (Clients)
        $partners = \App\Models\Parceiro::where(function($query) use ($q) {
                $query->where('razao_social', 'like', "%{$q}%")
                      ->orWhere('nome_fantasia', 'like', "%{$q}%")
                      ->orWhere('cnpj', 'like', "%{$q}%")
                      ->orWhere('codigo_sankhya', 'like', "%{$q}%");
            })
            ->limit(5)
            ->get();

        foreach ($partners as $partner) {
            $name = $partner->nome_fantasia ?: $partner->razao_social;
            $doc = $partner->cnpj ?: 'S/N';
            $code = $partner->codigo_sankhya ?: '-';
            $cityState = trim(($partner->cidade ?: '') . ($partner->uf ? '/' . $partner->uf : ''));

            $subtitleParts = [];
            $subtitleParts[] = 'Cód: ' . $code;
            $subtitleParts[] = 'CNPJ: ' . $doc;
            if ($cityState) $subtitleParts[] = $cityState;

            $results[] = [
                'type' => 'cliente',
                'badge' => 'Cliente',
                'title' => $name . ($partner->nome_fantasia && $partner->razao_social !== $partner->nome_fantasia ? " ({$partner->razao_social})" : ''),
                'subtitle' => implode(' · ', $subtitleParts),
                'url' => url('/cotacoes?q=' . urlencode($partner->razao_social))
            ];
        }

        // 3. Search Products
        $products = \App\Models\Produto::where(function($query) use ($q) {
                $query->where('descricao', 'like', "%{$q}%")
                      ->orWhere('codigo_sankhya', 'like', "%{$q}%")
                      ->orWhere('marca', 'like', "%{$q}%");
            })
            ->limit(5)
            ->get();

        foreach ($products as $product) {
            $results[] = [
                'type' => 'produto',
                'badge' => 'Produto',
                'title' => $product->descricao,
                'subtitle' => 'Cód: ' . ($product->codigo_sankhya ?: '-') . ($product->marca ? ' · Marca: ' . $product->marca : '') . ($product->unidade ? ' · ' . $product->unidade : ''),
                'url' => url('/produtos?q=' . urlencode($product->descricao))
            ];
        }

        return response()->json(['success' => true, 'results' => $results]);
    }
}
