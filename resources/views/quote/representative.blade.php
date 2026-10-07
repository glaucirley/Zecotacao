@extends(auth()->check() ? (auth()->user()->isRepresentante() ? 'layouts.representative' : 'layouts.app') : 'layouts.public')

@section('styles')
<style>
    /* Items Search and Filter Bar */
    .items-filter-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 15px;
        margin-bottom: 16px;
        flex-wrap: wrap;
    }
    .search-box-wrapper {
        position: relative;
        flex-grow: 1;
        min-width: 250px;
    }
    .search-box-wrapper .search-icon {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 14px;
    }
    .search-items-input {
        width: 100%;
        padding: 9px 12px 9px 36px;
        border-radius: 8px;
        border: 1px solid #cbd5e1;
        font-size: 13px;
        outline: none;
        transition: all 0.15s ease;
        background: #ffffff;
    }
    .search-items-input:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }
    /* Product Autocomplete Search Dropdown */
    .product-autocomplete-results {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        z-index: 1050;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1);
        max-height: 260px;
        overflow-y: auto;
        margin-top: 4px;
    }
    .product-autocomplete-item {
        padding: 10px 14px;
        border-bottom: 1px solid #f1f5f9;
        cursor: pointer;
        transition: background 0.15s ease;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .product-autocomplete-item:hover {
        background: #f8fafc;
    }
    .product-autocomplete-item .prod-code {
        font-weight: 700;
        color: #1e293b;
        font-size: 12px;
        background: #e2e8f0;
        padding: 2px 6px;
        border-radius: 4px;
        margin-right: 8px;
    }
    .product-autocomplete-item .prod-title {
        font-weight: 600;
        color: #0f172a;
        font-size: 13px;
    }
    .product-autocomplete-item .prod-price {
        font-size: 12px;
        color: #2563eb;
        font-weight: 600;
        white-space: nowrap;
    }

    .filter-tabs-wrapper {
        display: flex;
        gap: 6px;
        background: #f1f5f9;
        padding: 4px;
        border-radius: 10px;
    }
    .filter-tab-btn {
        border: none;
        background: transparent;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        color: #64748b;
        cursor: pointer;
        transition: all 0.15s ease;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .filter-tab-btn.active {
        background: #ffffff;
        color: #0f172a;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }
    .filter-tab-btn .tab-badge {
        background: #e2e8f0;
        color: #475569;
        padding: 2px 7px;
        border-radius: 10px;
        font-size: 11px;
    }
    .filter-tab-btn.tab-attention.active {
        color: #dc2626;
    }
    .filter-tab-btn.tab-attention .tab-badge {
        background: #fee2e2;
        color: #dc2626;
    }

    /* Stepper Quantity Control */
    .qty-stepper {
        display: inline-flex;
        align-items: center;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        overflow: hidden;
        background: #ffffff;
    }
    .qty-stepper .qty-btn {
        border: none;
        background: #f8fafc;
        color: #334155;
        font-weight: 700;
        font-size: 14px;
        width: 32px;
        height: 32px;
        cursor: pointer;
        user-select: none;
        transition: background 0.15s ease;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .qty-stepper .qty-btn:hover:not(:disabled) {
        background: #e2e8f0;
    }
    .qty-stepper .qty-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    .qty-stepper .qty-input {
        border: none !important;
        border-left: 1px solid #e2e8f0 !important;
        border-right: 1px solid #e2e8f0 !important;
        border-radius: 0 !important;
        width: 48px !important;
        height: 32px !important;
        padding: 0 !important;
        font-weight: 600;
        font-size: 14px;
    }

    .btn-min-fix {
        padding: 5px 10px;
        font-size: 11px;
        font-weight: 600;
        border-color: #cbd5e1;
        color: #2563eb;
    }

    /* Mobile sub-info label */
    .mobile-sub-info {
        display: none;
        font-size: 11px;
        color: #64748b;
        margin-top: 3px;
    }
    .mobile-label {
        display: none;
        font-weight: 600;
        font-size: 11px;
        color: #64748b;
    }

    /* Mobile sticky floating bar */
    .sticky-mobile-total-bar {
        display: none;
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: #0f172a;
        color: #ffffff;
        padding: 12px 20px;
        z-index: 999;
        box-shadow: 0 -4px 12px rgba(0,0,0,0.15);
        justify-content: space-between;
        align-items: center;
    }

    @media (max-width: 768px) {
        .desktop-only {
            display: none !important;
        }
        .mobile-sub-info {
            display: block;
        }
        .mobile-label {
            display: inline-block;
            margin-right: 4px;
        }

        #items-table table, #items-table thead, #items-table tbody, #items-table th, #items-table td, #items-table tr {
            display: block;
        }
        #items-table thead {
            display: none;
        }
        
        .item-card-row {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            margin-bottom: 12px;
            padding: 14px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            position: relative;
        }
        .item-card-row.below-min-card {
            border-color: #fca5a5;
            background: #fef2f2;
        }

        .item-card-row td {
            padding: 4px 0 !important;
            border: none !important;
            text-align: left !important;
        }

        .item-card-row td.col-code {
            font-size: 11px;
            color: #64748b;
            margin-bottom: 2px;
        }
        .item-card-row td.col-desc {
            font-size: 14px;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 8px;
            padding-right: 36px !important;
        }
        .item-card-row td.col-un {
            display: inline-block;
            margin-right: 15px;
        }
        .item-card-row td.col-qtd {
            display: inline-block;
            margin-bottom: 8px;
        }
        .item-card-row td.col-proposto {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 6px;
            padding-top: 8px !important;
            border-top: 1px dashed #e2e8f0 !important;
        }
        .item-card-row td.col-status {
            display: inline-block;
            margin-top: 6px;
        }
        .item-card-row td.col-actions {
            position: absolute;
            top: 12px;
            right: 12px;
        }
        
        .sticky-mobile-total-bar {
            display: flex;
        }
        body {
            padding-bottom: 70px;
        }

        /* Actions Panel Vertical Stacking for Mobile */
        .actions-panel-card {
            flex-direction: column-reverse !important;
            align-items: stretch !important;
            gap: 12px !important;
            padding: 16px !important;
        }
        .actions-panel-card .left-actions,
        .actions-panel-card .right-actions {
            flex-direction: column-reverse !important;
            width: 100% !important;
            gap: 10px !important;
        }
        .actions-panel-card button {
            width: 100% !important;
            justify-content: center !important;
            padding: 12px 16px !important;
            font-size: 14px !important;
            margin: 0 !important;
        }
    }

    /* Toast Notification System */
    .toast-container {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 999999;
        display: flex;
        flex-direction: column;
        gap: 10px;
        pointer-events: none;
        max-width: 90vw;
        width: 380px;
    }

    @media (max-width: 640px) {
        .toast-container {
            top: 15px !important;
            left: 50% !important;
            right: auto !important;
            transform: translateX(-50%) !important;
            width: calc(100vw - 28px) !important;
            max-width: 100vw !important;
        }
    }

    .app-toast {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 12px 16px;
        border-radius: 10px;
        color: #ffffff;
        font-size: 13px;
        line-height: 1.4;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.25), 0 8px 10px -6px rgba(0, 0, 0, 0.2);
        pointer-events: auto;
        cursor: pointer;
        transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        transform: translateY(-15px);
        opacity: 0;
    }

    .app-toast.show {
        transform: translateY(0);
        opacity: 1;
    }

    .app-toast.hide {
        transform: translateY(-15px);
        opacity: 0;
    }

    .app-toast-success {
        background: #059669; /* Emerald 600 */
        border: 1px solid #10b981;
    }

    .app-toast-error {
        background: #dc2626; /* Red 600 */
        border: 1px solid #ef4444;
    }

    .app-toast-warning {
        background: #d97706; /* Amber 600 */
        border: 1px solid #f59e0b;
    }

    .app-toast-info {
        background: #2563eb; /* Blue 600 */
        border: 1px solid #3b82f6;
    }

    .app-toast-icon {
        font-size: 18px;
        line-height: 1;
        flex-shrink: 0;
        margin-top: 1px;
    }

    .app-toast-body {
        flex-grow: 1;
    }

    .app-toast-title {
        font-weight: 700;
        font-size: 13.5px;
        margin-bottom: 2px;
    }

    .app-toast-msg {
        font-size: 12.5px;
        word-break: break-word;
        opacity: 0.95;
    }

    .app-toast-close {
        background: transparent;
        border: none;
        color: #ffffff;
        opacity: 0.7;
        font-size: 18px;
        line-height: 1;
        cursor: pointer;
        padding: 0;
        margin-left: 4px;
        flex-shrink: 0;
        transition: opacity 0.15s;
    }

    .app-toast-close:hover {
        opacity: 1;
    }
</style>
@endsection

@section('content')
<div id="toast-container" class="toast-container"></div>
<div id="representative-panel" style="display: none;">
    <!-- Dynamic Authenticated User Top Navigation Bar -->
    <div id="user-auth-bar" style="display: none; margin-bottom: 16px; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 12px; padding: 10px 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
            <div style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: #334155;">
                <span id="logged-user-role-badge" style="background: #e0f2fe; color: #0284c7; padding: 4px 8px; border-radius: 6px; font-weight: 700; font-size: 11px; text-transform: uppercase;">
                    👤 <span id="logged-user-role">Usuário</span>
                </span>
                <span id="logged-user-name" style="font-weight: 600;">-</span>
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <button type="button" onclick="goToHomeDashboard()" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 6px; font-weight: 700; font-size: 12.5px; padding: 8px 16px; border-radius: 8px; background: #2563eb; color: #ffffff; border: none; cursor: pointer; transition: all 0.2s ease;">
                    <span>🏠</span> <span id="btn-home-label">Ir para o Meu Painel</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Title and Status Row -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 15px;">
        <div>
            <h1 style="font-size: 28px; margin-bottom: 4px;">Cotação <span id="quote-number" style="color: var(--color-primary);">...</span></h1>
            <p style="color: var(--color-text-muted); font-size: 13px;">
                Origem: <span id="quote-origin" style="font-weight: 600;">...</span> | 
                Emitido em: <span id="quote-emission">...</span> | 
                Válido até: <span id="quote-validity">...</span>
            </p>
        </div>
        <div style="display: flex; align-items: center; gap: 10px;">
            <span id="quote-status-badge" class="badge-status">...</span>
        </div>
    </div>

    <!-- Locked Status Notice Banner -->
    <div id="locked-status-banner" style="display: none; margin-bottom: 20px; padding: 14px 18px; border-radius: 10px; background: #fffbeb; border: 1px solid #fef3c7; color: #92400e; font-size: 14px; font-weight: 500; align-items: center; gap: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
        <span style="font-size: 18px;">🔒</span>
        <span id="locked-status-banner-text">Esta cotação está em modo somente leitura e não permite edições.</span>
    </div>

    <!-- Info Cards Row -->
    <div class="grid-2" style="margin-bottom: 24px;">
        <!-- Client Card -->
        <div class="card" style="margin-bottom: 0;">
            <div class="card-header" style="margin-bottom: 12px; padding-bottom: 8px;">
                <h3 style="font-size: 16px;">Dados do Cliente</h3>
            </div>
            <p style="font-size: 15px; font-weight: 600; margin-bottom: 8px;" id="client-name">...</p>
            <p style="font-size: 13px; color: var(--color-text-muted);" id="client-cnpj">CNPJ: ...</p>
            <p style="font-size: 13px; color: var(--color-text-muted);" id="client-location">Cidade: ...</p>
            <p style="font-size: 13px; color: var(--color-text-muted);" id="client-contact">Contato: ...</p>
        </div>

        <!-- Sales Rep Card -->
        <div class="card" style="margin-bottom: 0;">
            <div class="card-header" style="margin-bottom: 12px; padding-bottom: 8px;">
                <h3 style="font-size: 16px;">Vendedor & Equipe</h3>
            </div>
            <p style="font-size: 15px; font-weight: 600; margin-bottom: 8px;" id="rep-name">...</p>
            <p style="font-size: 13px; color: var(--color-text-muted);" id="rep-team">Equipe: ...</p>
            <p style="font-size: 13px; color: var(--color-text-muted);" id="rep-email">E-mail: ...</p>
            <p style="font-size: 13px; color: var(--color-text-muted);" id="rep-phone">Celular: ...</p>
        </div>
    </div>

    <!-- Items Grid Card -->
    <div class="card">
        <!-- Live Search and Filter Bar -->
        <div class="items-filter-bar" style="margin-bottom: 16px;">
            <div class="search-box-wrapper">
                <span class="search-icon">🔍</span>
                <input type="text" id="item-search-input" class="search-items-input" placeholder="Buscar por código ou produto..." oninput="onSearchInput(this.value)">
            </div>
            <div class="filter-tabs-wrapper">
                <button type="button" class="filter-tab-btn active" id="tab-all" onclick="setFilterTab('all')">
                    Todos <span class="tab-badge" id="count-all">0</span>
                </button>
                <button type="button" class="filter-tab-btn tab-attention" id="tab-attention" onclick="setFilterTab('attention')">
                    ⚠️ Requer Atenção <span class="tab-badge" id="count-attention">0</span>
                </button>
                <button type="button" class="filter-tab-btn" id="tab-approved" onclick="setFilterTab('approved')">
                    ✅ Normais <span class="tab-badge" id="count-approved">0</span>
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table-premium" id="items-table">
                <thead>
                    <tr>
                        <th style="width: 8%;">Código</th>
                        <th style="width: 30%;">Descrição</th>
                        <th style="width: 8%; text-align: center;">Un.</th>
                        <th style="width: 12%; text-align: center;">Qtd</th>
                        <th style="width: 11%; text-align: right;" class="desktop-only">Preço Sugerido</th>
                        <th style="width: 11%; text-align: right;" class="desktop-only">Preço Mínimo</th>
                        <th style="width: 14%; text-align: right;">Preço Proposto</th>
                        <th style="width: 9%; text-align: center;">Situação</th>
                        <th style="width: 7%; text-align: center;">Ações</th>
                    </tr>
                </thead>
                <tbody id="items-table-body">
                    <!-- Dynamic Rows -->
                </tbody>
            </table>
        </div>

        <!-- Add Item Row -->
        <div id="add-item-form-container" style="margin-top: 20px; background-color: var(--color-bg); padding: 20px; border-radius: var(--radius-md); border: 1px solid var(--color-border);">
            <h4 style="font-size: 14px; margin-bottom: 12px; color: var(--color-primary); display: flex; align-items: center; gap: 6px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                Adicionar Produto à Cotação
            </h4>
            
            <div style="display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end;">
                <!-- Live Product Search Box with Dropdown -->
                <div style="flex-grow: 1; min-width: 280px; position: relative;">
                    <label class="form-label" style="font-size: 12px; font-weight: 600;">Buscar Produto (Código, Nome ou Marca)</label>
                    <input type="hidden" id="selected-product-id">
                    <div style="position: relative;">
                        <input type="text" id="add-product-search-input" class="form-control" 
                               placeholder="🔍 Digite para buscar qualquer produto..." 
                               oninput="onAddProductSearchInput(this.value)" 
                               onfocus="onAddProductSearchFocus()" 
                               autocomplete="off" 
                               style="padding-right: 30px;">
                        <button type="button" id="clear-selected-prod-btn" onclick="clearSelectedProduct()" 
                                style="display: none; position: absolute; right: 10px; top: 50%; transform: translateY(-50%); border: none; background: none; color: #94a3b8; font-size: 18px; cursor: pointer; line-height: 1;">&times;</button>
                    </div>
                    <!-- Dynamic Autocomplete Results Menu -->
                    <div id="add-product-results-menu" class="product-autocomplete-results" style="display: none;"></div>
                </div>

                <div style="width: 100px;">
                    <label class="form-label" style="font-size: 12px;">Quantidade</label>
                    <input type="number" id="new-item-qtd" class="form-control text-center" value="1" min="1" oninput="calculateNewItemSubtotal()">
                </div>
                <div style="width: 120px;">
                    <label class="form-label" style="font-size: 12px;">Preço Sugerido</label>
                    <input type="number" id="new-item-sugerido" class="form-control text-right" readonly style="background-color: #f1f5f9; font-weight: 600;">
                </div>
                <div style="width: 120px;">
                    <label class="form-label" style="font-size: 12px;">Preço Mínimo</label>
                    <input type="number" id="new-item-minimo" class="form-control text-right" readonly style="background-color: #f1f5f9; color: #64748b;">
                </div>
                <div style="width: 130px;">
                    <label class="form-label" style="font-size: 12px;">Preço Proposto</label>
                    <input type="number" id="new-item-proposto" class="form-control text-right" step="0.01" min="0.01" style="font-weight: 700; color: var(--color-primary);" oninput="calculateNewItemSubtotal()">
                </div>
                <div>
                    <button type="button" class="btn btn-secondary" onclick="addNewItem()" style="padding: 9px 18px; font-weight: 600; font-size: 13px;">
                        + Adicionar
                    </button>
                </div>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 10px;">
                <div id="selected-product-badge" style="display: none; font-size: 12px; color: #059669; background: #ecfdf5; border: 1px solid #a7f3d0; padding: 4px 10px; border-radius: 6px;">
                    ✓ Produto selecionado: <strong id="selected-product-label"></strong>
                </div>
                <div style="font-size: 13px; font-weight: 600; color: var(--color-primary); margin-left: auto;" id="new-item-subtotal-label">
                    Subtotal Proposto: R$ 0,00
                </div>
            </div>
        </div>
    </div>

    <!-- Totals & Notes Section -->
    <div class="grid-2">
        <!-- Notes Card -->
        <div class="card">
            <div class="card-header">
                <h3>Observações da Cotação</h3>
            </div>
            <div class="form-group">
                <label for="obs-cliente" class="form-label">Observações para o Cliente (Visível no PDF)</label>
                <textarea id="obs-cliente" class="form-control" rows="3" placeholder="Ex: Prazo de entrega de 5 dias úteis."></textarea>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label for="obs-interna" class="form-label">Observações Internas (Exclusivo da Empresa)</label>
                <textarea id="obs-interna" class="form-control" rows="3" placeholder="Ex: Cliente solicita prioridade no faturamento."></textarea>
            </div>
        </div>

        <!-- Totals Card -->
        <div class="card" style="display: flex; flex-direction: column; justify-content: space-between;">
            <div class="card-header">
                <h3>Valores Finais</h3>
            </div>
            <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 20px;">
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--color-text-muted);">Subtotal Sugerido:</span>
                    <span style="font-weight: 500;" id="total-sugerido">R$ 0,00</span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--color-text-muted);">Desconto Comercial:</span>
                    <span style="font-weight: 500; color: #dc2626;" id="total-desconto">- R$ 0,00</span>
                </div>
                <div style="display: flex; justify-content: space-between; border-top: 2px solid var(--color-border); padding-top: 15px;">
                    <span style="font-size: 16px; font-weight: 600;">Total Proposto:</span>
                    <span style="font-size: 20px; font-weight: 700; color: var(--color-primary);" id="total-liquido">R$ 0,00</span>
                </div>
            </div>

            <!-- Conditions Form -->
            <div style="background-color: var(--color-bg); padding: 15px; border-radius: var(--radius-md); border: 1px solid var(--color-border);">
                <h4 style="font-size: 13px; margin-bottom: 10px; font-weight: 600;">Condições de Entrega / Pagamento</h4>
                <div class="grid-2" style="gap: 10px;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label" style="font-size: 11px;">Forma Pagamento</label>
                        <input type="text" id="forma-pagamento" class="form-control" style="padding: 6px 10px; font-size: 12px;">
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label" style="font-size: 11px;">Prazo Entrega</label>
                        <input type="text" id="prazo-entrega" class="form-control" style="padding: 6px 10px; font-size: 12px;">
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label" style="font-size: 11px;">Frete Tipo</label>
                        <select id="frete-tipo" class="form-control" style="padding: 6px 10px; font-size: 12px;">
                            <option value="CIF">CIF (Por conta do remetente)</option>
                            <option value="FOB">FOB (Por conta do destinatário)</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label" style="font-size: 11px;">Transportadora</label>
                        <input type="text" id="transportadora" class="form-control" style="padding: 6px 10px; font-size: 12px;">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Justification panel (Visible if items below minimum) -->
    <div id="justification-panel" class="card" style="display: none; border: 1px solid #f59e0b; background-color: #fffbeb;">
        <div class="card-header" style="border-bottom: 1px solid #fef3c7;">
            <h3 style="color: #d97706; display: flex; align-items: center; gap: 8px;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                Justificativa Necessária — Desconto Especial Detectado
            </h3>
        </div>
        <p style="font-size: 13px; color: #b45309; margin-bottom: 15px;">
            Existem produtos abaixo do preço mínimo configurado. É obrigatório registrar uma justificativa e anexar comprovantes para enviar a cotação ao gestor.
        </p>
        
        <div class="form-group">
            <label class="form-label" style="color: #b45309;">Justificativa por Escrito</label>
            <textarea id="just-texto" class="form-control" rows="3" placeholder="Justifique o motivo do desconto especial (ex: equiparação de preço com concorrente X)..." style="background-color: #ffffff; border-color: #fcd34d;"></textarea>
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label class="form-label" style="color: #b45309; font-weight: 600;">Anexos / Documentos (Comprovante Concorrente, etc)</label>
                <input type="file" id="just-anexos" class="form-control" multiple 
                       accept=".pdf,.png,.jpg,.jpeg,.webp,.gif,.doc,.docx,.xls,.xlsx,.csv,.ppt,.pptx,.odt,.ods,.odp,application/pdf,image/jpeg,image/png,image/webp,image/gif,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/csv,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation"
                       style="background-color: #ffffff; border-color: #fcd34d; padding: 6px 10px;">
                <small style="display: block; margin-top: 5px; font-size: 11px; color: #b45309;">
                    Formatos aceitos: PDF, imagens (JPG, PNG, WEBP) e Office (Word, Excel, PowerPoint). Limite: 10MB por arquivo.
                </small>
                <div id="just-anexos-preview" style="margin-top: 6px; font-size: 12px; display: none;"></div>
            </div>
            <div class="form-group">
                <label class="form-label" style="color: #b45309; font-weight: 600;">Upload de Justificativa por Áudio (Gravador/Arquivo)</label>
                <input type="file" id="just-audio" class="form-control" accept="audio/*,audio/mpeg,audio/wav,audio/ogg,audio/mp4,audio/m4a" style="background-color: #ffffff; border-color: #fcd34d; padding: 6px 10px;">
                <small style="display: block; margin-top: 5px; font-size: 11px; color: #b45309;">
                    Formatos aceitos: MP3, WAV, OGG, M4A. Limite: 10MB.
                </small>
                <div id="just-audio-preview" style="margin-top: 6px; font-size: 12px; display: none;"></div>
            </div>
        </div>
    </div>

    <!-- Floating Actions Panel -->
    <div class="card actions-panel-card" style="display: flex; justify-content: space-between; align-items: center; background-color: var(--color-card); box-shadow: var(--shadow-lg);">
        <div class="left-actions">
            <button id="btn-lost" class="btn btn-danger" onclick="openLostModal()">Marcar como Perdida</button>
        </div>
        <div class="right-actions" style="display: flex; gap: 12px; align-items: center;">
            <button id="btn-draft" class="btn btn-outline" onclick="saveDraft(true)">Salvar Rascunho</button>
            <button id="btn-pdf" class="btn btn-secondary" onclick="downloadPdf()" style="background-color: #10b981; border-color: #10b981; color: white; display: none;">📄 Gerar PDF</button>
            <button id="btn-release" class="btn btn-primary" onclick="openReleaseModal()" style="background-color: #0d9488; border-color: #0d9488; display: none;">Liberar para Faturamento</button>
            <button id="btn-submit" class="btn btn-primary" onclick="submitQuote()">Enviar para Aprovação</button>
        </div>
    </div>
</div>

<!-- Loading indicator -->
<div id="loading-spinner" class="centered-layout" style="min-height: 50vh;">
    <div style="text-align: center;">
        <div style="border: 4px solid rgba(15,81,50,0.1); border-top: 4px solid var(--color-primary); border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 0 auto 15px auto;"></div>
        <span style="font-weight: 500; color: var(--color-text-muted);">Carregando cotação comercial...</span>
    </div>
</div>

<!-- Lost quote modal -->
<div id="lost-modal" style="display: none; position: fixed; inset: 0; background-color: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 1000; padding: 20px;">
    <div class="auth-card" style="max-width: 500px; padding: 30px;">
        <h3 style="margin-bottom: 12px; color: var(--status-perdida);">Marcar Cotação como Perdida</h3>
        <p style="font-size: 13px; color: var(--color-text-muted); margin-bottom: 16px;">
            Você está prestes a encerrar esta cotação como perdida comercialmente. Esta ação é irreversível e exige justificativa.
        </p>
        <div class="form-group" style="margin-bottom: 20px;">
            <label for="lost-reason" class="form-label">Motivo do Fechamento Negativo</label>
            <textarea id="lost-reason" class="form-control" rows="3" placeholder="Descreva por que o cliente declinou a proposta..." required></textarea>
        </div>
        <div style="display: flex; justify-content: flex-end; gap: 12px;">
            <button class="btn btn-outline" onclick="closeLostModal()">Cancelar</button>
            <button class="btn btn-danger" onclick="confirmLost()">Confirmar Perda</button>
        </div>
    </div>
</div>

<!-- Release for Billing Modal -->
<div id="release-modal" style="display: none; position: fixed; inset: 0; background-color: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 1000; padding: 20px;">
    <div class="auth-card" style="max-width: 500px; padding: 30px;">
        <h3 style="margin-bottom: 12px; color: #0d9488;">Liberar para Faturamento</h3>
        <p style="font-size: 13px; color: var(--color-text-muted); margin-bottom: 16px;">
            Esta cotação foi aprovada. Preencha os dados do pedido no Sankhya para liberá-la para o faturamento.
        </p>
        
        <div class="form-group" style="margin-bottom: 12px;">
            <label for="release-pedido-externo" class="form-label">Número do Pedido no Sankhya</label>
            <input type="text" id="release-pedido-externo" class="form-control" placeholder="Ex: 509230" required>
        </div>

        <div class="form-group" style="margin-bottom: 12px;">
            <label for="release-tipo-faturamento" class="form-label">Tipo de Faturamento</label>
            <select id="release-tipo-faturamento" class="form-control" onchange="onReleaseTypeChange(this.value)">
                <option value="total">Faturamento Total (Valor Integral)</option>
                <option value="parcial">Faturamento Parcial</option>
            </select>
        </div>

        <div class="form-group" style="margin-bottom: 20px;">
            <label for="release-valor-pedido" class="form-label">Valor do Pedido (R$)</label>
            <input type="number" id="release-valor-pedido" class="form-control" step="0.01" min="0.01" required>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 12px;">
            <button class="btn btn-outline" onclick="closeReleaseModal()">Cancelar</button>
            <button class="btn btn-primary" onclick="confirmRelease()" style="background-color: #0d9488; border-color: #0d9488;">Liberar Faturamento</button>
        </div>
    </div>
</div>

<!-- Floating Sticky Total Bar for Mobile -->
<div id="sticky-mobile-bar" class="sticky-mobile-total-bar">
    <div>
        <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.8;">Total da Cotação</div>
        <div id="mobile-sticky-total" style="font-size: 18px; font-weight: 700;">R$ 0,00</div>
    </div>
    <div id="mobile-action-container">
        <button id="mobile-btn-submit" type="button" class="btn btn-primary" onclick="submitQuote()" style="padding: 8px 16px; font-size: 13px; background: #2563eb; border: none; border-radius: 8px; font-weight: 600;">
            Enviar Cotação
        </button>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Responsive In-Screen Toast Notifications (replaces blocking browser alerts)
    function showToast(message, type = 'info', title = null) {
        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            container.className = 'toast-container';
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        toast.className = `app-toast app-toast-${type}`;

        let icon = 'ℹ️';
        let defaultTitle = 'Aviso';
        if (type === 'success') {
            icon = '✓';
            defaultTitle = 'Sucesso';
        } else if (type === 'error') {
            icon = '✕';
            defaultTitle = 'Erro';
        } else if (type === 'warning') {
            icon = '⚠️';
            defaultTitle = 'Atenção';
        }

        const toastTitle = title || defaultTitle;
        const formattedMsg = String(message || '').replace(/\n/g, '<br>');

        toast.innerHTML = `
            <span class="app-toast-icon">${icon}</span>
            <div class="app-toast-body">
                <div class="app-toast-title">${toastTitle}</div>
                <div class="app-toast-msg">${formattedMsg}</div>
            </div>
            <button type="button" class="app-toast-close" title="Fechar">&times;</button>
        `;

        const closeBtn = toast.querySelector('.app-toast-close');
        const dismiss = () => {
            toast.classList.remove('show');
            toast.classList.add('hide');
            setTimeout(() => {
                if (toast.parentElement) toast.remove();
            }, 250);
        };

        if (closeBtn) {
            closeBtn.onclick = (e) => {
                e.stopPropagation();
                dismiss();
            };
        }

        toast.onclick = dismiss;

        container.appendChild(toast);
        requestAnimationFrame(() => {
            toast.classList.add('show');
        });

        const duration = (type === 'error' || String(message).length > 80) ? 6000 : 4000;
        setTimeout(() => {
            if (toast.parentElement) dismiss();
        }, duration);
    }

    // Global override to guarantee no native alert locks the mobile screen
    window.alert = function(msg) {
        showToast(msg, 'warning');
    };

    const QUOTE_ID = {{ isset($quoteId) ? (int)$quoteId : 'null' }};
    const TOKEN = "{{ $token ?? '' }}";
    const API_URL = "/api/v1";
    const QUOTE_BASE_URL = TOKEN ? `${API_URL}/cotacoes/token/${encodeURIComponent(TOKEN)}` : `${API_URL}/cotacoes/${QUOTE_ID}`;
    let quote = null;
    let productsList = [];
    let isEditingLocked = false;

    document.addEventListener("DOMContentLoaded", () => {
        checkUserSession();
        loadData();
        setupAttachmentValidation();
    });

    async function loadData() {
        try {
            // 1. Fetch Quote Details
            const quoteRes = await fetch(`${QUOTE_BASE_URL}`);
            if (!quoteRes.ok) {
                const errText = await quoteRes.text();
                let errMsg = "Erro HTTP " + quoteRes.status;
                try {
                    const parsed = JSON.parse(errText);
                    if (parsed.error || parsed.message) errMsg = parsed.error || parsed.message;
                } catch(e) {}
                showToast("Erro ao carregar a cotação: " + errMsg, 'error');
                return;
            }

            const quoteData = await quoteRes.json();
            if (!quoteData.success) {
                showToast("Erro ao carregar a cotação: " + (quoteData.error || quoteData.message || "Cotação não encontrada"), 'error');
                return;
            }
            quote = quoteData.data;

            // 2. Fetch Products Catalog for addition using quote endpoint
            try {
                const prodRes = await fetch(`${QUOTE_BASE_URL}/produtos`);
                if (prodRes.ok) {
                    const prodData = await prodRes.json();
                    if (prodData.success) {
                        productsList = prodData.data;
                    }
                }
            } catch (errProd) {
                console.warn("Não foi possível carregar o catálogo de produtos:", errProd);
            }

            renderView();
        } catch (e) {
            console.error("Erro em loadData:", e);
            showToast("Falha na conexão com o servidor: " + (e.message || "Erro de rede"), 'error');
        }
    }

    function renderView() {
        if (!quote) return;

        // Hide spinner & show panel
        document.getElementById("loading-spinner").style.display = "none";
        document.getElementById("representative-panel").style.display = "block";

        // Bind header details
        document.getElementById("quote-number").innerText = quote.numero;
        document.getElementById("quote-origin").innerText = quote.origem.toUpperCase();
        document.getElementById("quote-emission").innerText = new Date(quote.data_emissao).toLocaleString('pt-BR');
        document.getElementById("quote-validity").innerText = quote.data_validade ? new Date(quote.data_validade).toLocaleString('pt-BR') : 'Sem data';

        // Check if locked from editing (only EM_CRIACAO and DEVOLVIDA allow modifications)
        const editableStatuses = ['EM_CRIACAO', 'DEVOLVIDA'];
        isEditingLocked = !editableStatuses.includes(quote.status);

        // Update Locked Notice Banner
        const banner = document.getElementById("locked-status-banner");
        const bannerText = document.getElementById("locked-status-banner-text");
        if (banner && bannerText) {
            if (isEditingLocked) {
                banner.style.display = "flex";
                if (quote.status === 'AGUARDANDO_GESTOR') {
                    bannerText.innerHTML = "<strong>Cotação em análise de alçada:</strong> Aguardando aprovação do Gestor de Equipe. Os campos estão em modo somente leitura.";
                } else if (quote.status === 'COM_DIRETOR') {
                    bannerText.innerHTML = "<strong>Cotação em análise com a Diretoria:</strong> Aguardando decisão superior. Os campos estão em modo somente leitura.";
                } else if (quote.status === 'APROVADA') {
                    bannerText.innerHTML = "<strong>Cotação Aprovada:</strong> Proposta comercial aprovada! Clique em <strong>'📄 Gerar PDF'</strong> para emitir o documento e habilitar a liberação de faturamento.";
                } else if (quote.status === 'PDF_GERADO') {
                    bannerText.innerHTML = "<strong>PDF Gerado:</strong> Proposta em PDF emitida com sucesso. Clique em <strong>'Liberar para Faturamento'</strong> para registrar o pedido.";
                } else if (quote.status === 'FINALIZADA_COM_PEDIDO') {
                    bannerText.innerHTML = "<strong>Cotação Finalizada:</strong> Pedido externo já registrado para conferência e faturamento.";
                } else if (quote.status === 'FATURADA') {
                    bannerText.innerHTML = "<strong>Cotação Faturada:</strong> Processo comercial e faturamento concluídos.";
                } else if (quote.status === 'PERDIDA') {
                    bannerText.innerHTML = "<strong>Cotação Perdida:</strong> Negociação encerrada sem fechamento de pedido.";
                } else if (quote.status === 'EXPIRADA') {
                    bannerText.innerHTML = "<strong>Cotação Expirada:</strong> Prazo de validade comercial ultrapassado.";
                } else {
                    bannerText.innerHTML = "<strong>Modo Somente Leitura:</strong> Cotação com status " + quote.status.replace(/_/g, ' ') + ".";
                }
            } else {
                banner.style.display = "none";
            }
        }

        // Bind status badge
        const badge = document.getElementById("quote-status-badge");
        badge.className = "badge-status " + quote.status.toLowerCase().replace(/_/g, '-');
        if (quote.status === 'APROVADA') {
            badge.innerText = 'Aprovada (Pendente PDF)';
        } else if (quote.status === 'PDF_GERADO') {
            badge.innerText = 'PDF Gerado';
        } else {
            badge.innerText = quote.status.replace(/_/g, ' ');
        }

        // Bind Client profile
        const clientCity = (quote.parceiro && quote.parceiro.cidade && quote.parceiro.cidade !== 'null') ? quote.parceiro.cidade : '';
        const clientUf = (quote.parceiro && quote.parceiro.uf && quote.parceiro.uf !== 'null') ? quote.parceiro.uf : '';
        const locationText = (clientCity || clientUf) ? `${clientCity}${clientCity && clientUf ? ' - ' : ''}${clientUf}` : 'Não informada';

        document.getElementById("client-name").innerText = quote.parceiro.razao_social;
        document.getElementById("client-cnpj").innerText = "CNPJ/CPF: " + (quote.parceiro.cnpj || 'Não cadastrado');
        document.getElementById("client-location").innerText = "Localidade: " + locationText;
        document.getElementById("client-contact").innerText = "Contato: " + (quote.parceiro.telefone || quote.parceiro.email || 'N/A');

        // Bind Rep profile
        document.getElementById("rep-name").innerText = quote.representante.nome;
        document.getElementById("rep-team").innerText = "Equipe: " + (quote.representante.equipe ? quote.representante.equipe.nome : 'Sem Equipe');
        document.getElementById("rep-email").innerText = "E-mail: " + quote.representante.email;
        document.getElementById("rep-phone").innerText = "Celular: " + (quote.representante.telefone || 'Não cadastrado');

        // Bind Commercial Conditions
        document.getElementById("forma-pagamento").value = quote.forma_pagamento || "";
        document.getElementById("prazo-entrega").value = quote.prazo_entrega || "";
        document.getElementById("frete-tipo").value = quote.frete_tipo || "CIF";
        document.getElementById("transportadora").value = quote.transportadora || "";
        document.getElementById("obs-cliente").value = quote.observacao_cliente || "";
        document.getElementById("obs-interna").value = quote.observacao_interna || "";

        const btnPdf = document.getElementById("btn-pdf");
        const btnRelease = document.getElementById("btn-release");
        const btnSubmit = document.getElementById("btn-submit");
        const btnDraft = document.getElementById("btn-draft");
        const btnLost = document.getElementById("btn-lost");
        const addItemContainer = document.getElementById("add-item-form-container");
        const mobileActionContainer = document.getElementById("mobile-action-container");

        if (isEditingLocked) {
            document.getElementById("forma-pagamento").disabled = true;
            document.getElementById("prazo-entrega").disabled = true;
            document.getElementById("frete-tipo").disabled = true;
            document.getElementById("transportadora").disabled = true;
            document.getElementById("obs-cliente").disabled = true;
            document.getElementById("obs-interna").disabled = true;
            
            if (addItemContainer) addItemContainer.style.display = "none";
            if (btnLost) btnLost.style.display = "none";
            if (btnDraft) btnDraft.style.display = "none";
            if (btnSubmit) btnSubmit.style.display = "none";
            if (mobileActionContainer) mobileActionContainer.style.display = "none";
        } else {
            document.getElementById("forma-pagamento").disabled = false;
            document.getElementById("prazo-entrega").disabled = false;
            document.getElementById("frete-tipo").disabled = false;
            document.getElementById("transportadora").disabled = false;
            document.getElementById("obs-cliente").disabled = false;
            document.getElementById("obs-interna").disabled = false;
            
            if (addItemContainer) addItemContainer.style.display = "block";
            if (btnLost) btnLost.style.display = "inline-block";
            if (btnDraft) btnDraft.style.display = "inline-block";
            if (btnSubmit) btnSubmit.style.display = "inline-block";
            if (mobileActionContainer) mobileActionContainer.style.display = "block";
        }

        // Action Sequence Rules:
        // 1. PDF can ONLY be generated after approval (APROVADA, PDF_GERADO, and post-approval)
        const allowPdfStatuses = ['APROVADA', 'PDF_GERADO', 'AGUARDANDO_PEDIDO', 'FINALIZADA_COM_PEDIDO', 'FATURADA'];
        if (btnPdf) {
            btnPdf.style.display = allowPdfStatuses.includes(quote.status) ? "inline-block" : "none";
            btnPdf.disabled = !allowPdfStatuses.includes(quote.status);
        }

        // 2. "Liberar para Faturamento" can ONLY be executed after PDF has been generated (PDF_GERADO)
        if (btnRelease) {
            btnRelease.style.display = (quote.status === 'PDF_GERADO') ? "inline-block" : "none";
        }

        // Bind Items List
        renderItems();
    }

    let currentFilterTab = 'all'; // 'all', 'attention', 'approved'
    let currentSearchQuery = '';

    function onSearchInput(val) {
        currentSearchQuery = (val || '').trim().toLowerCase();
        renderItems();
    }

    function setFilterTab(tabName) {
        currentFilterTab = tabName;
        document.querySelectorAll('.filter-tab-btn').forEach(btn => btn.classList.remove('active'));
        const activeBtn = document.getElementById(`tab-${tabName}`);
        if (activeBtn) activeBtn.classList.add('active');
        renderItems();
    }

    function stepQty(itemId, delta) {
        if (isEditingLocked) {
            showToast("Esta cotação está com status " + quote.status.replace(/_/g, ' ') + " e não permite edição.", 'warning');
            return;
        }
        const item = quote.itens.find(i => i.id === itemId);
        if (!item) return;
        const current = parseInt(item.qtd) || 1;
        const updated = Math.max(1, current + delta);
        updateItemCalculations(itemId, updated, null);
        renderItems();
    }

    function formatCurrency(val) {
        const num = typeof val === 'number' ? val : parseFloat(val || 0);
        return num.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function getItemEffectiveMin(item) {
        const sug = parseFloat(item.preco_unit_sugerido || 0);
        const min = parseFloat(item.preco_minimo || 0);
        // Quando o mínimo cadastral for maior que o sugerido (inconsistência cadastral),
        // o piso de tolerância para não bloquear o representante é o preço sugerido.
        if (sug > 0 && min > sug) {
            return sug;
        }
        return min;
    }

    function isItemInconsistent(item) {
        const sug = parseFloat(item.preco_unit_sugerido || 0);
        const min = parseFloat(item.preco_minimo || 0);
        return (sug > 0 && min > sug);
    }

    function renderItems() {
        const body = document.getElementById("items-table-body");
        body.innerHTML = "";
        
        let hasItemBelowMin = false;
        let countAll = quote.itens.length;
        let countAttention = 0;
        let countApproved = 0;

        quote.itens.forEach(item => {
            const minEf = getItemEffectiveMin(item);
            const isBelowMin = parseFloat(item.preco_unit_proposto || 0) < minEf;
            if (isBelowMin && item.status_item !== 'aprovado') {
                hasItemBelowMin = true;
                countAttention++;
            } else {
                countApproved++;
            }
        });

        // Update Tab Badges
        const elAll = document.getElementById("count-all");
        const elAtt = document.getElementById("count-attention");
        const elApp = document.getElementById("count-approved");
        if (elAll) elAll.innerText = countAll;
        if (elAtt) elAtt.innerText = countAttention;
        if (elApp) elApp.innerText = countApproved;

        // Filter items for display
        const filtered = quote.itens.filter(item => {
            if (currentSearchQuery) {
                const q = currentSearchQuery;
                const code = String(item.produto.codigo_sankhya).toLowerCase();
                const desc = String(item.produto.descricao).toLowerCase();
                if (!code.includes(q) && !desc.includes(q)) return false;
            }

            const minEf = getItemEffectiveMin(item);
            const isBelowMin = parseFloat(item.preco_unit_proposto || 0) < minEf;
            if (currentFilterTab === 'attention') {
                return isBelowMin && item.status_item !== 'aprovado';
            }
            if (currentFilterTab === 'approved') {
                return !isBelowMin || item.status_item === 'aprovado';
            }
            return true;
        });

        if (filtered.length === 0) {
            body.innerHTML = `
                <tr>
                    <td colspan="9" style="text-align: center; color: var(--color-text-muted); padding: 30px 10px;">
                        Nenhum item encontrado com os filtros aplicados.
                    </td>
                </tr>
            `;
        } else {
            filtered.forEach(item => {
                const minEf = getItemEffectiveMin(item);
                const isInconsistent = isItemInconsistent(item);
                const isBelowMin = parseFloat(item.preco_unit_proposto || 0) < minEf;
                const inputClass = isBelowMin ? "price-below-min" : "";
                const isItemLocked = isEditingLocked || item.status_item === 'recusado';
                const rowClass = item.status_item === 'recusado' ? "recusado" : (item.status_item === 'aprovado' ? "aprovado" : "");

                body.innerHTML += `
                    <tr class="item-card-row ${rowClass} ${isBelowMin ? 'below-min-card' : ''}" id="row-${item.id}">
                        <td class="col-code">
                            <span class="mobile-label">Código:</span>
                            <strong class="code-badge">${item.produto.codigo_sankhya}</strong>
                        </td>
                        <td class="col-desc">
                            <div class="product-desc-title">
                                ${item.produto.descricao}
                                ${item.mostrar_selo_campanha && item.campanha_id ? '<span class="badge-campanha" style="display:inline-block; margin-left:8px;">Campanha</span>' : ''}
                                ${isInconsistent ? '<span class="badge" style="background-color: #fef3c7; color: #92400e; font-size: 11px; padding: 2px 6px; border-radius: 4px; border: 1px solid #fcd34d; margin-left:6px;" title="Preço mínimo cadastral maior que o sugerido. Venda pelo sugerido não exige justificativa.">⚠️ Mín > Sugerido</span>' : ''}
                            </div>
                            <div class="mobile-sub-info">
                                Sug: R$ ${formatCurrency(item.preco_unit_sugerido)} | 
                                Mín: R$ ${formatCurrency(item.preco_minimo)} ${isInconsistent ? '<span style="color:#b45309; font-weight:600;">(divergente)</span>' : ''}
                            </div>
                        </td>
                        <td class="text-center col-un">
                            <span class="mobile-label">Unidade:</span>
                            <span class="un-badge">${item.produto.unidade}</span>
                        </td>
                        <td class="text-center col-qtd">
                            <span class="mobile-label">Qtd:</span>
                            <div class="qty-stepper">
                                ${!isItemLocked ? `<button type="button" class="qty-btn" onclick="stepQty(${item.id}, -1)">–</button>` : ''}
                                <input type="number" class="form-control text-center qty-input" value="${item.qtd}" min="1" 
                                    ${isItemLocked ? 'disabled' : ''} 
                                    oninput="updateItemCalculations(${item.id}, this.value, null)">
                                ${!isItemLocked ? `<button type="button" class="qty-btn" onclick="stepQty(${item.id}, 1)">+</button>` : ''}
                            </div>
                        </td>
                        <td class="text-right col-sugerido desktop-only">R$ ${formatCurrency(item.preco_unit_sugerido)}</td>
                        <td class="text-right col-minimo desktop-only" style="color: #64748b;">
                            R$ ${formatCurrency(item.preco_minimo)}
                            ${isInconsistent ? '<span title="Cadastro com mínimo maior que sugerido. Venda pelo sugerido liberada." style="color:#d97706; font-size:12px; margin-left:2px;">⚠️</span>' : ''}
                        </td>
                        <td class="text-right col-proposto">
                            <span class="mobile-label">Preço Proposto:</span>
                            <div style="display: flex; align-items: center; justify-content: flex-end; gap: 6px;">
                                <input type="number" class="form-control text-right ${inputClass}" 
                                    value="${(parseFloat(item.preco_unit_proposto) || 0).toFixed(2)}" step="0.01" min="0.01"
                                    style="width: 105px; padding: 5px 8px; font-size: 14px; font-weight: 600;" 
                                    ${isItemLocked ? 'disabled' : ''} 
                                    oninput="updateItemCalculations(${item.id}, null, this.value)">
                                ${!isItemLocked && isBelowMin ? `<button type="button" class="btn btn-outline btn-min-fix" onclick="resetToMin(${item.id}, ${minEf})">Mín</button>` : ''}
                            </div>
                        </td>
                        <td class="text-center col-status">
                            <span class="badge-status ${item.status_item}">${item.status_item}</span>
                        </td>
                        <td class="text-center col-actions">
                            ${!isItemLocked ? `
                                <button type="button" class="btn btn-outline" style="padding: 6px 10px; border-color: #fecaca; color: #ef4444;" onclick="deleteItem(${item.id})" title="Remover item">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                                </button>
                            ` : '-'}
                        </td>
                    </tr>
                `;
            });
        }

        // Show/hide justification box
        document.getElementById("justification-panel").style.display = hasItemBelowMin && !isEditingLocked ? "block" : "none";
        
        recalculateTotalsFromState();
    }

    function updateItemCalculations(itemId, newQtd, newPrice) {
        if (isEditingLocked) return;
        const item = quote.itens.find(i => i.id === itemId);
        if (!item) return;

        if (newQtd !== null) item.qtd = parseInt(newQtd) || 1;
        if (newPrice !== null) item.preco_unit_proposto = parseFloat(parseFloat(newPrice).toFixed(2)) || 0.00;

        item.subtotal = item.qtd * item.preco_unit_proposto;
        
        // Recalculate discount percentage
        const sugerido = parseFloat(item.preco_unit_sugerido);
        item.ajuste_percentual = sugerido > 0 ? ((item.preco_unit_proposto - sugerido) / sugerido) * 100 : 0;

        // Apply price-below-min warning class dynamically in DOM
        const row = document.getElementById(`row-${itemId}`);
        if (row) {
            const inputPrice = row.querySelector("td:nth-child(7) input[type='number']");
            const minEf = getItemEffectiveMin(item);
            const isBelowMin = parseFloat(item.preco_unit_proposto) < minEf;
            
            if (isBelowMin) {
                inputPrice.classList.add("price-below-min");
                row.classList.add("below-min-card");
            } else {
                inputPrice.classList.remove("price-below-min");
                row.classList.remove("below-min-card");
            }
        }

        // Evaluate if any active item triggers the justification requirement
        let hasItemBelowMin = false;
        quote.itens.forEach(i => {
            const minEf = getItemEffectiveMin(i);
            if (parseFloat(i.preco_unit_proposto) < minEf && i.status_item !== 'aprovado') {
                hasItemBelowMin = true;
            }
        });
        document.getElementById("justification-panel").style.display = hasItemBelowMin && !isEditingLocked ? "block" : "none";

        recalculateTotalsFromState();
    }

    function resetToMin(itemId, minPrice) {
        if (isEditingLocked) return;
        const row = document.getElementById(`row-${itemId}`);
        if (row) {
            const inputPrice = row.querySelector("td:nth-child(7) input[type='number']");
            if (inputPrice) {
                const roundedPrice = parseFloat(minPrice).toFixed(2);
                inputPrice.value = roundedPrice;
                updateItemCalculations(itemId, null, roundedPrice);
                renderItems(); // re-render to update warning highlights
            }
        }
    }

    function recalculateTotalsFromState() {
        let subtotal = 0;
        let total = 0;

        quote.itens.forEach(item => {
            if (item.status_item === 'recusado') {
                return;
            }
            subtotal += item.qtd * Math.max(parseFloat(item.preco_unit_sugerido), parseFloat(item.preco_unit_proposto));
            total += parseFloat(item.subtotal);
        });

        const desconto = subtotal - total;

        document.getElementById("total-sugerido").innerText = "R$ " + formatCurrency(subtotal);
        document.getElementById("total-desconto").innerText = "- R$ " + formatCurrency(desconto > 0 ? desconto : 0);
        document.getElementById("total-liquido").innerText = "R$ " + formatCurrency(total);

        const mobileTotalEl = document.getElementById("mobile-sticky-total");
        if (mobileTotalEl) {
            mobileTotalEl.innerText = "R$ " + formatCurrency(total);
        }
    }

    // Live Autocomplete Product Search for Add Item Flow
    let addProductSearchTimer = null;
    let currentSearchedProducts = [];

    function onAddProductSearchInput(query) {
        clearTimeout(addProductSearchTimer);
        if (!query || query.trim().length < 1) {
            hideAddProductDropdown();
            return;
        }
        addProductSearchTimer = setTimeout(() => {
            fetchSearchProductsForAdd(query.trim());
        }, 250);
    }

    function onAddProductSearchFocus() {
        const input = document.getElementById("add-product-search-input");
        if (input && input.value.trim().length >= 1) {
            fetchSearchProductsForAdd(input.value.trim());
        }
    }

    async function fetchSearchProductsForAdd(query) {
        const menu = document.getElementById("add-product-results-menu");
        if (!menu) return;
        menu.style.display = "block";
        menu.innerHTML = `<div style="padding: 12px; text-align: center; color: #64748b; font-size: 12px;">🔍 Buscando no catálogo...</div>`;

        try {
            const res = await fetch(`${QUOTE_BASE_URL}/produtos?search=${encodeURIComponent(query)}&limit=30`);
            if (!res.ok) {
                menu.innerHTML = `<div style="padding: 12px; text-align: center; color: #ef4444; font-size: 12px;">Erro ao buscar produtos.</div>`;
                return;
            }

            const data = await res.json();
            if (data.success && data.data.length > 0) {
                currentSearchedProducts = data.data;
                renderAddProductSearchResults(data.data);
            } else {
                menu.innerHTML = `<div style="padding: 12px; text-align: center; color: #94a3b8; font-size: 12px;">Nenhum produto encontrado para "${query}".</div>`;
            }
        } catch (e) {
            menu.innerHTML = `<div style="padding: 12px; text-align: center; color: #ef4444; font-size: 12px;">Erro de conexão.</div>`;
        }
    }

    function renderAddProductSearchResults(products) {
        const menu = document.getElementById("add-product-results-menu");
        if (!menu) return;
        menu.innerHTML = "";

        products.forEach(p => {
            const div = document.createElement("div");
            div.className = "product-autocomplete-item";
            div.onclick = () => selectProductForAdd(p.id);

            const sugVal = p.preco_sugerido ? parseFloat(p.preco_sugerido) : 150.00;
            const sugText = formatCurrency(sugVal);

            div.innerHTML = `
                <div style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap; margin-right: 10px;">
                    <span class="prod-code">${p.codigo_sankhya}</span>
                    <span class="prod-title">${p.descricao}</span>
                    ${p.marca ? `<span style="font-size: 11px; color: #64748b; margin-left: 6px;">(${p.marca})</span>` : ''}
                </div>
                <div class="prod-price">R$ ${sugText}</div>
            `;
            menu.appendChild(div);
        });
    }

    function selectProductForAdd(productId) {
        const p = currentSearchedProducts.find(item => item.id === productId);
        if (!p) return;

        document.getElementById("selected-product-id").value = p.id;
        document.getElementById("add-product-search-input").value = `${p.codigo_sankhya} - ${p.descricao}`;
        document.getElementById("clear-selected-prod-btn").style.display = "block";
        
        // Set pricing from database or default
        const sugerido = p.preco_sugerido ? parseFloat(p.preco_sugerido) : 150.00;
        const minimo = p.preco_minimo ? parseFloat(p.preco_minimo) : roundNumber(sugerido * 0.90, 2);

        document.getElementById("new-item-sugerido").value = sugerido.toFixed(2);
        document.getElementById("new-item-minimo").value = minimo.toFixed(2);
        document.getElementById("new-item-proposto").value = sugerido.toFixed(2);

        // Show selected badge
        const badge = document.getElementById("selected-product-badge");
        if (badge) {
            badge.style.display = "inline-block";
            document.getElementById("selected-product-label").innerText = `${p.codigo_sankhya} | ${p.descricao} (${p.unidade})`;
        }

        hideAddProductDropdown();
        calculateNewItemSubtotal();
    }

    function clearSelectedProduct() {
        document.getElementById("selected-product-id").value = "";
        document.getElementById("add-product-search-input").value = "";
        document.getElementById("clear-selected-prod-btn").style.display = "none";
        document.getElementById("new-item-sugerido").value = "";
        document.getElementById("new-item-minimo").value = "";
        document.getElementById("new-item-proposto").value = "";
        document.getElementById("new-item-qtd").value = "1";
        document.getElementById("new-item-subtotal-label").innerText = "Subtotal Proposto: R$ 0,00";
        
        const badge = document.getElementById("selected-product-badge");
        if (badge) badge.style.display = "none";
        
        hideAddProductDropdown();
    }

    function hideAddProductDropdown() {
        const menu = document.getElementById("add-product-results-menu");
        if (menu) menu.style.display = "none";
    }

    function roundNumber(num, scale) {
        if (!("" + num).includes("e")) {
            return +(Math.round(num + "e+" + scale) + "e-" + scale);
        } else {
            const arr = ("" + num).split("e");
            let sig = "";
            if (+arr[1] + scale > 0) {
                sig = "+";
            }
            return +(Math.round(+arr[0] + "e" + sig + (+arr[1] + scale)) + "e-" + scale);
        }
    }

    // Close dropdown when clicking outside
    document.addEventListener("click", (e) => {
        const container = document.getElementById("add-item-form-container");
        if (container && !container.contains(e.target)) {
            hideAddProductDropdown();
        }
    });

    function calculateNewItemSubtotal() {
        const qtd = parseInt(document.getElementById("new-item-qtd").value) || 0;
        const proposto = parseFloat(document.getElementById("new-item-proposto").value) || 0.00;
        const sub = qtd * proposto;
        document.getElementById("new-item-subtotal-label").innerText = "Subtotal Proposto: R$ " + formatCurrency(sub);
    }

    async function addNewItem() {
        const productId = document.getElementById("selected-product-id").value;
        if (!productId) {
            showToast("Por favor, digite no campo de busca e selecione um produto da lista.", 'warning');
            return;
        }

        const sugerido = parseFloat(parseFloat(document.getElementById("new-item-sugerido").value).toFixed(2)) || 0;
        const minimo = parseFloat(parseFloat(document.getElementById("new-item-minimo").value).toFixed(2)) || 0;
        const proposto = parseFloat(parseFloat(document.getElementById("new-item-proposto").value).toFixed(2)) || 0;

        if (proposto <= 0) {
            showToast("Por favor, informe um preço proposto válido.", 'warning');
            return;
        }

        if (isEditingLocked) {
            showToast("Esta cotação não permite inclusão de produtos no status atual (" + quote.status.replace(/_/g, ' ') + ").", 'warning');
            return;
        }

        const payload = {
            produto_id: parseInt(productId),
            qtd: parseInt(document.getElementById("new-item-qtd").value) || 1,
            preco_unit_proposto: proposto,
            preco_unit_sugerido: sugerido,
            preco_minimo: minimo,
            margem_calculada: 30.00,
            custo: minimo * 0.7,
            imposto: 18.00,
        };

        try {
            const res = await fetch(`${QUOTE_BASE_URL}/itens`, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(payload)
            });
            const data = await res.json().catch(() => ({}));
            if (res.ok && data.success) {
                quote.itens = data.data.itens;
                renderItems();
                clearSelectedProduct();
            } else {
                showToast("Erro ao adicionar item: " + (data.message || data.error || ("Operação recusada pelo servidor HTTP " + res.status)), 'error');
            }
        } catch (e) {
            showToast("Erro de conexão ao adicionar produto.", 'error');
        }
    }

    async function deleteItem(itemId) {
        if (isEditingLocked) {
            showToast("Esta cotação não permite remoção de itens no status atual (" + quote.status.replace(/_/g, ' ') + ").", 'warning');
            return;
        }
        if (!confirm("Deseja realmente remover este item?")) return;

        try {
            const res = await fetch(`${QUOTE_BASE_URL}/itens/${itemId}`, {
                method: "DELETE"
            });
            const data = await res.json().catch(() => ({}));
            if (res.ok && data.success) {
                quote.itens = data.data.itens;
                renderItems();
            } else {
                showToast("Erro ao remover item: " + (data.message || data.error || ("Operação recusada pelo servidor HTTP " + res.status)), 'error');
            }
        } catch (e) {
            showToast("Erro de conexão.", 'error');
        }
    }

    // Save and Submit Actions
    async function saveDraft(showNotification = false) {
        if (isEditingLocked) {
            showToast("Esta cotação está com status " + quote.status.replace(/_/g, ' ') + " e não permite edições.", 'warning');
            return false;
        }

        // Collect current values from page
        const payload = {
            forma_pagamento: document.getElementById("forma-pagamento").value,
            prazo_entrega: document.getElementById("prazo-entrega").value,
            frete_tipo: document.getElementById("frete-tipo").value,
            transportadora: document.getElementById("transportadora").value,
            observacao_cliente: document.getElementById("obs-cliente").value,
            observacao_interna: document.getElementById("obs-interna").value,
            itens: quote.itens.map(i => ({
                id: i.id,
                qtd: i.qtd,
                preco_unit_proposto: i.preco_unit_proposto
            }))
        };

        try {
            const res = await fetch(`${QUOTE_BASE_URL}`, {
                method: "PATCH",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(payload)
            });
            const data = await res.json().catch(() => ({}));
            if (res.ok && data.success) {
                quote = data.data;
                if (showNotification) {
                    showToast("Rascunho salvo com sucesso.", 'success');
                }
                return true;
            } else {
                showToast("Erro ao salvar rascunho: " + (data.message || data.error || ("Falha no servidor HTTP " + res.status)), 'error');
                return false;
            }
        } catch (e) {
            showToast("Erro de conexão ao salvar.", 'error');
            return false;
        }
    }

    const MAX_ATTACHMENT_SIZE = 10 * 1024 * 1024; // 10MB
    const ALLOWED_ATTACHMENT_EXTS = ['pdf', 'png', 'jpg', 'jpeg', 'webp', 'gif', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'ppt', 'pptx', 'odt', 'ods', 'odp'];
    const BLOCKED_ATTACHMENT_EXTS = ['exe', 'zip', 'rar', '7z', 'tar', 'gz', 'bat', 'cmd', 'sh', 'com', 'scr', 'msi', 'vbs', 'js', 'bin', 'phtml', 'php', 'apk', 'jar'];

    function setupAttachmentValidation() {
        const fileInput = document.getElementById("just-anexos");
        const audioInput = document.getElementById("just-audio");

        if (fileInput) {
            fileInput.addEventListener("change", function() {
                validateAttachmentFiles(this);
            });
        }

        if (audioInput) {
            audioInput.addEventListener("change", function() {
                validateAudioFile(this);
            });
        }
    }

    function validateAttachmentFiles(fileInput) {
        const preview = document.getElementById("just-anexos-preview");
        if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
            if (preview) { preview.style.display = "none"; preview.innerHTML = ""; }
            return true;
        }

        let fileSummaries = [];
        for (let i = 0; i < fileInput.files.length; i++) {
            const file = fileInput.files[i];
            const name = file.name;
            const ext = (name.split('.').pop() || '').toLowerCase();

            // 1. Check size limit (10MB)
            if (file.size > MAX_ATTACHMENT_SIZE) {
                showToast(`O arquivo "${name}" ultrapassa o limite máximo de 10 MB (${(file.size / (1024*1024)).toFixed(1)} MB).\nPor favor, escolha um arquivo menor.`, 'error');
                fileInput.value = "";
                if (preview) { preview.style.display = "none"; preview.innerHTML = ""; }
                return false;
            }

            // 2. Check blocked or unapproved extension
            if (BLOCKED_ATTACHMENT_EXTS.includes(ext) || !ALLOWED_ATTACHMENT_EXTS.includes(ext)) {
                showToast(`O arquivo "${name}" não é permitido.\nApenas arquivos PDF, imagens (JPG, PNG, WEBP) e documentos do Office (Word, Excel, PowerPoint) são aceitos.`, 'error');
                fileInput.value = "";
                if (preview) { preview.style.display = "none"; preview.innerHTML = ""; }
                return false;
            }

            // 3. Check browser reported mime type for executables / archives
            const mime = (file.type || '').toLowerCase();
            if (mime.includes('zip') || mime.includes('executable') || mime.includes('x-dosexec') || mime.includes('x-msdownload')) {
                showToast(`O arquivo "${name}" possui formato executável ou compactado não permitido.`, 'error');
                fileInput.value = "";
                if (preview) { preview.style.display = "none"; preview.innerHTML = ""; }
                return false;
            }

            const sizeKb = Math.round(file.size / 1024);
            fileSummaries.push(`📁 ${name} (${sizeKb} KB)`);
        }

        if (preview) {
            preview.style.display = "block";
            preview.style.color = "#047857";
            preview.innerHTML = fileSummaries.join("<br>");
        }
        return true;
    }

    function validateAudioFile(audioInput) {
        const preview = document.getElementById("just-audio-preview");
        if (!audioInput || !audioInput.files || audioInput.files.length === 0) {
            if (preview) { preview.style.display = "none"; preview.innerHTML = ""; }
            return true;
        }

        const file = audioInput.files[0];
        if (file.size > MAX_ATTACHMENT_SIZE) {
            showToast(`O arquivo de áudio "${file.name}" ultrapassa o limite máximo de 10 MB.`, 'error');
            audioInput.value = "";
            if (preview) { preview.style.display = "none"; preview.innerHTML = ""; }
            return false;
        }

        if (preview) {
            preview.style.display = "block";
            preview.style.color = "#047857";
            preview.innerHTML = `🎵 ${file.name} (${Math.round(file.size / 1024)} KB)`;
        }
        return true;
    }

    async function submitQuote() {
        if (isEditingLocked) {
            showToast("Esta cotação já está com status " + quote.status.replace(/_/g, ' ') + " e não permite novo envio.", 'warning');
            return;
        }

        // 1. Save draft changes first
        const saved = await saveDraft(false);
        if (!saved) return;

        // 2. If justifications are visible (needed), upload them first
        const justificationPanel = document.getElementById("justification-panel");
        if (justificationPanel.style.display !== "none") {
            const texto = document.getElementById("just-texto").value;
            const fileInput = document.getElementById("just-anexos");
            const audioInput = document.getElementById("just-audio");

            if (!texto && !fileInput.files.length && !audioInput.files.length) {
                showToast("Justificativa e anexo comprovatório são obrigatórios para itens abaixo do preço mínimo.", 'error');
                return;
            }

            // Client-side validations
            if (!validateAttachmentFiles(fileInput)) {
                return;
            }
            if (!validateAudioFile(audioInput)) {
                return;
            }

            // Perform multipart submit for attachments/justification
            const formData = new FormData();
            formData.append("texto", texto);
            if (fileInput.files.length) {
                for (let i = 0; i < fileInput.files.length; i++) {
                    formData.append("anexos[]", fileInput.files[i]);
                }
            }
            if (audioInput.files.length) {
                formData.append("audio", audioInput.files[0]);
            }

            const justRes = await fetch(`${QUOTE_BASE_URL}/justificativa`, {
                method: "POST",
                body: formData
            });
            const justData = await justRes.json().catch(() => ({}));
            if (!justRes.ok || !justData.success) {
                let errorMsg = justData.message || justData.error || ("Falha no envio HTTP " + justRes.status);
                if (justData.messages && typeof justData.messages === 'object') {
                    const firstKey = Object.keys(justData.messages)[0];
                    if (firstKey && justData.messages[firstKey]) {
                        const val = justData.messages[firstKey];
                        errorMsg = Array.isArray(val) ? val[0] : val;
                    }
                }
                showToast("Erro ao enviar justificativa: " + errorMsg, 'error');
                return;
            }
        }

        // 3. Trigger submit to workflow routing
        try {
            const res = await fetch(`${QUOTE_BASE_URL}/enviar`, {
                method: "POST"
            });
            const data = await res.json().catch(() => ({}));
            if (res.ok && data.success) {
                showToast("Cotação enviada com sucesso! Situação: " + data.status.replace(/_/g, ' '), 'success');
                setTimeout(() => window.location.reload(), 1200);
            } else {
                showToast("Erro ao enviar cotação: " + (data.message || data.error || ("Falha no envio HTTP " + res.status)), 'error');
            }
        } catch (e) {
            showToast("Erro de conexão ao enviar.", 'error');
        }
    }

    function downloadPdf() {
        if (!quote) {
            showToast("Aguarde o carregamento da cotação.", 'info');
            return;
        }
        const allowPdfStatuses = ['APROVADA', 'PDF_GERADO', 'AGUARDANDO_PEDIDO', 'FINALIZADA_COM_PEDIDO', 'FATURADA'];
        if (!allowPdfStatuses.includes(quote.status)) {
            showToast("O PDF só pode ser gerado após a aprovação da cotação.", 'warning');
            return;
        }

        window.open(`${QUOTE_BASE_URL}/pdf`, '_blank');

        if (quote.status === 'APROVADA') {
            setTimeout(() => {
                loadData();
            }, 1500);
            window.addEventListener('focus', () => {
                if (quote && quote.status === 'APROVADA') {
                    loadData();
                }
            }, { once: true });
        }
    }

    // Mark as Lost Flow
    function openLostModal() {
        if (isEditingLocked) {
            showToast("Esta cotação não pode ser marcada como perdida no status atual (" + quote.status.replace(/_/g, ' ') + ").", 'warning');
            return;
        }
        document.getElementById("lost-modal").style.display = "flex";
    }

    function closeLostModal() {
        document.getElementById("lost-modal").style.display = "none";
        document.getElementById("lost-reason").value = "";
    }

    async function confirmLost() {
        if (isEditingLocked) {
            showToast("Não é possível encerrar esta cotação no status atual (" + quote.status.replace(/_/g, ' ') + ").", 'warning');
            return;
        }

        const justificativa = document.getElementById("lost-reason").value;
        if (!justificativa || justificativa.length < 5) {
            showToast("Digite um motivo válido (mínimo 5 caracteres).", 'warning');
            return;
        }

        try {
            const res = await fetch(`${QUOTE_BASE_URL}/perdida`, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ justificativa })
            });
            const data = await res.json().catch(() => ({}));
            if (res.ok && data.success) {
                showToast("Cotação encerrada como PERDIDA.", 'info');
                closeLostModal();
                setTimeout(() => window.location.reload(), 1200);
            } else {
                showToast("Erro ao encerrar cotação: " + (data.message || data.error || ("Falha no servidor HTTP " + res.status)), 'error');
            }
        } catch (e) {
            showToast("Erro de conexão.", 'error');
        }
    }

    // Faturamento Release Flow
    function openReleaseModal() {
        if (!quote) return;
        if (quote.status === 'APROVADA') {
            showToast("É necessário gerar o PDF da cotação antes de liberar para faturamento.", 'warning');
            return;
        }
        if (quote.status !== 'PDF_GERADO') {
            showToast("Esta cotação não está apta para faturamento no status atual (" + quote.status.replace(/_/g, ' ') + ").", 'warning');
            return;
        }

        document.getElementById("release-pedido-externo").value = "";
        document.getElementById("release-tipo-faturamento").value = "total";
        
        // Default the total field to the quotation proposed total
        document.getElementById("release-valor-pedido").value = parseFloat(quote.total).toFixed(2);
        document.getElementById("release-valor-pedido").readOnly = true;
        document.getElementById("release-valor-pedido").style.backgroundColor = "#e2e8f0";

        document.getElementById("release-modal").style.display = "flex";
    }

    function closeReleaseModal() {
        document.getElementById("release-modal").style.display = "none";
    }

    function onReleaseTypeChange(val) {
        const valueInput = document.getElementById("release-valor-pedido");
        if (val === 'parcial') {
            valueInput.readOnly = false;
            valueInput.style.backgroundColor = "#ffffff";
            valueInput.value = "";
            valueInput.placeholder = "Digite o valor parcial (R$)...";
        } else {
            valueInput.readOnly = true;
            valueInput.style.backgroundColor = "#e2e8f0";
            valueInput.value = parseFloat(quote.total).toFixed(2);
        }
    }

    async function confirmRelease() {
        const orderNum = document.getElementById("release-pedido-externo").value;
        const releaseType = document.getElementById("release-tipo-faturamento").value;
        const orderValue = parseFloat(document.getElementById("release-valor-pedido").value);

        if (!orderNum) {
            showToast("O número do pedido no Sankhya é obrigatório.", 'warning');
            return;
        }

        if (isNaN(orderValue) || orderValue <= 0) {
            showToast("Por favor, preencha um valor válido para o faturamento.", 'warning');
            return;
        }

        try {
            const res = await fetch(`${QUOTE_BASE_URL}/faturar`, {
                method: "POST",
                headers: { 
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    numero_pedido_externo: orderNum,
                    valor_pedido: orderValue,
                    tipo_faturamento: releaseType
                })
            });

            if (!res.ok) {
                const errText = await res.text();
                try {
                    const errJson = JSON.parse(errText);
                    showToast("Erro: " + (errJson.message || errJson.error), 'error');
                } catch(e) {
                    showToast(`Erro ${res.status}: ` + errText.substring(0, 200), 'error');
                }
                return;
            }

            const data = await res.json();
            if (data.success) {
                showToast("Cotação liberada para faturamento com sucesso!", 'success');
                closeReleaseModal();
                loadData(); // reload details and status
            } else {
                showToast("Erro: " + data.message, 'error');
            }
        } catch (e) {
            console.error(e);
            showToast("Erro de conexão.", 'error');
        }
    }

    let loggedUser = null;

    async function checkUserSession() {
        try {
            const authRes = await fetch(`${API_URL}/auth/me`);
            if (authRes.ok) {
                const authData = await authRes.json();
                if (authData.logged_in && authData.user) {
                    loggedUser = authData.user;
                    renderUserAuthBar(loggedUser);
                }
            }
        } catch(e) {
            console.warn("Session auth check bypassed:", e);
        }
    }

    function renderUserAuthBar(user) {
        const bar = document.getElementById("user-auth-bar");
        if (!bar) return;

        // Representante no layout mobile já possui o cabeçalho oficial e a barra inferior
        if (user.papel === 'representante') {
            bar.style.display = "none";
            return;
        }

        let roleText = 'Usuário';
        if (user.papel === 'administrador') roleText = 'Administrador';
        else if (user.papel === 'diretor') roleText = 'Diretor';
        else if (user.papel === 'faturamento') roleText = 'Faturamento';
        else if (user.papel === 'gestor') roleText = 'Gestor';

        const btnText = 'Ir para o Painel Principal';

        const roleEl = document.getElementById("logged-user-role");
        const nameEl = document.getElementById("logged-user-name");
        const btnEl = document.getElementById("btn-home-label");

        if (roleEl) roleEl.innerText = roleText;
        if (nameEl) nameEl.innerText = user.nome || user.email;
        if (btnEl) btnEl.innerText = btnText;

        bar.style.display = "block";
    }

    function goToHomeDashboard() {
        if (!loggedUser) {
            window.location.href = "{{ url('/') }}";
            return;
        }

        if (loggedUser.papel === 'representante') {
            window.location.href = "{{ url('/painel-representante') }}";
        } else if (loggedUser.papel === 'administrador' || loggedUser.papel === 'diretor') {
            window.location.href = "{{ url('/dashboard') }}";
        } else {
            window.location.href = "{{ url('/cotacoes') }}";
        }
    }

    function goBackToQuotes() {
        goToHomeDashboard();
    }
</script>
@endsection
