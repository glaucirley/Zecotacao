@extends(auth()->check() ? (auth()->user()->isRepresentante() ? 'layouts.representative' : 'layouts.app') : 'layouts.public')

@section('page_title', isset($quote) ? 'Cotação ' . $quote->numero : 'Cotação Comercial')
@section('hide_tab_bar', 'true')

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
    .filter-tab-btn.tab-rejected.active {
        color: #b91c1c;
    }
    .filter-tab-btn.tab-rejected .tab-badge {
        background: #fee2e2;
        color: #b91c1c;
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
        padding-bottom: 95px !important;
    }
    .mobile-container {
        padding-bottom: 95px !important;
    }
    /* Oculta a barra inferior do painel para evitar sobreposição com o rodapé de ações da proposta */
    .tab-bar {
        display: none !important;
    }

    /* Compact Item Card (3 Lines Layout) */
    .compact-item-card,
    .item-card-row {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        margin-bottom: 10px;
        padding: 12px 14px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.03);
        position: relative;
        transition: all 0.15s ease;
        display: flex;
        flex-direction: column;
        gap: 8px;
        width: 100%;
        box-sizing: border-box;
    }
    .compact-item-card.below-min-card,
    .item-card-row.below-min-card {
        border-color: #fca5a5 !important;
        background: #fff8f8 !important;
    }
    .compact-item-card.recusado,
    .item-card-row.recusado {
        border-color: #f87171 !important;
        background-color: #fef2f2 !important;
        border-left: 5px solid #ef4444 !important;
    }
    .compact-item-card.aprovado,
    .item-card-row.aprovado {
        border-left: 4px solid #10b981;
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
        background: rgba(255, 255, 255, 0.98);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border-top: 1px solid #e2e8f0;
        box-shadow: 0 -4px 16px rgba(0, 0, 0, 0.08);
        padding: 10px 16px;
        padding-bottom: calc(10px + env(safe-area-inset-bottom, 0px));
        z-index: 1050;
        display: none; /* Initially hidden until quote loads */
        transition: left 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        box-sizing: border-box;
    }

    /* No layout do Representante (.mobile-container), o rodapé se alinha perfeitamente ao contêiner mobile centralizado */
    .mobile-container .sticky-footer-quote-bar {
        left: 50% !important;
        transform: translateX(-50%) !important;
        width: 100% !important;
        max-width: 520px !important;
        right: auto !important;
        border-left: 1px solid #e2e8f0;
        border-right: 1px solid #e2e8f0;
    }

    /* Prevent footer from covering sidebar on desktop when in admin layout */
    @media (min-width: 769px) {
        .app-container:has(.sidebar) .sticky-footer-quote-bar {
            left: 260px;
        }
        .app-container.sidebar-collapsed:has(.sidebar) .sticky-footer-quote-bar {
            left: 76px;
        }
    }

    .sticky-footer-inner {
        max-width: 1200px;
        margin: 0 auto;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        width: 100%;
        box-sizing: border-box;
    }
    .sticky-footer-totals {
        display: flex;
        flex-direction: column;
        min-width: 0;
        flex: 1 1 auto;
    }
    .sticky-footer-val {
        font-size: 17px;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.2;
        white-space: nowrap;
    }
    .sticky-footer-discount {
        font-size: 11px;
        color: #64748b;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .sticky-footer-actions {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-shrink: 0;
    }
    .btn-sticky-primary {
        background: #2563eb;
        color: #ffffff;
        border: none;
        border-radius: 8px;
        padding: 9px 16px;
        font-size: 13px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        cursor: pointer;
        box-shadow: 0 2px 6px rgba(37,99,235,0.25);
        transition: all 0.15s ease;
        white-space: nowrap;
        max-width: 240px;
    }
    .btn-sticky-primary span {
        overflow: hidden;
        text-overflow: ellipsis;
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
        width: 36px;
        height: 36px;
        min-width: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        font-weight: 800;
        cursor: pointer;
        transition: background 0.15s, border-color 0.15s;
    }
    .btn-options-menu:hover {
        background: #e2e8f0;
        border-color: #94a3b8;
    }

    @media (max-width: 390px) {
        .sticky-footer-quote-bar {
            padding: 8px 10px;
            padding-bottom: calc(8px + env(safe-area-inset-bottom, 0px));
        }
        .sticky-footer-val {
            font-size: 15px;
        }
        .sticky-footer-discount {
            font-size: 10px;
        }
        .btn-sticky-primary {
            padding: 8px 10px;
            font-size: 12px;
            max-width: 170px;
        }
        .btn-options-menu {
            width: 32px;
            height: 32px;
            min-width: 32px;
        }
    }
    .options-menu-dropdown {
        position: absolute;
        bottom: 48px;
        right: 0;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 10px;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        padding: 6px;
        min-width: 200px;
        z-index: 1050;
        display: none; /* Must be hidden by default */
        flex-direction: column;
        gap: 4px;
    }
    .options-menu-dropdown.show {
        display: flex !important;
    }
    .options-menu-dropdown .menu-item,
    .options-menu-item {
        background: none;
        border: none;
        width: 100%;
        text-align: left;
        padding: 9px 12px;
        font-size: 13px;
        font-weight: 600;
        color: #334155;
        border-radius: 6px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 10px;
        transition: background 0.15s, color 0.15s;
    }
    .options-menu-dropdown .menu-item:hover,
    .options-menu-item:hover {
        background: #f1f5f9;
        color: #0f172a;
    }
    .options-menu-dropdown .menu-item.item-danger,
    .options-menu-item.item-danger {
        color: #dc2626;
    }
    .options-menu-dropdown .menu-item.item-danger:hover,
    .options-menu-item.item-danger:hover {
        background: #fef2f2;
        color: #b91c1c;
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

        <!-- Visual 6-Step Progress Timeline -->
        <div class="quote-stepper-wrapper" style="background: white; border: 1px solid var(--color-border); border-radius: 12px; padding: 12px 14px; margin-bottom: 16px; box-shadow: var(--shadow-sm);">
            <div class="quote-stepper">
                <div class="step-item" id="step-node-1">
                    <div class="step-circle">1</div>
                    <span class="step-label">Criada</span>
                </div>
                <div class="step-line" id="step-line-1"></div>
                <div class="step-item" id="step-node-2">
                    <div class="step-circle">2</div>
                    <span class="step-label">Enviada</span>
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
                    <span class="step-label">Pedido</span>
                </div>
                <div class="step-line" id="step-line-5"></div>
                <div class="step-item" id="step-node-6">
                    <div class="step-circle">6</div>
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

    <!-- Proposal Delivery Status Banner (WhatsApp / Web Share Tracking) -->
    <div id="proposal-delivery-banner" style="display: none; margin-bottom: 16px; padding: 12px 16px; border-radius: 10px; font-size: 13px; font-weight: 500; align-items: center; justify-content: space-between; gap: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
            <span id="proposal-delivery-icon"></span>
            <span id="proposal-delivery-text"></span>
        </div>
        <button type="button" class="btn btn-outline" id="btn-reopen-send-banner" onclick="openSendQuoteModal()" style="font-size: 11.5px; padding: 4px 10px; border-radius: 6px; white-space: nowrap; background: #ffffff;">
            Reenviar
        </button>
    </div>

    <!-- Priority Attention Alerts Container (Devolução com instruções, itens recusados com motivo, alerta de margem negativa) -->
    <div id="quote-attention-banner" style="display: none; margin-bottom: 16px; flex-direction: column; gap: 12px;"></div>

    <!-- Compact Client Header Strip -->
    <div class="compact-client-strip" style="background: white; border: 1px solid var(--color-border); border-radius: 12px; padding: 12px 16px; margin-bottom: 16px; box-shadow: var(--shadow-sm);">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; font-size: 13.5px;">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary)" stroke-width="2" style="flex-shrink:0;"><path d="M3 21h18M5 21V7l8-4v18M19 21V11l-6-4M9 9h1M9 13h1M9 17h1M15 13h1M15 17h1"/></svg>
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

    <!-- Dedicated Panel: Itens Não Encontrados pela IA -->
    <div id="ai-unfound-items-panel" class="card" style="display: none; margin-bottom: 16px; padding: 16px; background: #fff1f2; border: 1.5px solid #fda4af; border-radius: 12px; box-shadow: 0 2px 8px rgba(225, 29, 72, 0.08);">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 10px; margin-bottom: 10px;">
            <div>
                <h3 style="font-size: 15.5px; margin: 0; font-weight: 800; color: #9f1239; display: flex; align-items: center; gap: 8px;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#e11d48" stroke-width="2.5" style="flex-shrink:0;"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    <span>Itens Não Encontrados pela IA</span>
                    <span id="ai-unfound-count-badge" style="background: #e11d48; color: #ffffff; font-size: 11.5px; font-weight: 700; padding: 2px 9px; border-radius: 999px;">0</span>
                </h3>
                <p style="font-size: 12.5px; color: #be123c; margin: 4px 0 0 0; line-height: 1.4;">
                    Os itens abaixo foram solicitados pelo cliente, mas a IA não localizou correspondência exata no catálogo. Caso necessário, clique em <strong>Incluir Manualmente</strong> para buscar o produto correspondente no catálogo ou dispense o item.
                </p>
            </div>
        </div>
        <div id="ai-unfound-items-list" style="display: flex; flex-direction: column; gap: 10px;">
            <!-- Preenchido via JS -->
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
                <button type="button" class="filter-tab-btn tab-rejected" id="tab-rejected" onclick="setFilterTab('rejected')">
                    ❌ Recusados <span class="tab-badge" id="count-rejected">0</span>
                </button>
            </div>
        </div>

        <!-- Compact Item Cards Container (Replacing heavy table) -->
        <div id="items-cards-container" class="items-cards-container" style="display: flex; flex-direction: column; gap: 10px;">
            <!-- Dynamically injected 3-line compact item cards -->
        </div>

        <!-- Add Item Row -->
        <div id="add-item-form-container" style="margin-top: 16px; background-color: var(--color-bg); padding: 16px; border-radius: var(--radius-md); border: 1px solid var(--color-border); transition: all 0.2s ease;">
            <div id="replacing-unfound-banner" style="display: none; margin-bottom: 12px; padding: 10px 12px; background: #fff1f2; border: 1px solid #fda4af; border-radius: 8px; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap;">
                <div style="font-size: 12.5px; color: #9f1239; line-height: 1.35;">
                    <strong>🔄 Incluindo manualmente item não encontrado pela IA:</strong>
                    <span id="replacing-unfound-desc" style="font-weight: 700; text-decoration: underline;"></span>
                    <span id="replacing-unfound-qty" style="font-size: 11.5px; color: #be123c; margin-left: 4px;"></span>
                </div>
                <button type="button" onclick="cancelManualInclusionReplacement()" style="background: #ffffff; border: 1px solid #fda4af; color: #be123c; border-radius: 6px; padding: 4px 10px; font-size: 11.5px; font-weight: 700; cursor: pointer;">
                    Cancelar vínculo
                </button>
            </div>

            <h4 id="add-item-form-title" style="font-size: 13.5px; margin-bottom: 10px; color: var(--color-primary); display: flex; align-items: center; gap: 6px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                <span>Adicionar Produto Manualmente</span>
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
                    <button type="button" id="btn-add-new-item" class="btn btn-secondary" onclick="addNewItem()" style="padding: 9px 16px; font-weight: 600; font-size: 13px;">
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
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary)" stroke-width="2" style="flex-shrink:0;"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/></svg>
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
                <span id="just-panel-title">Justificativa Obrigatória — Desconto Especial</span>
            </h3>
        </div>
        <p id="just-panel-desc" style="font-size: 13px; color: #b45309; margin-bottom: 15px;">
            Existem produtos abaixo do preço mínimo ou com desconto acima da sua alçada. É obrigatório registrar uma justificativa e anexar comprovantes para enviar a cotação à aprovação.
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

    <!-- Collapsible Audit Trail & History Accordion (Collapsed by Default) -->
    <div class="card" style="margin-bottom: 24px; padding: 0; overflow: hidden; border: 1px solid var(--color-border); border-radius: 12px; background: #ffffff; box-shadow: var(--shadow-sm);">
        <div onclick="toggleAuditAccordion()" style="padding: 14px 18px; display: flex; justify-content: space-between; align-items: center; cursor: pointer; background: #f8fafc; transition: background 0.2s ease;">
            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; font-size: 13px;">
                <span style="font-size: 16px; line-height: 1;">🕘</span>
                <span style="font-weight: 700; color: #0f172a;">Trilha de Auditoria</span>
                <span style="color: #94a3b8;">·</span>
                <span id="audit-summary-details" style="color: #64748b; font-size: 12.5px;">Carregando eventos...</span>
            </div>
            <div style="display: flex; align-items: center; gap: 6px; font-size: 12px; color: #2563eb; font-weight: 600; white-space: nowrap;">
                <span id="audit-toggle-text">Ver ⌄</span>
                <svg id="audit-toggle-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="transition: transform 0.2s ease;"><polyline points="6 9 12 15 18 9"/></svg>
            </div>
        </div>

        <div id="audit-accordion-body" style="display: none; padding: 18px; border-top: 1px solid var(--color-border); background: white;">
            <div id="quote-audit-trail" style="display: flex; flex-direction: column; gap: 10px;">
                <div style="font-size: 12.5px; color: #64748b; text-align: center; padding: 12px;">Carregando histórico da cotação...</div>
            </div>
        </div>
    </div>
</div>

<!-- Sticky Bottom Action & Totals Bar (Point 2) -->
<div class="sticky-footer-quote-bar" id="sticky-footer-bar" style="display: none;">
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
            <div class="dropdown-wrapper" id="options-menu-wrapper" style="position: relative;">
                <button type="button" class="btn-options-menu" id="btn-options-menu" onclick="toggleOptionsMenu(event)" title="Mais opções">
                    ⋯
                </button>
                <div class="options-menu-dropdown" id="options-menu-dropdown">
                    <button type="button" class="menu-item" id="menu-send-whatsapp" onclick="handleMenuAction('send_whatsapp')">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                        <span>Enviar ao Cliente (WhatsApp)</span>
                    </button>
                    <button type="button" class="menu-item" id="menu-share-pdf" onclick="handleMenuAction('share_pdf')">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
                        <span>Compartilhar PDF</span>
                    </button>
                    <button type="button" class="menu-item" id="menu-download-pdf" onclick="handleMenuAction('download_pdf')">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                        <span>Baixar PDF da Proposta</span>
                    </button>
                    <button type="button" class="menu-item" id="menu-release-billing" onclick="handleMenuAction('release_billing')">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        <span>Liberar Faturamento</span>
                    </button>
                    <button type="button" class="menu-item" id="menu-save-draft" onclick="handleMenuAction('draft')">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                        <span>Salvar Rascunho</span>
                    </button>
                    <button type="button" class="menu-item" id="menu-edit-conditions" onclick="handleMenuAction('conditions')">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                        <span>Editar Condições</span>
                    </button>
                    <button type="button" class="menu-item item-danger" id="menu-mark-lost" onclick="handleMenuAction('lost')">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                        <span>Marcar como Perdida</span>
                    </button>
                </div>
            </div>

            <!-- Single Dynamic Primary Action Button -->
            <button type="button" class="btn-sticky-primary" id="btn-sticky-primary" onclick="handlePrimaryAction()">
                <span id="btn-sticky-primary-label">Carregando...</span>
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

<!-- Send Quote to Client Modal (WhatsApp & Web Share) -->
<div id="send-quote-modal" style="display: none; position: fixed; inset: 0; background-color: rgba(15, 23, 42, 0.55); backdrop-filter: blur(4px); align-items: center; justify-content: center; z-index: 1050; padding: 16px;">
    <div class="auth-card" style="max-width: 520px; width: 100%; max-height: 90vh; overflow-y: auto; padding: 24px; border-radius: 16px; box-shadow: 0 20px 40px rgba(0,0,0,0.2); background: #ffffff;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 36px; height: 36px; border-radius: 10px; background: #25d366; color: #ffffff; display: flex; align-items: center; justify-content: center;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                </div>
                <div>
                    <h3 style="font-size: 16px; margin: 0; font-weight: 700; color: #0f172a;">Enviar Proposta Comercial</h3>
                    <p style="font-size: 11.5px; color: #64748b; margin: 0;">Disparo direto pelo WhatsApp do seu aparelho</p>
                </div>
            </div>
            <button type="button" onclick="closeSendQuoteModal()" style="border: none; background: none; font-size: 20px; color: #94a3b8; cursor: pointer; padding: 4px 8px;">✕</button>
        </div>

        <!-- Seletor de Contatos Ranqueados -->
        <div style="margin-bottom: 16px;">
            <label style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: #475569; display: block; margin-bottom: 8px;">
                Contato de Destino
            </label>
            <div id="send-contacts-container" style="display: flex; flex-direction: column; gap: 8px;">
                <!-- Preenchido via JS com os contatos ranqueados -->
                <div style="text-align: center; padding: 16px; font-size: 12.5px; color: #94a3b8;">Carregando contatos do cliente...</div>
            </div>

            <!-- Formulário Novo / Outro Número (recolhível) -->
            <div id="send-custom-contact-box" style="display: none; margin-top: 10px; padding: 12px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px;">
                <div class="form-group" style="margin-bottom: 8px;">
                    <label class="form-label" style="font-size: 11.5px;">Nome do Contato (Opcional)</label>
                    <input type="text" id="send-custom-name" class="form-control" placeholder="Ex: Dr. Roberto / Comprador" style="font-size: 13px;" oninput="onCustomContactInput()">
                </div>
                <div class="form-group" style="margin-bottom: 4px;">
                    <label class="form-label" style="font-size: 11.5px;">Celular com WhatsApp (com DDD)</label>
                    <input type="tel" id="send-custom-phone" class="form-control" placeholder="(00) 90000-0000" inputmode="tel" style="font-size: 13px;" oninput="onCustomPhoneInput(this)">
                </div>
                <div id="send-phone-validation-feedback" style="font-size: 12px; font-weight: 600; margin-top: 6px; display: none;"></div>
            </div>
        </div>

        <!-- Prévia da Mensagem -->
        <div style="margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                <label style="font-size: 12px; font-weight: 700; text-transform: uppercase; color: #475569;">
                    Prévia da Mensagem
                </label>
                <span style="font-size: 11px; color: #64748b;">Texto editável</span>
            </div>
            <textarea id="send-message-preview" class="form-control" rows="5" style="font-size: 12.5px; line-height: 1.4; background: #f8fafc; border-color: #cbd5e1;"></textarea>
            <small style="display: block; margin-top: 5px; font-size: 11px; color: #64748b;">
                O link rastreável exclusivo da proposta será anexado à mensagem automaticamente.
            </small>
        </div>

        <!-- Botões de Ação -->
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <button type="button" class="btn btn-outline" onclick="submitSendQuote('WEB_SHARE')" id="btn-share-pdf-native" style="flex: 1; padding: 12px; font-size: 12.5px; border-radius: 10px; display: flex; align-items: center; justify-content: center; gap: 6px;" title="Compartilhar pelo menu nativo do aparelho">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
                <span>Compartilhar PDF</span>
            </button>
            <button type="button" class="btn btn-whatsapp" onclick="submitSendQuote('WHATSAPP')" id="btn-open-wa-native" style="flex: 1.5; padding: 12px; font-size: 13.5px; font-weight: 700; background: #25d366; color: #ffffff; border: none; border-radius: 10px; display: flex; align-items: center; justify-content: center; gap: 6px; box-shadow: 0 4px 12px rgba(37, 211, 102, 0.3); cursor: pointer;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                <span>Abrir WhatsApp</span>
            </button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
    window.escapeHtml = escapeHtml;

    // Global override to guarantee no native alert locks the mobile screen
    window.alert = function(msg) {
        if (typeof showToast === 'function') {
            showToast(msg, 'warning');
        } else {
            console.warn(msg);
        }
    };

    function updateStickyFooterOffset() {
        const bar = document.getElementById("sticky-footer-bar");
        if (!bar) return;
        const mobileContainer = document.querySelector(".mobile-container");
        if (mobileContainer) {
            // Em ambiente com contêiner mobile do representante, largura e alinhamento são geridos pelo CSS (.mobile-container .sticky-footer-quote-bar)
            bar.style.left = "";
            bar.style.right = "";
            return;
        }
        const sidebar = document.querySelector(".sidebar");
        if (!sidebar || window.innerWidth <= 768) {
            bar.style.left = "0px";
            bar.style.right = "0px";
        } else {
            const isCollapsed = document.querySelector(".app-container")?.classList.contains("sidebar-collapsed");
            bar.style.left = isCollapsed ? "76px" : "260px";
            bar.style.right = "0px";
        }
    }
    window.addEventListener("resize", updateStickyFooterOffset);

    const QUOTE_ID = {{ isset($quoteId) ? (int)$quoteId : 'null' }};
    const TOKEN = "{{ $token ?? '' }}";
    const API_URL = "{{ url('/api/v1') }}";
    const QUOTE_BASE_URL = TOKEN ? `${API_URL}/cotacoes/token/${encodeURIComponent(TOKEN)}` : `${API_URL}/cotacoes/${QUOTE_ID}`;
    let EXIGE_ANEXO_JUSTIFICATIVA = {{ isset($exigeAnexo) ? ($exigeAnexo ? 'true' : 'false') : (\App\Models\ParametroSistema::getVal('EXIGE_ANEXO_JUSTIFICATIVA', true) ? 'true' : 'false') }};
    let quote = null;
    let productsList = [];
    let isEditingLocked = false;
    const INITIAL_QUOTE = {!! isset($initialQuoteJson) ? $initialQuoteJson : 'null' !!};

    // Robust toast notification fallback
    if (typeof window.showToast !== 'function') {
        let lastToastMsg = null;
        let lastToastT = 0;
        window.showToast = function(message, type = 'info', title = null) {
            const now = Date.now();
            const strMsg = String(message || '').trim();
            if (strMsg === lastToastMsg && (now - lastToastT) < 2000) return;
            lastToastMsg = strMsg;
            lastToastT = now;

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
            if (type === 'success') { icon = '✓'; defaultTitle = 'Sucesso'; }
            else if (type === 'error') { icon = '✕'; defaultTitle = 'Erro'; }
            else if (type === 'warning') { icon = '⚠️'; defaultTitle = 'Atenção'; }

            const toastTitle = title || defaultTitle;
            const formattedMsg = strMsg.replace(/\n/g, '<br>');

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
                setTimeout(() => { if (toast.parentElement) toast.remove(); }, 250);
            };

            if (closeBtn) closeBtn.onclick = (e) => { e.stopPropagation(); dismiss(); };
            toast.onclick = dismiss;

            container.appendChild(toast);
            requestAnimationFrame(() => toast.classList.add('show'));
            setTimeout(dismiss, 5000);
        };
    }

    // Scroll smoothly to justification panel and highlight required field
    function highlightAndScrollToJustification(targetElementId = 'just-texto') {
        updateJustificationPanelVisibility();
        const panel = document.getElementById("justification-panel");
        const targetEl = document.getElementById(targetElementId);

        if (panel) {
            panel.style.display = "block";
            panel.scrollIntoView({ behavior: 'smooth', block: 'center' });
            
            // Visual pulse on panel
            panel.style.transition = 'all 0.3s ease';
            panel.style.borderColor = '#ef4444';
            panel.style.boxShadow = '0 0 0 4px rgba(239, 68, 68, 0.35)';
            
            setTimeout(() => {
                panel.style.borderColor = '#f59e0b';
                panel.style.boxShadow = 'none';
            }, 3000);
        }

        if (targetEl) {
            setTimeout(() => {
                try { targetEl.focus(); } catch (e) {}
                targetEl.style.transition = 'all 0.2s ease';
                targetEl.style.borderColor = '#ef4444';
                targetEl.style.boxShadow = '0 0 0 3px rgba(239, 68, 68, 0.3)';
                setTimeout(() => {
                    targetEl.style.borderColor = '#fcd34d';
                    targetEl.style.boxShadow = 'none';
                }, 3000);
            }, 300);
        }
    }

    document.addEventListener("DOMContentLoaded", () => {
        document.getElementById("sidebar-toggle")?.addEventListener("click", () => {
            setTimeout(updateStickyFooterOffset, 50);
        });
        checkUserSession();

        if (INITIAL_QUOTE) {
            quote = INITIAL_QUOTE;

            // Update Navbar and Tab Title dynamically
            const pageTitleEl = document.querySelector(".page-title");
            if (pageTitleEl) {
                pageTitleEl.innerText = `Cotação ${quote.numero}`;
                pageTitleEl.setAttribute("title", `Cotação ${quote.numero}`);
            }
            document.title = `Zé Cotação — Cotação ${quote.numero}`;

            // Render view immediately with quote details (Instant response, zero lag!)
            renderView();

            // Background fetch products catalog for addition only if quote is editable
            const editableStatuses = ['EM_CRIACAO', 'DEVOLVIDA'];
            if (editableStatuses.includes(quote.status)) {
                fetch(`${QUOTE_BASE_URL}/produtos?limit=50`)
                    .then(res => res.json())
                    .then(prodData => {
                        if (prodData && prodData.success) {
                            productsList = prodData.data;
                        }
                    })
                    .catch(errProd => console.warn("Background produtos:", errProd));
            }
        } else {
            loadData();
        }

        setupAttachmentValidation();
    });

    async function loadData() {
        try {
            // 1. Fetch Quote Details
            const quoteRes = await fetch(`${QUOTE_BASE_URL}`);
            if (!quoteRes.ok) {
                if (quoteRes.status === 404) {
                    const loadContainer = document.getElementById("loading-container");
                    if (loadContainer) {
                        loadContainer.innerHTML = `
                            <div style="text-align:center; padding:50px 20px;">
                                <div style="font-size:36px; margin-bottom:12px;">🔍</div>
                                <h3 style="color:#0f172a; margin-bottom:8px; font-weight:700;">Cotação não encontrada</h3>
                                <p style="color:#64748b; font-size:13px; margin-bottom:20px;">A cotação solicitada não existe ou foi removida.</p>
                                <a href="{{ url('/cotacoes') }}" class="btn btn-primary" style="text-decoration:none; display:inline-block; font-size:13px; font-weight:600; padding:8px 16px; border-radius:8px;">Voltar para Cotações</a>
                            </div>
                        `;
                    }
                    showToast("Cotação não encontrada", 'error');
                    return;
                }
                const errText = await quoteRes.text();
                let errMsg = "Erro HTTP " + quoteRes.status;
                try {
                    const parsed = JSON.parse(errText);
                    if (parsed.message && parsed.error && parsed.error !== parsed.message) {
                        errMsg = `${parsed.error}: ${parsed.message}`;
                    } else {
                        errMsg = parsed.message || parsed.error || errMsg;
                    }
                } catch(e) {}
                showToast("Erro ao carregar a cotação: " + errMsg, 'error');
                return;
            }

            const quoteData = await quoteRes.json();
            if (!quoteData.success) {
                const failMsg = (quoteData.message && quoteData.error && quoteData.error !== quoteData.message)
                    ? `${quoteData.error}: ${quoteData.message}`
                    : (quoteData.message || quoteData.error || "Cotação não encontrada");
                showToast("Erro ao carregar a cotação: " + failMsg, 'error');
                return;
            }
            quote = quoteData.data;
            if (typeof quoteData.exige_anexo_justificativa !== 'undefined') {
                EXIGE_ANEXO_JUSTIFICATIVA = Boolean(quoteData.exige_anexo_justificativa);
            }

            // Update Navbar and Tab Title dynamically
            const pageTitleEl = document.querySelector(".page-title");
            if (pageTitleEl) {
                pageTitleEl.innerText = `Cotação ${quote.numero}`;
                pageTitleEl.setAttribute("title", `Cotação ${quote.numero}`);
            }
            document.title = `Zé Cotação — Cotação ${quote.numero}`;

            // Render view immediately with quote details (Instant response, zero lag!)
            renderView();

            // Background fetch products catalog for addition only if quote is editable
            const editableStatuses = ['EM_CRIACAO', 'DEVOLVIDA'];
            if (editableStatuses.includes(quote.status)) {
                fetch(`${QUOTE_BASE_URL}/produtos?limit=50`)
                    .then(res => res.json())
                    .then(prodData => {
                        if (prodData && prodData.success) {
                            productsList = prodData.data;
                        }
                    })
                    .catch(errProd => console.warn("Background produtos:", errProd));
            }
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
        const isWaitingApproval = quote && ['AGUARDANDO_GESTOR', 'COM_DIRETOR'].includes(quote.status);
        if (!validityDateStr) return isWaitingApproval ? '⏸️ Validade pausada na aprovação' : 'Sem validade definida';
        const now = new Date();
        const valDate = new Date(validityDateStr);
        const diffMs = valDate.getTime() - now.getTime();
        if (diffMs <= 0) {
            return isWaitingApproval ? '⏸️ Validade pausada (aguardando aprovação)' : '⚠️ Validade expirada';
        }
        const diffMinutes = Math.floor(diffMs / (1000 * 60));
        const diffHours = Math.floor(diffMs / (1000 * 60 * 60));

        const isToday = valDate.toDateString() === now.toDateString();
        const tomorrow = new Date(now.getFullYear(), now.getMonth(), now.getDate() + 1);
        const isTomorrow = valDate.toDateString() === tomorrow.toDateString();
        const timeStr = valDate.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });

        if (isWaitingApproval) {
            return `⏸️ Validade protegida na aprovação (renova ao aprovar)`;
        }

        if (diffMinutes < 60) {
            return `⏳ Vence em ${diffMinutes} min`;
        } else if (isToday) {
            return `⏳ Vence hoje às ${timeStr} (${diffHours}h restantes)`;
        } else if (isTomorrow) {
            return `⏳ Vence amanhã às ${timeStr} (${diffHours}h restantes)`;
        } else {
            const startOfNow = new Date(now.getFullYear(), now.getMonth(), now.getDate()).getTime();
            const startOfVal = new Date(valDate.getFullYear(), valDate.getMonth(), valDate.getDate()).getTime();
            const diffDays = Math.round((startOfVal - startOfNow) / (1000 * 60 * 60 * 24));
            return `⏳ Vence em ${diffDays} dias (${valDate.toLocaleDateString('pt-BR')})`;
        }
    }

    function getQuoteApprovalSummary() {
        const history = Array.isArray(quote?.historico) ? quote.historico : [];
        let type = null; // 'diretoria' | 'gestor' | 'automatica'
        let approverName = '';

        // Inspect events in chronological order so the most recent approval wins
        const ordered = [...history].sort((a, b) => new Date(a.created_at || 0) - new Date(b.created_at || 0));
        let lastRoutedTo = null;

        ordered.forEach(ev => {
            const code = String(ev.evento || '').toUpperCase();
            const role = String(ev.usuario?.papel || ev.papel || '').toLowerCase();
            const uName = ev.usuario ? (ev.usuario.nome || ev.usuario.name || '') : '';

            if (code === 'DEVOLVIDA_AO_REPRESENTANTE' || code === 'COTACAO_DEVOLVIDA') {
                type = null;
                approverName = '';
                lastRoutedTo = null;
            } else if (code === 'ENVIADA_AO_DIRETOR' || code === 'ESCALADA_PARA_DIRETORIA') {
                lastRoutedTo = 'diretoria';
            } else if (code === 'ENVIADA_AO_GESTOR') {
                lastRoutedTo = 'gestor';
            } else if (code === 'APROVADA_DIRETOR' || code === 'APROVADA_ADMINISTRADOR') {
                type = 'diretoria';
                if (uName) approverName = uName;
            } else if (code === 'APROVADA_GESTOR') {
                type = (lastRoutedTo === 'diretoria') ? 'diretoria' : 'gestor';
                if (uName) approverName = uName;
            } else if (code === 'ITEM_APROVADO') {
                if (lastRoutedTo === 'diretoria' || role === 'diretor' || role === 'administrador') {
                    type = 'diretoria';
                } else if (lastRoutedTo === 'gestor' || role === 'gestor') {
                    type = 'gestor';
                }
                if (uName) approverName = uName;
            } else if (code === 'LIBERADA_AUTOMATICAMENTE') {
                type = 'automatica';
                approverName = '';
            }
        });

        // Calculate effective discount % across non-rejected items
        let totalSug = 0;
        let totalProp = 0;
        let hasBelowMin = false;
        if (quote && Array.isArray(quote.itens)) {
            quote.itens.forEach(it => {
                if (it.status_item === 'recusado' || it.nao_encontrado) return;
                const qtd = parseInt(it.qtd || 1);
                const sug = parseFloat(it.preco_unit_sugerido || 0);
                const prop = parseFloat(it.preco_unit_proposto || 0);
                const minEf = getItemEffectiveMin(it);
                if (prop < minEf) hasBelowMin = true;
                totalSug += qtd * Math.max(sug, prop);
                totalProp += qtd * prop;
            });
        }
        const discountPct = (totalSug > 0 && totalProp < totalSug) ? (((totalSug - totalProp) / totalSug) * 100) : 0;

        if (!type) {
            const { repLimit, gestorLimit, hasGestor } = getRepresentativeLimits();
            if (hasBelowMin || discountPct > (repLimit + 0.0001)) {
                type = (!hasGestor || (gestorLimit > 0 && discountPct > (gestorLimit + 0.0001))) ? 'diretoria' : 'gestor';
            } else {
                type = 'automatica';
            }
        }

        return { type, approverName, discountPct };
    }

    function updateStepper(status) {
        // 6 stages: 1=Criada, 2=Enviada, 3=Aprovada, 4=PDF, 5=Pedido, 6=Faturada
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
        } else if (status === 'PDF_GERADO') {
            activeStep = 4;
            isCompletedUpto = 4;
        } else if (['FINALIZADA_COM_PEDIDO', 'AGUARDANDO_PEDIDO'].includes(status)) {
            activeStep = 5;
            isCompletedUpto = 5;
        } else if (status === 'FATURADA') {
            activeStep = 6;
            isCompletedUpto = 6;
        } else {
            activeStep = 1;
            isCompletedUpto = 1;
        }

        for (let i = 1; i <= 6; i++) {
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

    function toggleAuditAccordion(forceState) {
        const body = document.getElementById("audit-accordion-body");
        const arrow = document.getElementById("audit-toggle-arrow");
        const text = document.getElementById("audit-toggle-text");
        if (!body) return;

        let shouldOpen;
        if (typeof forceState === 'boolean') {
            shouldOpen = forceState;
        } else {
            shouldOpen = body.style.display === "none";
        }

        body.style.display = shouldOpen ? "block" : "none";
        if (arrow) arrow.style.transform = shouldOpen ? "rotate(180deg)" : "rotate(0deg)";
        if (text) text.innerText = shouldOpen ? "Recolher ⌃" : "Ver ⌄";

        try {
            sessionStorage.setItem('zecotacao_audit_accordion_open', shouldOpen ? 'true' : 'false');
        } catch(e) {}
    }

    function formatAuditTime(d) {
        if (!d || isNaN(d.getTime())) return '';
        const h = String(d.getHours()).padStart(2, '0');
        const m = String(d.getMinutes()).padStart(2, '0');
        return `${h}:${m}`;
    }

    function formatAuditDate(d) {
        if (!d || isNaN(d.getTime())) return '';
        const day = String(d.getDate()).padStart(2, '0');
        const mo = String(d.getMonth() + 1).padStart(2, '0');
        return `${day}/${mo}`;
    }

    function formatAuditDateTime(d) {
        if (!d || isNaN(d.getTime())) return '';
        return `${formatAuditDate(d)} ${formatAuditTime(d)}`;
    }

    function getEventFriendlyTitle(evento, condicao) {
        const ev = String(evento || '').toUpperCase();
        const map = {
            'CRIADA': 'Cotação criada',
            'SALVA_RASCUNHO': 'Rascunho salvo',
            'EDITADA': 'Cotação editada',
            'SUBMETIDA_APROVACAO': 'Enviada para aprovação',
            'ENVIADA_PARA_APROVACAO': 'Enviada para aprovação',
            'APROVADA': 'Cotação aprovada',
            'APROVADA_AUTOMATICA': 'Aprovada automaticamente',
            'APROVADA_DIRETORIA': 'Aprovada pela diretoria',
            'DEVOLVIDA': 'Devolvida para ajustes',
            'DEVOLVIDA_AO_REPRESENTANTE': 'Devolvida para ajustes',
            'COTACAO_DEVOLVIDA': 'Devolvida para ajustes',
            'ESCALADA_DIRETORIA': 'Escalada para diretoria',
            'RECUSADA': 'Cotação recusada',
            'CANCELADA': 'Cotação cancelada',
            'PERDIDA': 'Marcada como perdida',
            'PDF_GERADO': 'PDF comercial gerado',
            'PROPOSTA_ENVIADA_WHATSAPP': 'Proposta enviada via WhatsApp',
            'PROPOSTA_ENVIADA_EMAIL': 'Proposta enviada por e-mail',
            'PROPOSTA_ENVIADA_LINK': 'Link compartilhado',
            'PROPOSTA_ABERTA_CLIENTE': 'Proposta aberta pelo cliente',
            'PEDIDO_GERADO': 'Pedido gerado no ERP',
            'FATURADA': 'Cotação faturada',
            'VALIDADE_ESTENDIDA_EM_APROVACAO': 'Validade estendida em aprovação'
        };
        if (map[ev]) return map[ev];
        return (evento || 'Evento de Workflow').replace(/_/g, ' ');
    }

    function groupAuditEvents(rawHistory) {
        if (!Array.isArray(rawHistory) || rawHistory.length === 0) return [];

        // 1. Sort chronological (oldest to newest)
        const sorted = rawHistory.slice().sort((a, b) => {
            const da = new Date(a.created_at || 0).getTime();
            const db = new Date(b.created_at || 0).getTime();
            return (da - db) || (a.id - b.id);
        });

        // 2. Group consecutive identical events
        const groups = [];
        let currentGroup = null;

        sorted.forEach(item => {
            const evName = String(item.evento || item.descricao || '').toUpperCase();
            const userId = item.usuario_id || (item.usuario ? item.usuario.id : null);
            const userRole = (item.usuario && item.usuario.papel) ? item.usuario.papel : (item.papel || '');
            const userName = item.usuario ? (item.usuario.nome || item.usuario.name) : (item.papel || 'Sistema');
            const itemDate = new Date(item.created_at || Date.now());

            if (!currentGroup) {
                currentGroup = {
                    evento: evName,
                    userId: userId,
                    userName: userName,
                    userRole: userRole,
                    items: [item],
                    startDate: itemDate,
                    endDate: itemDate
                };
            } else {
                const sameEv = currentGroup.evento === evName;
                const sameUser = (currentGroup.userId && userId && currentGroup.userId === userId) ||
                                 (currentGroup.userName === userName && currentGroup.userRole === userRole);

                if (sameEv && sameUser) {
                    currentGroup.items.push(item);
                    currentGroup.endDate = itemDate;
                } else {
                    groups.push(currentGroup);
                    currentGroup = {
                        evento: evName,
                        userId: userId,
                        userName: userName,
                        userRole: userRole,
                        items: [item],
                        startDate: itemDate,
                        endDate: itemDate
                    };
                }
            }
        });

        if (currentGroup) {
            groups.push(currentGroup);
        }

        // 3. Reverse groups so newest is first
        return groups.reverse();
    }

    function renderAuditTrail() {
        const container = document.getElementById("quote-audit-trail");
        const summaryEl = document.getElementById("audit-summary-details");
        if (!container) return;

        const rawHistory = Array.isArray(quote.historico) ? quote.historico : [];
        const totalCount = rawHistory.length;

        // Restore saved accordion state from session (default: closed)
        try {
            const savedState = sessionStorage.getItem('zecotacao_audit_accordion_open');
            toggleAuditAccordion(savedState === 'true');
        } catch(e) {
            toggleAuditAccordion(false);
        }

        if (totalCount === 0) {
            if (summaryEl) summaryEl.innerText = "0 eventos registrados";
            container.innerHTML = `
                <div style="font-size: 12.5px; color: #94a3b8; text-align: center; padding: 14px; background: #f8fafc; border-radius: 8px; border: 1px dashed #e2e8f0;">
                    Nenhum evento registrado no histórico até o momento.
                </div>
            `;
            return;
        }

        // Sort chronological to identify latest event
        const sortedChronological = rawHistory.slice().sort((a, b) => {
            const da = new Date(a.created_at || 0).getTime();
            const db = new Date(b.created_at || 0).getTime();
            return (da - db) || (a.id - b.id);
        });
        const latest = sortedChronological[sortedChronological.length - 1];
        const latestTitle = getEventFriendlyTitle(latest.evento, latest.condicao);
        const latestDateStr = formatAuditDateTime(new Date(latest.created_at || Date.now()));

        if (summaryEl) {
            summaryEl.innerHTML = `<strong>${totalCount} evento${totalCount > 1 ? 's' : ''}</strong> · Último: ${escapeHtml(latestTitle)} — ${latestDateStr}`;
        }

        // Group consecutive duplicate events and reverse (newest first)
        const grouped = groupAuditEvents(rawHistory);
        container.innerHTML = "";

        grouped.forEach(group => {
            const isGrouped = group.items.length > 1;
            const lastItem = group.items[group.items.length - 1];
            const cleanUser = group.userName || 'Sistema';
            const cleanRole = group.userRole ? `(${group.userRole})` : '';
            const cleanUserInitial = String(cleanUser).replace(/<[^>]*>/g, '').trim().charAt(0).toUpperCase() || 'U';

            let actionText = "";
            if (isGrouped) {
                if (group.evento === 'EDITADA') {
                    actionText = `Editada ${group.items.length}× por ${escapeHtml(cleanUser)}`;
                } else {
                    actionText = `${getEventFriendlyTitle(group.evento)} ${group.items.length}× por ${escapeHtml(cleanUser)}`;
                }
            } else {
                actionText = getEventFriendlyTitle(group.evento, lastItem.condicao);
            }

            // Date Range
            let dateDisplay = "";
            const startStr = formatAuditDateTime(group.startDate);
            const endStr = formatAuditDateTime(group.endDate);
            if (formatAuditDate(group.startDate) === formatAuditDate(group.endDate)) {
                if (formatAuditTime(group.startDate) === formatAuditTime(group.endDate)) {
                    dateDisplay = startStr;
                } else {
                    dateDisplay = `${startStr}–${formatAuditTime(group.endDate)}`;
                }
            } else {
                dateDisplay = `${startStr} – ${endStr}`;
            }

            const details = lastItem.justificativa || lastItem.observacao || lastItem.condicao || '';

            const row = document.createElement("div");
            row.style.cssText = "display: flex; gap: 12px; align-items: flex-start; padding: 10px 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 12.5px;";
            row.innerHTML = `
                <div style="width: 28px; height: 28px; border-radius: 50%; background: #dbeafe; color: #1d4ed8; font-weight: 700; display: flex; align-items: center; justify-content: center; font-size: 12px; flex-shrink: 0;">
                    ${escapeHtml(cleanUserInitial)}
                </div>
                <div style="flex-grow: 1;">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 4px;">
                        <span style="font-weight: 700; color: #0f172a;">
                            ${escapeHtml(cleanUser)} <span style="font-weight: 500; color: #64748b; font-size: 11.5px;">${escapeHtml(cleanRole)}</span>
                            ${isGrouped ? `<span style="background: #e2e8f0; color: #334155; font-size: 10.5px; font-weight: 700; padding: 1px 7px; border-radius: 999px; margin-left: 6px;">${group.items.length}× repetido</span>` : ''}
                        </span>
                        <span style="color: #64748b; font-size: 11px; font-weight: 500;">📅 ${dateDisplay}</span>
                    </div>
                    <div style="color: #2563eb; font-weight: 600; margin-top: 2px;">${actionText}</div>
                    ${details ? `<div style="color: #475569; margin-top: 4px; background: #ffffff; padding: 6px 10px; border-radius: 6px; border: 1px solid #e2e8f0; font-size: 12px;">${escapeHtml(details)}</div>` : ''}
                </div>
            `;
            container.appendChild(row);
        });
    }

    function renderAttentionBanners() {
        const container = document.getElementById("quote-attention-banner");
        if (!container || !quote) return;
        container.innerHTML = "";
        let hasAlert = false;

        // 1. Devolução com instruções do gestor
        if (quote.status === 'DEVOLVIDA') {
            const rawHist = Array.isArray(quote.historico) ? quote.historico : [];
            const returnEv = rawHist.slice().reverse().find(h => {
                const ev = String(h.evento || '').toUpperCase();
                return ev.includes('DEVOLVID') || (h.condicao && h.condicao.toLowerCase().includes('devolvi'));
            });
            const instruction = (returnEv && (returnEv.condicao || returnEv.justificativa || returnEv.observacao)) 
                ? (returnEv.condicao || returnEv.justificativa || returnEv.observacao)
                : (quote.observacao_interna || 'O gestor devolveu a cotação solicitando revisão dos descontos ou condições comerciais.');

            const bannerDevolvida = document.createElement("div");
            bannerDevolvida.style.cssText = "background: #fffbeb; border: 1.5px solid #f59e0b; border-radius: 12px; padding: 14px 18px; color: #92400e; box-shadow: 0 1px 3px rgba(0,0,0,0.05);";
            bannerDevolvida.innerHTML = `
                <div style="display: flex; align-items: flex-start; gap: 12px;">
                    <span style="font-size: 24px; line-height: 1; flex-shrink: 0;">⚠️</span>
                    <div style="flex: 1;">
                        <div style="font-weight: 800; font-size: 14px; margin-bottom: 4px; color: #92400e;">
                            Cotação Devolvida para Ajustes pelo Gestor
                        </div>
                        <div style="font-size: 13px; color: #78350f; line-height: 1.45;">
                            <strong>Instruções do Gestor:</strong> ${escapeHtml(instruction)}
                        </div>
                    </div>
                </div>
            `;
            container.appendChild(bannerDevolvida);
            hasAlert = true;
        }

        // 2. Itens recusados com motivo
        const rejectedItems = [];
        (quote.itens || []).forEach(item => {
            const isRejected = (item.decisao_diretoria === 'RECUSADO') || (item.status === 'RECUSADO');
            if (isRejected) {
                const justif = (quote.justificativas || []).find(j => j.cotacao_item_id === item.id);
                const desc = item.produto ? item.produto.descricao : (item.descricao_original || 'Produto');
                const reason = item.motivo_recusa || (justif ? justif.texto : null) || 'Recusado na aprovação';
                rejectedItems.push({ desc, reason });
            }
        });

        if (rejectedItems.length > 0) {
            const bannerRecusados = document.createElement("div");
            bannerRecusados.style.cssText = "background: #fef2f2; border: 1.5px solid #ef4444; border-radius: 12px; padding: 14px 18px; color: #991b1b; box-shadow: 0 1px 3px rgba(0,0,0,0.05);";
            const itemsListHtml = rejectedItems.map(it => `
                <li style="margin-top: 4px;">
                    <strong>${escapeHtml(it.desc)}:</strong> <span style="color: #7f1d1d;">${escapeHtml(it.reason)}</span>
                </li>
            `).join('');

            bannerRecusados.innerHTML = `
                <div style="display: flex; align-items: flex-start; gap: 12px;">
                    <span style="font-size: 24px; line-height: 1; flex-shrink: 0;">❌</span>
                    <div style="flex: 1;">
                        <div style="font-weight: 800; font-size: 14px; margin-bottom: 4px; color: #991b1b;">
                            Itens Recusados na Análise Comercial
                        </div>
                        <div style="font-size: 13px; color: #7f1d1d; line-height: 1.45;">
                            Alguns itens desta proposta foram recusados:
                            <ul style="margin: 6px 0 0 18px; padding: 0;">
                                ${itemsListHtml}
                            </ul>
                        </div>
                    </div>
                </div>
            `;
            container.appendChild(bannerRecusados);
            hasAlert = true;
        }

        // 3. Alerta de margem negativa
        let hasNegativeMargin = false;
        let countNegative = 0;
        (quote.itens || []).forEach(item => {
            if (item.tem_margem_negativa) {
                hasNegativeMargin = true;
                countNegative++;
            }
        });

        if (hasNegativeMargin) {
            const bannerMargem = document.createElement("div");
            bannerMargem.style.cssText = "background: #fef2f2; border: 1.5px solid #dc2626; border-radius: 12px; padding: 14px 18px; color: #991b1b; box-shadow: 0 1px 3px rgba(0,0,0,0.05);";
            bannerMargem.innerHTML = `
                <div style="display: flex; align-items: flex-start; gap: 12px;">
                    <span style="font-size: 24px; line-height: 1; flex-shrink: 0;">🚨</span>
                    <div style="flex: 1;">
                        <div style="font-weight: 800; font-size: 14px; margin-bottom: 4px; color: #991b1b;">
                            Alerta Crítico: Margem Negativa (${countNegative} item(ns) abaixo do custo)
                        </div>
                        <div style="font-size: 13px; color: #7f1d1d; line-height: 1.45;">
                            Existem itens com preço proposto inferior ao custo cadastrado no ERP Sankhya. Propostas com margem negativa exigem justificativa e alçada especial da diretoria.
                        </div>
                    </div>
                </div>
            `;
            container.appendChild(bannerMargem);
            hasAlert = true;
        }

        container.style.display = hasAlert ? "flex" : "none";
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
        } else if (action === 'send_whatsapp') {
            openSendQuoteModal();
        } else if (action === 'share_pdf') {
            shareViaWebShare();
        } else if (action === 'download_pdf') {
            downloadPdf();
        } else if (action === 'release_billing') {
            openReleaseModal();
        }
    }

    function handlePrimaryAction() {
        if (!quote) return;
        if (['EM_CRIACAO', 'DEVOLVIDA'].includes(quote.status)) {
            submitQuote();
        } else if (['APROVADA', 'PDF_GERADO'].includes(quote.status)) {
            openSendQuoteModal();
        } else if (quote.status === 'FINALIZADA_COM_PEDIDO') {
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
                    bannerText.innerHTML = "<strong>Cotação em análise de alçada:</strong> Aguardando aprovação do Gestor de Equipe. A validade comercial está protegida durante a análise.";
                } else if (quote.status === 'COM_DIRETOR') {
                    bannerText.innerHTML = "<strong>Cotação em análise com a Diretoria:</strong> Aguardando decisão superior. A validade comercial está protegida durante a análise.";
                } else if (quote.status === 'APROVADA') {
                    const appInfo = getQuoteApprovalSummary();
                    const discStr = appInfo.discountPct > 0.005 ? ` com <strong>${appInfo.discountPct.toFixed(1).replace('.', ',')}% de desconto</strong>` : '';
                    const byStr = appInfo.approverName ? ` por <strong>${escapeHtml(appInfo.approverName)}</strong>` : '';
                    if (appInfo.type === 'diretoria') {
                        bannerText.innerHTML = `<strong>Aprovada pela Diretoria:</strong> Cotação revisada e aprovada pela Diretoria${byStr}${discStr}. Clique no botão <strong>'Enviar ao Cliente'</strong> no rodapé para enviar via WhatsApp (o PDF é gerado automaticamente).`;
                    } else if (appInfo.type === 'gestor') {
                        bannerText.innerHTML = `<strong>Aprovada pelo Gestor:</strong> Cotação revisada e aprovada pelo Gestor${byStr}${discStr}. Clique no botão <strong>'Enviar ao Cliente'</strong> no rodapé para enviar via WhatsApp (o PDF é gerado automaticamente).`;
                    } else {
                        bannerText.innerHTML = `<strong>Aprovada automaticamente:</strong> Cotação ${appInfo.discountPct > 0.005 ? `com desconto de ${appInfo.discountPct.toFixed(1).replace('.', ',')}% dentro da sua alçada` : 'sem desconto'}, não precisa de gestor. Clique no botão <strong>'Enviar ao Cliente'</strong> no rodapé para enviar via WhatsApp (o PDF é gerado automaticamente).`;
                    }
                } else if (quote.status === 'PDF_GERADO') {
                    bannerText.innerHTML = "<strong>PDF Emitido:</strong> Documento gerado com sucesso. Clique no botão <strong>'Enviar ao Cliente'</strong> no rodapé para enviar a proposta via WhatsApp.";
                } else if (quote.status === 'FINALIZADA_COM_PEDIDO') {
                    bannerText.innerHTML = "<strong>Cotação Finalizada:</strong> Pedido externo já registrado para conferência e faturamento.";
                } else if (quote.status === 'FATURADA') {
                    bannerText.innerHTML = "<strong>Cotação Faturada:</strong> Processo comercial e faturamento concluídos.";
                } else if (quote.status === 'PERDIDA') {
                    bannerText.innerHTML = "<strong>Cotação Perdida:</strong> Negociação encerrada sem fechamento de pedido.";
                } else if (quote.status === 'EXPIRADA') {
                    bannerText.innerHTML = "<strong>Cotação Expirada:</strong> Prazo de validade comercial ultrapassado.";
                } else {
                    bannerText.innerHTML = "<strong>Modo Somente Leitura:</strong> Cotação com status " + escapeHtml(quote.status.replace(/_/g, ' ')) + ".";
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
            } else if (['APROVADA', 'PDF_GERADO'].includes(quote.status)) {
                primaryBtn.style.background = "#25d366";
                primaryBtn.style.color = "#ffffff";
                primaryBtn.style.border = "none";
                primaryBtn.style.boxShadow = "0 4px 14px rgba(37, 211, 102, 0.35)";
                primaryBtn.disabled = false;
                primaryBtn.style.cursor = "pointer";
                primaryBtn.innerHTML = `
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                    <span>Enviar ao Cliente</span>
                `;
            } else if (quote.status === 'FINALIZADA_COM_PEDIDO') {
                primaryBtn.style.background = "#fef3c7";
                primaryBtn.style.color = "#92400e";
                primaryBtn.disabled = true;
                primaryBtn.style.cursor = "default";
                primaryBtn.innerHTML = `
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    <span>Aguardando conferência do faturamento</span>
                `;
            } else if (quote.status === 'FATURADA') {
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

        // Show Sticky Footer Bar and update offset
        const stickyBar = document.getElementById("sticky-footer-bar");
        if (stickyBar) {
            stickyBar.style.display = "block";
            updateStickyFooterOffset();
        }

        // Update options menu "⋯" dropdown items
        const optionsWrapper = document.getElementById("options-menu-wrapper");
        if (optionsWrapper) {
            const isClosed = ['FATURADA', 'PERDIDA', 'EXPIRADA'].includes(quote.status);
            optionsWrapper.style.display = isClosed ? "none" : "block";

            const menuSendWa = document.getElementById("menu-send-whatsapp");
            const menuSharePdf = document.getElementById("menu-share-pdf");
            const menuDownloadPdf = document.getElementById("menu-download-pdf");
            const menuReleaseBilling = document.getElementById("menu-release-billing");
            const menuSaveDraft = document.getElementById("menu-save-draft");
            const menuMarkLost = document.getElementById("menu-mark-lost");

            const isApprovedOrSent = ['APROVADA', 'PDF_GERADO', 'FINALIZADA_COM_PEDIDO'].includes(quote.status);
            if (menuSendWa) menuSendWa.style.display = isApprovedOrSent ? "flex" : "none";
            if (menuSharePdf) menuSharePdf.style.display = isApprovedOrSent ? "flex" : "none";
            if (menuDownloadPdf) menuDownloadPdf.style.display = isApprovedOrSent ? "flex" : "none";
            if (menuReleaseBilling) menuReleaseBilling.style.display = (quote.status === 'PDF_GERADO') ? "flex" : "none";
            if (menuSaveDraft) menuSaveDraft.style.display = ['EM_CRIACAO', 'DEVOLVIDA'].includes(quote.status) ? "flex" : "none";
            if (menuMarkLost) menuMarkLost.style.display = ['EM_CRIACAO', 'DEVOLVIDA'].includes(quote.status) ? "flex" : "none";
        }

        // Update proposal delivery tracking banner
        updateDeliveryBanner();

        // Bind Priority Attention Alerts (Manager instructions, rejected items, negative margin)
        renderAttentionBanners();

        // Bind Items List
        renderItems();

        // Bind Live Audit Trail
        renderAuditTrail();
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

    function getRepresentativeLimits() {
        const repLimit = parseFloat(quote?.representante?.limite_desconto_percentual || 0);
        const gestor = quote?.representante?.equipe?.gestor;
        const hasGestor = Boolean(gestor && gestor.ativo);
        const gestorLimit = hasGestor ? parseFloat(gestor.limite_desconto_percentual || 0) : 0;
        return { repLimit, gestorLimit, hasGestor, gestorNome: gestor?.nome };
    }

    let replacingUnfoundItemId = null;

    function getItemPriceTagHtml(item) {
        if (!item) return '';
        if (item.nao_encontrado) {
            return `<span class="badge-price-tag tag-below-min" style="background:#ffe4e6; color:#be123c; border:1px solid #fecdd3; font-weight:700;">⚠️ Aguardando inclusão manual</span>`;
        }
        if (item.status_item === 'recusado') {
            return `<span class="badge-price-tag tag-below-min" style="background:#fee2e2; color:#991b1b; border:1px solid #fca5a5; font-weight:700;">✕ Item recusado na avaliação</span>`;
        }

        const propPrice = parseFloat(item.preco_unit_proposto || 0);
        const sugPrice = parseFloat(item.preco_unit_sugerido || 0);
        const minEf = getItemEffectiveMin(item);
        const isBelowMin = propPrice < minEf;
        const { repLimit, gestorLimit, hasGestor } = getRepresentativeLimits();
        const isQuoteApprovedOrAfter = ['APROVADA', 'PDF_GERADO', 'AGUARDANDO_PEDIDO', 'FINALIZADA_COM_PEDIDO', 'FATURADA'].includes(quote?.status);
        const isItemApproved = (item.status_item === 'aprovado') || isQuoteApprovedOrAfter;

        if (isItemApproved) {
            const appInfo = getQuoteApprovalSummary();
            const originLabel = appInfo.type === 'diretoria'
                ? 'Aprovado pela Diretoria'
                : (appInfo.type === 'gestor' ? 'Aprovado pelo Gestor' : 'Liberado na sua alçada');

            if (Math.abs(propPrice - sugPrice) < 0.005) {
                return `<span class="badge-price-tag tag-table">Preço de tabela</span>`;
            }
            if (propPrice < sugPrice) {
                const discount = sugPrice > 0 ? (((sugPrice - propPrice) / sugPrice) * 100) : 0;
                const perc = discount.toFixed(1).replace('.', ',');
                return `<span class="badge-price-tag tag-discount" style="background:#ecfdf5; color:#047857; border:1px solid #a7f3d0; font-weight:600;">✓ −${perc}% · ${originLabel}</span>`;
            }
            const percAbove = sugPrice > 0 ? (((propPrice - sugPrice) / sugPrice) * 100).toFixed(1).replace('.', ',') : '0';
            return `<span class="badge-price-tag tag-above">+${percAbove}%</span>`;
        }

        const isPendingApprovalQueue = ['AGUARDANDO_GESTOR', 'COM_DIRETOR'].includes(quote?.status);
        const pendingDestLabel = quote?.status === 'COM_DIRETOR' ? 'Em análise na Diretoria' : 'Em análise no Gestor';

        if (isBelowMin) {
            if (isPendingApprovalQueue) {
                return `<span class="badge-price-tag tag-below-min" style="background:#fffbeb; color:#b45309; border:1px solid #fcd34d; font-weight:700;">⏳ Abaixo do mínimo · ${pendingDestLabel}</span>`;
            }
            const dest = (!hasGestor || (gestorLimit > 0 && sugPrice > 0 && (((sugPrice - propPrice) / sugPrice) * 100) > gestorLimit))
                ? 'a diretoria'
                : 'o gestor';
            return `<span class="badge-price-tag tag-below-min" style="background:#fee2e2; color:#b91c1c; border:1px solid #fca5a5; font-weight:700;">⚠️ Abaixo do mínimo · Vai para ${dest}</span>`;
        }

        if (Math.abs(propPrice - sugPrice) < 0.005) {
            return `<span class="badge-price-tag tag-table">Preço de tabela</span>`;
        }

        if (propPrice < sugPrice) {
            const discount = sugPrice > 0 ? (((sugPrice - propPrice) / sugPrice) * 100) : 0;
            const perc = discount.toFixed(1).replace('.', ',');

            if (discount <= (repLimit + 0.0001)) {
                return `<span class="badge-price-tag tag-discount" style="background:#ecfdf5; color:#047857; border:1px solid #a7f3d0; font-weight:600;">−${perc}% · Liberado na sua alçada</span>`;
            } else if (isPendingApprovalQueue) {
                return `<span class="badge-price-tag tag-below-min" style="background:#fffbeb; color:#b45309; border:1px solid #fcd34d; font-weight:600;">−${perc}% · ${pendingDestLabel}</span>`;
            } else if (hasGestor && discount <= (gestorLimit + 0.0001)) {
                return `<span class="badge-price-tag tag-below-min" style="background:#fffbeb; color:#b45309; border:1px solid #fcd34d; font-weight:600;">−${perc}% · Acima da sua alçada, vai para o gestor</span>`;
            } else {
                return `<span class="badge-price-tag tag-below-min" style="background:#fef2f2; color:#b91c1c; border:1px solid #fca5a5; font-weight:600;">−${perc}% · Acima da sua alçada, vai para a diretoria</span>`;
            }
        } else {
            const perc = sugPrice > 0 ? (((propPrice - sugPrice) / sugPrice) * 100).toFixed(1).replace('.', ',') : '0';
            return `<span class="badge-price-tag tag-above">+${perc}%</span>`;
        }
    }

    function updateJustificationPanelVisibility() {
        const justPanel = document.getElementById("justification-panel");
        if (!justPanel) return;

        if (isEditingLocked) {
            justPanel.style.display = "none";
            return;
        }

        const { repLimit } = getRepresentativeLimits();
        let hasItemBelowMin = false;
        let maxItemDiscount = 0;
        let totalSug = 0;
        let totalProp = 0;

        if (quote && quote.itens) {
            quote.itens.forEach(i => {
                if (i.status_item === 'recusado' || i.nao_encontrado) return;
                const minEf = getItemEffectiveMin(i);
                const prop = parseFloat(i.preco_unit_proposto || 0);
                const sug = parseFloat(i.preco_unit_sugerido || 0);
                const qtd = parseInt(i.qtd || 1);

                if (prop < minEf && i.status_item !== 'aprovado') {
                    hasItemBelowMin = true;
                }

                if (sug > 0 && prop < sug && i.status_item !== 'aprovado') {
                    const itemDisc = ((sug - prop) / sug) * 100;
                    if (itemDisc > maxItemDiscount) {
                        maxItemDiscount = itemDisc;
                    }
                }

                totalSug += qtd * Math.max(sug, prop);
                totalProp += qtd * prop;
            });
        }

        const quoteDiscount = totalSug > 0 && totalProp < totalSug
            ? ((totalSug - totalProp) / totalSug) * 100
            : 0;

        const effectiveDiscount = Math.max(maxItemDiscount, quoteDiscount);
        const isAboveRepLimit = effectiveDiscount > (repLimit + 0.0001);
        const requiresApproval = hasItemBelowMin || isAboveRepLimit;

        if (requiresApproval) {
            justPanel.style.display = "block";
            const panelTitle = document.getElementById("just-panel-title");
            const panelDesc = document.getElementById("just-panel-desc");
            if (panelTitle && panelDesc) {
                if (hasItemBelowMin) {
                    panelTitle.innerText = "Justificativa Obrigatória — Itens Abaixo do Mínimo";
                    panelDesc.innerText = "Existem produtos abaixo do preço mínimo configurado. É obrigatório registrar uma justificativa e anexar comprovantes para enviar a cotação à aprovação.";
                } else {
                    panelTitle.innerText = "Justificativa Obrigatória — Desconto Acima da Alçada";
                    panelDesc.innerText = `O desconto comercial aplicado (${effectiveDiscount.toFixed(1).replace('.', ',')}%) excede a sua alçada permitida (${repLimit.toFixed(1).replace('.', ',')}%). É obrigatório registrar uma justificativa e anexar comprovantes para enviar a cotação à aprovação.`;
                }
            }
        } else {
            justPanel.style.display = "none";
        }
    }

    function renderUnfoundItemsPanel() {
        const panel = document.getElementById("ai-unfound-items-panel");
        const listEl = document.getElementById("ai-unfound-items-list");
        const countBadge = document.getElementById("ai-unfound-count-badge");
        if (!panel || !listEl) return;

        const unfoundItems = (quote && Array.isArray(quote.itens))
            ? quote.itens.filter(i => Boolean(i.nao_encontrado) && i.status_item !== 'recusado')
            : [];

        if (unfoundItems.length === 0) {
            panel.style.display = "none";
            listEl.innerHTML = "";
            if (replacingUnfoundItemId) {
                cancelManualInclusionReplacement();
            }
            return;
        }

        panel.style.display = "block";
        if (countBadge) {
            countBadge.innerText = `${unfoundItems.length} ${unfoundItems.length === 1 ? 'item' : 'itens'}`;
        }

        listEl.innerHTML = "";
        unfoundItems.forEach(item => {
            const rawDesc = item.descricao_original || (item.produto ? item.produto.descricao : 'Item solicitado pelo cliente');
            const obsIa = item.observacao_ia || 'Item não identificado automaticamente no catálogo pela IA';
            const un = (item.produto && item.produto.unidade) ? item.produto.unidade : 'UN';
            const isBeingReplaced = (replacingUnfoundItemId === item.id);

            const card = document.createElement("div");
            card.style.cssText = `background: #ffffff; border: 1.5px solid ${isBeingReplaced ? '#2563eb' : '#fecdd3'}; border-radius: 10px; padding: 12px 14px; display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; box-shadow: 0 1px 2px rgba(0,0,0,0.03);`;
            card.innerHTML = `
                <div style="flex: 1; min-width: 200px;">
                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                        <span style="font-weight: 800; font-size: 14px; color: #881337;">"${escapeHtml(rawDesc)}"</span>
                        <span style="background: #ffe4e6; color: #be123c; border: 1px solid #fda4af; font-size: 11.5px; font-weight: 700; padding: 2px 8px; border-radius: 6px;">
                            Qtd solicitada: ${item.qtd} ${escapeHtml(un)}
                        </span>
                    </div>
                    <div style="font-size: 12px; color: #9f1239; margin-top: 4px;">
                        <strong>Observação da IA:</strong> ${escapeHtml(obsIa)}
                    </div>
                </div>
                ${!isEditingLocked ? `
                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                        <button type="button" onclick="startManualInclusionForUnfoundItem(${item.id})" style="background: #2563eb; color: #ffffff; border: none; border-radius: 8px; padding: 8px 13px; font-size: 12.5px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 2px 5px rgba(37, 99, 235, 0.25);">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
                            <span>Incluir Manualmente</span>
                        </button>
                        <button type="button" onclick="deleteItem(${item.id})" style="background: #ffffff; color: #be123c; border: 1px solid #fda4af; border-radius: 8px; padding: 7px 11px; font-size: 12px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 4px;" title="Dispensar/remover item da cotação">
                            <span>Dispensar</span>
                        </button>
                    </div>
                ` : ''}
            `;
            listEl.appendChild(card);
        });
    }

    function startManualInclusionForUnfoundItem(itemId) {
        if (isEditingLocked) {
            showToast("Esta cotação está bloqueada para edições.", 'warning');
            return;
        }
        const item = quote.itens.find(i => i.id === itemId);
        if (!item) return;

        replacingUnfoundItemId = item.id;
        const rawDesc = item.descricao_original || (item.produto ? item.produto.descricao : '');

        // Update banner inside #add-item-form-container
        const banner = document.getElementById("replacing-unfound-banner");
        const descEl = document.getElementById("replacing-unfound-desc");
        const qtyEl = document.getElementById("replacing-unfound-qty");
        const formContainer = document.getElementById("add-item-form-container");
        const btnAdd = document.getElementById("btn-add-new-item");

        if (banner && descEl && qtyEl) {
            banner.style.display = "flex";
            descEl.innerText = `"${rawDesc}"`;
            qtyEl.innerText = `(Qtd solicitada: ${item.qtd})`;
        }
        if (formContainer) {
            formContainer.style.borderColor = "#2563eb";
            formContainer.style.boxShadow = "0 0 0 3px rgba(37, 99, 235, 0.15)";
        }
        if (btnAdd) {
            btnAdd.innerText = "✓ Incluir e Substituir";
            btnAdd.style.background = "#2563eb";
            btnAdd.style.color = "#ffffff";
        }

        // Pre-fill requested quantity
        const qtdInput = document.getElementById("new-item-qtd");
        if (qtdInput) {
            qtdInput.value = item.qtd || 1;
        }

        // Extract a helpful initial search term from the client's requested description
        const cleanTerm = String(rawDesc)
            .replace(/^\d+\s*(un|und|unid|unidades|cx|caixas|pc|pç|pct|kg|l|lt|sacos|sc|frascos|galao|gl)\b\s*(de\s+)?/i, '')
            .trim();
        const searchWords = cleanTerm.split(/\s+/).filter(w => w.length >= 2).slice(0, 3).join(' ');

        const searchInput = document.getElementById("add-product-search-input");
        if (searchInput) {
            document.getElementById("selected-product-id").value = "";
            searchInput.value = searchWords || cleanTerm;
            document.getElementById("clear-selected-prod-btn").style.display = searchInput.value ? "block" : "none";
        }

        renderUnfoundItemsPanel();

        if (formContainer) {
            formContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        setTimeout(() => {
            if (searchInput) {
                searchInput.focus();
                if (searchInput.value.trim().length >= 1) {
                    fetchSearchProductsForAdd(searchInput.value.trim());
                }
            }
        }, 250);
    }

    function cancelManualInclusionReplacement() {
        replacingUnfoundItemId = null;
        const banner = document.getElementById("replacing-unfound-banner");
        const formContainer = document.getElementById("add-item-form-container");
        const btnAdd = document.getElementById("btn-add-new-item");
        if (banner) banner.style.display = "none";
        if (formContainer) {
            formContainer.style.borderColor = "var(--color-border)";
            formContainer.style.boxShadow = "none";
        }
        if (btnAdd) {
            btnAdd.innerText = "+ Adicionar";
            btnAdd.style.background = "";
            btnAdd.style.color = "";
        }
        const panel = document.getElementById("ai-unfound-items-panel");
        if (panel && panel.style.display !== "none") {
            renderUnfoundItemsPanel();
        }
    }

    // Render Compact 3-Line Cards (Point 1)
    function renderItems() {
        renderUnfoundItemsPanel();

        const container = document.getElementById("items-cards-container");
        if (!container) return;
        container.innerHTML = "";
        
        let hasItemBelowMin = false;
        let countAll = quote.itens.length;
        let countAttention = 0;
        let countApproved = 0;
        let countRejected = 0;

        quote.itens.forEach(item => {
            const minEf = getItemEffectiveMin(item);
            const isBelowMin = parseFloat(item.preco_unit_proposto || 0) < minEf;
            const isRejected = item.status_item === 'recusado';
            const isUnfound = Boolean(item.nao_encontrado);

            if (isRejected) {
                countRejected++;
            } else if ((isBelowMin || isUnfound) && item.status_item !== 'aprovado') {
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
        const elRej = document.getElementById("count-rejected");
        if (elAll) elAll.innerText = countAll;
        if (elAllBadge) elAllBadge.innerText = `${countAll} itens`;
        if (elAtt) elAtt.innerText = countAttention;
        if (elApp) elApp.innerText = countApproved;
        if (elRej) elRej.innerText = countRejected;

        // Filter items for display
        const filtered = quote.itens.filter(item => {
            const prodDesc = item.produto ? item.produto.descricao : (item.descricao_original || '');
            const prodCode = item.produto ? item.produto.codigo_sankhya : '';

            if (currentSearchQuery) {
                const q = currentSearchQuery;
                const code = String(prodCode).toLowerCase();
                const desc = String(prodDesc).toLowerCase();
                if (!code.includes(q) && !desc.includes(q)) return false;
            }

            const minEf = getItemEffectiveMin(item);
            const isBelowMin = parseFloat(item.preco_unit_proposto || 0) < minEf;
            const isRejected = item.status_item === 'recusado';
            const isUnfound = Boolean(item.nao_encontrado);

            if (currentFilterTab === 'attention') {
                return (isBelowMin || isUnfound) && item.status_item !== 'aprovado' && !isRejected;
            }
            if (currentFilterTab === 'approved') {
                return (!isBelowMin && !isUnfound || item.status_item === 'aprovado') && !isRejected;
            }
            if (currentFilterTab === 'rejected') {
                return isRejected;
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
                try {
                    const minEf = getItemEffectiveMin(item);
                    const isInconsistent = isItemInconsistent(item);
                    const propPrice = parseFloat(item.preco_unit_proposto || 0);
                    const sugPrice = parseFloat(item.preco_unit_sugerido || 0);
                    const isBelowMin = propPrice < minEf;
                    const isRejected = item.status_item === 'recusado';
                    const isApproved = item.status_item === 'aprovado';
                    const isItemLocked = isEditingLocked || isRejected;
                    const rowClass = isRejected ? "recusado" : (isApproved ? "aprovado" : "");

                    // Semantic Price Tag Badge for Line 3
                    const priceTagHtml = getItemPriceTagHtml(item);

                    // Check justification/rejection reason
                    const justifItem = (quote.justificativas && quote.justificativas.length) 
                        ? quote.justificativas.find(j => j.cotacao_item_id === item.id) 
                        : null;
                    const motivoTexto = justifItem ? justifItem.texto : '';

                    const prodDesc = item.produto ? item.produto.descricao : (item.descricao_original || 'Produto sem cadastro');
                    const prodCode = item.produto ? item.produto.codigo_sankhya : 'S/C';
                    const prodUn = item.produto ? item.produto.unidade : 'UN';
                    const isUnfound = Boolean(item.nao_encontrado);

                    container.innerHTML += `
                        <div class="compact-item-card ${rowClass} ${isUnfound || (isBelowMin && !isRejected && !isApproved) ? 'below-min-card' : ''}" id="card-item-${item.id}">
                            <!-- LINHA 1: descrição em destaque + código + unidade + etiqueta status + lixeira -->
                            <div class="item-line-1">
                                <div class="item-desc-wrap" style="display:flex; align-items:center; flex-wrap:wrap; gap:6px;">
                                    <span class="item-desc" style="font-weight:700; ${isRejected ? 'color:#991b1b;' : (isUnfound ? 'color:#be123c;' : '')}">${escapeHtml(isUnfound ? (item.descricao_original || prodDesc) : prodDesc)}</span>
                                    <span class="item-code-un" style="color:var(--color-text-muted); font-size:12px;">${isUnfound ? 'Sem código no catálogo' : '#' + escapeHtml(prodCode)} · ${escapeHtml(prodUn)}</span>
                                    ${item.mostrar_selo_campanha && item.campanha_id ? '<span class="badge-campanha" style="margin-left:4px;">Campanha</span>' : ''}
                                    ${isInconsistent ? '<span class="badge" style="background:#fef3c7; color:#92400e; font-size:10.5px; padding:2px 5px; border-radius:4px; border:1px solid #fcd34d;" title="Mínimo cadastral divergente">⚠️ Mín > Sugerido</span>' : ''}
                                    ${isUnfound ? `
                                        <span class="badge" style="background:#ffe4e6; color:#9f1239; font-size:11px; font-weight:700; padding:2px 8px; border-radius:6px; border:1px solid #fecdd3; display:inline-flex; align-items:center; gap:4px;" title="${escapeHtml(item.observacao_ia || 'Item não encontrado no catálogo pelo assistente IA')}">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                            Não encontrado pela IA
                                        </span>
                                    ` : ''}
                                    ${isRejected ? `
                                        <span class="badge-status recusado" style="background:#fee2e2; color:#b91c1c; border:1px solid #fca5a5; font-size:11px; font-weight:700; padding:2px 8px; border-radius:6px; display:inline-flex; align-items:center; gap:4px;">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                            Recusado
                                        </span>
                                    ` : (isApproved ? `
                                        <span class="badge-status aprovado" style="background:#ecfdf5; color:#047857; border:1px solid #a7f3d0; font-size:11px; font-weight:700; padding:2px 8px; border-radius:6px; display:inline-flex; align-items:center; gap:4px;">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                            Aprovado
                                        </span>
                                    ` : '')}
                                    ${isUnfound ? `
                                        <div style="width:100%; margin-top:8px; padding:8px 12px; background:#fff1f2; border:1px dashed #fda4af; border-radius:8px; font-size:12px; color:#9f1239;">
                                            <strong>⚠️ Item solicitado pelo cliente:</strong> "${escapeHtml(item.descricao_original || prodDesc)}"
                                            ${item.observacao_ia ? `<div style="font-size:11px; color:#be123c; margin-top:2px;">Obs IA: ${escapeHtml(item.observacao_ia)}</div>` : ''}
                                        </div>
                                    ` : (item.descricao_original ? `
                                        <div style="width:100%; font-size:11.5px; color:#64748b; margin-top:2px;">
                                            <span style="font-weight:600; color:#475569;">Pedido original:</span> "${escapeHtml(item.descricao_original)}"
                                        </div>
                                    ` : '')}
                                </div>
                                <div>
                                    ${!isItemLocked ? `
                                        <button type="button" class="btn-remove-item" onclick="deleteItem(${item.id})" title="Remover item">
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                                        </button>
                                    ` : ''}
                                </div>
                            </div>

                            <!-- LINHA 2: quantidade (–/+) à esquerda e preço proposto (ou ação de inclusão manual) à direita -->
                            <div class="item-line-2">
                                ${!isItemLocked ? `
                                    <div class="qty-stepper">
                                        <button type="button" class="qty-btn" onclick="stepQty(${item.id}, -1)">–</button>
                                        <input type="number" class="qty-input" value="${item.qtd}" min="1" inputmode="numeric"
                                            oninput="updateItemCalculations(${item.id}, this.value, null)">
                                        <button type="button" class="qty-btn" onclick="stepQty(${item.id}, 1)">+</button>
                                    </div>
                                ` : `
                                    <div style="display:flex; align-items:center; gap:6px;">
                                        <span style="font-size:11px; color:${isRejected ? '#991b1b' : '#64748b'}; font-weight:700; text-transform:uppercase;">Qtd:</span>
                                        <span style="font-weight:700; font-size:13.5px; color:${isRejected ? '#991b1b' : '#1e293b'}; padding:4px 10px; background:${isRejected ? '#fee2e2' : '#f1f5f9'}; border:1px solid ${isRejected ? '#fca5a5' : '#e2e8f0'}; border-radius:6px;">${item.qtd} un</span>
                                    </div>
                                `}

                                ${isUnfound ? (
                                    !isItemLocked ? `
                                        <button type="button" onclick="startManualInclusionForUnfoundItem(${item.id})" style="background:#2563eb; color:#ffffff; border:none; border-radius:8px; padding:7px 12px; font-size:12px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:6px; box-shadow:0 2px 4px rgba(37,99,235,0.2);">
                                            🔍 + Incluir Manualmente no Catálogo
                                        </button>
                                    ` : `<span style="font-size:12px; color:#be123c; font-weight:700;">Não cotado</span>`
                                ) : (!isItemLocked ? `
                                    <div class="item-price-proposto-wrap">
                                        <span class="price-currency">R$</span>
                                        <input type="number" class="price-input ${isBelowMin ? 'price-below-min' : ''}" 
                                            value="${propPrice.toFixed(2)}" step="0.01" min="0.01" inputmode="decimal"
                                            oninput="updateItemCalculations(${item.id}, null, this.value)">
                                        ${isBelowMin ? `
                                            <button type="button" class="btn btn-outline" style="padding:4px 8px; font-size:11px; font-weight:700; color:#dc2626; border-color:#fca5a5; background:#fff1f2; border-radius:6px;" onclick="resetToMin(${item.id}, ${minEf})">Mín</button>
                                        ` : ''}
                                    </div>
                                ` : `
                                    <div style="display:flex; align-items:center; gap:6px;">
                                        <span style="font-size:11px; color:${isRejected ? '#991b1b' : '#64748b'}; font-weight:700; text-transform:uppercase;">Preço:</span>
                                        <span style="font-weight:700; font-size:14px; color:${isRejected ? '#991b1b; text-decoration:line-through;' : '#0f172a;'}; padding:4px 10px; background:${isRejected ? '#fee2e2' : '#f1f5f9'}; border:1px solid ${isRejected ? '#fca5a5' : '#e2e8f0'}; border-radius:6px;">R$ ${propPrice.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
                                    </div>
                                `)}
                            </div>

                            <!-- LINHA 3: Sug. R$ X · Mín. R$ Y e selo à direita -->
                            <div class="item-line-3">
                                <div class="item-benchmarks">
                                    ${isUnfound ? `
                                        <span style="color:#be123c; font-weight:600;">Selecione o item equivalente no catálogo para definir preço</span>
                                    ` : `
                                        <span>Sug. R$ ${formatCurrency(item.preco_unit_sugerido)}</span>
                                        <span>·</span>
                                        <span>Mín. R$ ${formatCurrency(item.preco_minimo)}</span>
                                    `}
                                </div>
                                <div id="price-tag-wrap-${item.id}">
                                    ${isRejected ? `<span class="badge-price-tag tag-below-min" style="background:#fee2e2; color:#b91c1c; border:1px solid #fca5a5; font-weight:700;">Desconsiderado do total</span>` : priceTagHtml}
                                </div>
                            </div>

                            ${isRejected && motivoTexto ? `
                                <div style="margin-top: 4px; padding: 6px 10px; background: #fff5f5; border-radius: 6px; font-size: 11.5px; color: #991b1b; border: 1px dashed #fca5a5; display: flex; align-items: center; gap: 6px;">
                                    <strong>Motivo da recusa:</strong> <span>${escapeHtml(motivoTexto)}</span>
                                </div>
                            ` : ''}
                        </div>
                    `;
                } catch (errItem) {
                    console.error("Erro ao renderizar item da cotação:", item, errItem);
                }
            });
        }

        // Show/hide justification box
        updateJustificationPanelVisibility();
        
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

        // Update tag badge dynamically in real time as user edits price
        const tagWrap = document.getElementById(`price-tag-wrap-${itemId}`);
        if (tagWrap && item.status_item !== 'recusado') {
            tagWrap.innerHTML = getItemPriceTagHtml(item);
        }

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

        // Evaluate if any condition requires justification panel
        updateJustificationPanelVisibility();

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
            if (item.status_item === 'recusado' || item.nao_encontrado) {
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
                menu.innerHTML = `<div style="padding: 12px; text-align: center; color: #94a3b8; font-size: 12px;">Nenhum produto encontrado para "${escapeHtml(query)}".</div>`;
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
                    <span class="prod-code">${escapeHtml(p.codigo_sankhya)}</span>
                    <span class="prod-title">${escapeHtml(p.descricao)}</span>
                    ${p.marca ? `<span style="font-size: 11px; color: #64748b; margin-left: 6px;">(${escapeHtml(p.marca)})</span>` : ''}
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
        if (!replacingUnfoundItemId) {
            document.getElementById("new-item-qtd").value = "1";
        }
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

        const wasReplacing = Boolean(replacingUnfoundItemId);
        const payload = {
            produto_id: parseInt(productId),
            qtd: parseInt(document.getElementById("new-item-qtd").value) || 1,
            preco_unit_proposto: proposto,
            preco_unit_sugerido: sugerido,
            preco_minimo: minimo,
            substituir_item_id: replacingUnfoundItemId || null,
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
                if (data.data.historico) {
                    quote.historico = data.data.historico;
                    renderAuditTrail();
                    renderAttentionBanners();
                }
                cancelManualInclusionReplacement();
                renderItems();
                clearSelectedProduct();
                if (wasReplacing) {
                    showToast("Produto incluído manualmente no lugar do item não encontrado pela IA!", 'success');
                }
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
        const targetItem = quote?.itens?.find(i => i.id === itemId);
        const confirmMsg = targetItem && targetItem.nao_encontrado
            ? `Deseja dispensar/remover o item não encontrado "${targetItem.descricao_original || 'Item'}" desta cotação?`
            : "Deseja realmente remover este item?";
        if (!confirm(confirmMsg)) return;

        try {
            const res = await fetch(`${QUOTE_BASE_URL}/itens/${itemId}`, {
                method: "DELETE"
            });
            const data = await res.json().catch(() => ({}));
            if (res.ok && data.success) {
                if (replacingUnfoundItemId === itemId) {
                    cancelManualInclusionReplacement();
                }
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
            fileSummaries.push(`📁 ${escapeHtml(name)} (${sizeKb} KB)`);
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
            preview.innerHTML = `🎵 ${escapeHtml(file.name)} (${Math.round(file.size / 1024)} KB)`;
        }
        return true;
    }

    async function submitQuote() {
        if (isEditingLocked) {
            showToast("Esta cotação já está com status " + quote.status.replace(/_/g, ' ') + " e não permite novo envio.", 'warning');
            return;
        }

        const pendingUnfound = (quote && Array.isArray(quote.itens))
            ? quote.itens.filter(i => Boolean(i.nao_encontrado) && i.status_item !== 'recusado')
            : [];
        if (pendingUnfound.length > 0) {
            showToast(`Existem ${pendingUnfound.length} item(ns) não encontrado(s) pela IA pendentes. Clique em "Incluir Manualmente" para substituí-los pelo produto do catálogo ou em "Dispensar" antes de enviar.`, 'warning');
            document.getElementById("ai-unfound-items-panel")?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }

        // 1. Save draft changes first
        const saved = await saveDraft(false);
        if (!saved) return;

        // Re-evaluate justification visibility after saving draft
        updateJustificationPanelVisibility();

        // 2. If justifications are visible (needed), validate and upload them
        const justificationPanel = document.getElementById("justification-panel");
        const isJustRequired = justificationPanel && justificationPanel.style.display !== "none";

        if (isJustRequired) {
            const texto = (document.getElementById("just-texto")?.value || "").trim();
            const fileInput = document.getElementById("just-anexos");
            const audioInput = document.getElementById("just-audio");

            const hasText = texto.length > 0;
            const hasNewFiles = Boolean((fileInput && fileInput.files && fileInput.files.length > 0) || (audioInput && audioInput.files && audioInput.files.length > 0));
            const hasExistingJustification = Boolean(
                quote.justificativas &&
                quote.justificativas.some(j => !j.cotacao_item_id && ((j.texto && String(j.texto).trim().length > 0) || j.audio_url))
            );
            const hasExistingAttachments = Boolean(
                (quote.anexos && quote.anexos.length > 0) ||
                (quote.justificativas && quote.justificativas.some(j => !j.cotacao_item_id && j.anexos && j.anexos.length > 0))
            );

            // Mandatory written text if no existing justification
            if (!hasText && !hasExistingJustification) {
                showToast("É obrigatório informar uma justificativa por escrito antes de enviar para aprovação.", 'error');
                highlightAndScrollToJustification('just-texto');
                return;
            }

            // Mandatory attachment if EXIGE_ANEXO_JUSTIFICATIVA is enabled
            if (EXIGE_ANEXO_JUSTIFICATIVA && !hasNewFiles && !hasExistingAttachments) {
                showToast("O parâmetro de segurança exige anexo de pelo menos um comprovante (documento, foto ou orçamento concorrente) para aprovação de cotação com desconto acima da alçada.", 'error');
                highlightAndScrollToJustification('just-anexos');
                return;
            }

            // Client-side validations
            if (fileInput && fileInput.files && fileInput.files.length > 0 && !validateAttachmentFiles(fileInput)) {
                return;
            }
            if (audioInput && audioInput.files && audioInput.files.length > 0 && !validateAudioFile(audioInput)) {
                return;
            }

            // Perform multipart submit for attachments/justification if new content provided
            if (hasText || hasNewFiles) {
                const formData = new FormData();
                if (hasText) formData.append("texto", texto);
                if (fileInput && fileInput.files && fileInput.files.length > 0) {
                    for (let i = 0; i < fileInput.files.length; i++) {
                        formData.append("anexos[]", fileInput.files[i]);
                    }
                }
                if (audioInput && audioInput.files && audioInput.files.length > 0) {
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
        }

        // 3. Trigger submit to workflow routing
        try {
            const res = await fetch(`${QUOTE_BASE_URL}/enviar`, {
                method: "POST"
            });
            const data = await res.json().catch(() => ({}));
            if (res.ok && data.success) {
                if (data.status === 'APROVADA') {
                    showToast(data.message || "Aprovada automaticamente: sem desconto, não precisa de gestor.", 'success');
                } else if (data.status === 'AGUARDANDO_GESTOR') {
                    showToast("Cotação enviada ao Gestor de Equipe para análise.", 'info');
                } else if (data.status === 'COM_DIRETOR') {
                    showToast("Cotação enviada à Diretoria para análise.", 'info');
                } else {
                    showToast(data.message || ("Cotação enviada com sucesso! Situação: " + data.status.replace(/_/g, ' ')), 'success');
                }
                setTimeout(() => window.location.reload(), 1400);
                const errMsg = data.message || data.error || ("Falha no envio HTTP " + res.status);
                showToast("Erro ao enviar cotação: " + errMsg, 'error');
                if (String(errMsg).toLowerCase().includes('justificativa') || String(errMsg).toLowerCase().includes('anexo') || String(errMsg).toLowerCase().includes('comprovante')) {
                    highlightAndScrollToJustification('just-texto');
                }
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

    // ==========================================
    // PROPOSAL DELIVERY & WHATSAPP TRACKING FLOW
    // ==========================================
    let sendDeliveryContacts = [];
    let selectedContactId = null;

    function formatPhoneNumber(phone) {
        if (!phone) return '';
        let digits = String(phone).replace(/\D/g, '');
        if (digits.length >= 12 && digits.startsWith('55')) {
            digits = digits.substring(2);
        }
        if (digits.length === 11) {
            return `(${digits.substring(0, 2)}) ${digits.substring(2, 7)}-${digits.substring(7)}`;
        } else if (digits.length === 10) {
            return `(${digits.substring(0, 2)}) ${digits.substring(2, 6)}-${digits.substring(6)}`;
        }
        return phone;
    }

    function updateDeliveryBanner() {
        const banner = document.getElementById("proposal-delivery-banner");
        const iconEl = document.getElementById("proposal-delivery-icon");
        const textEl = document.getElementById("proposal-delivery-text");
        const reopenBtn = document.getElementById("btn-reopen-send-banner");
        if (!banner || !iconEl || !textEl) return;

        const env = quote.ultimo_envio;
        const envAberto = quote.ultimo_envio_aberto 
            || (env && env.aberto_em ? env : null)
            || (Array.isArray(quote.envios) ? quote.envios.find(e => e.aberto_em) : null);

        if (!env && !envAberto) {
            banner.style.display = "none";
            return;
        }

        banner.style.display = "flex";

        // Prioridade 1: Proposta já foi visualizada pelo cliente (seja neste ou em envio anterior)
        if (envAberto && envAberto.aberto_em) {
            banner.style.background = "#ecfdf5";
            banner.style.border = "1px solid #a7f3d0";
            banner.style.color = "#065f46";
            iconEl.innerHTML = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>`;
            
            const openDate = new Date(envAberto.aberto_em);
            const dateStr = openDate.toLocaleDateString('pt-BR');
            const timeStr = openDate.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
            const contactName = envAberto.nome_contato_snapshot || (envAberto.contato ? envAberto.contato.nome_contato : 'Cliente');
            
            textEl.innerHTML = `<strong>Visualizada pelo cliente em ${dateStr} às ${timeStr}</strong> · Contato Confirmado (${escapeHtml(contactName)})`;
        } else if (env && env.canal === 'WHATSAPP') {
            // Prioridade 2: Enviada via WhatsApp e aguardando primeira abertura
            banner.style.background = "#eff6ff";
            banner.style.border = "1px solid #bfdbfe";
            banner.style.color = "#1e40af";
            iconEl.innerHTML = `<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" style="color: #2563eb;"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>`;
            
            const contactName = env.nome_contato_snapshot || (env.contato ? env.contato.nome_contato : 'Cliente');
            const rawTel = (env.contato && env.contato.telefone_formatado) ? env.contato.telefone_formatado : (env.telefone_destino || env.telefone || '');
            const tel = formatPhoneNumber(rawTel);
            
            textEl.innerHTML = `Enviada para <strong>${escapeHtml(contactName)}</strong> · ${escapeHtml(tel)} · <em>aguardando abertura</em>`;
        } else if (env) {
            // Prioridade 3: Compartilhado nativamente como arquivo PDF
            banner.style.background = "#f1f5f9";
            banner.style.border = "1px solid #cbd5e1";
            banner.style.color = "#334155";
            iconEl.innerHTML = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#475569" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>`;

            const sendDate = env.enviado_em ? new Date(env.enviado_em) : new Date();
            const dateStr = sendDate.toLocaleDateString('pt-BR');
            const timeStr = sendDate.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });

            textEl.innerHTML = `<strong>PDF da proposta compartilhado</strong> em ${dateStr} às ${timeStr}`;
        }

        if (reopenBtn) {
            reopenBtn.style.display = ['APROVADA', 'PDF_GERADO', 'FINALIZADA_COM_PEDIDO'].includes(quote.status) ? "inline-block" : "none";
        }
    }

    // Converte "R$ 13.00" / "R$ 1,021.39" em "R$ 13,00" / "R$ 1.021,39" (valores já corretos ficam intactos)
    function normalizeCurrencyInText(text) {
        if (!text) return text;
        return String(text).replace(/R\$\s?((?:\d{1,3}(?:,\d{3})+|\d+)\.\d{2})(?!\d)/g, (m, num) => {
            const val = parseFloat(num.replace(/,/g, ''));
            if (isNaN(val)) return m;
            return 'R$ ' + val.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        });
    }

    async function openSendQuoteModal() {
        if (!quote) return;
        const allowStatuses = ['APROVADA', 'PDF_GERADO', 'FINALIZADA_COM_PEDIDO'];
        if (!allowStatuses.includes(quote.status)) {
            showToast("A proposta só pode ser enviada ao cliente após a aprovação (status atual: " + quote.status.replace(/_/g, ' ') + ").", "warning");
            return;
        }

        const modal = document.getElementById("send-quote-modal");
        const container = document.getElementById("send-contacts-container");
        const previewTextarea = document.getElementById("send-message-preview");
        if (modal) modal.style.display = "flex";

        container.innerHTML = `<div style="text-align: center; padding: 16px; font-size: 12.5px; color: #94a3b8;">Carregando contatos confiáveis...</div>`;

        try {
            const res = await fetch(`${QUOTE_BASE_URL}/contatos-envio`);
            const data = await res.json().catch(() => ({}));
            if (res.ok && data.success) {
                sendDeliveryContacts = data.contatos || [];
                renderSendContactsList(sendDeliveryContacts);
                if (previewTextarea && data.previa_mensagem) {
                    previewTextarea.value = normalizeCurrencyInText(data.previa_mensagem);
                }
                const customNameInput = document.getElementById("send-custom-name");
                if (customNameInput && quote && quote.parceiro) {
                    customNameInput.value = customNameInput.value || quote.parceiro.nome_fantasia || quote.parceiro.razao_social || '';
                }
            } else {
                const errMsg = data.message || data.error || (res.status === 404 ? 'Cotação não encontrada' : 'Falha ao carregar contatos do cliente.');
                container.innerHTML = `<div style="color: #dc2626; padding: 12px; font-size: 12.5px;">${escapeHtml(errMsg)}</div>`;
                validateSendContactForm(false);
            }
        } catch (e) {
            container.innerHTML = `<div style="color: #dc2626; padding: 12px; font-size: 12.5px;">Erro de conexão ao buscar contatos: ${escapeHtml(e.message || '')}</div>`;
            validateSendContactForm(false);
        }
    }

    function closeSendQuoteModal() {
        const modal = document.getElementById("send-quote-modal");
        if (modal) modal.style.display = "none";
    }

    function renderSendContactsList(contatos) {
        const container = document.getElementById("send-contacts-container");
        if (!container) return;
        container.innerHTML = "";

        if (!contatos || contatos.length === 0) {
            selectedContactId = "outro";
            container.innerHTML = `
                <div style="font-size: 12.5px; color: #64748b; background: #fffbeb; border: 1px solid #fef3c7; padding: 10px 12px; border-radius: 8px;">
                    Nenhum telefone registrado para este cliente ainda. Informe o celular do cliente abaixo para realizar o primeiro envio e salvá-lo no histórico.
                </div>
            `;
            document.getElementById("send-custom-contact-box").style.display = "block";
            validateSendContactForm(false);
            return;
        }

        let hasSuggested = false;

        contatos.forEach((c, idx) => {
            const contactKey = (c.id !== null && c.id !== undefined) ? String(c.id) : ('sankhya_' + idx);
            c._key = contactKey;

            const isChecked = c.is_sugerido || (idx === 0 && !hasSuggested);
            if (isChecked) {
                selectedContactId = contactKey;
                hasSuggested = true;
            }

            const itemDiv = document.createElement("label");
            itemDiv.style.cssText = `display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; border: 1.5px solid ${isChecked ? '#22c55e' : '#e2e8f0'}; background: ${isChecked ? '#f0fdf4' : '#ffffff'}; border-radius: 10px; cursor: pointer; transition: all 0.15s ease;`;
            itemDiv.id = `contact-card-${contactKey}`;

            const badgeConfirmado = c.confirmado
                ? `<span style="background: #dcfce7; color: #166534; font-size: 10.5px; font-weight: 700; padding: 2px 7px; border-radius: 12px; border: 1px solid #bbf7d0;">✓ Confirmado</span>`
                : `<span style="background: #f1f5f9; color: #64748b; font-size: 10.5px; font-weight: 600; padding: 2px 7px; border-radius: 12px; border: 1px solid #e2e8f0;">Não confirmado</span>`;

            const badgeOrigem = c.origem === 'SANKHYA'
                ? `<span style="font-size: 10px; color: #94a3b8; font-weight: 600;">Sankhya</span>`
                : `<span style="font-size: 10px; color: #94a3b8; font-weight: 600;">Vendedor</span>`;

            itemDiv.innerHTML = `
                <div style="display: flex; align-items: center; gap: 10px;">
                    <input type="radio" name="delivery_contact_choice" value="${contactKey}" ${isChecked ? 'checked' : ''} onchange="onContactSelectionChange(this.value)">
                    <div>
                        <div style="display: flex; align-items: center; gap: 6px;">
                            <strong style="font-size: 13.5px; color: #0f172a;">${escapeHtml(c.nome_contato)}</strong>
                            ${c.cargo ? `<span style="font-size: 11px; color: #64748b;">(${escapeHtml(c.cargo)})</span>` : ''}
                        </div>
                        <div style="font-size: 12px; color: #475569; font-weight: 600; margin-top: 2px;">
                            ${escapeHtml(c.telefone_formatado || c.telefone)}
                        </div>
                    </div>
                </div>
                <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 3px;">
                    ${badgeConfirmado}
                    ${badgeOrigem}
                </div>
            `;
            container.appendChild(itemDiv);
        });

        // Add "+ Outro número" radio option
        const outroDiv = document.createElement("label");
        outroDiv.style.cssText = `display: flex; align-items: center; gap: 10px; padding: 10px 14px; border: 1.5px dashed #cbd5e1; background: #ffffff; border-radius: 10px; cursor: pointer; margin-top: 4px;`;
        outroDiv.id = `contact-card-outro`;
        outroDiv.innerHTML = `
            <input type="radio" name="delivery_contact_choice" value="outro" onchange="onContactSelectionChange(this.value)">
            <span style="font-size: 13px; font-weight: 600; color: #334155;">+ Outro número de WhatsApp</span>
        `;
        container.appendChild(outroDiv);

        document.getElementById("send-custom-contact-box").style.display = (selectedContactId === 'outro') ? "block" : "none";
        validateSendContactForm(false);
    }

    function validateSendContactForm(showErrors = false) {
        const btnWa = document.getElementById("btn-open-wa-native");
        const feedback = document.getElementById("send-phone-validation-feedback");
        const phoneInput = document.getElementById("send-custom-phone");

        const checkedRadio = document.querySelector('input[name="delivery_contact_choice"]:checked');
        const activeChoice = checkedRadio ? checkedRadio.value : (selectedContactId || (sendDeliveryContacts.length ? (sendDeliveryContacts[0]._key || sendDeliveryContacts[0].id) : 'outro'));
        const isCustom = (activeChoice === 'outro' || !sendDeliveryContacts || sendDeliveryContacts.length === 0);

        let isValid = true;
        let errorMsg = '';

        if (isCustom) {
            const rawPhone = phoneInput ? phoneInput.value : '';
            const digits = rawPhone.replace(/\D/g, '');

            if (digits.length === 0) {
                isValid = false;
                if (showErrors) {
                    errorMsg = "Informe o celular com DDD para enviar via WhatsApp.";
                }
            } else if (digits.length < 11) {
                isValid = false;
                if (digits.length === 10) {
                    errorMsg = "⚠️ Número fixo não tem WhatsApp. Informe um celular com 9 dígitos.";
                } else {
                    errorMsg = `⚠️ Celular incompleto (${digits.length} de 11 dígitos). Ex: (34) 99999-9999`;
                }
            } else if (digits.substring(2, 3) !== '9') {
                isValid = false;
                errorMsg = "⚠️ Celulares com WhatsApp devem começar com o dígito 9 após o DDD.";
            } else {
                isValid = true;
            }

            if (feedback) {
                if (!isValid) {
                    if (errorMsg) {
                        feedback.style.display = "block";
                        feedback.style.color = "#dc2626";
                        feedback.innerText = errorMsg;
                        if (phoneInput) phoneInput.style.borderColor = "#ef4444";
                    } else {
                        feedback.style.display = "none";
                        if (phoneInput) phoneInput.style.borderColor = "#cbd5e1";
                    }
                } else {
                    feedback.style.display = "block";
                    feedback.style.color = "#16a34a";
                    feedback.innerText = "✓ Celular válido para WhatsApp";
                    if (phoneInput) phoneInput.style.borderColor = "#22c55e";
                }
            }
        } else {
            // Predefined contact from client list
            isValid = true;
            if (feedback) {
                feedback.style.display = "none";
            }
            if (phoneInput) {
                phoneInput.style.borderColor = "#cbd5e1";
            }
        }

        if (btnWa) {
            btnWa.disabled = !isValid;
            btnWa.style.opacity = isValid ? "1" : "0.5";
            btnWa.style.cursor = isValid ? "pointer" : "not-allowed";
        }

        return isValid;
    }

    function onContactSelectionChange(val) {
        selectedContactId = val;
        document.querySelectorAll("[id^='contact-card-']").forEach(el => {
            el.style.borderColor = "#e2e8f0";
            el.style.backgroundColor = "#ffffff";
        });
        const activeCard = document.getElementById(`contact-card-${val}`);
        if (activeCard) {
            activeCard.style.borderColor = "#22c55e";
            activeCard.style.backgroundColor = "#f0fdf4";
        }

        const customBox = document.getElementById("send-custom-contact-box");
        if (val === 'outro') {
            customBox.style.display = "block";
            const customNameInput = document.getElementById("send-custom-name");
            if (customNameInput && !customNameInput.value && quote && quote.parceiro) {
                customNameInput.value = quote.parceiro.nome_fantasia || quote.parceiro.razao_social || '';
            }
            validateSendContactForm(false);
            document.getElementById("send-custom-phone")?.focus();
        } else {
            customBox.style.display = "none";
            validateSendContactForm(false);
        }
    }

    function onCustomPhoneInput(input) {
        let v = input.value.replace(/\D/g, '');
        if (v.length > 11) v = v.substring(0, 11);

        if (v.length > 6) {
            input.value = `(${v.substring(0,2)}) ${v.substring(2,7)}-${v.substring(7)}`;
        } else if (v.length > 2) {
            input.value = `(${v.substring(0,2)}) ${v.substring(2)}`;
        } else if (v.length > 0) {
            input.value = `(${v}`;
        }

        validateSendContactForm(true);
    }

    function onCustomContactInput() {
        // optionally update preview with name
    }

    async function submitSendQuote(canal) {
        if (!quote) return;
        const btnWa = document.getElementById("btn-open-wa-native");
        const btnShare = document.getElementById("btn-share-pdf-native");
        
        let payload = {
            canal: canal,
            mensagem: document.getElementById("send-message-preview")?.value || ''
        };

        if (canal === 'WHATSAPP') {
            if (!validateSendContactForm(true)) {
                document.getElementById("send-custom-phone")?.focus();
                return;
            }

            const checkedRadio = document.querySelector('input[name="delivery_contact_choice"]:checked');
            const activeChoice = checkedRadio ? checkedRadio.value : (selectedContactId || (sendDeliveryContacts.length ? (sendDeliveryContacts[0]._key || sendDeliveryContacts[0].id) : 'outro'));

            if (activeChoice === 'outro' || !sendDeliveryContacts || sendDeliveryContacts.length === 0) {
                const rawPhone = document.getElementById("send-custom-phone")?.value || '';
                const fallbackName = (quote?.parceiro?.nome_fantasia || quote?.parceiro?.razao_social || 'Cliente');
                const contactName = document.getElementById("send-custom-name")?.value?.trim() || fallbackName;
                const digits = rawPhone.replace(/\D/g, '');
                
                payload.telefone = digits;
                payload.nome_contato = contactName;
            } else {
                const c = sendDeliveryContacts.find(x => String(x._key || x.id) === String(activeChoice)) 
                       || sendDeliveryContacts.find(x => String(x.id) === String(activeChoice)) 
                       || sendDeliveryContacts[0];
                if (c) {
                    if (c.id && !isNaN(Number(c.id))) {
                        payload.contato_id = Number(c.id);
                    }
                    payload.telefone = c.telefone;
                    payload.nome_contato = c.nome_contato;
                }
            }

            if (!payload.telefone) {
                const feedback = document.getElementById("send-phone-validation-feedback");
                if (feedback) {
                    feedback.style.display = "block";
                    feedback.style.color = "#dc2626";
                    feedback.innerText = "Selecione ou informe um número de telefone com WhatsApp.";
                }
                return;
            }
        }

        // Abrir a janela/aba no clique (síncrono, antes do await) para evitar bloqueio de pop-up no celular
        let waWindow = null;
        if (canal === 'WHATSAPP') {
            waWindow = window.open('about:blank', '_blank');
            if (waWindow && waWindow.document) {
                try {
                    waWindow.document.write('<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Abrindo WhatsApp...</title></head><body style="font-family:sans-serif;display:flex;align-items:center;justify-content:center;height:90vh;color:#334155;font-size:15px;">Redirecionando para o WhatsApp...</body></html>');
                } catch (e) {}
            }
        }

        try {
            if (btnWa) btnWa.disabled = true;
            if (btnShare) btnShare.disabled = true;

            const res = await fetch(`${QUOTE_BASE_URL}/envios`, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(payload)
            });
            const data = await res.json().catch(() => ({}));

            if (res.ok && data.success) {
                if (canal === 'WHATSAPP') {
                    if (data.url_whatsapp) {
                        if (waWindow && !waWindow.closed) {
                            waWindow.location.href = data.url_whatsapp;
                        } else {
                            window.location.href = data.url_whatsapp;
                        }
                    } else if (waWindow && !waWindow.closed) {
                        waWindow.close();
                    }
                    showToast("WhatsApp aberto com a proposta pronta para envio!", "success");
                    if (data.warning) {
                        setTimeout(() => {
                            showToast(data.warning, "info");
                        }, 2200);
                    }
                } else if (canal === 'WEB_SHARE') {
                    await shareViaWebShare(`${QUOTE_BASE_URL}/pdf`, quote.numero);
                }

                closeSendQuoteModal();
                
                // Atualizar dados e banner de entrega imediatamente
                setTimeout(() => {
                    loadData();
                }, 1200);

            } else {
                if (waWindow && !waWindow.closed) {
                    waWindow.close();
                }
                showToast("Erro no envio: " + (data.error || data.message || "Falha na comunicação"), "error");
            }
        } catch (e) {
            if (waWindow && !waWindow.closed) {
                waWindow.close();
            }
            showToast("Erro de conexão ao processar envio.", "error");
        } finally {
            if (btnWa) btnWa.disabled = false;
            if (btnShare) btnShare.disabled = false;
        }
    }

    async function shareViaWebShare(pdfUrl, quoteNumero) {
        if (!pdfUrl) pdfUrl = `${QUOTE_BASE_URL}/pdf`;
        if (!quoteNumero) quoteNumero = quote ? quote.numero : '';

        if (navigator.share) {
            try {
                showToast("Preparando PDF para compartilhamento...", "info");
                const res = await fetch(pdfUrl);
                const blob = await res.blob();
                const file = new File([blob], `proposta_${quoteNumero}.pdf`, { type: 'application/pdf' });

                if (navigator.canShare && navigator.canShare({ files: [file] })) {
                    await navigator.share({
                        title: `Proposta Comercial #${quoteNumero}`,
                        text: `Segue proposta comercial #${quoteNumero} da Central Veterinária.`,
                        files: [file]
                    });
                    showToast("Compartilhamento concluído!", "success");
                } else {
                    await navigator.share({
                        title: `Proposta Comercial #${quoteNumero}`,
                        text: `Segue proposta comercial #${quoteNumero} da Central Veterinária.`,
                        url: pdfUrl
                    });
                }
            } catch (err) {
                if (err.name !== 'AbortError') {
                    window.open(pdfUrl, '_blank');
                }
            }
        } else {
            window.open(pdfUrl, '_blank');
        }
    }
</script>
@endsection
