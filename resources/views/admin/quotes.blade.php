@extends('layouts.app')

@section('page_title', 'Cotações')

@section('content')
<style>
    /* Custom Styling matching the requested Mockup 100% */
    .page-header-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
        flex-wrap: wrap;
        gap: 15px;
    }
    
    .page-title-group {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .page-title-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        background: #2563eb;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #ffffff;
        font-size: 20px;
        box-shadow: 0 4px 10px rgba(37, 99, 235, 0.2);
    }

    .page-title-text h2 {
        margin: 0;
        font-size: 22px;
        font-weight: 700;
        color: #0f172a;
        letter-spacing: -0.3px;
    }

    .page-title-text p {
        margin: 2px 0 0 0;
        font-size: 13px;
        color: #64748b;
    }

    .header-actions {
        display: flex;
        gap: 10px;
        align-items: center;
    }

    .btn-action-primary {
        background: #2563eb;
        color: #ffffff;
        border: none;
        padding: 10px 20px;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s ease;
        box-shadow: 0 2px 6px rgba(37, 99, 235, 0.25);
    }
    .btn-action-primary:hover {
        background: #1d4ed8;
    }

    .btn-action-outline {
        background: #ffffff;
        color: #334155;
        border: 1px solid #e2e8f0;
        padding: 10px 18px;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s ease;
    }
    .btn-action-outline:hover {
        background: #f8fafc;
        border-color: #cbd5e1;
    }

    /* Filter Bar Layout */
    .filters-bar-card {
        background: #ffffff;
        border-radius: 14px;
        border: 1px solid #e2e8f0;
        padding: 14px 18px;
        margin-bottom: 20px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    }

    .filters-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        align-items: center;
    }

    .filter-input-search {
        position: relative;
        flex: 1 1 240px;
        min-width: 220px;
    }

    .filter-input-search input {
        width: 100%;
        padding: 9px 12px 9px 38px;
        border-radius: 10px;
        border: 1px solid #e2e8f0;
        font-size: 13px;
        color: #1e293b;
        background: #f8fafc;
        outline: none;
        transition: all 0.2s ease;
    }
    .filter-input-search input:focus {
        border-color: #2563eb;
        background: #ffffff;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }

    .filter-input-search .search-icon {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 14px;
    }

    .filter-select {
        padding: 9px 12px;
        border-radius: 10px;
        border: 1px solid #e2e8f0;
        font-size: 13px;
        color: #334155;
        background: #ffffff;
        outline: none;
        cursor: pointer;
        transition: all 0.2s ease;
        min-width: 140px;
    }
    .filter-select:focus {
        border-color: #2563eb;
    }

    .btn-clear-filter {
        color: #2563eb;
        font-size: 13px;
        font-weight: 600;
        background: none;
        border: none;
        cursor: pointer;
        padding: 6px 10px;
        text-decoration: none;
        margin-left: auto;
    }
    .btn-clear-filter:hover {
        text-decoration: underline;
    }

    /* Main Table Container */
    .table-container-card {
        background: #ffffff;
        border-radius: 14px;
        border: 1px solid #e2e8f0;
        padding: 0;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    }

    .table-responsive {
        position: relative;
        overflow-x: auto;
        width: 100%;
        min-height: 200px;
    }

    .table-mockup {
        width: 100%;
        table-layout: fixed;
        border-collapse: collapse;
        text-align: left;
    }

    .table-mockup thead th {
        background: #f8fafc;
        color: #64748b;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 12px 14px;
        border-bottom: 1px solid #e2e8f0;
        white-space: nowrap;
        position: relative;
        user-select: none;
    }

    .col-cotacao-cliente { width: 34%; }
    .col-vendedor        { width: 24%; }
    .col-total           { width: 18%; }
    .col-status          { width: 14%; text-align: center; }
    .col-acoes           { width: 10%; text-align: center; }

    /* Modal Wizard Stepper */
    .wizard-steps-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 10px 16px;
        margin-bottom: 18px;
    }
    .wizard-step-node {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 12.5px;
        font-weight: 600;
        color: #64748b;
        cursor: pointer;
        user-select: none;
    }
    .wizard-step-node.active {
        color: #2563eb;
        font-weight: 700;
    }
    .wizard-step-node.completed {
        color: #16a34a;
    }
    .wizard-step-circle {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 11.5px;
        font-weight: 700;
        background: #e2e8f0;
        color: #475569;
    }
    .wizard-step-node.active .wizard-step-circle {
        background: #2563eb;
        color: #ffffff;
    }
    .wizard-step-node.completed .wizard-step-circle {
        background: #dcfce7;
        color: #16a34a;
    }
    .wizard-step-line {
        flex: 1;
        height: 2px;
        background: #e2e8f0;
        margin: 0 12px;
    }

    /* Quotes Status Tabs */
    .quotes-tabs-wrapper {
        display: flex;
        gap: 8px;
        margin-bottom: 16px;
        overflow-x: auto;
        padding-bottom: 2px;
    }
    .quotes-tab-btn {
        border: 1px solid #e2e8f0;
        background: #ffffff;
        padding: 8px 16px;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 600;
        color: #475569;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s ease;
        white-space: nowrap;
    }
    .quotes-tab-btn:hover {
        background: #f8fafc;
        border-color: #cbd5e1;
    }
    .quotes-tab-btn.active {
        background: #2563eb;
        color: #ffffff;
        border-color: #2563eb;
    }
    .quotes-tab-count {
        background: rgba(0,0,0,0.06);
        color: inherit;
        padding: 2px 7px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 700;
    }
    .quotes-tab-btn.active .quotes-tab-count {
        background: rgba(255,255,255,0.25);
        color: #ffffff;
    }
    .quote-row-clickable {
        cursor: pointer;
        transition: background 0.15s ease;
    }
    .quote-row-clickable:hover td {
        background-color: #f8fafc !important;
    }

    /* Resizable Handle for Column Headers */
    .table-mockup thead th .resizer {
        position: absolute;
        right: 0;
        top: 0;
        height: 100%;
        width: 6px;
        background: transparent;
        cursor: col-resize;
        z-index: 10;
    }
    .table-mockup thead th .resizer:hover,
    .table-mockup thead th .resizer.resizing {
        background: #2563eb;
    }

    .table-mockup tbody tr {
        border-bottom: 1px solid #f1f5f9;
        transition: background 0.15s ease;
    }

    .table-mockup tbody tr:hover {
        background: #f8fafc;
    }

    .table-mockup tbody td {
        padding: 14px 18px;
        vertical-align: middle;
        font-size: 13px;
        color: #1e293b;
    }

    /* Pill Card for Quote Number */
    .quote-pill-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        padding: 8px 12px;
        border-radius: 10px;
        max-width: 100%;
        white-space: nowrap;
    }
    .quote-pill-badge .doc-icon {
        color: #2563eb;
        font-size: 14px;
        flex-shrink: 0;
    }
    .quote-pill-badge .quote-num {
        font-weight: 700;
        color: #0f172a;
        font-size: 12px;
        white-space: nowrap;
    }

    /* User Avatar Circle */
    .user-avatar-circle {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: #dbeafe;
        color: #1d4ed8;
        font-weight: 700;
        font-size: 13px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    /* Date Container */
    .date-text-container {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: #475569;
        font-size: 13px;
        white-space: nowrap;
    }
    .date-text-container .cal-icon {
        color: #94a3b8;
    }

    /* Price Text */
    .price-text-primary {
        font-size: 14px;
        font-weight: 700;
        color: #2563eb;
        white-space: nowrap;
    }

    /* Mockup Badges */
    .badge-status-mockup {
        display: inline-flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
        text-transform: none;
        letter-spacing: 0.1px;
        white-space: nowrap;
    }
    .badge-status-mockup.em-criacao {
        background: #dbeafe;
        color: #1e40af;
    }
    .badge-status-mockup.liberada,
    .badge-status-mockup.aprovada {
        background: #dcfce7;
        color: #15803d;
    }
    .badge-status-mockup.pdf-gerado {
        background: #ccfbf1;
        color: #0f766e;
    }
    .badge-status-mockup.aguardando-gestor {
        background: #fef3c7;
        color: #b45309;
    }
    .badge-status-mockup.com-diretor {
        background: #ffedd5;
        color: #c2410c;
    }
    .badge-status-mockup.faturada {
        background: #d1fae5;
        color: #047857;
    }
    .badge-status-mockup.perdida,
    .badge-status-mockup.recusada {
        background: #fee2e2;
        color: #b91c1c;
    }
    .badge-status-mockup.expirada,
    .badge-status-mockup.cancelada {
        background: #f1f5f9;
        color: #475569;
    }

    .badge-subtext {
        font-size: 10px;
        color: #64748b;
        font-weight: 500;
        margin-top: 2px;
        text-transform: none;
    }

    /* Action Buttons */
    .btn-abrir-row {
        background: #2563eb;
        color: #ffffff;
        border: none;
        padding: 6px 14px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 12px;
        cursor: pointer;
        transition: background 0.15s ease;
        white-space: nowrap;
    }
    .btn-abrir-row:hover {
        background: #1d4ed8;
    }

    .btn-dots-row {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        width: 30px;
        height: 30px;
        border-radius: 8px;
        color: #64748b;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 15px;
        transition: all 0.15s ease;
        flex-shrink: 0;
    }
    .btn-dots-row:hover {
        background: #f8fafc;
        color: #0f172a;
    }

    /* Dropdown Menu for Action Dots */
    .dropdown-dots-wrapper {
        position: relative;
        display: inline-block;
    }
    .dropdown-dots-menu {
        display: none;
        position: absolute;
        right: 0;
        top: 38px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12);
        width: 150px;
        z-index: 250;
        padding: 6px 0;
    }
    .dropdown-dots-menu.show {
        display: block;
    }
    .dropdown-dots-menu.dropup {
        top: auto !important;
        bottom: 36px !important;
        box-shadow: 0 -10px 25px rgba(0, 0, 0, 0.15) !important;
    }
    .dropdown-dots-item {
        display: flex;
        align-items: center;
        gap: 8px;
        width: 100%;
        padding: 8px 14px;
        font-size: 12px;
        color: #334155;
        border: none;
        background: none;
        text-align: left;
        cursor: pointer;
        text-decoration: none;
    }
    .dropdown-dots-item:hover {
        background: #f1f5f9;
        color: #0f172a;
    }
    .dropdown-dots-item.danger {
        color: #ef4444;
    }
    .dropdown-dots-item.danger:hover {
        background: #fef2f2;
    }

    /* Column Customization Modal Panel */
    .column-customizer-modal {
        display: none;
        position: absolute;
        top: 100%;
        right: 0;
        margin-top: 8px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12);
        width: 280px;
        padding: 16px;
        z-index: 300;
    }
    .column-customizer-modal.show {
        display: block;
    }
    .column-customizer-modal h4 {
        margin: 0 0 12px 0;
        font-size: 14px;
        font-weight: 700;
        color: #0f172a;
    }
    .col-toggle-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 10px;
        font-size: 13px;
        color: #334155;
    }
    .col-toggle-item label {
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    /* Footer Pagination */
    .table-footer-pagination {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 16px 20px;
        border-top: 1px solid #f1f5f9;
        background: #ffffff;
    }

    .pagination-btn-square {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        color: #334155;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
    }
    .pagination-btn-square.active {
        background: #2563eb;
        color: #ffffff;
        border-color: #2563eb;
    }
    .pagination-btn-square:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
</style>

<!-- Header Title & Action Buttons -->
<div class="page-header-container">
    <div class="page-title-group">
        <div class="page-title-icon">
            📄
        </div>
        <div class="page-title-text">
            <h2>Cotações</h2>
            <p>Acompanhe e gerencie todas as propostas comerciais</p>
        </div>
    </div>

    <div class="header-actions">
        <button class="btn-action-primary" onclick="openCreateModal()">
            <span>+</span> Nova cotação
        </button>
        <button class="btn-action-outline" onclick="loadQuotes()">
            🔄 Atualizar
        </button>
    </div>
</div>

<!-- Status Tabs with Counts -->
<div class="quotes-tabs-wrapper">
    <button type="button" class="quotes-tab-btn active" id="tab-btn-todas" onclick="setQuoteTab('todas')">
        Todas <span id="tab-cnt-todas" class="quotes-tab-count">0</span>
    </button>
    <button type="button" class="quotes-tab-btn" id="tab-btn-ativas" onclick="setQuoteTab('ativas')">
        Ativas <span id="tab-cnt-ativas" class="quotes-tab-count">0</span>
    </button>
    <button type="button" class="quotes-tab-btn" id="tab-btn-aguardando" onclick="setQuoteTab('aguardando')">
        Aguardando <span id="tab-cnt-aguardando" class="quotes-tab-count">0</span>
    </button>
    <button type="button" class="quotes-tab-btn" id="tab-btn-faturamento" onclick="setQuoteTab('faturamento')">
        Faturamento <span id="tab-cnt-faturamento" class="quotes-tab-count">0</span>
    </button>
    <button type="button" class="quotes-tab-btn" id="tab-btn-encerradas" onclick="setQuoteTab('encerradas')">
        Encerradas <span id="tab-cnt-encerradas" class="quotes-tab-count">0</span>
    </button>
</div>

<!-- Filters Bar -->
<div class="filters-bar-card">
    <div class="filters-grid">
        <div class="filter-input-search">
            <span class="search-icon">🔍</span>
            <input type="text" id="search-input" placeholder="Buscar por Nº, Cliente ou CNPJ..." oninput="filterQuotes()">
        </div>

        <select id="status-filter" class="filter-select" onchange="filterQuotes()" style="display:none;">
            <option value="">Todos os Status</option>
        </select>

        <select id="rep-filter" class="filter-select" onchange="filterQuotes()">
            <option value="">Vendedor</option>
            <!-- Dynamic options -->
        </select>

        <select id="period-filter" class="filter-select" onchange="filterQuotes()">
            <option value="">Período</option>
            <option value="hoje">Hoje</option>
            <option value="7d">Últimos 7 dias</option>
            <option value="30d">Últimos 30 dias</option>
            <option value="mes">Este mês</option>
        </select>

        <select id="team-filter" class="filter-select" onchange="filterQuotes()">
            <option value="">Equipe</option>
            <!-- Dynamic options -->
        </select>

        <button type="button" class="btn-clear-filter" onclick="clearAllFilters()">
            Limpar Filtros
        </button>
    </div>
</div>

<!-- Table Card Container -->
<div class="table-container-card">
    <div class="table-responsive">
        <table class="table-mockup" id="main-quotes-table">
            <thead>
                <tr>
                    <th class="col-cotacao-cliente">COTAÇÃO &amp; CLIENTE</th>
                    <th class="col-vendedor">VENDEDOR &amp; EQUIPE</th>
                    <th class="col-total">VALOR &amp; EMISSÃO</th>
                    <th class="col-status" style="text-align: center;">STATUS</th>
                    <th class="col-acoes" style="text-align: center;">AÇÕES</th>
                </tr>
            </thead>
            <tbody id="quotes-table-body">
                <!-- Dynamic rows -->
            </tbody>
        </table>
    </div>

    <!-- Loading spinner -->
    <div id="loading-spinner" style="text-align: center; padding: 50px 20px;">
        <div style="border: 3px solid rgba(37,99,235,0.1); border-top: 3px solid #2563eb; border-radius: 50%; width: 32px; height: 32px; animation: spin 1s linear infinite; margin: 0 auto;"></div>
    </div>

    <!-- Empty state -->
    <div id="empty-state" style="display: none; text-align: center; padding: 50px 20px; color: #64748b;">
        Nenhuma cotação localizada com os filtros aplicados.
    </div>

    <!-- Pagination Footer -->
    <div class="table-footer-pagination">
        <span id="pagination-info" style="font-size: 13px; color: #64748b;">Mostrando 0 de 0 registros</span>
        <div style="display: flex; gap: 6px; align-items: center;">
            <button class="pagination-btn-square" disabled>&lt;</button>
            <button class="pagination-btn-square active">1</button>
            <button class="pagination-btn-square" disabled>&gt;</button>
        </div>
    </div>
</div>

<!-- Read-only Detail Sheet Drawer -->
<div id="detail-overlay" class="sheet-overlay" onclick="closeDetailModal()"></div>
<div id="detail-modal" class="sheet-drawer" style="width: 600px;">
    <div class="sheet-header">
        <h3 style="margin: 0; color: var(--color-primary);">Detalhes da Cotação <span id="modal-quote-num">...</span></h3>
        <button type="button" class="sheet-close-btn" onclick="closeDetailModal()">&times;</button>
    </div>
    <div class="sheet-body">

        <div class="grid-2" style="gap: 20px; margin-bottom: 20px; font-size: 13px;">
            <div>
                <p><strong>Cliente:</strong> <span id="modal-client">...</span></p>
                <p><strong id="modal-doc-label">CNPJ:</strong> <span id="modal-cnpj">...</span></p>
                <p><strong>Vendedor:</strong> <span id="modal-rep">...</span></p>
                <p><strong>Emissão:</strong> <span id="modal-date">...</span></p>
            </div>
            <div>
                <p><strong>Forma Pagamento:</strong> <span id="modal-payment">...</span></p>
                <p><strong>Prazo de Entrega:</strong> <span id="modal-delivery">...</span></p>
                <p><strong>Frete:</strong> <span id="modal-freight">...</span></p>
                <p><strong>Status Atual:</strong> <span id="modal-status" class="badge-status">...</span><span id="modal-priority" class="badge-status priority" style="display:none; background-color:rgba(239,68,68,0.1); color:#ef4444; border:1px solid rgba(239,68,68,0.2); font-size:11px; font-weight:700; margin-left:8px; padding: 2px 6px; border-radius: 4px;">⚡ GRANDE CONTA</span></p>
            </div>
        </div>

        <h4 style="margin-top: 20px; margin-bottom: 10px; color: var(--color-primary);">Itens da Cotação</h4>
        <table class="table-premium" style="font-size: 12px; margin-bottom: 20px;">
            <thead>
                <tr>
                    <th>Produto</th>
                    <th style="text-align: center;">Qtd</th>
                    <th style="text-align: right;">Sugerido</th>
                    <th style="text-align: right;">Proposto</th>
                    <th style="text-align: center;">Ajuste</th>
                    <th style="text-align: center;">Status Item</th>
                </tr>
            </thead>
            <tbody id="modal-items-body">
                <!-- Dynamic items -->
            </tbody>
        </table>

        <div style="display: flex; justify-content: flex-end; gap: 20px; font-size: 14px; margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
            <div>Subtotal Sugerido: <strong id="modal-subtotal">R$ 0,00</strong></div>
            <div>Desconto Total: <strong id="modal-discount" style="color: var(--status-recusada);">R$ 0,00</strong></div>
            <div>Valor Final: <strong id="modal-total" style="color: var(--color-primary);">R$ 0,00</strong></div>
        </div>

        <h4 style="margin-top: 20px; margin-bottom: 10px; color: var(--color-primary);">Histórico de Auditoria / Workflow</h4>
        <div id="modal-history-list" style="font-size: 12px; display: flex; flex-direction: column; gap: 8px; max-height: 150px; overflow-y: auto; background: #f8f9fa; padding: 12px; border-radius: 8px;">
            <!-- History -->
        </div>

        <div style="display: flex; justify-content: flex-end; margin-top: 24px;">
            <button class="btn btn-outline" onclick="closeDetailModal()">Fechar Visualização</button>
        </div>
    </div>
</div>

<!-- Create Manual Quote Sheet Drawer with 3-Step Wizard -->
<div id="create-overlay" class="sheet-overlay" onclick="closeCreateModal()"></div>
<div id="create-modal" class="sheet-drawer" style="width: 720px; max-width: 95vw;">
    <div class="sheet-header">
        <h3 style="margin: 0; color: var(--color-primary); font-size: 18px; font-weight:700;">Nova Cotação Comercial</h3>
        <button type="button" class="sheet-close-btn" onclick="closeCreateModal()">&times;</button>
    </div>
    <div class="sheet-body" style="padding: 16px 20px;">

        <!-- Wizard Steps Navigation Header -->
        <div class="wizard-steps-header">
            <div class="wizard-step-node active" id="admin-step-node-1" onclick="goToAdminWizardStep(1)">
                <span class="wizard-step-circle">1</span>
                <span>Cliente &amp; Vendedor</span>
            </div>
            <div class="wizard-step-line"></div>
            <div class="wizard-step-node" id="admin-step-node-2" onclick="goToAdminWizardStep(2)">
                <span class="wizard-step-circle">2</span>
                <span>Produtos (<span id="admin-wizard-prod-count">0</span>)</span>
            </div>
            <div class="wizard-step-line"></div>
            <div class="wizard-step-node" id="admin-step-node-3" onclick="goToAdminWizardStep(3)">
                <span class="wizard-step-circle">3</span>
                <span>Condições</span>
            </div>
        </div>

        <form id="create-quote-form" onsubmit="submitManualQuote(event)">

            <!-- ETAPA 1: CLIENTE E VENDEDOR -->
            <div id="admin-wizard-step-1" class="admin-wizard-panel">
                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label" style="font-weight: 600; font-size: 13px;">Cliente (Parceiro Comercial) <span style="color:#ef4444;">*</span></label>
                    <div id="admin-selected-partner-card" style="display:none; background:#f0fdf4; border:1px solid #86efac; border-radius:10px; padding:12px 14px; margin-bottom:8px;">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <div>
                                <div style="font-size:11px; font-weight:700; color:#15803d; text-transform:uppercase; letter-spacing:0.4px;">✓ Cliente Selecionado</div>
                                <div id="admin-sp-name" style="font-weight:700; font-size:14px; color:#166534; margin-top:2px;">-</div>
                                <div id="admin-sp-doc" style="font-size:12px; color:#15803d; margin-top:2px;">-</div>
                            </div>
                            <button type="button" onclick="clearAdminSelectedPartner()" style="background:#dcfce7; color:#15803d; border:1px solid #86efac; padding:6px 14px; border-radius:6px; font-size:12px; font-weight:700; cursor:pointer;">Alterar</button>
                        </div>
                    </div>
                    
                    <div id="admin-partner-search-box">
                        <input type="text" id="admin-partner-search-input" class="form-control" placeholder="🔍 Digite iniciais, razão social, CNPJ ou código do cliente..." oninput="filterAdminPartnerOptions()" style="font-size: 13px;">
                        <div id="admin-partner-search-results" style="max-height:220px; overflow-y:auto; margin-top:6px; display:flex; flex-direction:column; gap:6px; border:1px solid #e2e8f0; border-radius:8px; padding:6px; background:#fff;">
                            <!-- Partner search cards dynamically rendered -->
                        </div>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 20px;" id="rep-group-container">
                    <label for="create-representante" class="form-label" style="font-weight: 600; font-size: 13px;">Representante Comercial Responsável <span style="color:#ef4444;">*</span></label>
                    <input type="text" id="admin-rep-search-input" class="form-control" placeholder="🔍 Filtrar vendedor por nome ou e-mail..." oninput="filterAdminRepSelect()" style="font-size: 12px; margin-bottom: 6px; padding: 7px 10px;">
                    <select id="create-representante" class="form-control" required style="font-size: 13px;">
                        <option value="">Selecione um representante...</option>
                    </select>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px; padding-top: 14px; border-top: 1px solid #f1f5f9;">
                    <button type="button" class="btn btn-outline" onclick="closeCreateModal()">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="validateAdminStep1AndNext()" style="font-weight:700; padding:8px 22px;">
                        Avançar para Produtos &rarr;
                    </button>
                </div>
            </div>

            <!-- ETAPA 2: PRODUTOS DA COTAÇÃO -->
            <div id="admin-wizard-step-2" class="admin-wizard-panel" style="display:none;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                    <div>
                        <h4 style="margin:0; color:var(--color-primary); font-size:15px; font-weight:700;">Itens da Cotação</h4>
                        <span style="font-size:12px; color:#64748b;">Adicione os produtos e defina as quantidades e preços propostos</span>
                    </div>
                    <button type="button" class="btn btn-secondary" style="font-size:12px; padding:7px 14px; font-weight:600;" onclick="addAdminManualProductRow()">+ Adicionar Produto</button>
                </div>

                <div style="background:#f8fafc; padding:12px; border-radius:10px; border:1px solid #e2e8f0; margin-bottom:16px;">
                    <div id="manual-items-container" style="display: flex; flex-direction: column; gap: 10px;">
                        <!-- Dynamic product rows here -->
                    </div>
                    
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-top:14px; padding-top:12px; border-top:1px solid #cbd5e1; font-size:14px;">
                        <span style="font-weight:600; color:#64748b;">Total Previsto:</span>
                        <span id="admin-manual-quote-total" style="color:var(--color-primary); font-weight:800; font-size:17px;">R$ 0,00</span>
                    </div>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 24px; padding-top: 14px; border-top: 1px solid #f1f5f9;">
                    <button type="button" class="btn btn-outline" onclick="goToAdminWizardStep(1)">&larr; Voltar para Cliente</button>
                    <button type="button" class="btn btn-primary" onclick="validateAdminStep2AndNext()" style="font-weight:700; padding:8px 22px;">
                        Avançar para Condições &rarr;
                    </button>
                </div>
            </div>

            <!-- ETAPA 3: CONDIÇÕES COMERCIAIS & FINALIZAÇÃO -->
            <div id="admin-wizard-step-3" class="admin-wizard-panel" style="display:none;">
                <div class="grid-2" style="gap: 12px; margin-bottom: 12px;">
                    <div class="form-group" style="margin-bottom:0;">
                        <label for="create-pagamento" class="form-label" style="font-weight: 600; font-size: 13px;">Forma de Pagamento</label>
                        <input type="text" id="create-pagamento" class="form-control" placeholder="Ex: 30/60 dias" value="A combinar" style="font-size: 13px;">
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label for="create-prazo" class="form-label" style="font-weight: 600; font-size: 13px;">Prazo de Entrega</label>
                        <input type="text" id="create-prazo" class="form-control" placeholder="Ex: 3 dias" value="3 dias" style="font-size: 13px;">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 12px;">
                    <label for="create-frete" class="form-label" style="font-weight: 600; font-size: 13px;">Tipo de Frete</label>
                    <select id="create-frete" class="form-control" style="font-size: 13px;">
                        <option value="CIF">CIF (Frete por conta do emitente)</option>
                        <option value="FOB">FOB (Frete por conta do destinatário)</option>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 16px;">
                    <label for="create-obs-cliente" class="form-label" style="font-weight: 600; font-size: 13px;">Observação Cliente / Instruções</label>
                    <textarea id="create-obs-cliente" class="form-control" rows="2" placeholder="Ex: Horário de recebimento das 8h às 17h, nota fiscal com pedido..." style="font-size: 13px;"></textarea>
                </div>

                <!-- Resumo da Cotação -->
                <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:10px; padding:12px 16px; margin-bottom:16px;">
                    <div style="font-weight:700; color:#166534; font-size:13px; margin-bottom:6px;">Resumo da Proposta Comercial</div>
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:8px; font-size:12px; color:#15803d;">
                        <div>Cliente: <strong id="admin-summary-client">-</strong></div>
                        <div>Vendedor: <strong id="admin-summary-rep">-</strong></div>
                        <div>Total de Itens: <strong id="admin-summary-items">0 item(ns)</strong></div>
                        <div>Valor Total: <strong id="admin-summary-total" style="font-size:13px; color:#14532d;">R$ 0,00</strong></div>
                    </div>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 24px; padding-top: 14px; border-top: 1px solid #f1f5f9;">
                    <button type="button" class="btn btn-outline" onclick="goToAdminWizardStep(2)">&larr; Voltar para Produtos</button>
                    <button type="submit" class="btn btn-primary" style="font-weight:700; padding:9px 24px;">
                        💾 Salvar e Gerar Cotação
                    </button>
                </div>
            </div>

        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const API_URL = "/api/v1";
    const CURRENT_USER = @json(auth()->user());
    let rawQuotes = [];
    let metaProducts = [];

    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        return String(text)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    let currentAdminWizardStep = 1;

    function goToAdminWizardStep(step) {
        if (step === 2 && !adminSelectedPartner) {
            showToast("Por favor, selecione um Cliente na Etapa 1 antes de prosseguir.", "warning");
            return;
        }
        if (step === 3) {
            const prodRows = document.querySelectorAll("#manual-items-container .admin-prod-row-item .admin-item-prod-id");
            let validCount = 0;
            prodRows.forEach(el => { if (el.value) validCount++; });
            if (validCount === 0) {
                showToast("Adicione pelo menos 1 produto na Etapa 2 antes de avançar para as Condições.", "warning");
                return;
            }
        }

        currentAdminWizardStep = step;
        [1, 2, 3].forEach(s => {
            const panel = document.getElementById(`admin-wizard-step-${s}`);
            const node = document.getElementById(`admin-step-node-${s}`);
            if (panel) panel.style.display = s === step ? 'block' : 'none';
            if (node) {
                node.classList.remove('active', 'completed');
                if (s === step) node.classList.add('active');
                else if (s < step) node.classList.add('completed');
            }
        });

        if (step === 3) {
            updateAdminSummary();
        }
    }

    function validateAdminStep1AndNext() {
        if (!adminSelectedPartner) {
            showToast("Por favor, selecione um Cliente (Parceiro Comercial) para continuar.", "warning");
            return;
        }
        const repVal = document.getElementById("create-representante").value;
        if (!repVal) {
            showToast("Por favor, selecione o Representante Comercial responsável.", "warning");
            return;
        }
        goToAdminWizardStep(2);
    }

    function validateAdminStep2AndNext() {
        const rows = document.querySelectorAll("#manual-items-container .admin-prod-row-item");
        let validCount = 0;
        let hasInvalid = false;
        rows.forEach(r => {
            const prodId = r.querySelector(".admin-item-prod-id")?.value;
            const qtd = parseInt(r.querySelector(".admin-item-qtd")?.value || 0);
            const price = parseFloat(r.querySelector(".admin-item-price")?.value || 0);
            if (prodId && qtd > 0 && price > 0) {
                validCount++;
            } else if (prodId || price > 0) {
                hasInvalid = true;
            }
        });

        if (validCount === 0) {
            showToast("Adicione pelo menos 1 produto válido com quantidade e preço maior que zero.", "warning");
            return;
        }
        if (hasInvalid) {
            showToast("Existem produtos na lista com quantidade ou preço inválidos. Por favor, verifique.", "warning");
            return;
        }
        goToAdminWizardStep(3);
    }

    function updateAdminSummary() {
        const clientEl = document.getElementById("admin-summary-client");
        const repEl = document.getElementById("admin-summary-rep");
        const itemsEl = document.getElementById("admin-summary-items");
        const totalEl = document.getElementById("admin-summary-total");

        const repSelect = document.getElementById("create-representante");
        const selectedRepName = repSelect && repSelect.selectedIndex >= 0 ? repSelect.options[repSelect.selectedIndex].text : '-';

        let itemCount = 0;
        document.querySelectorAll("#manual-items-container .admin-prod-row-item").forEach(r => {
            if (r.querySelector(".admin-item-prod-id")?.value) itemCount++;
        });

        const totalText = document.getElementById("admin-manual-quote-total")?.innerText || 'R$ 0,00';

        if (clientEl) clientEl.innerText = adminSelectedPartner ? adminSelectedPartner.razao_social : '-';
        if (repEl) repEl.innerText = selectedRepName;
        if (itemsEl) itemsEl.innerText = `${itemCount} item(ns)`;
        if (totalEl) totalEl.innerText = totalText;
    }

    function normalizeStr(str) {
        if (!str) return '';
        try {
            return String(str)
                .normalize("NFD")
                .replace(/[\u0300-\u036f]/g, "")
                .toLowerCase();
        } catch(e) {
            return String(str).toLowerCase();
        }
    }

    document.addEventListener("DOMContentLoaded", () => {
        initColumnPreferences();
        initColumnResizable();
        loadQuotes();
        
        // Hide open dropdown menus on outside click
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.dropdown-dots-wrapper')) {
                document.querySelectorAll('.dropdown-dots-menu').forEach(m => m.classList.remove('show'));
            }
            if (!e.target.closest('#column-customizer-panel') && !e.target.closest('[onclick="toggleColumnCustomizer(event)"]')) {
                const panel = document.getElementById('column-customizer-panel');
                if (panel) panel.classList.remove('show');
            }
        });
    });

    /* --- Column Customization & LocalStorage Engine --- */
    function toggleColumnCustomizer(e) {
        e.stopPropagation();
        const panel = document.getElementById('column-customizer-panel');
        panel.classList.toggle('show');
    }

    function toggleColumnVisibility(colClass, visible) {
        const elements = document.querySelectorAll(`.${colClass}`);
        elements.forEach(el => {
            el.style.display = visible ? '' : 'none';
        });

        saveColumnPreferences();
    }

    function saveColumnPreferences() {
        const config = {};
        document.querySelectorAll('.column-customizer-modal input[data-col]').forEach(cb => {
            const col = cb.getAttribute('data-col');
            const th = document.querySelector(`th.${col}`);
            config[col] = {
                visible: cb.checked,
                width: th ? th.style.width : null
            };
        });
        localStorage.setItem('zecotacao_quotes_columns_config', JSON.stringify(config));
    }

    function initColumnPreferences() {
        const saved = localStorage.getItem('zecotacao_quotes_columns_config');
        if (!saved) return;

        try {
            const config = JSON.parse(saved);
            Object.keys(config).forEach(colClass => {
                const item = config[colClass];
                const cb = document.querySelector(`.column-customizer-modal input[data-col="${colClass}"]`);
                if (cb) {
                    cb.checked = item.visible;
                }
                const th = document.querySelector(`th.${colClass}`);
                if (th && item.width) {
                    th.style.width = item.width;
                }
                toggleColumnVisibility(colClass, item.visible);
            });
        } catch (e) {
            console.error("Error loading column preferences", e);
        }
    }

    function resetColumnPreferences() {
        localStorage.removeItem('zecotacao_quotes_columns_config');
        document.querySelectorAll('.column-customizer-modal input[data-col]').forEach(cb => {
            cb.checked = true;
            const col = cb.getAttribute('data-col');
            toggleColumnVisibility(col, true);
        });

        // Default widths
        const ths = {
            'col-numero': '140px',
            'col-parceiro': '250px',
            'col-vendedor': '170px',
            'col-emissao': '110px',
            'col-total': '130px',
            'col-status': '140px',
            'col-acoes': '135px'
        };
        Object.keys(ths).forEach(c => {
            const th = document.querySelector(`th.${c}`);
            if (th) th.style.width = ths[c];
        });
    }

    /* --- Mouse Drag Column Resizing Engine --- */
    function initColumnResizable() {
        const table = document.getElementById('main-quotes-table');
        if (!table) return;

        const cols = table.querySelectorAll('th');
        cols.forEach(th => {
            const resizer = th.querySelector('.resizer');
            if (!resizer) return;

            let startX, startWidth;

            resizer.addEventListener('mousedown', (e) => {
                e.preventDefault();
                startX = e.pageX;
                startWidth = th.offsetWidth;
                resizer.classList.add('resizing');

                const onMouseMove = (moveEvent) => {
                    const diff = moveEvent.pageX - startX;
                    const newWidth = Math.max(60, startWidth + diff);
                    th.style.width = `${newWidth}px`;
                };

                const onMouseUp = () => {
                    resizer.classList.remove('resizing');
                    document.removeEventListener('mousemove', onMouseMove);
                    document.removeEventListener('mouseup', onMouseUp);
                    saveColumnPreferences();
                };

                document.addEventListener('mousemove', onMouseMove);
                document.addEventListener('mouseup', onMouseUp);
            });
        });
    }

    async function loadQuotes() {
        try {
            document.getElementById("loading-spinner").style.display = "block";
            const res = await fetch(`${API_URL}/cotacoes/todas`);
            if (res.status === 401 || res.status === 403) {
                window.location.href = "{{ url('/login') }}";
                return;
            }

            const data = await res.json();
            document.getElementById("loading-spinner").style.display = "none";

            if (data.success) {
                rawQuotes = data.data;
                populateDynamicFilterSelects(rawQuotes);
                updateTabCounts(rawQuotes);
                filterQuotes();
            } else {
                showToast("Erro ao buscar cotações: " + data.error, "error");
            }
        } catch (e) {
            console.error(e);
            document.getElementById("loading-spinner").style.display = "none";
            showToast("Erro ao conectar no servidor.", "error");
        }
    }

    let currentQuoteTab = 'todas';

    function setQuoteTab(tab) {
        currentQuoteTab = tab;
        document.querySelectorAll('.quotes-tab-btn').forEach(btn => btn.classList.remove('active'));
        const activeBtn = document.getElementById(`tab-btn-${tab}`);
        if (activeBtn) activeBtn.classList.add('active');
        filterQuotes();
    }

    function updateTabCounts(list) {
        let cntTodas = list.length;
        let cntAtivas = 0;
        let cntAguardando = 0;
        let cntFaturamento = 0;
        let cntEncerradas = 0;

        list.forEach(q => {
            const s = q.status;
            if (['EM_CRIACAO', 'AGUARDANDO_GESTOR', 'COM_DIRETOR', 'APROVADA', 'PDF_GERADO'].includes(s)) cntAtivas++;
            if (['AGUARDANDO_GESTOR', 'COM_DIRETOR'].includes(s)) cntAguardando++;
            if (['PDF_GERADO', 'FATURADA'].includes(s)) cntFaturamento++;
            if (['FATURADA', 'PERDIDA', 'RECUSADA', 'EXPIRADA', 'CANCELADA'].includes(s)) cntEncerradas++;
        });

        const elTodas = document.getElementById('tab-cnt-todas');
        const elAtivas = document.getElementById('tab-cnt-ativas');
        const elAguardando = document.getElementById('tab-cnt-aguardando');
        const elFaturamento = document.getElementById('tab-cnt-faturamento');
        const elEncerradas = document.getElementById('tab-cnt-encerradas');

        if (elTodas) elTodas.innerText = cntTodas;
        if (elAtivas) elAtivas.innerText = cntAtivas;
        if (elAguardando) elAguardando.innerText = cntAguardando;
        if (elFaturamento) elFaturamento.innerText = cntFaturamento;
        if (elEncerradas) elEncerradas.innerText = cntEncerradas;
    }

    function populateDynamicFilterSelects(list) {
        const repSelect = document.getElementById("rep-filter");
        const teamSelect = document.getElementById("team-filter");

        const repsMap = new Map();
        const teamsMap = new Map();

        list.forEach(q => {
            if (q.representante) {
                repsMap.set(q.representante.id, q.representante.nome);
                if (q.representante.equipe) {
                    teamsMap.set(q.representante.equipe.id, q.representante.equipe.nome);
                }
            }
        });

        // Reps options
        repSelect.innerHTML = '<option value="">Vendedor</option>';
        repsMap.forEach((name, id) => {
            repSelect.innerHTML += `<option value="${id}">${name}</option>`;
        });

        // Teams options
        teamSelect.innerHTML = '<option value="">Equipe</option>';
        teamsMap.forEach((name, id) => {
            teamSelect.innerHTML += `<option value="${id}">${name}</option>`;
        });
    }

    function renderQuotes(list) {
        const body = document.getElementById("quotes-table-body");
        const paginationInfo = document.getElementById("pagination-info");
        body.innerHTML = "";

        paginationInfo.innerText = `Mostrando ${list.length} de ${rawQuotes.length} registros`;

        if (list.length === 0) {
            document.getElementById("empty-state").style.display = "block";
            return;
        } else {
            document.getElementById("empty-state").style.display = "none";
        }

        list.forEach(q => {
            const date = new Date(q.created_at).toLocaleDateString('pt-BR');
            const statusClass = (q.status || '').toLowerCase().replace(/_/g, '-');
            let statusText = 'Em criação';
            let subText = '';

            switch (q.status) {
                case 'EM_CRIACAO':
                    statusText = 'Em criação';
                    break;
                case 'APROVADA':
                    statusText = 'Aprovada';
                    subText = 'Pronta p/ PDF';
                    break;
                case 'PDF_GERADO':
                    statusText = 'PDF gerado';
                    subText = 'Apto faturamento';
                    break;
                case 'AGUARDANDO_GESTOR':
                    statusText = 'Aguardando gestor';
                    break;
                case 'COM_DIRETOR':
                    statusText = 'Aguardando diretor';
                    break;
                case 'FATURADA':
                    statusText = 'Pedido faturado';
                    break;
                case 'PERDIDA':
                    statusText = 'Perdida';
                    break;
                case 'RECUSADA':
                    statusText = 'Recusada';
                    break;
                case 'EXPIRADA':
                    statusText = 'Expirada';
                    break;
                case 'CANCELADA':
                    statusText = 'Cancelada';
                    break;
                default:
                    statusText = q.status ? q.status.replace(/_/g, ' ').toLowerCase().replace(/^\w/, c => c.toUpperCase()) : 'Em criação';
                    break;
            }

            const rawRepName = q.representante ? q.representante.nome : 'Sem Vendedor';
            const rawTeamName = (q.representante && q.representante.equipe) ? q.representante.equipe.nome : 'Sem Equipe';
            const cleanRepName = rawRepName.replace(/<[^>]*>/g, '').trim();
            const initial = cleanRepName.charAt(0).toUpperCase() || 'S';

            const repNameEscaped = escapeHtml(rawRepName);
            const teamNameEscaped = escapeHtml(rawTeamName);

            const p = q.parceiro || {};
            const clientName = p.razao_social || p.nome_fantasia || 'Cliente Sem Razão';
            const clientDoc = p.cnpj || p.cnpj_cpf || '';
            const pUf = (p.uf === '2' || (p.cidade && p.cidade.trim().toUpperCase() === 'UBERLANDIA')) ? 'MG' : (p.uf || '');
            const clientCity = (p.cidade || pUf) ? `${p.cidade || ''}${pUf ? '/' + pUf : ''}` : '';
            const clientSub = [clientDoc, clientCity].filter(Boolean).join(' • ');

            const clientNameEscaped = escapeHtml(clientName);
            const clientSubEscaped = escapeHtml(clientSub || ('Cód: ' + (p.codigo_sankhya || 'N/A')));
            const quoteNumEscaped = escapeHtml(q.numero || ('#' + q.id));

            body.innerHTML += `
                <tr class="quote-row-clickable" onclick="window.location.href='{{ url('/cotacoes/id') }}/${q.id}'" title="Clique para abrir a cotação">
                    <td class="col-cotacao-cliente">
                        <div style="display:flex; align-items:flex-start; gap:8px;">
                            <div class="quote-pill-badge" style="margin-top:2px; flex-shrink:0;">
                                <span class="quote-num">${quoteNumEscaped}</span>
                            </div>
                            <div style="min-width:0; overflow:hidden;">
                                <div style="font-weight:700; color:#0f172a; font-size:13px; line-height:1.3; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="${clientNameEscaped}">${clientNameEscaped}</div>
                                <div style="font-size:11px; color:#64748b; margin-top:2px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">${clientSubEscaped}</div>
                            </div>
                        </div>
                    </td>
                    <td class="col-vendedor">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <div class="user-avatar-circle">${initial}</div>
                            <div style="min-width:0; overflow:hidden;">
                                <div style="font-weight: 700; color: #0f172a; font-size:13px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="${repNameEscaped}">${repNameEscaped}</div>
                                <div style="font-size: 11px; color: #64748b; margin-top:2px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">${teamNameEscaped}</div>
                            </div>
                        </div>
                    </td>
                    <td class="col-total">
                        <div>
                            <span class="price-text-primary">R$ ${parseFloat(q.total).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</span>
                            <div class="date-text-container" style="font-size:11px; margin-top:2px; color:#64748b;">
                                <span>${date}</span>
                            </div>
                        </div>
                    </td>
                    <td class="col-status text-center">
                        <div class="badge-status-mockup ${statusClass}">
                            <span>${statusText}</span>
                            ${subText ? `<span class="badge-subtext">${subText}</span>` : ''}
                        </div>
                    </td>
                    <td class="col-acoes text-center" onclick="event.stopPropagation()">
                        <div style="display: flex; align-items: center; justify-content: center; gap:6px;">
                            <a href="{{ url('/cotacoes/id') }}/${q.id}" class="btn-abrir-row" style="text-decoration:none;" title="Abrir Cotação">Abrir</a>
                            <div class="dropdown-dots-wrapper">
                                <button class="btn-dots-row" onclick="toggleDotsMenu(event, ${q.id})" title="Mais Ações">⋮</button>
                                <div class="dropdown-dots-menu" id="dots-menu-${q.id}">
                                    <a href="{{ url('/cotacoes/id') }}/${q.id}" class="dropdown-dots-item">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:6px;"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                        Ver / Editar
                                    </a>
                                    <button type="button" class="dropdown-dots-item" onclick="handleDetailClick(${q.id}, '${q.status}')">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:6px;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                        Resumo Rápido
                                    </button>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            `;
        });

        // Re-apply column visibility and widths after rendering
        initColumnPreferences();
    }

    function toggleDotsMenu(event, id) {
        event.stopPropagation();
        const btn = event.currentTarget;
        const menu = document.getElementById(`dots-menu-${id}`);
        const td = menu ? menu.closest('td') : null;

        // Close all other open action menus
        document.querySelectorAll('.dropdown-dots-menu').forEach(m => {
            if (m !== menu) {
                m.classList.remove('show', 'dropup');
                if (m.closest('td')) m.closest('td').classList.remove('active-dropdown-cell');
            }
        });

        if (!menu) return;
        const willShow = !menu.classList.contains('show');

        if (willShow) {
            menu.classList.add('show');
            if (td) td.classList.add('active-dropdown-cell');

            // Detect if menu should open upwards (dropup) when near table bottom
            const btnRect = btn.getBoundingClientRect();
            const container = document.querySelector('.table-responsive');
            if (container) {
                const containerRect = container.getBoundingClientRect();
                const spaceBelow = containerRect.bottom - btnRect.bottom;
                if (spaceBelow < 140) {
                    menu.classList.add('dropup');
                } else {
                    menu.classList.remove('dropup');
                }
            }
        } else {
            menu.classList.remove('show', 'dropup');
            if (td) td.classList.remove('active-dropdown-cell');
        }
    }

    // Global listener to close open menus when clicking anywhere outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.dropdown-dots-wrapper')) {
            document.querySelectorAll('.dropdown-dots-menu').forEach(m => {
                m.classList.remove('show', 'dropup');
                if (m.closest('td')) m.closest('td').classList.remove('active-dropdown-cell');
            });
        }
    });

    function filterQuotes() {
        const search = document.getElementById("search-input").value.toLowerCase();
        const repId = document.getElementById("rep-filter").value;
        const period = document.getElementById("period-filter").value;
        const teamId = document.getElementById("team-filter").value;

        const now = new Date();

        const filtered = rawQuotes.filter(q => {
            // Tab filter
            let matchesTab = true;
            if (currentQuoteTab === 'ativas') {
                matchesTab = ['EM_CRIACAO', 'AGUARDANDO_GESTOR', 'COM_DIRETOR', 'APROVADA', 'PDF_GERADO'].includes(q.status);
            } else if (currentQuoteTab === 'aguardando') {
                matchesTab = ['AGUARDANDO_GESTOR', 'COM_DIRETOR'].includes(q.status);
            } else if (currentQuoteTab === 'faturamento') {
                matchesTab = ['PDF_GERADO', 'FATURADA'].includes(q.status);
            } else if (currentQuoteTab === 'encerradas') {
                matchesTab = ['FATURADA', 'PERDIDA', 'RECUSADA', 'EXPIRADA', 'CANCELADA'].includes(q.status);
            }

            // Representative match
            const matchesRep = repId === "" || q.representante_id == repId;

            // Team match
            const matchesTeam = teamId === "" || (q.representante && q.representante.equipe_id == teamId);

            // Search text match
            const num = (q.numero || '').toLowerCase();
            const client = (q.parceiro ? q.parceiro.razao_social : '').toLowerCase();
            const rep = (q.representante ? q.representante.nome : '').toLowerCase();
            const matchesSearch = search === "" || num.includes(search) || client.includes(search) || rep.includes(search);

            // Period match
            let matchesPeriod = true;
            if (period !== "") {
                const qDate = new Date(q.created_at);
                const diffTime = Math.abs(now - qDate);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

                if (period === 'hoje') {
                    matchesPeriod = qDate.toDateString() === now.toDateString();
                } else if (period === '7d') {
                    matchesPeriod = diffDays <= 7;
                } else if (period === '30d') {
                    matchesPeriod = diffDays <= 30;
                } else if (period === 'mes') {
                    matchesPeriod = qDate.getMonth() === now.getMonth() && qDate.getFullYear() === now.getFullYear();
                }
            }

            return matchesTab && matchesRep && matchesTeam && matchesSearch && matchesPeriod;
        });

        renderQuotes(filtered);
    }

    function clearAllFilters() {
        document.getElementById("search-input").value = "";
        document.getElementById("status-filter").value = "";
        document.getElementById("rep-filter").value = "";
        document.getElementById("period-filter").value = "";
        document.getElementById("team-filter").value = "";
        setQuoteTab('todas');
    }

    // Modal and Actions handlers
    function handleDetailClick(id, status) {
        const quote = rawQuotes.find(q => q.id == id);
        if (!quote) return;

        const clientName = quote.parceiro ? (quote.parceiro.razao_social || quote.parceiro.nome_fantasia || 'Cliente Sem Nome') : 'Cliente Não Identificado';
        const docVal = (quote.parceiro && (quote.parceiro.cnpj || quote.parceiro.cnpj_cpf)) ? (quote.parceiro.cnpj || quote.parceiro.cnpj_cpf) : 'N/A';
        const cleanDoc = docVal.replace(/\D/g, '');
        const docLabel = cleanDoc.length === 11 ? 'CPF:' : 'CNPJ:';
        const docLabelEl = document.getElementById("modal-doc-label");
        if (docLabelEl) docLabelEl.innerText = docLabel;
        const repName = quote.representante ? quote.representante.nome : 'Sem Vendedor';

        document.getElementById("modal-quote-num").innerText = quote.numero || ('#' + quote.id);
        document.getElementById("modal-client").innerText = clientName;
        document.getElementById("modal-cnpj").innerText = docVal;
        document.getElementById("modal-rep").innerText = repName;
        document.getElementById("modal-date").innerText = quote.created_at ? new Date(quote.created_at).toLocaleDateString('pt-BR') : 'N/A';

        document.getElementById("modal-payment").innerText = quote.condicao_pagamento || 'A combinar';
        document.getElementById("modal-delivery").innerText = quote.prazo_entrega || '3 dias';
        document.getElementById("modal-freight").innerText = quote.tipo_frete || 'CIF';

        const statusText = quote.status === 'APROVADA' ? 'Aprovada (Pendente PDF)' : (quote.status === 'PDF_GERADO' ? 'PDF Gerado' : (quote.status ? quote.status.replace(/_/g, ' ') : 'N/A'));
        document.getElementById("modal-status").innerText = statusText;

        const priorityBadge = document.getElementById("modal-priority");
        if (quote.prioridade) {
            priorityBadge.style.display = 'inline-block';
        } else {
            priorityBadge.style.display = 'none';
        }

        // Render Items
        const itemsBody = document.getElementById("modal-items-body");
        itemsBody.innerHTML = "";
        (quote.itens || []).forEach(item => {
            const desc = item.produto ? item.produto.descricao : 'Item sem cadastro';
            const vlrSug = parseFloat(item.preco_tabela || 0);
            const vlrProp = parseFloat(item.preco_unitario || 0);

            let diffPct = 0;
            if (vlrSug > 0) {
                diffPct = ((vlrProp - vlrSug) / vlrSug) * 100;
            }

            let adjColor = diffPct < 0 ? '#ef4444' : '#10b981';
            let adjText = diffPct.toLocaleString('pt-BR', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) + '%';

            itemsBody.innerHTML += `
                <tr>
                    <td><strong>${desc}</strong></td>
                    <td class="text-center">${item.quantidade}</td>
                    <td class="text-right">R$ ${vlrSug.toLocaleString('pt-BR', {minimumFractionDigits:2})}</td>
                    <td class="text-right">R$ ${vlrProp.toLocaleString('pt-BR', {minimumFractionDigits:2})}</td>
                    <td class="text-center" style="color:${adjColor}; font-weight:bold;">${adjText}</td>
                    <td class="text-center"><span class="badge-status ${item.status ? item.status.toLowerCase() : 'pendente'}">${item.status || 'APROVADO'}</span></td>
                </tr>
            `;
        });

        document.getElementById("modal-subtotal").innerText = "R$ " + parseFloat(quote.subtotal || quote.total || 0).toLocaleString('pt-BR', {minimumFractionDigits:2});
        document.getElementById("modal-discount").innerText = "R$ " + parseFloat(quote.desconto_total || 0).toLocaleString('pt-BR', {minimumFractionDigits:2});
        document.getElementById("modal-total").innerText = "R$ " + parseFloat(quote.total || 0).toLocaleString('pt-BR', {minimumFractionDigits:2});

        // Open drawer
        document.getElementById("detail-overlay").classList.add("active", "open");
        document.getElementById("detail-modal").classList.add("active", "open");
    }

    function closeDetailModal() {
        document.getElementById("detail-overlay").classList.remove("active", "open");
        document.getElementById("detail-modal").classList.remove("active", "open");
    }

    async function deleteQuote(id) {
        const confirmed = await appConfirmModal('Excluir Cotação', 'Tem certeza que deseja excluir esta cotação? Esta ação é irreversível.', 'Excluir Cotação', true);
        if (!confirmed) {
            return;
        }

        try {
            const res = await fetch(`${API_URL}/cotacoes/${id}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });

            const data = await res.json();
            if (data.success) {
                showToast("Cotação excluída com sucesso!", "success");
                loadQuotes();
            } else {
                showToast("Erro ao excluir: " + (data.error || data.message), "error");
            }
        } catch (e) {
            console.error(e);
            showToast("Erro de conexão ao excluir a cotação.", "error");
        }
    }

    let adminPartnersList = [];
    let adminSelectedPartner = null;
    let adminPartnerSearchTimer = null;
    let adminProductsList = [];
    let adminRepsList = [];
    let adminRowCounter = 0;
    let adminProdRowTimers = {};

    function filterAdminRepSelect() {
        const query = (document.getElementById("admin-rep-search-input")?.value || "").toLowerCase().trim();
        const rSelect = document.getElementById("create-representante");
        if (!rSelect) return;

        const currentVal = rSelect.value;
        rSelect.innerHTML = '<option value="">Selecione um representante...</option>';

        const filtered = adminRepsList.filter(r => {
            const name = (r.nome || "").toLowerCase();
            const email = (r.email || "").toLowerCase();
            const papel = (r.papel || "").toLowerCase();
            return !query || name.includes(query) || email.includes(query) || papel.includes(query);
        });

        filtered.forEach(r => {
            const opt = document.createElement("option");
            opt.value = r.id;
            opt.textContent = `${r.nome} (${r.email || r.papel})`;
            if (r.id == currentVal) opt.selected = true;
            rSelect.appendChild(opt);
        });
    }

    function openCreateModal() {
        adminSelectedPartner = null;
        adminPartnersList = [];
        adminProductsList = [];
        adminRepsList = [];
        adminRowCounter = 0;

        const form = document.getElementById("create-quote-form");
        if (form) form.reset();
        const repSearch = document.getElementById("admin-rep-search-input");
        if (repSearch) repSearch.value = "";
        clearAdminSelectedPartner();

        const container = document.getElementById("manual-items-container");
        if (container) container.innerHTML = "";

        goToAdminWizardStep(1);

        document.getElementById("create-overlay").classList.add("active", "open");
        document.getElementById("create-modal").classList.add("active", "open");
        loadAdminModalData();
    }

    function closeCreateModal() {
        document.getElementById("create-overlay").classList.remove("active", "open");
        document.getElementById("create-modal").classList.remove("active", "open");
    }

    async function loadAdminModalData() {
        try {
            const container = document.getElementById("admin-partner-search-results");
            if (container) {
                container.innerHTML = '<div style="font-size:12px; color:var(--color-primary); text-align:center; padding:10px; font-weight:600;">🔄 Carregando clientes...</div>';
            }

            const rSelect = document.getElementById("create-representante");
            if (rSelect) {
                rSelect.innerHTML = '<option value="">🔄 Carregando vendedores...</option>';
            }

            const [pRes, rRes, prodRes] = await Promise.all([
                fetch(`${API_URL}/clientes?limit=50`),
                fetch(`${API_URL}/usuarios`),
                fetch(`${API_URL}/produtos?limit=40`)
            ]);

            const pData = await pRes.json();
            const rData = await rRes.json();
            const prodData = await prodRes.json();

            // 1. Process Partners (Clientes)
            if (pData.success && Array.isArray(pData.data)) {
                adminPartnersList = pData.data;
            } else if (Array.isArray(pData)) {
                adminPartnersList = pData;
            } else {
                adminPartnersList = [];
            }
            renderAdminPartnerOptions(adminPartnersList);

            // 2. Process Representatives (Vendedores)
            let users = [];
            if (rData.success && rData.data) {
                if (Array.isArray(rData.data)) {
                    users = rData.data;
                } else if (Array.isArray(rData.data.users)) {
                    users = rData.data.users;
                }
            } else if (Array.isArray(rData)) {
                users = rData;
            }

            adminRepsList = users.filter(u => u.papel === 'representante' || u.papel === 'gerente');
            if (rSelect) {
                rSelect.innerHTML = '<option value="" selected>Selecione um representante...</option>';
                adminRepsList.forEach(r => {
                    rSelect.innerHTML += `<option value="${r.id}">${r.nome} (${r.email || r.papel})</option>`;
                });
            }

            // 3. Process Products
            if (prodData.success && Array.isArray(prodData.data)) {
                adminProductsList = prodData.data;
            } else if (Array.isArray(prodData)) {
                adminProductsList = prodData;
            } else {
                adminProductsList = [];
            }

            // Add first product row if container is empty
            const itemsContainer = document.getElementById("manual-items-container");
            if (itemsContainer && itemsContainer.children.length === 0) {
                addAdminManualProductRow();
            }

        } catch(e) {
            console.error("Error loading admin modal data:", e);
        }
    }

    function renderAdminPartnerOptions(list, isSearching = false) {
        const container = document.getElementById("admin-partner-search-results");
        if (!container) return;

        if (adminSelectedPartner) {
            container.style.display = "none";
            return;
        } else {
            container.style.display = "flex";
        }

        const inputEl = document.getElementById("admin-partner-search-input");
        const query = inputEl ? inputEl.value.trim() : "";

        if (!list || list.length === 0) {
            container.innerHTML = `
                <div style="text-align:center; padding:12px; background:#f8fafc; border-radius:8px; border:1px dashed var(--color-border); font-size:12px; color:var(--color-text-muted);">
                    ${query ? (isSearching ? `🔍 Buscando clientes para "<strong>${query}</strong>"...` : `Nenhum cliente encontrado para "<strong>${query}</strong>".`) : 'Sem clientes para exibir.'}
                </div>
            `;
            return;
        }

        container.innerHTML = "";
        list.forEach(p => {
            const code = p.codigo_sankhya ? `Cód: ${p.codigo_sankhya}` : 'Cód: N/A';
            const docVal = p.cnpj || p.cnpj_cpf;
            const docStr = docVal ? `CNPJ/CPF: ${docVal}` : 'Sem documento';
            const pUf = (p.uf === '2' || (p.cidade && p.cidade.trim().toUpperCase() === 'UBERLANDIA')) ? 'MG' : (p.uf || '');
            const cityStr = (p.cidade || pUf) ? ` &bull; ${p.cidade || ''}${pUf ? '/' + pUf : ''}` : '';

            const card = document.createElement("div");
            card.className = "partner-item-card";
            card.style.cssText = "display:flex; justify-content:space-between; align-items:center; background:#fff; border:1px solid #e2e8f0; border-radius:8px; padding:8px 12px; cursor:pointer; transition:all 0.2s;";
            card.onclick = () => selectAdminPartnerById(p.id);
            card.innerHTML = `
                <div style="flex-grow:1; padding-right:8px;">
                    <div style="font-weight:700; font-size:13px; color:var(--color-text); line-height:1.3;">${p.razao_social}</div>
                    <div style="font-size:11px; color:var(--color-text-muted); margin-top:2px;">${code}${cityStr}</div>
                    <div style="font-size:11px; color:var(--color-primary); font-weight:600; margin-top:1px;">${docStr}</div>
                </div>
                <div style="background:#e0f2fe; color:#0284c7; padding:4px 10px; border-radius:6px; font-size:11px; font-weight:700; flex-shrink:0;">
                    Selecionar
                </div>
            `;
            container.appendChild(card);
        });
    }

    function filterAdminPartnerOptions() {
        clearTimeout(adminPartnerSearchTimer);
        const inputEl = document.getElementById("admin-partner-search-input");
        if (!inputEl) return;

        const rawQuery = inputEl.value;
        const normalizedQuery = normalizeStr(rawQuery).trim();

        if (!normalizedQuery) {
            renderAdminPartnerOptions(adminPartnersList ? adminPartnersList.slice(0, 50) : []);
            return;
        }

        const terms = normalizedQuery.split(/\s+/).filter(Boolean);
        const localMatches = (adminPartnersList || []).filter(p => {
            const searchables = [
                normalizeStr(p.razao_social),
                normalizeStr(p.nome_fantasia),
                normalizeStr(p.cnpj || p.cnpj_cpf),
                normalizeStr(p.codigo_sankhya),
                normalizeStr(p.cidade)
            ].join(" ");
            return terms.every(term => searchables.includes(term));
        });

        renderAdminPartnerOptions(localMatches, true);

        adminPartnerSearchTimer = setTimeout(() => {
            executeAdminServerPartnerSearch(rawQuery.trim());
        }, 250);
    }

        let adminPartnerSearchReqId = 0;

        async function executeAdminServerPartnerSearch(query) {
            if (!query) return;
            const trimmedQuery = query.trim();
            if (!trimmedQuery) return;

            const thisReqId = ++adminPartnerSearchReqId;

            try {
                const res = await fetch(`${API_URL}/clientes?search=${encodeURIComponent(trimmedQuery)}&limit=50`);
                if (!res.ok) {
                    throw new Error(`Servidor retornou status ${res.status}`);
                }
                const data = await res.json();
                
                if (thisReqId !== adminPartnerSearchReqId) return;

                let results = [];
                if (data.success && Array.isArray(data.data)) {
                    results = data.data;
                } else if (Array.isArray(data)) {
                    results = data;
                }

                if (Array.isArray(results)) {
                    results.forEach(partner => {
                        if (!adminPartnersList.some(p => p.id == partner.id)) {
                            adminPartnersList.push(partner);
                        }
                    });
                    renderAdminPartnerOptions(results, false);
                }
            } catch(e) {
                console.error("Error searching admin partners:", e);
                const container = document.getElementById("admin-partner-search-results");
                if (container && thisReqId === adminPartnerSearchReqId) {
                    container.innerHTML = `<div style="text-align:center; padding:12px; color:#ef4444; font-size:12px;">⚠️ Erro na consulta (${e.message}).</div>`;
                }
            }
        }

    function selectAdminPartnerById(id) {
        adminSelectedPartner = adminPartnersList.find(p => p.id == id);
        if (!adminSelectedPartner) return;

        const card = document.getElementById("admin-selected-partner-card");
        const searchBox = document.getElementById("admin-partner-search-box");

        const docStr = adminSelectedPartner.cnpj || adminSelectedPartner.cnpj_cpf || 'Não informado';
        const codeStr = adminSelectedPartner.codigo_sankhya || adminSelectedPartner.codparc || adminSelectedPartner.id || 'N/A';
        const spUf = (adminSelectedPartner.uf === '2' || (adminSelectedPartner.cidade && adminSelectedPartner.cidade.trim().toUpperCase() === 'UBERLANDIA')) ? 'MG' : (adminSelectedPartner.uf || '');
        const cityStr = adminSelectedPartner.cidade ? ` | ${adminSelectedPartner.cidade}${spUf ? '/' + spUf : ''}` : '';

        document.getElementById("admin-sp-name").innerText = adminSelectedPartner.razao_social;
        document.getElementById("admin-sp-doc").innerText = `CNPJ/CPF: ${docStr} | Código: ${codeStr}${cityStr}`;

        if (card) card.style.display = "block";
        if (searchBox) searchBox.style.display = "none";
    }

    function clearAdminSelectedPartner() {
        adminSelectedPartner = null;
        const card = document.getElementById("admin-selected-partner-card");
        const searchBox = document.getElementById("admin-partner-search-box");
        const input = document.getElementById("admin-partner-search-input");

        if (card) card.style.display = "none";
        if (searchBox) searchBox.style.display = "block";
        if (input) {
            input.value = "";
            renderAdminPartnerOptions(adminPartnersList ? adminPartnersList.slice(0, 50) : []);
        }
    }

    function addAdminManualProductRow() {
        const container = document.getElementById("manual-items-container");
        if (!container) return;

        const rowId = ++adminRowCounter;
        const row = document.createElement("div");
        row.id = `admin-prod-row-${rowId}`;
        row.className = "admin-prod-row-item";
        row.style.cssText = "background: #fff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px; display: flex; flex-direction: column; gap: 8px; position: relative;";

        row.innerHTML = `
            <input type="hidden" class="admin-item-prod-id" id="admin-item-prod-id-${rowId}">
            
            <!-- Selected Product Badge -->
            <div id="admin-prod-selected-badge-${rowId}" style="display:none; background:#f0fdf4; border:1px solid #86efac; border-radius:6px; padding:6px 10px; justify-content:space-between; align-items:center;">
                <div>
                    <div id="admin-prod-selected-title-${rowId}" style="font-weight:700; font-size:12.5px; color:#166534;"></div>
                    <div id="admin-prod-selected-meta-${rowId}" style="font-size:11px; color:#15803d;"></div>
                </div>
                <button type="button" onclick="clearAdminRowProduct(${rowId})" style="background:#dcfce7; color:#15803d; border:1px solid #86efac; padding:3px 8px; border-radius:4px; font-size:11px; font-weight:700; cursor:pointer;">Trocar</button>
            </div>

            <!-- Product Search Input & Results Dropdown -->
            <div id="admin-prod-search-box-${rowId}" style="position:relative;">
                <input type="text" id="admin-prod-search-input-${rowId}" class="form-control" placeholder="🔍 Digite para buscar produto por código, nome ou marca..." oninput="filterAdminRowProduct(${rowId})" style="font-size:12px;">
                <div id="admin-prod-results-${rowId}" style="display:none; max-height:160px; overflow-y:auto; position:absolute; top:100%; left:0; right:0; z-index:100; background:#fff; border:1px solid #cbd5e1; border-radius:6px; box-shadow:0 4px 6px -1px rgba(0,0,0,0.1); margin-top:2px;"></div>
            </div>

            <!-- Quantity, Price & Subtotal Row -->
            <div style="display:flex; gap:8px; align-items:flex-start;">
                <div style="width:90px;">
                    <label style="font-size:10px; color:#64748b; font-weight:700; display:block; margin-bottom:2px;">Qtd</label>
                    <input type="number" min="1" value="1" class="form-control admin-item-qtd" onchange="calcAdminQuoteTotal()" oninput="calcAdminQuoteTotal()" style="font-size:12px; text-align:center;">
                </div>
                <div style="flex-grow:1;">
                    <label style="font-size:10px; color:#64748b; font-weight:700; display:block; margin-bottom:2px;">Preço Unitário (R$)</label>
                    <input type="number" step="0.01" min="0.01" class="form-control admin-item-price" placeholder="0.00" onchange="calcAdminQuoteTotal()" oninput="calcAdminQuoteTotal()" style="font-size:12px;">
                    <div id="admin-item-min-warning-${rowId}" class="admin-item-min-warning" style="display:none; color:#dc2626; font-size:11px; font-weight:600; margin-top:3px;"></div>
                </div>
                <div style="width:110px; text-align:right;">
                    <label style="font-size:10px; color:#64748b; font-weight:700; display:block; margin-bottom:2px;">Subtotal</label>
                    <div class="admin-item-subtotal" style="font-size:13px; font-weight:700; color:var(--color-primary); padding-top:4px;">R$ 0,00</div>
                </div>
                <button type="button" onclick="removeAdminProductRow(${rowId})" style="background:#fef2f2; color:#ef4444; border:1px solid #fca5a5; padding:6px 10px; border-radius:6px; font-size:12px; font-weight:700; cursor:pointer; margin-top:14px;">✕</button>
            </div>
        `;

        container.appendChild(row);
        renderAdminRowProductResults(rowId, adminProductsList ? adminProductsList.slice(0, 20) : []);
    }

    function filterAdminRowProduct(rowId) {
        clearTimeout(adminProdRowTimers[rowId]);
        const input = document.getElementById(`admin-prod-search-input-${rowId}`);
        if (!input) return;

        const rawQuery = input.value;
        const query = rawQuery.trim();

        if (!query) {
            renderAdminRowProductResults(rowId, adminProductsList ? adminProductsList.slice(0, 20) : []);
            return;
        }

        const normalizedQuery = normalizeStr(query);
        const terms = normalizedQuery.split(/\s+/).filter(Boolean);
        const localMatches = (adminProductsList || []).filter(p => {
            const searchables = [
                normalizeStr(p.descricao),
                normalizeStr(p.codigo_sankhya),
                normalizeStr(p.codprod),
                normalizeStr(p.marca),
            ].join(" ");
            return terms.every(term => searchables.includes(term));
        });

        renderAdminRowProductResults(rowId, localMatches, true);

        adminProdRowTimers[rowId] = setTimeout(() => {
            executeAdminServerProductSearch(rowId, query);
        }, 250);
    }

    async function executeAdminServerProductSearch(rowId, query) {
        try {
            const res = await fetch(`${API_URL}/produtos?search=${encodeURIComponent(query)}&limit=40`);
            const data = await res.json();
            
            let results = [];
            if (data.success && Array.isArray(data.data)) {
                results = data.data;
            } else if (Array.isArray(data)) {
                results = data;
            }

            if (Array.isArray(results)) {
                results.forEach(prod => {
                    if (!adminProductsList.some(p => p.id == prod.id)) {
                        adminProductsList.push(prod);
                    }
                });

                const input = document.getElementById(`admin-prod-search-input-${rowId}`);
                if (input && normalizeStr(input.value.trim()) === normalizeStr(query)) {
                    renderAdminRowProductResults(rowId, results, false);
                }
            }
        } catch(e) {
            console.error("Error searching admin row products:", e);
        }
    }

    function renderAdminRowProductResults(rowId, products, isSearching = false) {
        const container = document.getElementById(`admin-prod-results-${rowId}`);
        if (!container) return;

        const input = document.getElementById(`admin-prod-search-input-${rowId}`);
        const query = input ? input.value.trim() : "";

        if (!products || products.length === 0) {
            container.style.display = "block";
            container.innerHTML = `
                <div style="font-size:11px; color:#64748b; padding:8px; text-align:center;">
                    ${query ? (isSearching ? `🔍 Buscando "${query}"...` : `Nenhum produto para "${query}".`) : 'Digite para buscar...'}
                </div>
            `;
            return;
        }

        container.style.display = "block";
        container.innerHTML = "";
        products.forEach(p => {
            const price = parseFloat(p.preco_sugerido || p.preco_tabela || p.preco_venda || p.preco || 0);
            const isQuotable = (p.cotavel !== false) && price > 0;
            const priceStr = isQuotable ? `R$ ${price.toLocaleString('pt-BR', {minimumFractionDigits: 2})}` : 'Sem preço na tabela';
            const codeStr = p.codigo_sankhya || p.codprod || p.id;
            const brandStr = p.marca ? ` | ${p.marca}` : '';

            const itemDiv = document.createElement("div");
            itemDiv.style.cssText = `padding:6px 10px; border-bottom:1px solid #f1f5f9; cursor:${isQuotable ? 'pointer' : 'not-allowed'}; font-size:12px; ${isQuotable ? '' : 'opacity:0.6; background:#f8fafc;'}`;
            itemDiv.onclick = (e) => {
                e.stopPropagation();
                if (!isQuotable) {
                    showToast(`O produto "${p.descricao}" não possui preço cadastrado na tabela de preços e não pode ser cotado.`, 'warning');
                    return;
                }
                selectAdminRowProduct(rowId, p.id);
            };
            itemDiv.innerHTML = `
                <div style="font-weight:700; color:#1e293b;">${p.descricao}</div>
                <div style="font-size:10.5px; color:#64748b; display:flex; justify-content:space-between; margin-top:2px;">
                    <span>Cód: ${codeStr}${brandStr}</span>
                    <span style="color:${isQuotable ? 'var(--color-primary)' : '#ef4444'}; font-weight:700;">${priceStr}</span>
                </div>
            `;
            container.appendChild(itemDiv);
        });
    }

    function selectAdminRowProduct(rowId, prodId) {
        const prod = adminProductsList.find(p => p.id == prodId);
        if (!prod) return;

        const price = parseFloat(prod.preco_sugerido || prod.preco_tabela || prod.preco_venda || prod.preco || 0);
        if ((prod.cotavel === false || price <= 0) && !['PROD001', 'PROD002', 'PROD003'].includes(prod.codigo_sankhya)) {
            showToast(`O produto "${prod.descricao}" não possui preço cadastrado na tabela de preços e não pode ser cotado.`, 'warning');
            return;
        }

        document.getElementById(`admin-item-prod-id-${rowId}`).value = prod.id;

        const badge = document.getElementById(`admin-prod-selected-badge-${rowId}`);
        const searchBox = document.getElementById(`admin-prod-search-box-${rowId}`);
        const resultsBox = document.getElementById(`admin-prod-results-${rowId}`);

        const codeStr = prod.codigo_sankhya || prod.codprod || prod.id;

        const rowEl = document.getElementById(`admin-prod-row-${rowId}`);
        const sugPrice = price;
        const minPrice = parseFloat(prod.preco_minimo || (sugPrice * 0.9));
        if (rowEl) {
            rowEl.dataset.sugPrice = sugPrice;
            rowEl.dataset.minPrice = minPrice;
        }

        document.getElementById(`admin-prod-selected-title-${rowId}`).innerText = prod.descricao;
        document.getElementById(`admin-prod-selected-meta-${rowId}`).innerText = `Cód: ${codeStr} ${prod.marca ? '| ' + prod.marca : ''} | Sugerido: R$ ${sugPrice.toLocaleString('pt-BR', {minimumFractionDigits: 2})} · Mínimo: R$ ${minPrice.toLocaleString('pt-BR', {minimumFractionDigits: 2})}`;

        const priceInput = document.querySelector(`#admin-prod-row-${rowId} .admin-item-price`);
        if (priceInput && (!priceInput.value || parseFloat(priceInput.value) <= 0)) {
            priceInput.value = sugPrice.toFixed(2);
        }

        if (badge) badge.style.display = "flex";
        if (searchBox) searchBox.style.display = "none";
        if (resultsBox) resultsBox.style.display = "none";

        calcAdminQuoteTotal();
    }

    function clearAdminRowProduct(rowId) {
        document.getElementById(`admin-item-prod-id-${rowId}`).value = "";
        const rowEl = document.getElementById(`admin-prod-row-${rowId}`);
        if (rowEl) {
            delete rowEl.dataset.sugPrice;
            delete rowEl.dataset.minPrice;
        }
        const badge = document.getElementById(`admin-prod-selected-badge-${rowId}`);
        const searchBox = document.getElementById(`admin-prod-search-box-${rowId}`);
        const input = document.getElementById(`admin-prod-search-input-${rowId}`);

        if (badge) badge.style.display = "none";
        if (searchBox) searchBox.style.display = "block";
        if (input) {
            input.value = "";
            renderAdminRowProductResults(rowId, adminProductsList ? adminProductsList.slice(0, 20) : []);
        }

        calcAdminQuoteTotal();
    }

    function removeAdminProductRow(rowId) {
        const row = document.getElementById(`admin-prod-row-${rowId}`);
        if (row) row.remove();
        calcAdminQuoteTotal();
    }

    function calcAdminQuoteTotal() {
        let total = 0;
        const rows = document.querySelectorAll("#manual-items-container .admin-prod-row-item");

        rows.forEach(r => {
            const qtdEl = r.querySelector(".admin-item-qtd");
            const priceEl = r.querySelector(".admin-item-price");
            const subtotalEl = r.querySelector(".admin-item-subtotal");
            const warningEl = r.querySelector(".admin-item-min-warning");

            const qtd = qtdEl ? (parseFloat(qtdEl.value) || 0) : 0;
            const price = priceEl ? (parseFloat(priceEl.value) || 0) : 0;
            const minPrice = parseFloat(r.dataset.minPrice || 0);
            const subtotal = qtd * price;

            if (subtotalEl) {
                subtotalEl.innerText = "R$ " + subtotal.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            }

            if (warningEl) {
                if (minPrice > 0 && price > 0 && price < minPrice) {
                    const diffPct = (((price - minPrice) / minPrice) * 100).toFixed(1);
                    warningEl.innerText = `⚠️ Abaixo do preço mínimo (R$ ${minPrice.toLocaleString('pt-BR', {minimumFractionDigits: 2})}) • ${diffPct}%`;
                    warningEl.style.display = 'block';
                } else {
                    warningEl.style.display = 'none';
                }
            }

            total += subtotal;
        });

        let validItemCount = 0;
        rows.forEach(r => {
            if (r.querySelector(".admin-item-prod-id")?.value) validItemCount++;
        });
        const badgeCount = document.getElementById("admin-wizard-prod-count");
        if (badgeCount) badgeCount.innerText = validItemCount;

        const totalEl = document.getElementById("admin-manual-quote-total");
        if (totalEl) {
            totalEl.innerText = "R$ " + total.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }
    }

    async function submitManualQuote(e) {
        e.preventDefault();

        if (!adminSelectedPartner) {
            showToast("Por favor, pesquise e selecione um Cliente (Parceiro).", "warning");
            return;
        }

        const representante_id = document.getElementById("create-representante").value;
        if (!representante_id) {
            showToast("Por favor, selecione um Representante Comercial.", "warning");
            return;
        }

        const rows = document.querySelectorAll("#manual-items-container .admin-prod-row-item");
        const itens = [];
        let hasInvalidItem = false;

        rows.forEach(r => {
            const prodIdEl = r.querySelector(".admin-item-prod-id");
            const qtdEl = r.querySelector(".admin-item-qtd");
            const priceEl = r.querySelector(".admin-item-price");

            const prodId = prodIdEl ? prodIdEl.value : null;
            const qtd = qtdEl ? parseInt(qtdEl.value) : 0;
            const price = priceEl ? parseFloat(priceEl.value) : 0;

            if (prodId && qtd > 0 && price > 0) {
                itens.push({
                    produto_id: parseInt(prodId),
                    qtd: qtd,
                    preco_unit_proposto: price
                });
            } else if (prodId || price > 0) {
                hasInvalidItem = true;
            }
        });

        if (itens.length === 0) {
            showToast("Adicione e selecione pelo menos 1 produto válido com quantidade e preço maior que zero.", "warning");
            return;
        }

        if (hasInvalidItem) {
            showToast("Existem produtos na lista com quantidade ou preço inválidos. Por favor, verifique.", "warning");
            return;
        }

        const payload = {
            parceiro_id: parseInt(adminSelectedPartner.id),
            representante_id: parseInt(representante_id),
            forma_pagamento: document.getElementById("create-pagamento").value || 'A combinar',
            prazo_entrega: document.getElementById("create-prazo").value || '3 dias',
            frete_tipo: document.getElementById("create-frete").value || 'CIF',
            observacao_cliente: document.getElementById("create-obs-cliente").value || '',
            itens: itens
        };

        const submitBtn = document.querySelector("#create-quote-form button[type='submit']");
        const origSubmitText = submitBtn ? submitBtn.innerHTML : '';
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '⏳ Gerando Cotação...';
        }

        try {
            const res = await fetch(`${API_URL}/cotacoes/manual`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(payload)
            });

            let data = null;
            try {
                data = await res.json();
            } catch(e) {
                data = null;
            }

            if (res.ok && (data?.success || data?.cotacao || data?.id)) {
                showToast("Cotação criada com sucesso!", "success");
                closeCreateModal();
                loadQuotes();
            } else {
                let errMsg = "Não foi possível gerar a cotação no momento.";
                if (data?.message) {
                    errMsg = data.message;
                } else if (data?.error) {
                    errMsg = data.error;
                } else if (!res.ok) {
                    errMsg = `Erro no servidor (código ${res.status}). Por favor, tente novamente.`;
                }
                if (data?.messages && typeof data.messages === 'object') {
                    const details = Object.values(data.messages).flat().join("<br>• ");
                    errMsg += "<br><br>• " + details;
                }
                showToast(errMsg, "error");
            }
        } catch (err) {
            console.error(err);
            showToast("Erro de comunicação com o servidor ao salvar cotação. Verifique sua conexão e tente novamente.", "error");
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = origSubmitText;
            }
        }
    }
</script>
@endsection
