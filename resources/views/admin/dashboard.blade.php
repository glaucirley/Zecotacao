@extends('layouts.app')

@section('page_title', 'Visão Geral & Fila de Trabalho')

@section('content')
<style>
    .dash-nav-tabs {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
        border-bottom: 2px solid #e2e8f0;
    }
    .dash-nav-tab {
        background: transparent;
        border: none;
        padding: 10px 20px;
        font-size: 14px;
        font-weight: 700;
        color: #64748b;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        border-radius: 8px 8px 0 0;
        border-bottom: 3px solid transparent;
        margin-bottom: -2px;
        transition: all 0.2s ease;
    }
    .dash-nav-tab:hover {
        color: #1e293b;
    }
    .dash-nav-tab.active {
        color: var(--color-primary);
        border-bottom-color: var(--color-primary);
        background: rgba(37, 99, 235, 0.05);
    }
    .dash-tab-badge {
        background: #ef4444;
        color: #ffffff;
        font-size: 11px;
        font-weight: 700;
        padding: 2px 7px;
        border-radius: 999px;
        line-height: 1.2;
    }
    
    /* Minha Fila Cards Grid */
    .queue-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }
    .queue-card {
        background: #ffffff;
        border-radius: 14px;
        border: 1px solid #e2e8f0;
        padding: 20px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        gap: 16px;
        transition: all 0.2s ease;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    }
    .queue-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px -5px rgba(0,0,0,0.08);
        border-color: #cbd5e1;
    }
    .queue-card-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
    }
    .queue-icon-box {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .queue-number {
        font-size: 32px;
        font-weight: 800;
        line-height: 1;
        color: #0f172a;
    }
    .queue-title {
        font-size: 15px;
        font-weight: 700;
        color: #0f172a;
        margin: 0 0 6px 0;
    }
    .queue-desc {
        font-size: 13px;
        color: #64748b;
        line-height: 1.45;
        margin: 0;
    }
    .queue-action-btn {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 14px;
        border-radius: 9px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        color: #1e293b;
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s ease;
    }
    .queue-action-btn:hover {
        background: #2563eb;
        color: #ffffff;
        border-color: #2563eb;
    }

    /* Drill-down KPI Cards */
    .kpi-block {
        cursor: pointer;
        transition: all 0.15s ease;
        border-radius: 10px;
        padding: 8px 12px;
    }
    .kpi-block:hover {
        background: #f8fafc;
        transform: translateY(-1px);
    }
    .kpi-diff {
        font-size: 11.5px;
        font-weight: 600;
        margin-top: 4px;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    .kpi-diff.up { color: #10b981; }
    .kpi-diff.down { color: #ef4444; }
    .kpi-diff.neutral { color: #64748b; }
</style>

<!-- Top Dashboard Tabs Switcher -->
<div class="dash-nav-tabs">
    <button type="button" class="dash-nav-tab active" id="tab-btn-queue" onclick="switchMainTab('queue')">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
        <span>Minha Fila</span>
        <span id="tab-badge-queue" class="dash-tab-badge" style="display:none;">0</span>
    </button>
    <button type="button" class="dash-nav-tab" id="tab-btn-bi" onclick="switchMainTab('bi')">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg>
        <span>Indicadores & BI</span>
    </button>
</div>

<!-- ================= TAB 1: MINHA FILA ================= -->
<div id="tab-content-queue">
    <div class="queue-grid">
        <!-- 1. Aprovações -->
        <div class="queue-card">
            <div class="queue-card-top">
                <div class="queue-icon-box" style="background: rgba(139, 92, 246, 0.1); color: #8b5cf6;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                </div>
                <div id="queue-approvals-num" class="queue-number" style="color: #8b5cf6;">0</div>
            </div>
            <div>
                <h3 class="queue-title">Aprovações esperando você</h3>
                <p class="queue-desc">Cotações com desconto acima da alçada comercial que necessitam de aprovação.</p>
            </div>
            <a href="{{ url('/aprovacoes') }}" class="queue-action-btn">
                <span>Analisar Aprovações</span>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </a>
        </div>

        <!-- 2. Pedidos Faturamento -->
        <div class="queue-card">
            <div class="queue-card-top">
                <div class="queue-icon-box" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M12 4v16"/><path d="M2 12h20"/></svg>
                </div>
                <div id="queue-billing-num" class="queue-number" style="color: #10b981;">0</div>
            </div>
            <div>
                <h3 class="queue-title">Pedidos para conferir no faturamento</h3>
                <p class="queue-desc">Pedidos com número do ERP prontos para conferência de itens e liberação.</p>
            </div>
            <a href="{{ url('/faturamento') }}" class="queue-action-btn">
                <span>Conferir Faturamento</span>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </a>
        </div>

        <!-- 3. Cotações Vencem Hoje -->
        <div class="queue-card">
            <div class="queue-card-top">
                <div class="queue-icon-box" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
                <div id="queue-expiring-num" class="queue-number" style="color: #f59e0b;">0</div>
            </div>
            <div>
                <h3 class="queue-title">Cotações que vencem hoje</h3>
                <p class="queue-desc">Cotações ativas que expiram em menos de 24 horas e demandam fechamento.</p>
            </div>
            <a href="{{ url('/cotacoes') }}" class="queue-action-btn">
                <span>Ver Cotações Ativas</span>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </a>
        </div>

        <!-- 4. Saúde do Cadastro ERP -->
        <div class="queue-card">
            <div class="queue-card-top">
                <div class="queue-icon-box" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                </div>
                <div id="queue-health-num" class="queue-number" style="color: #ef4444;">0</div>
            </div>
            <div>
                <h3 class="queue-title">Saúde do cadastro (Sankhya)</h3>
                <p class="queue-desc" id="queue-health-desc">Inconsistências cadastrais (produtos sem preço na tabela ou dados incompletos).</p>
            </div>
            <a href="{{ url('/produtos') }}" class="queue-action-btn">
                <span>Revisar Catálogo de Produtos</span>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </a>
        </div>
    </div>
</div>

<!-- ================= TAB 2: INDICADORES & BI ================= -->
<div id="tab-content-bi" style="display: none;">
    <!-- Date Filters Row -->
    <div class="card" style="margin-bottom: 20px; padding: 15px 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <button class="btn btn-secondary active-filter" id="btn-period-7" onclick="setPeriod(7)" style="font-size:12px; padding: 8px 14px;">7 Dias</button>
                <button class="btn btn-secondary" id="btn-period-30" onclick="setPeriod(30)" style="font-size:12px; padding: 8px 14px;">30 Dias</button>
                <button class="btn btn-secondary" id="btn-period-month" onclick="setPeriod('month')" style="font-size:12px; padding: 8px 14px;">Este Mês</button>
                <button class="btn btn-secondary" id="btn-period-custom" onclick="setPeriod('custom')" style="font-size:12px; padding: 8px 14px;">Personalizado</button>
            </div>
            
            <div id="custom-date-inputs" style="display: none; align-items: center; gap: 10px;">
                <div style="display: flex; align-items: center; gap: 5px;">
                    <label for="filter-start-date" style="font-size: 12px; font-weight: 600; color: var(--color-text-muted);">De:</label>
                    <input type="date" id="filter-start-date" class="form-control" style="padding: 6px 10px; font-size:12px; width:auto; margin:0;">
                </div>
                <div style="display: flex; align-items: center; gap: 5px;">
                    <label for="filter-end-date" style="font-size: 12px; font-weight: 600; color: var(--color-text-muted);">Até:</label>
                    <input type="date" id="filter-end-date" class="form-control" style="padding: 6px 10px; font-size:12px; width:auto; margin:0;">
                </div>
                <button class="btn btn-primary" onclick="loadDashboardData()" style="font-size:12px; padding: 8px 16px; background-color: var(--color-accent); border-color: var(--color-accent);">Filtrar</button>
            </div>
        </div>
    </div>

    <!-- Loading indicator -->
    <div id="dashboard-loading" style="text-align: center; padding: 60px 20px;">
        <div style="border: 4px solid rgba(15,81,50,0.1); border-top: 4px solid var(--color-primary); border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 0 auto 15px auto;"></div>
        <span style="font-weight: 500; color: var(--color-text-muted);">Carregando métricas consolidadas...</span>
    </div>

    <!-- Main Dashboard Container -->
    <div id="dashboard-content" style="display: none; flex-direction: column; gap: 24px;">
        
        <!-- SLA Alerts Banner -->
        <div id="sla-alerts-container" style="display: none; width: 100%;">
            <div style="background-color: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.3); border-left: 5px solid #ef4444; border-radius: 12px; padding: 16px;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; flex-wrap: wrap; gap: 10px;">
                    <div style="display: flex; align-items: center; gap: 8px; font-weight: 700; color: #dc2626;">
                        <span>ALERTA DE SLA DE APROVAÇÃO</span>
                        <span id="sla-count-badge" class="badge-status" style="background-color: #fee2e2; color: #dc2626;">0 Cotações Paradas</span>
                    </div>
                </div>
                <div id="sla-list" style="font-size: 13px; color: #475569; display: flex; flex-direction: column; gap: 8px;">
                </div>
            </div>
        </div>

        <!-- Module 1: Grouped KPIs (Always visible at the top) -->
        <div class="card" id="widget-kpi-container" style="padding: 20px; width: 100%;">
            <div class="kpi-grid">
                <!-- KPI 1 -->
                <div style="display: flex; align-items: center; gap: 15px; border-right: 1px solid var(--color-border); padding-right: 15px;" class="kpi-block" onclick="window.location.href='{{ url('/cotacoes') }}'" title="Clique para ver todas as cotações">
                    <div style="background-color: var(--color-secondary); padding: 12px; border-radius: 12px; color: var(--color-primary); display: flex;">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    </div>
                    <div>
                        <div style="font-size: 11px; font-weight: 700; color: var(--color-text-muted); text-transform: uppercase; letter-spacing: 0.5px;">Cotações Criadas</div>
                        <div id="kpi-total-quotes" style="font-size: 22px; font-weight: 800; color: var(--color-text-main); margin-top: 2px;">0</div>
                        <div id="kpi-quotes-diff" class="kpi-diff neutral">-- vs período ant.</div>
                    </div>
                </div>
                <!-- KPI 2 -->
                <div style="display: flex; align-items: center; gap: 15px; border-right: 1px solid var(--color-border); padding-right: 15px;" class="kpi-block" onclick="window.location.href='{{ url('/cotacoes') }}'" title="Clique para ver faturadas">
                    <div style="background-color: rgba(16,185,129,0.08); padding: 12px; border-radius: 12px; color: var(--status-pdf-gerado); display: flex;">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    </div>
                    <div>
                        <div style="font-size: 11px; font-weight: 700; color: var(--color-text-muted); text-transform: uppercase; letter-spacing: 0.5px;">Volume Faturado</div>
                        <div id="kpi-total-billed" style="font-size: 22px; font-weight: 800; color: var(--color-text-main); margin-top: 2px;">R$ 0,00</div>
                        <div id="kpi-billed-diff" class="kpi-diff neutral">-- vs período ant.</div>
                    </div>
                </div>
                <!-- KPI 3 -->
                <div style="display: flex; align-items: center; gap: 15px; border-right: 1px solid var(--color-border); padding-right: 15px;" class="kpi-block" onclick="window.location.href='{{ url('/cotacoes') }}'" title="Clique para ver cotações ganhas">
                    <div style="background-color: rgba(59,130,246,0.08); padding: 12px; border-radius: 12px; color: var(--status-em-criacao); display: flex;">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    </div>
                    <div>
                        <div style="font-size: 11px; font-weight: 700; color: var(--color-text-muted); text-transform: uppercase; letter-spacing: 0.5px;">Taxa de Conversão</div>
                        <div id="kpi-conversion" style="font-size: 22px; font-weight: 800; color: var(--color-text-main); margin-top: 2px;">0%</div>
                        <div id="kpi-conv-diff" class="kpi-diff neutral">-- vs período ant.</div>
                    </div>
                </div>
                <!-- KPI 4 -->
                <div style="display: flex; align-items: center; gap: 15px;" class="kpi-block" onclick="window.location.href='{{ url('/cotacoes') }}'" title="Clique para ver lista de cotações">
                    <div style="background-color: rgba(245,158,11,0.08); padding: 12px; border-radius: 12px; color: var(--status-devolvida); display: flex;">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                    </div>
                    <div>
                        <div style="font-size: 11px; font-weight: 700; color: var(--color-text-muted); text-transform: uppercase; letter-spacing: 0.5px;">Desconto Médio</div>
                        <div id="kpi-discount" style="font-size: 22px; font-weight: 800; color: var(--color-text-main); margin-top: 2px;">0%</div>
                        <div class="kpi-diff neutral">Média de desconto</div>
                    </div>
                </div>
            </div>
        </div>

    <!-- Unified Analysis Card with Selector in Upper Right Corner -->
    <div class="card" style="padding: 24px; width: 100%;">
        
        <!-- Tab Switcher Header -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 15px; border-bottom: 1px solid var(--color-border); padding-bottom: 15px;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="background-color: var(--color-secondary); padding: 8px; border-radius: 8px; color: var(--color-primary); display: flex;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.21 15.89A10 10 0 1 1 8 2.83"/><path d="M22 12A10 10 0 0 0 12 2v10z"/></svg>
                </div>
                <h3 id="analysis-title" style="font-size: 16px; font-weight: 700; color: var(--color-text-main); margin: 0;">Evolução Temporal de Vendas</h3>
            </div>
            <div>
                <select id="analysis-selector" class="form-control" onchange="switchAnalysisView()" style="font-size: 13px; font-weight: 600; padding: 8px 16px; width: auto; border-radius: 8px; cursor: pointer; border: 1px solid var(--color-border); background-color: var(--color-card-bg);">
                    <option value="timeline">📈 Evolução Temporal de Vendas</option>
                    <option value="status">📊 Distribuição de Status</option>
                    <option value="vendedores">👥 Desempenho por Representante</option>
                    <option value="clientes">🛍️ Top Clientes Compradores</option>
                    <option value="catalogo">📦 Demanda de Catálogo (Mais Cotados)</option>
                    <option value="descontos">📉 Pressão de Desconto (Margens Baixas)</option>
                    <option value="rejeicoes">❌ Perdas de Venda (Índice de Rejeição)</option>
                    <option value="inexistentes">🔍 Demandas de Produtos Inexistentes</option>
                    <option value="checkins">📌 Visitas de Vendedores (Check-ins)</option>
                </select>
            </div>
        </div>

        <!-- Dynamic Container Views -->
        <div id="analysis-views-wrapper">
            
            <!-- View 1: Timeline -->
            <div id="view-timeline" class="analysis-view-container">
                <div style="position: relative; height: 350px; width: 100%;">
                    <canvas id="chart-timeline"></canvas>
                </div>
            </div>

            <!-- View 2: Status -->
            <div id="view-status" class="analysis-view-container" style="display: none;">
                <div style="position: relative; height: 350px; width: 100%; display: flex; justify-content: center; align-items: center;">
                    <div style="width: 350px; max-width: 100%;">
                        <canvas id="chart-status"></canvas>
                    </div>
                </div>
            </div>

            <!-- View 3: Vendedores (Chart + Table side-by-side) -->
            <div id="view-vendedores" class="analysis-view-container" style="display: none;">
                <div class="grid-2" style="gap: 24px; align-items: start;">
                    <div style="position: relative; height: 320px; width: 100%;">
                        <canvas id="chart-sellers"></canvas>
                    </div>
                    <div class="table-responsive" style="margin: 0;">
                        <table class="table-premium" style="font-size: 12px;">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Equipe</th>
                                    <th style="text-align: right;">Faturado</th>
                                    <th style="text-align: center;">Conversão</th>
                                </tr>
                            </thead>
                            <tbody id="table-sellers-body">
                                <!-- Dynamic rows -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- View 4: Clientes -->
            <div id="view-clientes" class="analysis-view-container" style="display: none;">
                <div class="table-responsive" style="margin: 0;">
                    <table class="table-premium" style="font-size: 13px;">
                        <thead>
                            <tr>
                                <th>Razão Social</th>
                                <th>Cód. Sankhya</th>
                                <th style="text-align: right;">Total Cotado</th>
                            </tr>
                        </thead>
                        <tbody id="table-partners-body">
                            <!-- Dynamic rows -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- View 5: Catalogo -->
            <div id="view-catalogo" class="analysis-view-container" style="display: none;">
                <div class="table-responsive" style="margin: 0;">
                    <table class="table-premium" style="font-size: 13px;">
                        <thead>
                            <tr>
                                <th>Produto</th>
                                <th style="text-align: center;">Cotações</th>
                                <th style="text-align: center;">Qtd</th>
                                <th style="text-align: right;">Total Cotado</th>
                            </tr>
                        </thead>
                        <tbody id="table-products-demand-body">
                            <!-- Dynamic rows -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- View 6: Descontos -->
            <div id="view-descontos" class="analysis-view-container" style="display: none;">
                <div class="table-responsive" style="margin: 0;">
                    <table class="table-premium" style="font-size: 13px;">
                        <thead>
                            <tr>
                                <th>Produto</th>
                                <th style="text-align: center;">Pedidos Sub-mín.</th>
                                <th style="text-align: center;">Desc. Médio</th>
                            </tr>
                        </thead>
                        <tbody id="table-products-pressure-body">
                            <!-- Dynamic rows -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- View 7: Rejeicoes -->
            <div id="view-rejeicoes" class="analysis-view-container" style="display: none;">
                <div class="table-responsive" style="margin: 0;">
                    <table class="table-premium" style="font-size: 13px;">
                        <thead>
                            <tr>
                                <th>Produto</th>
                                <th style="text-align: center;">Perdidos (Cotação)</th>
                                <th style="text-align: center;">Qtd Recusada</th>
                            </tr>
                        </thead>
                        <tbody id="table-products-loss-body">
                            <!-- Dynamic rows -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- View 8: Inexistentes (Demand for Missing Catalog Items) -->
            <div id="view-inexistentes" class="analysis-view-container" style="display: none;">
                <div style="background-color: rgba(245,158,11,0.05); border-left: 4px solid var(--status-devolvida); padding: 12px; border-radius: 4px; margin-bottom: 16px; font-size: 13px; color: var(--color-text-muted);">
                    ⚠️ Estes produtos foram solicitados por integradores ou vendedores via WhatsApp, porém não foram encontrados em nosso banco local e nem no ERP Sankhya. Reincidências altas indicam alta demanda de mercado para viabilizar cadastro.
                </div>
                <div class="table-responsive" style="margin: 0;">
                    <table class="table-premium" style="font-size: 13px;">
                        <thead>
                            <tr>
                                <th style="width: 15%;">Código Informado</th>
                                <th style="width: 45%;">Descrição Solicitada</th>
                                <th style="width: 15%; text-align: center;">Reincidência (Solicitações)</th>
                                <th style="width: 25%;">Último Solicitante</th>
                            </tr>
                        </thead>
                        <tbody id="table-products-missing-body">
                            <!-- Dynamic rows -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- View 9: Check-ins (Audit of visited partners) -->
            <div id="view-checkins" class="analysis-view-container" style="display: none;">
                <div class="table-responsive" style="margin: 0;">
                    <table class="table-premium" style="font-size: 13px;">
                        <thead>
                            <tr>
                                <th style="width: 25%;">Representante / Vendedor</th>
                                <th style="width: 30%;">Cliente / Parceiro Visitado</th>
                                <th style="width: 20%;">Data e Hora</th>
                                <th style="width: 15%;">Coordenadas GPS</th>
                                <th style="width: 10%; text-align: center;">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="table-checkins-body">
                            <!-- Dynamic rows -->
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

@section('scripts')
<!-- Load Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    .active-filter {
        background-color: var(--color-primary) !important;
        color: white !important;
        border-color: var(--color-primary) !important;
        font-weight: 600;
    }
</style>

<script>
    const API_URL = "{{ url('/api/v1') }}";
    let activePeriod = 7;
    let chartTimelineInstance = null;
    let chartStatusInstance = null;
    let chartSellersInstance = null;

    document.addEventListener("DOMContentLoaded", () => {
        setPeriod(30); // Default to last 30 days
    });

    function setPeriod(period) {
        activePeriod = period;
        
        // Toggle active button style
        document.querySelectorAll(".btn-secondary").forEach(btn => btn.classList.remove("active-filter"));
        
        const customContainer = document.getElementById("custom-date-inputs");
        
        if (period === 7) {
            document.getElementById("btn-period-7").classList.add("active-filter");
            customContainer.style.display = "none";
            loadDashboardData();
        } else if (period === 30) {
            document.getElementById("btn-period-30").classList.add("active-filter");
            customContainer.style.display = "none";
            loadDashboardData();
        } else if (period === 'month') {
            document.getElementById("btn-period-month").classList.add("active-filter");
            customContainer.style.display = "none";
            loadDashboardData();
        } else if (period === 'custom') {
            document.getElementById("btn-period-custom").classList.add("active-filter");
            customContainer.style.display = "flex";
            
            // Set defaults to custom inputs
            const today = new Date();
            const last30 = new Date();
            last30.setDate(today.getDate() - 30);
            
            document.getElementById("filter-start-date").value = last30.toISOString().split('T')[0];
            document.getElementById("filter-end-date").value = today.toISOString().split('T')[0];
        }
    }

    async function loadDashboardData() {
        document.getElementById("dashboard-loading").style.display = "block";
        document.getElementById("dashboard-content").style.display = "none";

        let url = `${API_URL}/dashboard/stats`;
        let params = [];

        if (activePeriod === 7) {
            const start = new Date();
            start.setDate(start.getDate() - 7);
            params.push(`start_date=${start.toISOString().split('T')[0]}`);
            params.push(`end_date=${new Date().toISOString().split('T')[0]}`);
        } else if (activePeriod === 30) {
            const start = new Date();
            start.setDate(start.getDate() - 30);
            params.push(`start_date=${start.toISOString().split('T')[0]}`);
            params.push(`end_date=${new Date().toISOString().split('T')[0]}`);
        } else if (activePeriod === 'month') {
            const now = new Date();
            const start = new Date(now.getFullYear(), now.getMonth(), 1);
            params.push(`start_date=${start.toISOString().split('T')[0]}`);
            params.push(`end_date=${new Date().toISOString().split('T')[0]}`);
        } else if (activePeriod === 'custom') {
            params.push(`start_date=${document.getElementById("filter-start-date").value}`);
            params.push(`end_date=${document.getElementById("filter-end-date").value}`);
        }

        if (params.length > 0) {
            url += `?${params.join('&')}`;
        }

        try {
            const res = await fetch(url);
            if (res.status === 401 || res.status === 403) {
                window.location.href = "{{ url('/login') }}";
                return;
            }
            
            const json = await res.json();
            document.getElementById("dashboard-loading").style.display = "none";

            if (json.success) {
                renderDashboard(json.data);
            } else {
                showToast("Erro ao buscar dados do dashboard: " + json.error, "error");
            }
        } catch(e) {
            console.error(e);
            document.getElementById("dashboard-loading").style.display = "none";
            showToast("Erro de conexão ao buscar dashboard.", "error");
        }
    }

    function renderDashboard(data) {
        document.getElementById("dashboard-content").style.display = "flex";
        const perms = data.permissions;

        // 1. Hide/Show selector options based on user permissions
        const selector = document.getElementById("analysis-selector");
        const toggleOption = (val, allowed) => {
            const opt = selector.querySelector(`option[value="${val}"]`);
            if (opt) {
                opt.style.display = allowed ? "block" : "none";
                opt.disabled = !allowed;
            }
        };

        const showProducts = perms.ver_kpis; // Products intelligence aligns with general kpis permission

        toggleOption('timeline', perms.ver_evolucao_temporal);
        toggleOption('status', perms.ver_status_dist);
        toggleOption('vendedores', perms.ver_ranking_vendedores);
        toggleOption('clientes', perms.ver_top_clientes);
        toggleOption('catalogo', showProducts);
        toggleOption('descontos', showProducts);
        toggleOption('rejeicoes', showProducts);

        // Hide/Show KPI Container completely
        document.getElementById("widget-kpi-container").style.display = perms.ver_kpis ? "block" : "none";

        // Resolve active selected dropdown option if it is now disabled under new permissions
        let activeVal = selector.value;
        const currentOpt = selector.querySelector(`option[value="${activeVal}"]`);
        if (!currentOpt || currentOpt.disabled) {
            const firstEnabled = Array.from(selector.options).find(opt => !opt.disabled);
            if (firstEnabled) {
                selector.value = firstEnabled.value;
            }
        }

        // Apply dynamic visibility logic
        switchAnalysisView();

        // 1.5. Render SLA Alerts if any
        const slaContainer = document.getElementById("sla-alerts-container");
        const slaList = document.getElementById("sla-list");
        if (data.sla_alerts && data.sla_alerts.length > 0) {
            slaContainer.style.display = "block";
            document.getElementById("sla-count-badge").innerText = `${data.sla_alerts.length} Cotação(ões) Parada(s)`;
            slaList.innerHTML = data.sla_alerts.map(a => `
                <div style="display: flex; justify-content: space-between; align-items: center; background: #ffffff; padding: 8px 12px; border-radius: 6px; border: 1px solid #fecdd3; flex-wrap: wrap; gap: 8px;">
                    <div>
                        <strong>${a.numero}</strong> (${a.client}) - Vendedor: <em>${a.rep}</em>
                    </div>
                    <div>
                        <span class="badge-status priority" style="background-color: #fee2e2; color: #dc2626;">Parada há ${a.hours_stuck}h (SLA: ${a.threshold}h)</span>
                        <a href="{{ url('/aprovacoes') }}/${a.id}" class="btn btn-secondary" style="padding: 3px 8px; font-size: 11px; margin-left: 8px;">Analisar</a>
                    </div>
                </div>
            `).join("");
        } else if (slaContainer) {
            slaContainer.style.display = "none";
        }

        // 2. Populate General KPIs
        if (perms.ver_kpis && data.summary) {
            const sum = data.summary;
            document.getElementById("kpi-total-quotes").innerText = sum.total_quotes;
            document.getElementById("kpi-total-billed").innerText = 'R$ ' + parseFloat(sum.total_billed).toLocaleString('pt-BR', { minimumFractionDigits: 2 });
            document.getElementById("kpi-conversion").innerText = parseFloat(sum.conversao_rate).toLocaleString('pt-BR', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) + '%';
            
            let descMed = parseFloat(sum.desconto_medio || 0);
            if (descMed > 0 && descMed < 1) descMed = descMed * 100;
            document.getElementById("kpi-discount").innerText = descMed.toLocaleString('pt-BR', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) + '%';

            if (sum.comparisons) {
                const qDiff = sum.comparisons.quotes_diff;
                const bDiff = sum.comparisons.billed_diff;
                const cDiff = sum.comparisons.conversao_diff;

                const qEl = document.getElementById("kpi-quotes-diff");
                if (qEl) {
                    qEl.className = 'kpi-diff ' + (qDiff > 0 ? 'up' : (qDiff < 0 ? 'down' : 'neutral'));
                    qEl.innerText = (qDiff > 0 ? `▲ +${qDiff}%` : (qDiff < 0 ? `▼ ${qDiff}%` : '= 0%')) + ' vs período ant.';
                }

                const bEl = document.getElementById("kpi-billed-diff");
                if (bEl) {
                    bEl.className = 'kpi-diff ' + (bDiff > 0 ? 'up' : (bDiff < 0 ? 'down' : 'neutral'));
                    bEl.innerText = (bDiff > 0 ? `▲ +${bDiff}%` : (bDiff < 0 ? `▼ ${bDiff}%` : '= 0%')) + ' vs período ant.';
                }

                const cEl = document.getElementById("kpi-conv-diff");
                if (cEl) {
                    cEl.className = 'kpi-diff ' + (cDiff > 0 ? 'up' : (cDiff < 0 ? 'down' : 'neutral'));
                    cEl.innerText = (cDiff > 0 ? `▲ +${cDiff}%` : (cDiff < 0 ? `▼ ${cDiff}%` : '= 0%')) + ' vs período ant.';
                }
            }
        }

        // 3. Render Timeline Chart
        if (perms.ver_evolucao_temporal && data.timeline) {
            const dates = data.timeline.map(t => new Date(t.date).toLocaleDateString('pt-BR'));
            const counts = data.timeline.map(t => t.count);
            const values = data.timeline.map(t => t.value_billed);
            
            if (chartTimelineInstance) {
                chartTimelineInstance.destroy();
            }

            const ctx = document.getElementById("chart-timeline").getContext("2d");
            chartTimelineInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: dates,
                    datasets: [
                        {
                            label: 'Volume Faturado (R$)',
                            data: values,
                            borderColor: '#1a56db',
                            backgroundColor: 'rgba(26, 86, 219, 0.05)',
                            fill: true,
                            yAxisID: 'yValue',
                            tension: 0.3
                        },
                        {
                            label: 'Qtd de Cotações',
                            data: counts,
                            borderColor: '#3b82f6',
                            backgroundColor: 'transparent',
                            borderDash: [5, 5],
                            yAxisID: 'yCount',
                            tension: 0.3
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        yValue: {
                            type: 'linear',
                            position: 'left',
                            title: { display: true, text: 'Valor (R$)' }
                        },
                        yCount: {
                            type: 'linear',
                            position: 'right',
                            grid: { drawOnChartArea: false },
                            title: { display: true, text: 'Quantidade' }
                        }
                    }
                }
            });
        }

        // 4. Render Status Doughnut Chart
        if (perms.ver_status_dist && data.status_distribution) {
            const dist = data.status_distribution;
            
            if (chartStatusInstance) {
                chartStatusInstance.destroy();
            }

            const ctx = document.getElementById("chart-status").getContext("2d");
            
            const labels = [];
            const values = [];
            const colors = [];

            const colorMapping = {
                'EM_CRIACAO': '#3b82f6',
                'DEVOLVIDA': '#f59e0b',
                'AGUARDANDO_GESTOR': '#8b5cf6',
                'COM_DIRETOR': '#ec4899',
                'APROVADA': '#10b981',
                'PDF_GERADO': '#0ea5e9',
                'FINALIZADA_COM_PEDIDO': '#111827',
                'FATURADA': '#1a56db',
                'PERDIDA': '#ef4444',
                'EXPIRADA': '#64748b'
            };

            const labelsMapping = {
                'EM_CRIACAO': 'Rascunho',
                'DEVOLVIDA': 'Devolvida',
                'AGUARDANDO_GESTOR': 'Aguardando Gestor',
                'COM_DIRETOR': 'Com Diretor',
                'APROVADA': 'Aprovada (Pendente PDF)',
                'PDF_GERADO': 'PDF Gerado',
                'FINALIZADA_COM_PEDIDO': 'Fechada',
                'FATURADA': 'Faturada',
                'PERDIDA': 'Perdida',
                'EXPIRADA': 'Expirada'
            };

            for (const [status, count] of Object.entries(dist)) {
                if (count > 0) {
                    labels.push(labelsMapping[status] || status);
                    values.push(count);
                    colors.push(colorMapping[status] || '#cbd5e1');
                }
            }

            // Fallback empty doughnut
            if (values.length === 0) {
                labels.push('Sem dados');
                values.push(1);
                colors.push('#e2e8f0');
            }

            chartStatusInstance = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: values,
                        backgroundColor: colors,
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { boxWidth: 12, font: { size: 11 } }
                        }
                    }
                }
            });
        }

        // 5. Render Sellers Charts and Table
        if (perms.ver_ranking_vendedores && data.top_sellers) {
            const sellers = data.top_sellers;
            
            // Build table rows
            const tbody = document.getElementById("table-sellers-body");
            tbody.innerHTML = "";

            if (sellers.length === 0) {
                tbody.innerHTML = `<tr><td colspan="4" style="text-align:center; color:var(--color-text-muted);">Nenhum dado cadastrado.</td></tr>`;
            } else {
                sellers.forEach(s => {
                    tbody.innerHTML += `
                        <tr>
                            <td><strong>${s.name}</strong></td>
                            <td>${s.team}</td>
                            <td style="text-align: right; font-weight: 600; color: var(--color-primary);">R$ ${s.value_billed.toLocaleString('pt-BR', {minimumFractionDigits:2})}</td>
                            <td style="text-align: center; font-weight: 600;">${s.conversao.toLocaleString('pt-BR', {minimumFractionDigits: 0, maximumFractionDigits: 0})}%</td>
                        </tr>
                    `;
                });
            }

            // Build Horizontal Bar chart
            const names = sellers.map(s => s.name);
            const billedValues = sellers.map(s => s.value_billed);
            const quoteValues = sellers.map(s => s.value_quotes);

            if (chartSellersInstance) {
                chartSellersInstance.destroy();
            }

            const ctx = document.getElementById("chart-sellers").getContext("2d");
            chartSellersInstance = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: names,
                    datasets: [
                        {
                            label: 'Faturado (R$)',
                            data: billedValues,
                            backgroundColor: '#1a56db',
                            borderRadius: 6
                        },
                        {
                            label: 'Total Cotado (R$)',
                            data: quoteValues,
                            backgroundColor: '#93c5fd',
                            borderRadius: 6
                        }
                    ]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' }
                    },
                    scales: {
                        x: { title: { display: true, text: 'Valor em R$' } }
                    }
                }
            });
        }

        // 6. Render Top Partners (Clientes)
        if (perms.ver_top_clientes && data.top_partners) {
            const partners = data.top_partners;
            const tbody = document.getElementById("table-partners-body");
            tbody.innerHTML = "";

            if (partners.length === 0) {
                tbody.innerHTML = `<tr><td colspan="3" style="text-align:center; color:var(--color-text-muted);">Nenhum dado de compras.</td></tr>`;
            } else {
                partners.forEach(p => {
                    tbody.innerHTML += `
                        <tr>
                            <td><strong>${p.name}</strong></td>
                            <td>${p.code}</td>
                            <td style="text-align: right; font-weight: 600; color: var(--color-primary);">R$ ${p.value.toLocaleString('pt-BR', {minimumFractionDigits:2})}</td>
                        </tr>
                    `;
                });
            }
        }

        // 7. Render Product Analysis
        if (showProducts && data.product_analysis) {
            const prod = data.product_analysis;

            // A. Most Quoted Products (Demand)
            const demandBody = document.getElementById("table-products-demand-body");
            demandBody.innerHTML = "";
            if (!prod.most_quoted || prod.most_quoted.length === 0) {
                demandBody.innerHTML = `<tr><td colspan="4" style="text-align:center; color:var(--color-text-muted);">Nenhum dado.</td></tr>`;
            } else {
                prod.most_quoted.forEach(p => {
                    demandBody.innerHTML += `
                        <tr>
                            <td><strong>${p.name}</strong><br><span style="font-size:10px; color:var(--color-text-muted);">Cód: ${p.code}</span></td>
                            <td style="text-align: center;">${p.quotes}</td>
                            <td style="text-align: center;">${p.qty}</td>
                            <td style="text-align: right; font-weight: 600; color: var(--color-primary);">R$ ${p.value.toLocaleString('pt-BR', {minimumFractionDigits:2})}</td>
                        </tr>
                    `;
                });
            }

            // B. Margin Pressure (High Discounts)
            const pressureBody = document.getElementById("table-products-pressure-body");
            pressureBody.innerHTML = "";
            if (!prod.high_discounts || prod.high_discounts.length === 0) {
                pressureBody.innerHTML = `<tr><td colspan="3" style="text-align:center; color:var(--color-text-muted);">Nenhum dado.</td></tr>`;
            } else {
                prod.high_discounts.forEach(p => {
                    pressureBody.innerHTML += `
                        <tr>
                            <td><strong>${p.name}</strong><br><span style="font-size:10px; color:var(--color-text-muted);">Cód: ${p.code}</span></td>
                            <td style="text-align: center; font-weight: 600; color: var(--status-perdida);">${p.requests}</td>
                            <td style="text-align: center; font-weight: 600; color: var(--status-perdida);">${p.avg_discount}%</td>
                        </tr>
                    `;
                });
            }

            // C. Canceled / Rejection Index (Losses)
            const lossBody = document.getElementById("table-products-loss-body");
            lossBody.innerHTML = "";
            if (!prod.high_rejections || prod.high_rejections.length === 0) {
                lossBody.innerHTML = `<tr><td colspan="3" style="text-align:center; color:var(--color-text-muted);">Nenhum dado.</td></tr>`;
            } else {
                prod.high_rejections.forEach(p => {
                    lossBody.innerHTML += `
                        <tr>
                            <td><strong>${p.name}</strong><br><span style="font-size:10px; color:var(--color-text-muted);">Cód: ${p.code}</span></td>
                            <td style="text-align: center; font-weight: 600; color: var(--status-perdida);">${p.lost_quotes}</td>
                            <td style="text-align: center; font-weight: 600;">${p.lost_qty}</td>
                        </tr>
                    `;
                });
            }

            // D. Demand for Missing / Non-existent Products
            const missingBody = document.getElementById("table-products-missing-body");
            if (missingBody) {
                missingBody.innerHTML = "";
                if (!prod.missing_products || prod.missing_products.length === 0) {
                    missingBody.innerHTML = `<tr><td colspan="4" style="text-align:center; color:var(--color-text-muted);">Nenhum produto inexistente solicitado até o momento.</td></tr>`;
                } else {
                    prod.missing_products.forEach(p => {
                        const date = p.updated_at ? new Date(p.updated_at).toLocaleString('pt-BR') : 'Sem data';
                        missingBody.innerHTML += `
                            <tr>
                                <td><strong>${p.code}</strong></td>
                                <td>${p.name}</td>
                                <td style="text-align: center; font-weight: 700; color: var(--status-devolvida);">${p.requests}</td>
                                <td>
                                    <strong>${p.last_requester}</strong><br>
                                    <span style="font-size:11px; color:var(--color-text-muted);">Solicitado em: ${date}</span>
                                </td>
                            </tr>
                        `;
                    });
                }
            }
        }
    }

    function switchAnalysisView() {
        const selector = document.getElementById("analysis-selector");
        const val = selector.value;

        // Hide all views
        document.querySelectorAll(".analysis-view-container").forEach(el => {
            el.style.display = "none";
        });

        if (val === 'checkins') {
            loadAllCheckins();
        }

        // Show selected view
        const targetView = document.getElementById("view-" + val);
        if (targetView) {
            if (val === 'vendedores') {
                targetView.style.display = "grid"; // Chart + table side-by-side grid
            } else {
                targetView.style.display = "block";
            }
        }

        // Update Title text dynamically (stripping leading emojis)
        const activeOptionText = selector.options[selector.selectedIndex].text;
        document.getElementById("analysis-title").innerText = activeOptionText.replace(/^[^\s]+\s+/, '');

        // Fix potential hidden canvas size rendering bugs in Chart.js
        if (val === 'timeline' && chartTimelineInstance) {
            chartTimelineInstance.resize();
            chartTimelineInstance.update();
        }
        if (val === 'status' && chartStatusInstance) {
            chartStatusInstance.resize();
            chartStatusInstance.update();
        }
        if (val === 'vendedores' && chartSellersInstance) {
            chartSellersInstance.resize();
            chartSellersInstance.update();
        }
    }

    async function loadAllCheckins() {
        const tbody = document.getElementById("table-checkins-body");
        tbody.innerHTML = `<tr><td colspan="5" style="text-align:center; padding:30px;"><div style="border: 2px solid rgba(15,81,50,0.1); border-top: 2px solid var(--color-primary); border-radius: 50%; width: 20px; height: 20px; animation: spin 1s linear infinite; margin: 0 auto;"></div></td></tr>`;

        try {
            const res = await fetch("{{ url('/api/v1/checkin/todos') }}");
            const data = await res.json();
            if (data.success) {
                tbody.innerHTML = "";
                if (data.data.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="5" style="text-align:center; color:var(--color-text-muted);">Nenhum registro de check-in de visitas.</td></tr>`;
                    return;
                }
                data.data.forEach(c => {
                    const date = new Date(c.created_at).toLocaleString('pt-BR');
                    tbody.innerHTML += `
                        <tr>
                            <td><strong>${c.usuario.nome}</strong><br><span style="font-size:11px; color:var(--color-text-muted);">${c.usuario.email}</span></td>
                            <td><strong>${c.parceiro.razao_social}</strong><br><span style="font-size:11px; color:var(--color-text-muted);">Sankhya: ${c.parceiro.codigo_sankhya}</span></td>
                            <td>${date}</td>
                            <td><span style="font-family:monospace; font-size:11px; background:#f1f5f9; padding:2px 6px; border-radius:4px;">📍 Lat: ${parseFloat(c.latitude).toFixed(5)}, Lng: ${parseFloat(c.longitude).toFixed(5)}</span></td>
                            <td class="text-center">
                                <a href="https://maps.google.com/?q=${c.latitude},${c.longitude}" target="_blank" class="btn btn-secondary" style="font-size:11px; padding:6px 12px; font-weight:600; text-decoration:none; display:inline-block;">
                                    Ver no Mapa
                                </a>
                            </td>
                        </tr>
                    `;
                });
            } else {
                tbody.innerHTML = `<tr><td colspan="5" style="text-align:center; color:var(--status-perdida);">Erro ao carregar dados: ${data.error}</td></tr>`;
            }
        } catch(e) {
            console.error(e);
            tbody.innerHTML = `<tr><td colspan="5" style="text-align:center; color:var(--status-perdida);">Erro de conexão.</td></tr>`;
        }
    }

    // Main Tab Switching (Minha Fila vs Indicadores BI)
    function switchMainTab(tab) {
        const btnQueue = document.getElementById('tab-btn-queue');
        const btnBi = document.getElementById('tab-btn-bi');
        const contentQueue = document.getElementById('tab-content-queue');
        const contentBi = document.getElementById('tab-content-bi');

        if (tab === 'queue') {
            btnQueue.classList.add('active');
            btnBi.classList.remove('active');
            contentQueue.style.display = 'block';
            contentBi.style.display = 'none';
        } else {
            btnBi.classList.add('active');
            btnQueue.classList.remove('active');
            contentBi.style.display = 'block';
            contentQueue.style.display = 'none';

            // Resize charts to fit current layout
            setTimeout(() => {
                if (chartTimelineInstance) chartTimelineInstance.resize();
                if (chartStatusInstance) chartStatusInstance.resize();
                if (chartSellersInstance) chartSellersInstance.resize();
            }, 100);
        }
    }

    // Load Minha Fila Counts
    async function loadQueueData() {
        try {
            const res = await fetch("{{ url('/api/v1/dashboard/queue') }}");
            const json = await res.json();
            if (json.success && json.data) {
                const q = json.data;
                const approvals = q.approvals || 0;
                const billing = q.billing || 0;
                const expiring = q.expiring_today || 0;
                const semPreco = q.catalog_health?.products_without_price || 0;
                const semEquipe = q.catalog_health?.users_without_team || 0;

                const appEl = document.getElementById('queue-approvals-num');
                if (appEl) appEl.innerText = approvals;

                const billEl = document.getElementById('queue-billing-num');
                if (billEl) billEl.innerText = billing;

                const expEl = document.getElementById('queue-expiring-num');
                if (expEl) expEl.innerText = expiring;

                const healthEl = document.getElementById('queue-health-num');
                if (healthEl) healthEl.innerText = semPreco + semEquipe;

                const healthDesc = document.getElementById('queue-health-desc');
                if (healthDesc) {
                    healthDesc.innerHTML = `<strong>${semPreco} produtos sem preço de tabela</strong> · ${semEquipe} representantes sem equipe.`;
                }

                const totalPending = approvals + billing + expiring;
                const badge = document.getElementById('tab-badge-queue');
                if (badge) {
                    if (totalPending > 0) {
                        badge.innerText = totalPending;
                        badge.style.display = 'inline-block';
                    } else {
                        badge.style.display = 'none';
                    }
                }
            }
        } catch (e) {
            console.error("Erro ao carregar fila:", e);
        }
    }

    document.addEventListener("DOMContentLoaded", () => {
        loadQueueData();
        loadDashboardData();
    });
</script>
@endsection
