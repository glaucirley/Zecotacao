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
                ->whereIn('status', ['FINALIZADA_COM_PEDIDO', 'FATURADA'])
                ->sum('total');

            $totalPrecoSugerido = (clone $baseQuery)
                ->whereIn('status', ['PDF_GERADO', 'FINALIZADA_COM_PEDIDO', 'FATURADA'])
                ->sum('subtotal');

            $totalPrecoProposto = (clone $baseQuery)
                ->whereIn('status', ['PDF_GERADO', 'FINALIZADA_COM_PEDIDO', 'FATURADA'])
                ->sum('total');

            $descontoMedio = 0;
            if ($totalPrecoSugerido > 0) {
                $descontoMedio = (($totalPrecoSugerido - $totalPrecoProposto) / $totalPrecoSugerido) * 100;
            }

            $convertedCount = (clone $baseQuery)
                ->whereIn('status', ['FINALIZADA_COM_PEDIDO', 'FATURADA'])
                ->count();
            $conversaoRate = $totalQuotes > 0 ? ($convertedCount / $totalQuotes) * 100 : 0;

            $data['summary'] = [
                'total_quotes' => $totalQuotes,
                'total_billed' => (float)$totalBilled,
                'conversao_rate' => (float)$conversaoRate,
                'desconto_medio' => (float)$descontoMedio,
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

            $allStatuses = ['EM_CRIACAO', 'DEVOLVIDA', 'AGUARDANDO_GESTOR', 'COM_DIRETOR', 'PDF_GERADO', 'FINALIZADA_COM_PEDIDO', 'FATURADA', 'PERDIDA'];
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
                    'total_quotes' => $s->total_quotes,
                    'value_quotes' => (float)$s->value_quotes,
                    'value_billed' => (float)$s->value_billed,
                    'conversao' => $s->total_quotes > 0 ? ($s->count_billed / $s->total_quotes) * 100 : 0
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
                    'total_quotes' => $p->total_quotes,
                    'value' => (float)$p->value_quotes
                ];
            })->sortByDesc('value')->values()->take(5);

            $data['top_partners'] = $topPartners;
        }

        // E. Timeline
        if ($user->hasDashPermission('ver_evolucao_temporal')) {
            $timeline = (clone $baseQuery)
                ->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('count(*) as count'),
                    DB::raw('sum(total) as value'),
                    DB::raw('sum(case when status in (\'FINALIZADA_COM_PEDIDO\', \'FATURADA\') then total else 0 end) as value_billed')
                )
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            $data['timeline'] = $timeline;
        }

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
}
