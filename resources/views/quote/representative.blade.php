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
    }

    body {
        padding-bottom: 85px !important;
    }

    /* Compact Item Card (3 Lines Layout) */
    .item-card-row {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        margin-bottom: 10px;
        padding: 0 !important;
        box-shadow: 0 1px 3px rgba(0,0,0,0.03);
        position: relative;
        transition: border-color 0.15s;
    }
    .item-card-row.below-min-card {
        border-color: #fca5a5 !important;
        background: #fff8f8 !important;
    }
    .compact-item-card {
        padding: 10px 14px;
        display: flex;
        flex-direction: column;
        gap: 8px;
        width: 100%;
        box-sizing: border-box;
    }
    .item-line-1 {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 8px;
    }
    .item-title-box {
        display: flex;
        align-items: baseline;
        gap: 6px;
        flex-wrap: wrap;
        flex: 1;
        min-width: 0;
    }
    .item-desc-text {
        font-size: 13.5px;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.3;
    }
    .item-meta-text {
        font-size: 11.5px;
        color: #64748b;
        font-weight: 500;
    }
    .badge-campanha-sm {
        background: #fdf2f8;
        color: #be185d;
        border: 1px solid #fbcfe8;
        font-size: 10px;
        font-weight: 700;
        padding: 1px 5px;
        border-radius: 4px;
    }
    .badge-inconsistent-sm {
        background: #fef3c7;
        color: #92400e;
        border: 1px solid #fcd34d;
        font-size: 10px;
        font-weight: 700;
        padding: 1px 5px;
        border-radius: 4px;
    }
    .btn-delete-item {
        background: none;
        border: none;
        color: #94a3b8;
        cursor: pointer;
        padding: 4px 6px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: color 0.15s, background 0.15s;
    }
    .btn-delete-item:hover {
        color: #ef4444;
        background: #fee2e2;
    }

    .item-line-2 {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
    }
    .item-qty-container {
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .item-price-container {
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .item-field-label {
        font-size: 11.5px;
        color: #64748b;
        font-weight: 600;
    }

    .item-line-3 {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
        padding-top: 6px;
        border-top: 1px dashed #e2e8f0;
        font-size: 11.5px;
    }
    .item-benchmarks {
        color: #64748b;
        font-weight: 500;
    }
    .badge-price-tag {
        font-size: 10.5px;
        font-weight: 700;
        padding: 2px 7px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        gap: 3px;
    }
    .badge-price-tag.tag-table {
        background: #f1f5f9;
        color: #475569;
        border: 1px solid #e2e8f0;
    }
    .badge-price-tag.tag-discount {
        background: #eff6ff;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
    }
    .badge-price-tag.tag-below-min {
        background: #fee2e2;
        color: #b91c1c;
        border: 1px solid #fca5a5;
    }
    .badge-price-tag.tag-above {
        background: #f0fdf4;
        color: #15803d;
        border: 1px solid #bbf7d0;
    }

    /* Stepper Progress Bar (Fluxo 5 Etapas) */
    .quote-stepper {
        display: flex;
        align-items: center;
        justify-content: space-between;
        position: relative;
        padding: 4px 0;
    }
    .step-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 4px;
        z-index: 2;
    }
    .step-circle {
        width: 22px;
        height: 22px;
        border-radius: 50%;
        background: #e2e8f0;
        color: #64748b;
        font-size: 11px;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
    }
    .step-item.active .step-circle {
        background: #2563eb;
        color: #ffffff;
        box-shadow: 0 0 0 3px rgba(37,99,235,0.2);
    }
    .step-item.completed .step-circle {
        background: #16a34a;
        color: #ffffff;
    }
    .step-label {
        font-size: 10px;
        font-weight: 600;
        color: #64748b;
        white-space: nowrap;
    }
    .step-item.active .step-label {
        color: #0f172a;
        font-weight: 700;
    }
    .step-item.completed .step-label {
        color: #16a34a;
    }
    .step-line {
        flex: 1;
        height: 2px;
        background: #e2e8f0;
        margin: 0 4px;
        margin-bottom: 14px;
        z-index: 1;
        transition: background 0.2s ease;
    }
    .step-line.completed {
        background: #16a34a;
    }

    /* Sticky Bottom Bar */
    .sticky-footer-quote-bar {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: rgba(255, 255, 255, 0.96);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border-top: 1px solid #e2e8f0;
        box-shadow: 0 -4px 16px rgba(0, 0, 0, 0.08);
        padding: 10px 16px;
        z-index: 999;
    }
    .sticky-footer-inner {
        max-width: 1200px;
        margin: 0 auto;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
    }
    .sticky-total-block {
        display: flex;
        flex-direction: column;
        min-width: 0;
    }
    .sticky-total-val {
        font-size: 18px;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.1;
    }
    .sticky-discount-val {
        font-size: 11px;
        color: #64748b;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .sticky-actions-block {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .btn-sticky-primary {
        background: #2563eb;
        color: #ffffff;
        border: none;
        border-radius: 8px;
        padding: 10px 18px;
        font-size: 13.5px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        cursor: pointer;
        box-shadow: 0 2px 6px rgba(37,99,235,0.25);
        transition: all 0.15s ease;
        white-space: nowrap;
    }
    .btn-sticky-primary:active {
        transform: scale(0.98);
    }
    .btn-options-menu {
        background: #f1f5f9;
        border: 1px solid #cbd5e1;
        color: #334155;
        border-radius: 8px;
        width: 38px;
        height: 38px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        font-weight: 800;
        cursor: pointer;
        transition: background 0.15s;
    }
    .btn-options-menu:hover {
        background: #e2e8f0;
    }
    .options-menu-dropdown {
        position: absolute;
        bottom: 46px;
        right: 0;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 10px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.15);
        padding: 6px;
        min-width: 200px;
        z-index: 1000;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    .options-menu-item {
        background: none;
        border: none;
        text-align: left;
        padding: 8px 12px;
        font-size: 12.5px;
        font-weight: 600;
        color: #334155;
        border-radius: 6px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: background 0.15s;
    }
    .options-menu-item:hover {
        background: #f1f5f9;
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

    <!-- Title and Stepper Row -->
    <div style="margin-bottom: 16px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 10px; margin-bottom: 12px;">
            <div>
                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                    <h1 style="font-size: 24px; margin: 0; font-weight: 700;">Cotação <span id="quote-number" style="color: var(--color-primary);">...</span></h1>
                    <span id="quote-status-badge" class="badge-status">...</span>
                </div>
                <div style="color: var(--color-text-muted); font-size: 12px; margin-top: 4px; display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                    <span>Origem: <strong id="quote-origin">...</strong></span>
                    <span>·</span>
                    <span>Emitida: <span id="quote-emission">...</span></span>
                    <span>·</span>
                    <span id="validity-relative-badge" style="font-weight: 600; color: #475569;">Válida até: <span id="quote-validity">...</span></span>
                </div>
            </div>
        </div>

        <!-- Visual 5-Step Progress Timeline -->
        <div class="quote-stepper-wrapper" style="background: white; border: 1px solid var(--color-border); border-radius: 12px; padding: 12px 14px; margin-bottom: 16px; box-shadow: var(--shadow-sm);">
            <div class="quote-stepper">
                <div class="step-item" id="step-node-1">
                    <div class="step-circle">1</div>
                    <span class="step-label">Criada</span>
                </div>
                <div class="step-line" id="step-line-1"></div>
                <div class="step-item" id="step-node-2">
                    <div class="step-circle">2</div>
                    <span class="step-label">Em Análise</span>
                </div>
                <div class="step-line" id="step-line-2"></div>
                <div class="step-item" id="step-node-3">
                    <div class="step-circle">3</div>
                    <span class="step-label">Aprovada</span>
                </div>
                <div class="step-line" id="step-line-3"></div>
                <div class="step-item" id="step-node-4">
                    <div class="step-circle">4</div>
                    <span class="step-label">PDF</span>
                </div>
                <div class="step-line" id="step-line-4"></div>
                <div class="step-item" id="step-node-5">
                    <div class="step-circle">5</div>
                    <span class="step-label">Faturada</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Locked Status Notice Banner -->
    <div id="locked-status-banner" style="display: none; margin-bottom: 16px; padding: 12px 16px; border-radius: 10px; background: #fffbeb; border: 1px solid #fef3c7; color: #92400e; font-size: 13.5px; font-weight: 500; align-items: center; gap: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        <span id="locked-status-banner-text">Esta cotação está em modo somente leitura e não permite edições.</span>
    </div>

    <!-- Compact Client Header Strip -->
    <div class="compact-client-strip" style="background: white; border: 1px solid var(--color-border); border-radius: 12px; padding: 12px 16px; margin-bottom: 16px; box-shadow: var(--shadow-sm);">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; font-size: 13.5px;">
                <span style="font-size: 16px;">🏢</span>
                <strong id="client-name" style="color: var(--color-text); font-size: 14.5px;">...</strong>
                <span style="color: #94a3b8;">·</span>
                <span id="client-cnpj" style="color: var(--color-text-muted);">CNPJ: ...</span>
                <span style="color: #94a3b8;">·</span>
                <span id="client-location" style="color: var(--color-text-muted); font-weight: 500;">Cidade: ...</span>
            </div>
            <button type="button" class="btn btn-outline" onclick="toggleClientDetails()" style="padding: 4px 10px; font-size: 12px; border-radius: 6px; border-color: #cbd5e1; color: #475569; display: inline-flex; align-items: center; gap: 4px;">
                <span id="btn-client-details-text">Detalhes</span>
                <svg id="client-details-arrow" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
        </div>
        <!-- Collapsible Client Details -->
        <div id="client-details-panel" style="display: none; margin-top: 10px; padding-top: 10px; border-top: 1px solid #f1f5f9; font-size: 12.5px; color: var(--color-text-muted);">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 8px;">
                <div><strong>Contato / Telefone:</strong> <span id="client-contact">...</span></div>
                <div><strong>Representante:</strong> <span id="rep-name">...</span></div>
            </div>
        </div>
    </div>

    <!-- Items Section Card -->
    <div class="card" style="margin-bottom: 16px; padding: 16px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
            <h3 style="font-size: 16px; margin: 0; font-weight: 700; display: flex; align-items: center; gap: 6px;">
                <span>Itens da Cotação</span>
                <span class="tab-badge" id="count-all-badge" style="background: var(--color-bg); color: var(--color-text-muted); font-size: 12px; padding: 2px 8px; border-radius: 12px; border: 1px solid var(--color-border);">0</span>
            </h3>
        </div>

        <!-- Live Search and Filter Bar -->
        <div class="items-filter-bar" style="margin-bottom: 14px;">
            <div class="search-box-wrapper">
                <span class="search-icon">🔍</span>
                <input type="text" id="item-search-input" class="search-items-input" placeholder="Buscar por código ou produto..." oninput="onSearchInput(this.value)">
            </div>
            <div class="filter-tabs-wrapper">
                <button type="button" class="filter-tab-btn active" id="tab-all" onclick="setFilterTab('all')">
                    Todos <span class="tab-badge" id="count-all">0</span>
                </button>
                <button type="button" class="filter-tab-btn tab-attention" id="tab-attention" onclick="setFilterTab('attention')">
                    ⚠️ Atenção <span class="tab-badge" id="count-attention">0</span>
                </button>
                <button type="button" class="filter-tab-btn" id="tab-approved" onclick="setFilterTab('approved')">
                    ✅ Normais <span class="tab-badge" id="count-approved">0</span>
                </button>
            </div>
        </div>

        <!-- Compact Item Cards Container (Replacing heavy table) -->
        <div id="items-cards-container" class="items-cards-container" style="display: flex; flex-direction: column; gap: 10px;">
            <!-- Dynamically injected 3-line compact item cards -->
        </div>

        <!-- Add Item Row -->
        <div id="add-item-form-container" style="margin-top: 16px; background-color: var(--color-bg); padding: 16px; border-radius: var(--radius-md); border: 1px solid var(--color-border);">
            <h4 style="font-size: 13.5px; margin-bottom: 10px; color: var(--color-primary); display: flex; align-items: center; gap: 6px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                Adicionar Produto
            </h4>
            
            <div style="display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end;">
                <!-- Live Product Search Box with Dropdown -->
                <div style="flex-grow: 1; min-width: 240px; position: relative;">
                    <label class="form-label" style="font-size: 12px; font-weight: 600;">Produto (Código ou Descrição)</label>
                    <input type="hidden" id="selected-product-id">
                    <div style="position: relative;">
                        <input type="text" id="add-product-search-input" class="form-control" 
                               placeholder="🔍 Buscar produto no catálogo..." 
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

                <div style="width: 85px;">
                    <label class="form-label" style="font-size: 12px;">Qtd</label>
                    <input type="number" id="new-item-qtd" class="form-control text-center" value="1" min="1" inputmode="numeric" oninput="calculateNewItemSubtotal()">
                </div>
                <div style="width: 105px;">
                    <label class="form-label" style="font-size: 12px;">Preço Sug.</label>
                    <input type="text" id="new-item-sugerido" class="form-control text-right" readonly style="background-color: #f1f5f9; font-weight: 600;">
                </div>
                <div style="width: 105px;">
                    <label class="form-label" style="font-size: 12px;">Preço Mín.</label>
                    <input type="text" id="new-item-minimo" class="form-control text-right" readonly style="background-color: #f1f5f9; color: #64748b;">
                </div>
                <div style="width: 115px;">
                    <label class="form-label" style="font-size: 12px;">Preço Prop.</label>
                    <input type="number" id="new-item-proposto" class="form-control text-right" step="0.01" min="0.01" inputmode="decimal" style="font-weight: 700; color: var(--color-primary);" oninput="calculateNewItemSubtotal()">
                </div>
                <div>
                    <button type="button" class="btn btn-secondary" onclick="addNewItem()" style="padding: 9px 16px; font-weight: 600; font-size: 13px;">
                        + Adicionar
                    </button>
                </div>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 10px; flex-wrap: wrap; gap: 8px;">
                <div id="selected-product-badge" style="display: none; font-size: 12px; color: #059669; background: #ecfdf5; border: 1px solid #a7f3d0; padding: 4px 10px; border-radius: 6px;">
                    ✓ <strong id="selected-product-label"></strong>
                </div>
                <div style="font-size: 13px; font-weight: 600; color: var(--color-primary); margin-left: auto;" id="new-item-subtotal-label">
                    Subtotal: R$ 0,00
                </div>
            </div>
        </div>
    </div>

    <!-- Collapsible Conditions & Observations Accordion -->
    <div class="card" style="margin-bottom: 16px; padding: 0; overflow: hidden; border: 1px solid var(--color-border);">
        <div onclick="toggleConditionsAccordion()" style="padding: 14px 18px; display: flex; justify-content: space-between; align-items: center; cursor: pointer; background: #f8fafc; transition: background 0.2s ease;">
            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                <span style="font-size: 16px;">📋</span>
                <span id="conditions-summary-badge" style="font-size: 13px; font-weight: 600; color: #334155;">
                    Condições & Observações
                </span>
                <span style="color: #94a3b8;">·</span>
                <span id="conditions-summary-details" style="font-size: 12.5px; color: #64748b;">
                    Carregando...
                </span>
            </div>
            <div style="display: flex; align-items: center; gap: 6px; font-size: 12px; color: #2563eb; font-weight: 600;">
                <span id="conditions-toggle-text">Ver / Editar</span>
                <svg id="conditions-toggle-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="transition: transform 0.2s ease;"><polyline points="6 9 12 15 18 9"/></svg>
            </div>
        </div>

        <div id="conditions-accordion-body" style="display: none; padding: 18px; border-top: 1px solid var(--color-border); background: white;">
            <div class="grid-2" style="gap: 16px;">
                <!-- Conditions -->
                <div>
                    <h4 style="font-size: 13px; margin-bottom: 12px; font-weight: 700; color: #1e293b;">Condições Comerciais</h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <div class="form-group" style="margin-bottom: 8px;">
                            <label class="form-label" style="font-size: 11px;">Forma de Pagamento</label>
                            <input type="text" id="forma-pagamento" class="form-control" style="padding: 8px 10px; font-size: 12.5px;" onchange="updateConditionsSummaryText()">
                        </div>
                        <div class="form-group" style="margin-bottom: 8px;">
                            <label class="form-label" style="font-size: 11px;">Prazo de Entrega</label>
                            <input type="text" id="prazo-entrega" class="form-control" style="padding: 8px 10px; font-size: 12.5px;" onchange="updateConditionsSummaryText()">
                        </div>
                        <div class="form-group" style="margin-bottom: 8px;">
                            <label class="form-label" style="font-size: 11px;">Tipo de Frete</label>
                            <select id="frete-tipo" class="form-control" style="padding: 8px 10px; font-size: 12.5px;" onchange="updateConditionsSummaryText()">
                                <option value="CIF">CIF (Por conta do remetente)</option>
                                <option value="FOB">FOB (Por conta do destinatário)</option>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom: 8px;">
                            <label class="form-label" style="font-size: 11px;">Transportadora</label>
                            <input type="text" id="transportadora" class="form-control" style="padding: 8px 10px; font-size: 12.5px;">
                        </div>
                    </div>
                </div>

                <!-- Observations -->
                <div>
                    <h4 style="font-size: 13px; margin-bottom: 12px; font-weight: 700; color: #1e293b;">Observações</h4>
                    <div class="form-group" style="margin-bottom: 10px;">
                        <label for="obs-cliente" class="form-label" style="font-size: 11px;">Observação para o Cliente (Visível no PDF)</label>
                        <textarea id="obs-cliente" class="form-control" rows="2" style="font-size: 12.5px;" placeholder="Ex: Prazo de entrega de 5 dias úteis."></textarea>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="obs-interna" class="form-label" style="font-size: 11px;">Observação Interna (Apenas Equipe)</label>
                        <textarea id="obs-interna" class="form-control" rows="2" style="font-size: 12.5px;" placeholder="Ex: Cliente solicita prioridade no faturamento."></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Justification Panel (Shown if any item is below minimum) -->
    <div id="justification-panel" class="card" style="display: none; border: 1px solid #f59e0b; background-color: #fffbeb; margin-bottom: 20px;">
        <div class="card-header" style="border-bottom: 1px solid #fef3c7;">
            <h3 style="color: #d97706; display: flex; align-items: center; gap: 8px; font-size: 15px;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                Justificativa Obrigatória — Itens Abaixo do Mínimo
            </h3>
        </div>
        <p style="font-size: 13px; color: #b45309; margin-bottom: 15px;">
            Existem produtos abaixo do preço mínimo configurado. É obrigatório registrar uma justificativa e anexar comprovantes para enviar a cotação ao gestor.
        </p>
        
        <div class="form-group">
            <label class="form-label" style="color: #b45309;">Justificativa por Escrito</label>
            <textarea id="just-texto" class="form-control" rows="3" placeholder="Justifique o motivo do desconto especial (ex: equiparação de preço com concorrente X)..." style="background-color: #ffffff; border-color: #fcd34d; font-size: 13px;"></textarea>
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label class="form-label" style="color: #b45309; font-weight: 600;">Anexos / Documentos (Comprovante Concorrente, etc)</label>
                <input type="file" id="just-anexos" class="form-control" multiple 
                       accept=".pdf,.png,.jpg,.jpeg,.webp,.gif,.doc,.docx,.xls,.xlsx,.csv,.ppt,.pptx,.odt,.ods,.odp,application/pdf,image/jpeg,image/png,image/webp,image/gif,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/csv,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation"
                       style="background-color: #ffffff; border-color: #fcd34d; padding: 6px 10px;">
                <small style="display: block; margin-top: 5px; font-size: 11px; color: #b45309;">
                    Formatos aceitos: PDF, imagens (JPG, PNG, WEBP) e Office. Limite: 10MB por arquivo.
                </small>
                <div id="just-anexos-preview" style="margin-top: 6px; font-size: 12px; display: none;"></div>
            </div>
            <div class="form-group">
                <label class="form-label" style="color: #b45309; font-weight: 600;">Áudio de Justificativa</label>
                <input type="file" id="just-audio" class="form-control" accept="audio/*,audio/mpeg,audio/wav,audio/ogg,audio/mp4,audio/m4a" style="background-color: #ffffff; border-color: #fcd34d; padding: 6px 10px;">
                <small style="display: block; margin-top: 5px; font-size: 11px; color: #b45309;">
                    Formatos: MP3, WAV, OGG, M4A. Limite: 10MB.
                </small>
                <div id="just-audio-preview" style="margin-top: 6px; font-size: 12px; display: none;"></div>
            </div>
        </div>
    </div>
</div>

<!-- Sticky Bottom Action & Totals Bar (Point 2) -->
<div class="sticky-footer-quote-bar" id="sticky-footer-bar">
    <div class="sticky-footer-inner">
        <!-- Totals & Discount -->
        <div class="sticky-footer-totals">
            <div style="font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 600; letter-spacing: 0.5px;">Total Proposto</div>
            <div class="sticky-footer-val" id="sticky-total-proposto">R$ 0,00</div>
            <div class="sticky-footer-discount" id="sticky-total-discount">Desconto: R$ 0,00 (0,0%)</div>
        </div>

        <!-- Action Buttons -->
        <div class="sticky-footer-actions">
            <!-- Secondary Options Menu Button "⋯" -->
            <div class="dropdown-wrapper" style="position: relative;">
                <button type="button" class="btn-options-menu" onclick="toggleOptionsMenu(event)" title="Mais opções">
                    ⋯
                </button>
                <div class="options-menu-dropdown" id="options-menu-dropdown">
                    <button type="button" class="menu-item" id="menu-save-draft" onclick="handleMenuAction('draft')">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                        Salvar Rascunho
                    </button>
                    <button type="button" class="menu-item" onclick="handleMenuAction('conditions')">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                        Editar Condições
                    </button>
                    <button type="button" class="menu-item item-danger" id="menu-mark-lost" onclick="handleMenuAction('lost')">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                        Marcar como Perdida
                    </button>
                </div>
            </div>

            <!-- Single Dynamic Primary Action Button -->
            <button type="button" class="btn-sticky-primary btn-submit-action" id="btn-sticky-primary" onclick="handlePrimaryAction()">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                <span id="btn-sticky-primary-label">Enviar para Aprovação</span>
            </button>
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
            <input type="text" id="release-pedido-externo" class="form-control" inputmode="numeric" placeholder="Ex: 509230" required>
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
            <input type="number" id="release-valor-pedido" class="form-control" step="0.01" min="0.01" inputmode="decimal" required>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 12px;">
            <button class="btn btn-outline" onclick="closeReleaseModal()">Cancelar</button>
            <button class="btn btn-primary" onclick="confirmRelease()" style="background-color: #0d9488; border-color: #0d9488;">Liberar Faturamento</button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Responsive In-Screen Toast Notifications with message deduplication
    let lastToastMessage = "";
    let lastToastTime = 0;

    function showToast(message, type = 'info', title = null) {
        const now = Date.now();
        const strMsg = String(message || '').trim();
        // Prevent duplicate toasts within 2 seconds
        if (strMsg === lastToastMessage && (now - lastToastTime) < 2000) {
            return;
        }
        lastToastMessage = strMsg;
        lastToastTime = now;

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

    // Friendly status labels, styles, and relative expiry helper
    function getFriendlyStatus(status) {
        const map = {
            'EM_CRIACAO': { label: 'Em criação', bg: '#f1f5f9', color: '#475569', border: '#cbd5e1' },
            'AGUARDANDO_GESTOR': { label: 'Em análise (Gestor)', bg: '#fef3c7', color: '#92400e', border: '#fcd34d' },
            'COM_DIRETOR': { label: 'Em análise (Diretoria)', bg: '#ffedd5', color: '#9a3412', border: '#fdba74' },
            'DEVOLVIDA': { label: 'Devolvida para ajuste', bg: '#fee2e2', color: '#991b1b', border: '#fca5a5' },
            'APROVADA': { label: 'Aprovada (Pendente PDF)', bg: '#dcfce7', color: '#166534', border: '#86efac' },
            'PDF_GERADO': { label: 'PDF Gerado', bg: '#ccfbf1', color: '#115e59', border: '#5eead4' },
            'AGUARDANDO_PEDIDO': { label: 'Aguardando pedido', bg: '#e0f2fe', color: '#075985', border: '#7dd3fc' },
            'FINALIZADA_COM_PEDIDO': { label: 'Pedido registrado', bg: '#e0e7ff', color: '#3730a3', border: '#a5b4fc' },
            'FATURADA': { label: 'Faturada', bg: '#dcfce7', color: '#14532d', border: '#4ade80' },
            'PERDIDA': { label: 'Perdida', bg: '#fee2e2', color: '#b91c1c', border: '#f87171' },
            'EXPIRADA': { label: 'Expirada', bg: '#f1f5f9', color: '#64748b', border: '#cbd5e1' }
        };
        return map[status] || { label: (status || '').replace(/_/g, ' '), bg: '#f1f5f9', color: '#475569', border: '#cbd5e1' };
    }

    function getRelativeValidityText(validityDateStr) {
        if (!validityDateStr) return 'Sem validade definida';
        const now = new Date();
        const valDate = new Date(validityDateStr);
        const diffMs = valDate.getTime() - now.getTime();
        if (diffMs <= 0) {
            return '⚠️ Validade expirada';
        }
        const diffMinutes = Math.floor(diffMs / (1000 * 60));
        const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
        const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));

        if (diffMinutes < 60) {
            return `⏳ Vence em ${diffMinutes} min`;
        } else if (diffHours < 24) {
            const timeStr = valDate.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
            return `⏳ Vence hoje às ${timeStr} (${diffHours}h restantes)`;
        } else if (diffDays === 1) {
            const timeStr = valDate.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
            return `⏳ Vence amanhã às ${timeStr}`;
        } else {
            return `⏳ Vence em ${diffDays} dias (${valDate.toLocaleDateString('pt-BR')})`;
        }
    }

    function updateStepper(status) {
        // 5 stages: 1=Criada, 2=Em Análise, 3=Aprovada, 4=PDF, 5=Faturada
        let activeStep = 1;
        let isCompletedUpto = 1;

        if (['EM_CRIACAO', 'DEVOLVIDA'].includes(status)) {
            activeStep = 1;
            isCompletedUpto = 1;
        } else if (['AGUARDANDO_GESTOR', 'COM_DIRETOR'].includes(status)) {
            activeStep = 2;
            isCompletedUpto = 2;
        } else if (status === 'APROVADA') {
            activeStep = 3;
            isCompletedUpto = 3;
        } else if (['PDF_GERADO', 'AGUARDANDO_PEDIDO'].includes(status)) {
            activeStep = 4;
            isCompletedUpto = 4;
        } else if (['FINALIZADA_COM_PEDIDO', 'FATURADA'].includes(status)) {
            activeStep = 5;
            isCompletedUpto = 5;
        } else {
            activeStep = 1;
            isCompletedUpto = 1;
        }

        for (let i = 1; i <= 5; i++) {
            const node = document.getElementById(`step-node-${i}`);
            if (!node) continue;
            node.classList.remove('completed', 'active');
            if (i < isCompletedUpto) {
                node.classList.add('completed');
            } else if (i === activeStep) {
                node.classList.add('active');
            }

            const line = document.getElementById(`step-line-${i}`);
            if (line) {
                if (i < isCompletedUpto) {
                    line.classList.add('completed');
                } else {
                    line.classList.remove('completed');
                }
            }
        }
    }

    function toggleClientDetails() {
        const panel = document.getElementById("client-details-panel");
        const arrow = document.getElementById("client-details-arrow");
        const text = document.getElementById("btn-client-details-text");
        if (!panel) return;
        const isHidden = panel.style.display === "none";
        panel.style.display = isHidden ? "block" : "none";
        if (arrow) arrow.style.transform = isHidden ? "rotate(180deg)" : "rotate(0deg)";
        if (text) text.innerText = isHidden ? "Recolher" : "Detalhes";
    }

    function toggleConditionsAccordion() {
        const body = document.getElementById("conditions-accordion-body");
        const arrow = document.getElementById("conditions-toggle-arrow");
        const text = document.getElementById("conditions-toggle-text");
        if (!body) return;
        const isHidden = body.style.display === "none";
        body.style.display = isHidden ? "block" : "none";
        if (arrow) arrow.style.transform = isHidden ? "rotate(180deg)" : "rotate(0deg)";
        if (text) text.innerText = isHidden ? "Recolher" : "Ver / Editar";
    }

    function updateConditionsSummaryText() {
        const forma = document.getElementById("forma-pagamento") ? document.getElementById("forma-pagamento").value.trim() : "";
        const prazo = document.getElementById("prazo-entrega") ? document.getElementById("prazo-entrega").value.trim() : "";
        const frete = document.getElementById("frete-tipo") ? document.getElementById("frete-tipo").value.trim() : "CIF";
        
        const parts = [];
        if (forma) parts.push(forma);
        if (prazo) parts.push(prazo);
        if (frete) parts.push(`Frete ${frete}`);
        
        const summaryText = parts.length > 0 ? parts.join(" · ") : "Toque para definir pagamento e prazos";
        const el = document.getElementById("conditions-summary-details");
        if (el) el.innerText = summaryText;
    }

    function toggleOptionsMenu(event) {
        if (event) event.stopPropagation();
        const dropdown = document.getElementById("options-menu-dropdown");
        if (dropdown) {
            dropdown.classList.toggle("show");
        }
    }

    document.addEventListener("click", () => {
        const dropdown = document.getElementById("options-menu-dropdown");
        if (dropdown && dropdown.classList.contains("show")) {
            dropdown.classList.remove("show");
        }
    });

    function handleMenuAction(action) {
        const dropdown = document.getElementById("options-menu-dropdown");
        if (dropdown) dropdown.classList.remove("show");

        if (action === 'draft') {
            saveDraft(true);
        } else if (action === 'conditions') {
            const body = document.getElementById("conditions-accordion-body");
            if (body && body.style.display === "none") {
                toggleConditionsAccordion();
            }
            body?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        } else if (action === 'lost') {
            openLostModal();
        }
    }

    function handlePrimaryAction() {
        if (!quote) return;
        if (['EM_CRIACAO', 'DEVOLVIDA'].includes(quote.status)) {
            submitQuote();
        } else if (quote.status === 'APROVADA') {
            downloadPdf();
        } else if (quote.status === 'PDF_GERADO') {
            openReleaseModal();
        } else if (['AGUARDANDO_GESTOR', 'COM_DIRETOR'].includes(quote.status)) {
            showToast("Cotação em análise de alçada. Aguarde o retorno da gestão.", "info");
        } else {
            showToast("Esta cotação já está com status " + quote.status.replace(/_/g, ' ') + ".", "info");
        }
    }

    function renderView() {
        if (!quote) return;

        // Hide spinner & show panel
        document.getElementById("loading-spinner").style.display = "none";
        document.getElementById("representative-panel").style.display = "block";

        // Bind header details
        document.getElementById("quote-number").innerText = quote.numero;
        document.getElementById("quote-origin").innerText = (quote.origem || 'WEB').toUpperCase();
        document.getElementById("quote-emission").innerText = new Date(quote.data_emissao).toLocaleDateString('pt-BR');
        
        // Relative validity
        const validityEl = document.getElementById("quote-validity");
        if (validityEl) {
            validityEl.innerText = quote.data_validade ? new Date(quote.data_validade).toLocaleString('pt-BR') : 'Sem data';
        }
        const relBadge = document.getElementById("validity-relative-badge");
        if (relBadge) {
            relBadge.innerText = getRelativeValidityText(quote.data_validade);
        }

        // Stepper
        updateStepper(quote.status);

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
                    bannerText.innerHTML = "<strong>Cotação Aprovada:</strong> Proposta comercial aprovada! Clique no botão <strong>'Gerar PDF'</strong> no rodapé para emitir o documento e habilitar a liberação de faturamento.";
                } else if (quote.status === 'PDF_GERADO') {
                    bannerText.innerHTML = "<strong>PDF Emitido:</strong> Documento gerado com sucesso. Clique no botão <strong>'Liberar para Faturamento'</strong> no rodapé para registrar o pedido no Sankhya.";
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

        // Friendly Status badge
        const badge = document.getElementById("quote-status-badge");
        if (badge) {
            const info = getFriendlyStatus(quote.status);
            badge.innerText = info.label;
            badge.style.backgroundColor = info.bg;
            badge.style.color = info.color;
            badge.style.borderColor = info.border;
            badge.className = "badge-status " + quote.status.toLowerCase().replace(/_/g, '-');
        }

        // Bind Client profile (compact)
        let clientCity = (quote.parceiro && quote.parceiro.cidade && quote.parceiro.cidade !== 'null') ? quote.parceiro.cidade : '';
        clientCity = clientCity.replace(/[\-\/]\s*2\b/g, '').trim();
        let rawClientUf = (quote.parceiro && quote.parceiro.uf && quote.parceiro.uf !== 'null') ? quote.parceiro.uf : '';
        if (rawClientUf === '2' || (!isNaN(rawClientUf) && rawClientUf !== '') || (clientCity && clientCity.toUpperCase().includes('UBERLANDIA'))) {
            rawClientUf = 'MG';
        }
        const locationText = (clientCity || rawClientUf) ? `${clientCity}${clientCity && rawClientUf ? '/' : ''}${rawClientUf}` : 'Não informada';

        document.getElementById("client-name").innerText = quote.parceiro.razao_social;
        document.getElementById("client-cnpj").innerText = "CNPJ/CPF: " + (quote.parceiro.cnpj || 'Não cadastrado');
        document.getElementById("client-location").innerText = locationText;
        document.getElementById("client-contact").innerText = (quote.parceiro.telefone || quote.parceiro.email || 'Não informado');
        document.getElementById("rep-name").innerText = quote.representante.nome + (quote.representante.equipe ? ` (${quote.representante.equipe.nome})` : '');

        // Bind Commercial Conditions
        document.getElementById("forma-pagamento").value = quote.forma_pagamento || "";
        document.getElementById("prazo-entrega").value = quote.prazo_entrega || "";
        document.getElementById("frete-tipo").value = quote.frete_tipo || "CIF";
        document.getElementById("transportadora").value = quote.transportadora || "";
        document.getElementById("obs-cliente").value = quote.observacao_cliente || "";
        document.getElementById("obs-interna").value = quote.observacao_interna || "";
        updateConditionsSummaryText();

        const addItemContainer = document.getElementById("add-item-form-container");
        const menuDraft = document.getElementById("menu-save-draft");
        const menuLost = document.getElementById("menu-mark-lost");

        if (isEditingLocked) {
            document.getElementById("forma-pagamento").disabled = true;
            document.getElementById("prazo-entrega").disabled = true;
            document.getElementById("frete-tipo").disabled = true;
            document.getElementById("transportadora").disabled = true;
            document.getElementById("obs-cliente").disabled = true;
            document.getElementById("obs-interna").disabled = true;
            
            if (addItemContainer) addItemContainer.style.display = "none";
            if (menuDraft) menuDraft.style.display = "none";
            if (menuLost) menuLost.style.display = "none";
        } else {
            document.getElementById("forma-pagamento").disabled = false;
            document.getElementById("prazo-entrega").disabled = false;
            document.getElementById("frete-tipo").disabled = false;
            document.getElementById("transportadora").disabled = false;
            document.getElementById("obs-cliente").disabled = false;
            document.getElementById("obs-interna").disabled = false;
            
            if (addItemContainer) addItemContainer.style.display = "block";
            if (menuDraft) menuDraft.style.display = "flex";
            if (menuLost) menuLost.style.display = "flex";
        }

        // Configure Single Dynamic Primary Button in Sticky Footer
        const primaryBtn = document.getElementById("btn-sticky-primary");
        const primaryLabel = document.getElementById("btn-sticky-primary-label");

        if (primaryBtn && primaryLabel) {
            // Reset styles
            primaryBtn.className = "btn-sticky-primary";
            primaryBtn.disabled = false;
            primaryBtn.style.opacity = "1";
            primaryBtn.style.cursor = "pointer";

            if (['EM_CRIACAO', 'DEVOLVIDA'].includes(quote.status)) {
                primaryBtn.classList.add("btn-submit-action");
                primaryLabel.innerText = "Enviar para Aprovação";
                primaryBtn.innerHTML = `
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                    <span>Enviar para Aprovação</span>
                `;
            } else if (['AGUARDANDO_GESTOR', 'COM_DIRETOR'].includes(quote.status)) {
                primaryBtn.style.background = "#e2e8f0";
                primaryBtn.style.color = "#64748b";
                primaryBtn.disabled = true;
                primaryBtn.style.cursor = "default";
                primaryBtn.innerHTML = `
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    <span>Em Análise de Alçada</span>
                `;
            } else if (quote.status === 'APROVADA') {
                primaryBtn.classList.add("btn-pdf-action");
                primaryBtn.innerHTML = `
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                    <span>Gerar PDF da Proposta</span>
                `;
            } else if (quote.status === 'PDF_GERADO') {
                primaryBtn.classList.add("btn-release-action");
                primaryBtn.innerHTML = `
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    <span>Liberar Faturamento</span>
                `;
            } else if (['FINALIZADA_COM_PEDIDO', 'FATURADA'].includes(quote.status)) {
                primaryBtn.style.background = "#dcfce7";
                primaryBtn.style.color = "#15803d";
                primaryBtn.disabled = true;
                primaryBtn.style.cursor = "default";
                primaryBtn.innerHTML = `
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                    <span>Faturamento Concluído</span>
                `;
            } else if (quote.status === 'PERDIDA') {
                primaryBtn.style.background = "#fee2e2";
                primaryBtn.style.color = "#b91c1c";
                primaryBtn.disabled = true;
                primaryBtn.style.cursor = "default";
                primaryBtn.innerHTML = `<span>Proposta Perdida</span>`;
            } else if (quote.status === 'EXPIRADA') {
                primaryBtn.style.background = "#f1f5f9";
                primaryBtn.style.color = "#64748b";
                primaryBtn.disabled = true;
                primaryBtn.style.cursor = "default";
                primaryBtn.innerHTML = `<span>Proposta Expirada</span>`;
            }
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

    // Render Compact 3-Line Cards (Point 1)
    function renderItems() {
        const container = document.getElementById("items-cards-container");
        if (!container) return;
        container.innerHTML = "";
        
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
        const elAllBadge = document.getElementById("count-all-badge");
        const elAtt = document.getElementById("count-attention");
        const elApp = document.getElementById("count-approved");
        if (elAll) elAll.innerText = countAll;
        if (elAllBadge) elAllBadge.innerText = `${countAll} itens`;
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
            container.innerHTML = `
                <div style="text-align: center; color: var(--color-text-muted); padding: 35px 15px; background: white; border-radius: 12px; border: 1px dashed var(--color-border); font-size: 13.5px;">
                    Nenhum item encontrado com os filtros aplicados.
                </div>
            `;
        } else {
            filtered.forEach(item => {
                const minEf = getItemEffectiveMin(item);
                const isInconsistent = isItemInconsistent(item);
                const propPrice = parseFloat(item.preco_unit_proposto || 0);
                const sugPrice = parseFloat(item.preco_unit_sugerido || 0);
                const isBelowMin = propPrice < minEf;
                const isItemLocked = isEditingLocked || item.status_item === 'recusado';
                const rowClass = item.status_item === 'recusado' ? "recusado" : (item.status_item === 'aprovado' ? "aprovado" : "");

                // Semantic Price Tag Badge for Line 3
                let priceTagHtml = '';
                if (isBelowMin) {
                    priceTagHtml = `<span class="badge-price-tag tag-below-min">⚠️ Abaixo do mínimo</span>`;
                } else if (Math.abs(propPrice - sugPrice) < 0.005) {
                    priceTagHtml = `<span class="badge-price-tag tag-table">Preço de tabela</span>`;
                } else if (propPrice < sugPrice) {
                    const perc = sugPrice > 0 ? (((sugPrice - propPrice) / sugPrice) * 100).toFixed(1) : '0';
                    priceTagHtml = `<span class="badge-price-tag tag-discount">−${perc}%</span>`;
                } else {
                    const perc = sugPrice > 0 ? (((propPrice - sugPrice) / sugPrice) * 100).toFixed(1) : '0';
                    priceTagHtml = `<span class="badge-price-tag tag-above">+${perc}%</span>`;
                }

                container.innerHTML += `
                    <div class="compact-item-card ${rowClass} ${isBelowMin ? 'below-min-card' : ''}" id="card-item-${item.id}">
                        <!-- LINHA 1: descrição em destaque + código + unidade + lixeira -->
                        <div class="item-line-1">
                            <div class="item-desc-wrap">
                                <span class="item-desc">${item.produto.descricao}</span>
                                <span class="item-code-un">#${item.produto.codigo_sankhya} · ${item.produto.unidade}</span>
                                ${item.mostrar_selo_campanha && item.campanha_id ? '<span class="badge-campanha" style="margin-left:4px;">Campanha</span>' : ''}
                                ${isInconsistent ? '<span class="badge" style="background:#fef3c7; color:#92400e; font-size:10.5px; padding:2px 5px; border-radius:4px; border:1px solid #fcd34d; margin-left:4px;" title="Mínimo cadastral divergente">⚠️ Mín > Sugerido</span>' : ''}
                            </div>
                            <div>
                                ${!isItemLocked ? `
                                    <button type="button" class="btn-remove-item" onclick="deleteItem(${item.id})" title="Remover item">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                                    </button>
                                ` : ''}
                            </div>
                        </div>

                        <!-- LINHA 2: quantidade (–/+) à esquerda e preço proposto com inputmode="decimal" à direita -->
                        <div class="item-line-2">
                            <div class="qty-stepper">
                                ${!isItemLocked ? `<button type="button" class="qty-btn" onclick="stepQty(${item.id}, -1)">–</button>` : ''}
                                <input type="number" class="qty-input" value="${item.qtd}" min="1" inputmode="numeric"
                                    ${isItemLocked ? 'disabled' : ''} 
                                    oninput="updateItemCalculations(${item.id}, this.value, null)">
                                ${!isItemLocked ? `<button type="button" class="qty-btn" onclick="stepQty(${item.id}, 1)">+</button>` : ''}
                            </div>

                            <div class="item-price-proposto-wrap">
                                <span class="price-currency">R$</span>
                                <input type="number" class="price-input ${isBelowMin ? 'price-below-min' : ''}" 
                                    value="${propPrice.toFixed(2)}" step="0.01" min="0.01" inputmode="decimal"
                                    ${isItemLocked ? 'disabled' : ''} 
                                    oninput="updateItemCalculations(${item.id}, null, this.value)">
                                ${!isItemLocked && isBelowMin ? `
                                    <button type="button" class="btn btn-outline" style="padding:4px 8px; font-size:11px; font-weight:700; color:#dc2626; border-color:#fca5a5; background:#fff1f2; border-radius:6px;" onclick="resetToMin(${item.id}, ${minEf})">Mín</button>
                                ` : ''}
                            </div>
                        </div>

                        <!-- LINHA 3: Sug. R$ X · Mín. R$ Y e selo à direita -->
                        <div class="item-line-3">
                            <div class="item-benchmarks">
                                <span>Sug. R$ ${formatCurrency(item.preco_unit_sugerido)}</span>
                                <span>·</span>
                                <span>Mín. R$ ${formatCurrency(item.preco_minimo)}</span>
                            </div>
                            <div>
                                ${priceTagHtml}
                            </div>
                        </div>
                    </div>
                `;
            });
        }

        // Show/hide justification box
        const justPanel = document.getElementById("justification-panel");
        if (justPanel) {
            justPanel.style.display = hasItemBelowMin && !isEditingLocked ? "block" : "none";
        }
        
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
        const card = document.getElementById(`card-item-${itemId}`);
        if (card) {
            const inputPrice = card.querySelector(".price-input");
            const minEf = getItemEffectiveMin(item);
            const isBelowMin = parseFloat(item.preco_unit_proposto) < minEf;
            
            if (isBelowMin) {
                if (inputPrice) inputPrice.classList.add("price-below-min");
                card.classList.add("below-min-card");
            } else {
                if (inputPrice) inputPrice.classList.remove("price-below-min");
                card.classList.remove("below-min-card");
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
        const justPanel = document.getElementById("justification-panel");
        if (justPanel) {
            justPanel.style.display = hasItemBelowMin && !isEditingLocked ? "block" : "none";
        }

        recalculateTotalsFromState();
    }

    function resetToMin(itemId, minPrice) {
        if (isEditingLocked) return;
        const card = document.getElementById(`card-item-${itemId}`);
        if (card) {
            const inputPrice = card.querySelector(".price-input");
            if (inputPrice) {
                const roundedPrice = parseFloat(minPrice).toFixed(2);
                inputPrice.value = roundedPrice;
                updateItemCalculations(itemId, null, roundedPrice);
                renderItems(); // re-render to update badges & highlights
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
        const descontoPerc = subtotal > 0 && desconto > 0 ? ((desconto / subtotal) * 100).toFixed(1) : 0;

        // Sticky Footer Totals
        const stickyTotalEl = document.getElementById("sticky-total-proposto");
        if (stickyTotalEl) {
            stickyTotalEl.innerText = "R$ " + formatCurrency(total);
        }
        
        const stickyDiscountEl = document.getElementById("sticky-total-discount");
        if (stickyDiscountEl) {
            if (desconto > 0.009) {
                stickyDiscountEl.innerText = `Desconto: - R$ ${formatCurrency(desconto)} (-${descontoPerc}%)`;
                stickyDiscountEl.style.color = "#dc2626";
            } else {
                stickyDiscountEl.innerText = "Sem desconto comercial aplicado";
                stickyDiscountEl.style.color = "#64748b";
            }
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
            const isQuotable = (p.cotavel !== false) && parseFloat(p.preco_sugerido || 0) > 0;
            const sugVal = parseFloat(p.preco_sugerido || 0);
            const sugText = isQuotable ? `R$ ${formatCurrency(sugVal)}` : 'Sem preço na tabela';

            if (!isQuotable) {
                div.style.opacity = '0.55';
                div.style.cursor = 'not-allowed';
            }
            div.onclick = () => {
                if (!isQuotable) {
                    showToast(`O produto "${p.descricao}" não possui preço na tabela de preços e não pode ser cotado.`, 'warning');
                    return;
                }
                selectProductForAdd(p.id);
            };

            div.innerHTML = `
                <div style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap; margin-right: 10px;">
                    <span class="prod-code">${p.codigo_sankhya}</span>
                    <span class="prod-title">${p.descricao}</span>
                    ${p.marca ? `<span style="font-size: 11px; color: #64748b; margin-left: 6px;">(${p.marca})</span>` : ''}
                </div>
                <div class="prod-price" style="${isQuotable ? '' : 'color: #ef4444; font-size: 11px;'}">${sugText}</div>
            `;
            menu.appendChild(div);
        });
    }

    function selectProductForAdd(productId) {
        const p = currentSearchedProducts.find(item => item.id === productId);
        if (!p) return;

        const isQuotable = (p.cotavel !== false) && parseFloat(p.preco_sugerido || 0) > 0;
        if (!isQuotable && !['PROD001', 'PROD002', 'PROD003'].includes(p.codigo_sankhya)) {
            showToast(`O produto "${p.descricao}" não possui preço cadastrado na tabela de preços e não pode ser cotado.`, 'warning');
            return;
        }

        document.getElementById("selected-product-id").value = p.id;
        document.getElementById("add-product-search-input").value = `${p.codigo_sankhya} - ${p.descricao}`;
        document.getElementById("clear-selected-prod-btn").style.display = "block";
        
        // Set pricing from database or default
        const sugerido = parseFloat(p.preco_sugerido || 0);
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
