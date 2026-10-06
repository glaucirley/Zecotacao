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
        min-width: 1050px;
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
        padding: 14px 16px;
        border-bottom: 1px solid #e2e8f0;
        white-space: nowrap;
        position: relative;
        user-select: none;
    }

    /* Robust Minimum Column Widths */
    .col-numero   { min-width: 140px; }
    .col-parceiro { min-width: 250px; }
    .col-vendedor { min-width: 170px; }
    .col-emissao  { min-width: 110px; }
    .col-total    { min-width: 130px; }
    .col-status   { min-width: 140px; }

    /* Sticky Action Column (Pinned to Right Edge with clean border & crisp padding) */
    th.col-acoes, td.col-acoes {
        position: sticky;
        right: 0;
        background: #ffffff !important;
        z-index: 15;
        border-left: 1px solid #e2e8f0 !important;
        box-shadow: -6px 0 12px -2px rgba(0, 0, 0, 0.08);
        min-width: 135px !important;
        width: 135px !important;
        padding: 10px 10px !important;
    }
    th.col-acoes {
        background: #f8fafc !important;
        z-index: 16;
    }
    td.col-acoes.active-dropdown-cell {
        z-index: 100 !important;
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
        padding: 4px 14px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        white-space: nowrap;
    }
    .badge-status-mockup.em-criacao {
        background: #dbeafe;
        color: #2563eb;
    }
    .badge-status-mockup.liberada {
        background: #dcfce7;
        color: #16a34a;
    }
    .badge-status-mockup.aguardando-gestor {
        background: #fef3c7;
        color: #d97706;
    }
    .badge-status-mockup.com-diretor {
        background: #ffedd5;
        color: #ea580c;
    }
    .badge-status-mockup.faturada {
        background: #ccfbf1;
        color: #0d9488;
    }
    .badge-status-mockup.perdida {
        background: #fee2e2;
        color: #dc2626;
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

<!-- Filters Bar -->
<div class="filters-bar-card">
    <div class="filters-grid">
        <div class="filter-input-search">
            <span class="search-icon">🔍</span>
            <input type="text" id="search-input" placeholder="Buscar por Nº ou Cliente..." oninput="filterQuotes()">
        </div>

        <select id="status-filter" class="filter-select" onchange="filterQuotes()">
            <option value="">Todos os Status</option>
            <option value="EM_CRIACAO">Rascunho (Em criação)</option>
            <option value="AGUARDANDO_GESTOR">Pendente (Gestor)</option>
            <option value="COM_DIRETOR">Pendente (Diretor)</option>
            <option value="PDF_GERADO">Liberada (Pendente PDF)</option>
            <option value="AGUARDANDO_PEDIDO">Em Faturamento</option>
            <option value="FATURADA">Faturada</option>
            <option value="PERDIDA">Perdida</option>
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

        <div style="position: relative; display: inline-block;">
            <button class="btn-action-outline" style="padding: 8px 14px; font-size: 12px;" onclick="toggleColumnCustomizer(event)">
                ⚙️ Colunas / Filtros
            </button>
            <div id="column-customizer-panel" class="column-customizer-modal" onclick="event.stopPropagation()">
                <h4>Personalizar Colunas</h4>
                <div class="col-toggle-item">
                    <label><input type="checkbox" checked data-col="col-numero" onchange="toggleColumnVisibility('col-numero', this.checked)"> Cotação Nº</label>
                </div>
                <div class="col-toggle-item">
                    <label><input type="checkbox" checked data-col="col-parceiro" onchange="toggleColumnVisibility('col-parceiro', this.checked)"> Cliente / Parceiro</label>
                </div>
                <div class="col-toggle-item">
                    <label><input type="checkbox" checked data-col="col-vendedor" onchange="toggleColumnVisibility('col-vendedor', this.checked)"> Vendedor / Equipe</label>
                </div>
                <div class="col-toggle-item">
                    <label><input type="checkbox" checked data-col="col-emissao" onchange="toggleColumnVisibility('col-emissao', this.checked)"> Emissão</label>
                </div>
                <div class="col-toggle-item">
                    <label><input type="checkbox" checked data-col="col-total" onchange="toggleColumnVisibility('col-total', this.checked)"> Total Proposto</label>
                </div>
                <div class="col-toggle-item">
                    <label><input type="checkbox" checked data-col="col-status" onchange="toggleColumnVisibility('col-status', this.checked)"> Status</label>
                </div>
                <div class="col-toggle-item">
                    <label><input type="checkbox" checked data-col="col-acoes" onchange="toggleColumnVisibility('col-acoes', this.checked)"> Ações</label>
                </div>
                <div style="margin-top: 12px; pt: 8px; border-top: 1px solid #f1f5f9; text-align: right;">
                    <button type="button" class="btn-clear-filter" onclick="resetColumnPreferences()" style="font-size: 11px;">Restaurar Padrão</button>
                </div>
            </div>
        </div>

        <button type="button" class="btn-clear-filter" onclick="clearAllFilters()">
            Limpar
        </button>
    </div>
</div>

<!-- Table Card Container -->
<div class="table-container-card">
    <div class="table-responsive">
        <table class="table-mockup" id="main-quotes-table">
            <thead>
                <tr>
                    <th class="col-numero">COTAÇÃO ⇅ <div class="resizer"></div></th>
                    <th class="col-parceiro">CLIENTE / PARCEIRO <div class="resizer"></div></th>
                    <th class="col-vendedor">VENDEDOR / EQUIPE <div class="resizer"></div></th>
                    <th class="col-emissao">EMISSÃO ⇅ <div class="resizer"></div></th>
                    <th class="col-total">TOTAL PROPOSTO ⇅ <div class="resizer"></div></th>
                    <th class="col-status" style="text-align: center;">STATUS ⇅ <div class="resizer"></div></th>
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
                <p><strong>CNPJ:</strong> <span id="modal-cnpj">...</span></p>
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

<!-- Create Manual Quote Sheet Drawer -->
<div id="create-overlay" class="sheet-overlay" onclick="closeCreateModal()"></div>
<div id="create-modal" class="sheet-drawer" style="width: 680px; max-width: 95vw;">
    <div class="sheet-header">
        <h3 style="margin: 0; color: var(--color-primary); font-size: 18px; font-weight:700;">Incluir Nova Cotação Manual</h3>
        <button type="button" class="sheet-close-btn" onclick="closeCreateModal()">&times;</button>
    </div>
    <div class="sheet-body" style="padding: 16px 20px;">

        <form id="create-quote-form" onsubmit="submitManualQuote(event)">
            <!-- Cliente (Parceiro) Selection -->
            <div class="form-group" style="margin-bottom: 14px;">
                <label class="form-label" style="font-weight: 600;">Cliente (Parceiro) <span style="color:#ef4444;">*</span></label>
                <div id="admin-selected-partner-card" style="display:none; background:#f0fdf4; border:1px solid #86efac; border-radius:10px; padding:10px 14px; margin-bottom:8px;">
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <div>
                            <div id="admin-sp-name" style="font-weight:700; font-size:14px; color:#166534;">-</div>
                            <div id="admin-sp-doc" style="font-size:11.5px; color:#15803d; margin-top:2px;">-</div>
                        </div>
                        <button type="button" onclick="clearAdminSelectedPartner()" style="background:#dcfce7; color:#15803d; border:1px solid #86efac; padding:5px 12px; border-radius:6px; font-size:12px; font-weight:700; cursor:pointer;">Alterar</button>
                    </div>
                </div>
                
                <div id="admin-partner-search-box">
                    <input type="text" id="admin-partner-search-input" class="form-control" placeholder="🔍 Digite iniciais, razão social, CNPJ ou código do cliente..." oninput="filterAdminPartnerOptions()" style="font-size: 13px;">
                    <div id="admin-partner-search-results" style="max-height:180px; overflow-y:auto; margin-top:6px; display:flex; flex-direction:column; gap:6px; border:1px solid #e2e8f0; border-radius:8px; padding:6px; background:#fff;">
                        <!-- Partner search cards dynamically rendered -->
                    </div>
                </div>
            </div>

            <!-- Representante Comercial & Condições -->
            <div class="grid-2" style="gap: 12px; margin-bottom: 12px;">
                <div class="form-group" style="margin-bottom:0;" id="rep-group-container">
                    <label for="create-representante" class="form-label" style="font-weight: 600;">Representante Comercial <span style="color:#ef4444;">*</span></label>
                    <select id="create-representante" class="form-control" required style="font-size: 13px;">
                        <option value="">🔄 Carregando vendedores...</option>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label for="create-pagamento" class="form-label" style="font-weight: 600;">Forma de Pagamento</label>
                    <input type="text" id="create-pagamento" class="form-control" placeholder="Ex: 30/60 dias" value="A combinar" style="font-size: 13px;">
                </div>
            </div>

            <div class="grid-2" style="gap: 12px; margin-bottom: 14px;">
                <div class="form-group" style="margin-bottom:0;">
                    <label for="create-prazo" class="form-label" style="font-weight: 600;">Prazo de Entrega</label>
                    <input type="text" id="create-prazo" class="form-control" placeholder="Ex: 3 dias" value="3 dias" style="font-size: 13px;">
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label for="create-frete" class="form-label" style="font-weight: 600;">Tipo de Frete</label>
                    <select id="create-frete" class="form-control" style="font-size: 13px;">
                        <option value="CIF">CIF (Frete por conta do emitente)</option>
                        <option value="FOB">FOB (Frete por conta do destinatário)</option>
                    </select>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 16px;">
                <label for="create-obs-cliente" class="form-label" style="font-weight: 600;">Observação Cliente</label>
                <textarea id="create-obs-cliente" class="form-control" rows="2" placeholder="Ex: Horário de recebimento das 8h às 17h..." style="font-size: 13px;"></textarea>
            </div>

            <!-- Products Section -->
            <div style="display:flex; justify-content:space-between; align-items:center; margin-top:20px; margin-bottom:10px;">
                <h4 style="margin:0; color:var(--color-primary); font-size:15px; font-weight:700;">Produtos da Cotação</h4>
                <button type="button" class="btn btn-secondary" style="font-size:12px; padding:6px 12px; font-weight:600;" onclick="addAdminManualProductRow()">+ Adicionar Produto</button>
            </div>

            <div style="background:#f8fafc; padding:12px; border-radius:10px; border:1px solid #e2e8f0; margin-bottom:16px;">
                <div id="manual-items-container" style="display: flex; flex-direction: column; gap: 10px;">
                    <!-- Dynamic product rows here -->
                </div>
                
                <div style="display:flex; justify-content:flex-end; align-items:center; margin-top:12px; padding-top:10px; border-top:1px solid #cbd5e1; font-weight:700; font-size:14px; color:var(--color-text);">
                    <span>Total da Cotação:</span>
                    <span id="admin-manual-quote-total" style="color:var(--color-primary); margin-left:8px; font-size:16px;">R$ 0,00</span>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px;">
                <button type="button" class="btn btn-outline" onclick="closeCreateModal()">Cancelar</button>
                <button type="submit" class="btn btn-primary" style="font-weight:700; padding:8px 20px;">💾 Salvar e Gerar Cotação</button>
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
                renderQuotes(rawQuotes);
            } else {
                alert("Erro ao buscar cotações: " + data.error);
            }
        } catch (e) {
            console.error(e);
            document.getElementById("loading-spinner").style.display = "none";
            alert("Erro ao conectar no servidor.");
        }
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
            const statusClass = q.status.toLowerCase().replace(/_/g, '-');
            let statusText = 'EM CRIACAO';
            let subText = '';

            switch (q.status) {
                case 'EM_CRIACAO':
                    statusText = 'EM CRIACAO';
                    break;
                case 'PDF_GERADO':
                    statusText = 'LIBERADA';
                    subText = 'Aguardando PDF';
                    break;
                case 'AGUARDANDO_GESTOR':
                    statusText = 'PENDENTE GESTOR';
                    break;
                case 'COM_DIRETOR':
                    statusText = 'PENDENTE DIRETOR';
                    break;
                case 'FATURADA':
                    statusText = 'FATURADA';
                    break;
                case 'PERDIDA':
                    statusText = 'PERDIDA';
                    break;
                default:
                    statusText = q.status.replace(/_/g, ' ');
                    break;
            }

            const repName = q.representante ? q.representante.nome : 'Sem Vendedor';
            const teamName = (q.representante && q.representante.equipe) ? q.representante.equipe.nome : 'Sem Equipe';
            const initial = repName.charAt(0).toUpperCase();

            // Authorization logic for Editing / Deleting
            const canEdit = q.status === 'EM_CRIACAO' && (
                CURRENT_USER.papel === 'administrador' || 
                CURRENT_USER.id == q.representante_id ||
                (CURRENT_USER.papel === 'gestor' && q.representante && q.representante.equipe && q.representante.equipe.gestor_id == CURRENT_USER.id)
            );

            const canDelete = CURRENT_USER.papel === 'administrador' || (
                q.status === 'EM_CRIACAO' && (
                    CURRENT_USER.id == q.representante_id ||
                    (CURRENT_USER.papel === 'gestor' && q.representante && q.representante.equipe && q.representante.equipe.gestor_id == CURRENT_USER.id)
                )
            );

            body.innerHTML += `
                <tr>
                    <td class="col-numero">
                        <div class="quote-pill-badge">
                            <span class="doc-icon">📄</span>
                            <span class="quote-num">${q.numero}</span>
                        </div>
                    </td>
                    <td class="col-parceiro">
                        <strong style="font-weight: 700; color: #0f172a;">${q.parceiro.razao_social}</strong><br>
                        <span style="font-size: 11px; color: #64748b;">Cód. Sankhya: ${q.parceiro.codigo_sankhya}</span>
                    </td>
                    <td class="col-vendedor">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div class="user-avatar-circle">${initial}</div>
                            <div>
                                <strong style="font-weight: 700; color: #0f172a;">${repName}</strong><br>
                                <span style="font-size: 11px; color: #64748b;">${teamName}</span>
                            </div>
                        </div>
                    </td>
                    <td class="col-emissao">
                        <div class="date-text-container">
                            <span class="cal-icon">📅</span>
                            <span>${date}</span>
                        </div>
                    </td>
                    <td class="col-total">
                        <span class="price-text-primary">R$ ${parseFloat(q.total).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</span>
                    </td>
                    <td class="col-status text-center">
                        <div class="badge-status-mockup ${statusClass}">
                            <span>${statusText}</span>
                            ${subText ? `<span class="badge-subtext">${subText}</span>` : ''}
                        </div>
                    </td>
                    <td class="col-acoes text-center">
                        <div style="display: flex; align-items: center; justify-content: center;">
                            <div class="dropdown-dots-wrapper">
                                <button class="btn-dots-row" onclick="toggleDotsMenu(event, ${q.id})" title="Ações">⋮</button>
                                <div class="dropdown-dots-menu" id="dots-menu-${q.id}">
                                    ${canEdit ? `<a href="{{ url('/cotacoes/id') }}/${q.id}" class="dropdown-dots-item">✏️ Editar Cotação</a>` : ''}
                                    <button class="dropdown-dots-item" onclick="handleDetailClick(${q.id}, '${q.status}')">👁️ Ver Detalhes</button>
                                    ${canDelete ? `<button class="dropdown-dots-item danger" onclick="deleteQuote(${q.id})">🗑️ Excluir</button>` : ''}
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
        const status = document.getElementById("status-filter").value;
        const repId = document.getElementById("rep-filter").value;
        const period = document.getElementById("period-filter").value;
        const teamId = document.getElementById("team-filter").value;

        const now = new Date();

        const filtered = rawQuotes.filter(q => {
            // Status match
            const matchesStatus = status === "" || q.status === status;

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

            return matchesStatus && matchesRep && matchesTeam && matchesSearch && matchesPeriod;
        });

        renderQuotes(filtered);
    }

    function clearAllFilters() {
        document.getElementById("search-input").value = "";
        document.getElementById("status-filter").value = "";
        document.getElementById("rep-filter").value = "";
        document.getElementById("period-filter").value = "";
        document.getElementById("team-filter").value = "";
        renderQuotes(rawQuotes);
    }

    // Modal and Actions handlers
    function handleDetailClick(id, status) {
        const quote = rawQuotes.find(q => q.id == id);
        if (!quote) return;

        const clientName = quote.parceiro ? (quote.parceiro.razao_social || quote.parceiro.nome_fantasia || 'Cliente Sem Nome') : 'Cliente Não Identificado';
        const clientCnpj = (quote.parceiro && quote.parceiro.cnpj) ? quote.parceiro.cnpj : 'N/A';
        const repName = quote.representante ? quote.representante.nome : 'Sem Vendedor';

        document.getElementById("modal-quote-num").innerText = quote.numero || ('#' + quote.id);
        document.getElementById("modal-client").innerText = clientName;
        document.getElementById("modal-cnpj").innerText = clientCnpj;
        document.getElementById("modal-rep").innerText = repName;
        document.getElementById("modal-date").innerText = quote.created_at ? new Date(quote.created_at).toLocaleDateString('pt-BR') : 'N/A';

        document.getElementById("modal-payment").innerText = quote.condicao_pagamento || 'A combinar';
        document.getElementById("modal-delivery").innerText = quote.prazo_entrega || '3 dias';
        document.getElementById("modal-freight").innerText = quote.tipo_frete || 'CIF';

        const statusText = quote.status === 'PDF_GERADO' ? 'Liberada (Pendente PDF)' : (quote.status ? quote.status.replace(/_/g, ' ') : 'N/A');
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
            let adjText = diffPct.toFixed(1) + '%';

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
        if (!confirm("Tem certeza que deseja excluir esta cotação? Esta ação é irreversível.")) {
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
                alert("Cotação excluída com sucesso!");
                loadQuotes();
            } else {
                alert("Erro ao excluir: " + (data.error || data.message));
            }
        } catch (e) {
            console.error(e);
            alert("Erro de conexão ao excluir a cotação.");
        }
    }

    let adminPartnersList = [];
    let adminSelectedPartner = null;
    let adminPartnerSearchTimer = null;
    let adminProductsList = [];
    let adminRowCounter = 0;
    let adminProdRowTimers = {};

    function openCreateModal() {
        adminSelectedPartner = null;
        adminPartnersList = [];
        adminProductsList = [];
        adminRowCounter = 0;

        const form = document.getElementById("create-quote-form");
        if (form) form.reset();
        clearAdminSelectedPartner();

        const container = document.getElementById("manual-items-container");
        if (container) container.innerHTML = "";

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

            if (rSelect) {
                rSelect.innerHTML = '<option value="">Selecione um representante...</option>';
                const reps = users.filter(u => u.papel === 'representante' || u.papel === 'gerente' || u.papel === 'administrador');
                reps.forEach(r => {
                    const selected = (CURRENT_USER && CURRENT_USER.id == r.id) ? 'selected' : '';
                    rSelect.innerHTML += `<option value="${r.id}" ${selected}>${r.nome} (${r.email || r.papel})</option>`;
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
            const cityStr = (p.cidade || p.uf) ? ` &bull; ${p.cidade || ''}${p.uf ? '/' + p.uf : ''}` : '';

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
        const codeStr = adminSelectedPartner.codigo_sankhya || 'N/A';
        const cityStr = adminSelectedPartner.cidade ? ` | ${adminSelectedPartner.cidade}${adminSelectedPartner.uf ? '/' + adminSelectedPartner.uf : ''}` : '';

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
            <div style="display:flex; gap:8px; align-items:center;">
                <div style="width:90px;">
                    <label style="font-size:10px; color:#64748b; font-weight:700; display:block; margin-bottom:2px;">Qtd</label>
                    <input type="number" min="1" value="1" class="form-control admin-item-qtd" onchange="calcAdminQuoteTotal()" oninput="calcAdminQuoteTotal()" style="font-size:12px; text-align:center;">
                </div>
                <div style="flex-grow:1;">
                    <label style="font-size:10px; color:#64748b; font-weight:700; display:block; margin-bottom:2px;">Preço Unitário (R$)</label>
                    <input type="number" step="0.01" min="0.01" class="form-control admin-item-price" placeholder="0.00" onchange="calcAdminQuoteTotal()" oninput="calcAdminQuoteTotal()" style="font-size:12px;">
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
            const priceStr = price > 0 ? `R$ ${price.toLocaleString('pt-BR', {minimumFractionDigits: 2})}` : 'R$ 0,00';
            const codeStr = p.codigo_sankhya || p.codprod || p.id;
            const brandStr = p.marca ? ` | ${p.marca}` : '';

            const itemDiv = document.createElement("div");
            itemDiv.style.cssText = "padding:6px 10px; border-bottom:1px solid #f1f5f9; cursor:pointer; font-size:12px; hover:background:#f8fafc;";
            itemDiv.onclick = (e) => {
                e.stopPropagation();
                selectAdminRowProduct(rowId, p.id);
            };
            itemDiv.innerHTML = `
                <div style="font-weight:700; color:#1e293b;">${p.descricao}</div>
                <div style="font-size:10.5px; color:#64748b; display:flex; justify-content:space-between; margin-top:2px;">
                    <span>Cód: ${codeStr}${brandStr}</span>
                    <span style="color:var(--color-primary); font-weight:700;">${priceStr}</span>
                </div>
            `;
            container.appendChild(itemDiv);
        });
    }

    function selectAdminRowProduct(rowId, prodId) {
        const prod = adminProductsList.find(p => p.id == prodId);
        if (!prod) return;

        document.getElementById(`admin-item-prod-id-${rowId}`).value = prod.id;

        const badge = document.getElementById(`admin-prod-selected-badge-${rowId}`);
        const searchBox = document.getElementById(`admin-prod-search-box-${rowId}`);
        const resultsBox = document.getElementById(`admin-prod-results-${rowId}`);

        const price = parseFloat(prod.preco_sugerido || prod.preco_tabela || prod.preco_venda || prod.preco || 100);
        const codeStr = prod.codigo_sankhya || prod.codprod || prod.id;

        document.getElementById(`admin-prod-selected-title-${rowId}`).innerText = prod.descricao;
        document.getElementById(`admin-prod-selected-meta-${rowId}`).innerText = `Cód: ${codeStr} ${prod.marca ? '| ' + prod.marca : ''} | Sugerido: R$ ${price.toFixed(2)}`;

        const priceInput = document.querySelector(`#admin-prod-row-${rowId} .admin-item-price`);
        if (priceInput && (!priceInput.value || parseFloat(priceInput.value) <= 0)) {
            priceInput.value = price.toFixed(2);
        }

        if (badge) badge.style.display = "flex";
        if (searchBox) searchBox.style.display = "none";
        if (resultsBox) resultsBox.style.display = "none";

        calcAdminQuoteTotal();
    }

    function clearAdminRowProduct(rowId) {
        document.getElementById(`admin-item-prod-id-${rowId}`).value = "";
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

            const qtd = qtdEl ? (parseFloat(qtdEl.value) || 0) : 0;
            const price = priceEl ? (parseFloat(priceEl.value) || 0) : 0;
            const subtotal = qtd * price;

            if (subtotalEl) {
                subtotalEl.innerText = "R$ " + subtotal.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            }

            total += subtotal;
        });

        const totalEl = document.getElementById("admin-manual-quote-total");
        if (totalEl) {
            totalEl.innerText = "R$ " + total.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }
    }

    async function submitManualQuote(e) {
        e.preventDefault();

        if (!adminSelectedPartner) {
            alert("Por favor, pesquise e selecione um Cliente (Parceiro).");
            return;
        }

        const representante_id = document.getElementById("create-representante").value;
        if (!representante_id) {
            alert("Por favor, selecione um Representante Comercial.");
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
            alert("Adicione e selecione pelo menos 1 produto válido com quantidade e preço maior que zero.");
            return;
        }

        if (hasInvalidItem) {
            alert("Existem produtos na lista com quantidade ou preço inválidos. Por favor, verifique.");
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

            const data = await res.json();
            if (data.success || data.cotacao) {
                alert("✅ Cotação criada com sucesso!");
                closeCreateModal();
                loadQuotes();
            } else {
                let errMsg = data.error || data.message || "Erro desconhecido ao salvar cotação.";
                if (data.messages) {
                    errMsg += "\n" + JSON.stringify(data.messages);
                }
                alert("⚠️ Erro ao salvar cotação: " + errMsg);
            }
        } catch (err) {
            console.error(err);
            alert("Erro de comunicação com o servidor ao salvar cotação.");
        }
    }
</script>
@endsection
