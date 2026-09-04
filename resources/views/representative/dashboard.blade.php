<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Zé Cotação — Painel do Representante</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --color-primary: #1a56db;
            --color-primary-light: #3b82f6;
            --color-background: #f4f6f8;
            --color-card: #ffffff;
            --color-text: #1e293b;
            --color-text-muted: #64748b;
            --color-border: #e2e8f0;
            --color-accent: #2563eb;
            
            --status-em-criacao: #3b82f6;
            --status-devolvida: #f59e0b;
            --status-aguardando-gestor: #8b5cf6;
            --status-com-diretor: #ec4899;
            --status-pdf-gerado: #10b981;
            --status-finalizada: #111827;
            --status-perdida: #ef4444;
            
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Outfit', sans-serif;
            -webkit-tap-highlight-color: transparent;
        }
        
        body {
            background-color: #0f172a; /* Dark elegant container background */
            color: var(--color-text);
            display: flex;
            justify-content: center;
            min-height: 100vh;
        }
        
        /* Mobile Frame Container */
        .mobile-container {
            width: 100%;
            max-width: 480px;
            background-color: var(--color-background);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            position: relative;
            box-shadow: 0 0 40px rgba(0,0,0,0.5);
            overflow-x: hidden;
            padding-bottom: 75px; /* bottom bar spacing */
        }
        
        /* App Header */
        .app-header {
            background: linear-gradient(135deg, var(--color-primary), #115e3b);
            color: white;
            padding: 20px 16px;
            border-bottom-left-radius: 20px;
            border-bottom-right-radius: 20px;
            box-shadow: var(--shadow-md);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        
        .logo-title {
            font-size: 20px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }
        
        .logo-title span {
            color: #10b981;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 500;
        }
        
        .role-badge {
            background-color: rgba(255,255,255,0.2);
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 10px;
            text-transform: uppercase;
        }
        
        /* Search Box */
        .search-box-container {
            position: relative;
        }
        
        .search-input {
            width: 100%;
            background-color: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 12px;
            padding: 10px 12px 10px 38px;
            color: white;
            font-size: 14px;
            outline: none;
            transition: all 0.3s ease;
        }
        
        .search-input::placeholder {
            color: rgba(255,255,255,0.6);
        }
        
        .search-input:focus {
            background-color: white;
            color: var(--color-text);
            border-color: white;
            box-shadow: var(--shadow-sm);
        }
        
        .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255,255,255,0.6);
            pointer-events: none;
            transition: all 0.3s ease;
        }
        
        .search-input:focus ~ .search-icon {
            color: var(--color-text-muted);
        }
        
        /* Content Tabs */
        .tab-content {
            padding: 16px;
            display: none;
            flex-direction: column;
            gap: 12px;
            animation: fadeIn 0.3s ease;
        }
        
        .tab-content.active {
            display: flex;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Quotation Cards */
        .quote-card {
            background-color: var(--color-card);
            border-radius: 16px;
            padding: 16px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--color-border);
            display: flex;
            flex-direction: column;
            gap: 12px;
            text-decoration: none;
            color: inherit;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .quote-card:active {
            transform: scale(0.98);
            box-shadow: none;
        }
        
        .card-row-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .quote-num {
            font-size: 15px;
            font-weight: 600;
            color: var(--color-primary);
        }
        
        .status-badge {
            font-size: 11px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 20px;
            text-transform: uppercase;
        }
        
        .status-em-criacao { background-color: #eff6ff; color: var(--status-em-criacao); }
        .status-devolvida { background-color: #fffbeb; color: var(--status-devolvida); }
        .status-aguardando-gestor { background-color: #f5f3ff; color: var(--status-aguardando-gestor); }
        .status-com-diretor { background-color: #fdf2f8; color: var(--status-com-diretor); }
        .status-pdf-gerado { background-color: #ecfdf5; color: var(--status-pdf-gerado); }
        .status-finalizada-com-pedido { background-color: #f9fafb; color: var(--status-finalizada); }
        .status-faturada { background-color: #f9fafb; color: var(--status-finalizada); }
        .status-perdida { background-color: #fef2f2; color: var(--status-perdida); }
        
        .client-name {
            font-size: 16px;
            font-weight: 500;
            color: var(--color-text);
        }
        
        .card-row-bottom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px dashed var(--color-border);
            padding-top: 10px;
            font-size: 13px;
        }
        
        .quote-date {
            color: var(--color-text-muted);
        }
        
        .quote-total {
            font-size: 16px;
            font-weight: 700;
            color: var(--color-primary);
        }
        
        /* Bottom Tabbar */
        .tab-bar {
            position: fixed;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100%;
            max-width: 480px;
            background-color: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-top: 1px solid var(--color-border);
            display: flex;
            justify-content: space-around;
            padding: 10px 0 15px 0;
            z-index: 100;
            box-shadow: 0 -4px 12px rgba(0,0,0,0.05);
        }
        
        .tab-button {
            background: none;
            border: none;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            color: var(--color-text-muted);
            font-size: 11px;
            font-weight: 500;
            cursor: pointer;
            position: relative;
            transition: color 0.2s ease;
        }
        
        .tab-button.active {
            color: var(--color-primary);
        }
        
        .tab-button svg {
            transition: transform 0.2s ease;
        }
        
        .tab-button.active svg {
            transform: scale(1.1);
        }
        
        /* Badges */
        .notification-dot {
            position: absolute;
            top: -2px;
            right: 12px;
            background-color: var(--status-perdida);
            color: white;
            font-size: 9px;
            font-weight: 700;
            width: 15px;
            height: 15px;
            border-radius: 50%;
            display: none;
            align-items: center;
            justify-content: center;
            border: 2px solid white;
        }

        /* Checkin Specific UI */
        .checkin-card {
            background-color: var(--color-card);
            border-radius: 16px;
            padding: 18px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--color-border);
            display: flex;
            flex-direction: column;
            gap: 14px;
        }
        .checkin-item {
            background-color: var(--color-card);
            border-radius: 12px;
            padding: 12px 14px;
            border: 1px solid var(--color-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .checkin-item-left {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .checkin-item-title {
            font-size: 13px;
            font-weight: 600;
            color: var(--color-text);
        }
        .checkin-item-date {
            font-size: 11px;
            color: var(--color-text-muted);
        }
        .checkin-item-coords {
            font-size: 10px;
            font-family: monospace;
            background-color: #f1f5f9;
            padding: 2px 6px;
            border-radius: 4px;
            margin-top: 4px;
            display: inline-block;
        }
        .checkin-item-right a {
            color: var(--color-primary);
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        /* Notifications List */
        .notification-item {
            background-color: var(--color-card);
            border-radius: 12px;
            padding: 14px;
            border: 1px solid var(--color-border);
            display: flex;
            flex-direction: column;
            gap: 6px;
            cursor: pointer;
            transition: background-color 0.2s ease;
            position: relative;
        }
        
        .notification-item.unread {
            background-color: #f0f5ff;
            border-left: 4px solid var(--color-primary);
        }
        
        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .notification-title {
            font-size: 14px;
            font-weight: 600;
            color: var(--color-text);
        }
        
        .notification-time {
            font-size: 11px;
            color: var(--color-text-muted);
        }
        
        .notification-body {
            font-size: 13px;
            color: var(--color-text-muted);
            line-height: 1.4;
        }
        
        /* Profile UI */
        .profile-card {
            background-color: var(--color-card);
            border-radius: 16px;
            padding: 20px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--color-border);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 16px;
            text-align: center;
        }
        
        .profile-avatar {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--color-primary-light), var(--color-primary));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 28px;
            font-weight: 600;
            box-shadow: var(--shadow-md);
        }
        
        .profile-info {
            width: 100%;
        }
        
        .profile-info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid var(--color-border);
            font-size: 14px;
        }
        
        .profile-info-row:last-child {
            border-bottom: none;
        }
        
        .profile-label {
            color: var(--color-text-muted);
            font-weight: 500;
        }
        
        .profile-val {
            color: var(--color-text);
            font-weight: 600;
        }
        
        .btn-logout {
            width: 100%;
            background-color: var(--status-perdida);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: background-color 0.2s;
            margin-top: 10px;
        }
        
        .btn-logout:active {
            background-color: #b91c1c;
        }
        
        /* Alert Toast Banner */
        .alert-toast {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%) translateY(-100px);
            width: calc(100% - 32px);
            max-width: 440px;
            background-color: #1a56db;
            color: white;
            padding: 16px;
            border-radius: 14px;
            box-shadow: var(--shadow-lg);
            z-index: 1000;
            display: flex;
            gap: 12px;
            align-items: flex-start;
            transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            cursor: pointer;
        }
        
        .alert-toast.show {
            transform: translateX(-50%) translateY(0);
        }
        
        .alert-toast-icon {
            background-color: rgba(255,255,255,0.2);
            padding: 8px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .alert-toast-content {
            flex-grow: 1;
        }
        
        .alert-toast-title {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 2px;
        }
        
        .alert-toast-desc {
            font-size: 12px;
            opacity: 0.9;
            line-height: 1.3;
        }
        
        /* Bounce Animation */
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }
        
        .bounce-nav {
            animation: bounce 0.5s ease 3;
        }

        /* Horizontal Scrollable Filters */
        .filters-scroll::-webkit-scrollbar {
            display: none;
        }
        
        .filter-pill {
            background-color: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: rgba(255, 255, 255, 0.85);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            white-space: nowrap;
            cursor: pointer;
            transition: all 0.25s ease;
            outline: none;
        }
        
        .filter-pill.active {
            background-color: white;
            color: var(--color-primary);
            border-color: white;
            font-weight: 600;
            box-shadow: var(--shadow-sm);
        }

        /* Floating Action Button (FAB) */
        .fab-btn {
            position: fixed;
            bottom: 85px;
            right: max(16px, calc(50vw - 224px));
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border: none;
            border-radius: 30px;
            padding: 12px 18px;
            font-size: 14px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
            z-index: 99;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .fab-btn:active {
            transform: scale(0.95);
        }

        /* Drawer & Modal Overlay Styles */
        .modal-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: rgba(15, 23, 42, 0.65);
            backdrop-filter: blur(4px);
            z-index: 999;
        }
        .mobile-drawer {
            position: fixed;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100%;
            max-width: 480px;
            height: 90vh;
            background-color: #ffffff;
            border-top-left-radius: 24px;
            border-top-right-radius: 24px;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            box-shadow: 0 -10px 30px rgba(0,0,0,0.25);
            animation: slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            overflow: hidden;
        }
        @keyframes slideUp {
            from { transform: translate(-50%, 100%); }
            to { transform: translate(-50%, 0); }
        }
        .drawer-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 20px;
            background: linear-gradient(135deg, var(--color-primary), #115e3b);
            color: white;
            border-top-left-radius: 24px;
            border-top-right-radius: 24px;
        }
        .drawer-title {
            font-size: 16px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .btn-close-drawer {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            font-size: 22px;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        /* Stepper */
        .stepper-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 16px;
            background-color: #f8fafc;
            border-bottom: 1px solid var(--color-border);
        }
        .step-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 600;
            color: var(--color-text-muted);
            cursor: pointer;
        }
        .step-item.active {
            color: var(--color-primary);
        }
        .step-badge {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background-color: #e2e8f0;
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 700;
        }
        .step-item.active .step-badge {
            background-color: var(--color-primary);
            color: white;
        }
        .step-line {
            flex-grow: 1;
            height: 2px;
            background-color: #e2e8f0;
            margin: 0 4px;
        }

        /* Drawer Body */
        .drawer-body {
            padding: 16px;
            overflow-y: auto;
            flex-grow: 1;
        }

        .form-group-mobile {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 14px;
        }
        .form-label-mobile {
            font-size: 13px;
            font-weight: 600;
            color: var(--color-text);
        }
        .input-mobile, .select-mobile {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--color-border);
            border-radius: 12px;
            font-size: 14px;
            outline: none;
            background-color: #f8fafc;
            transition: border-color 0.2s, background-color 0.2s;
        }
        .input-mobile:focus, .select-mobile:focus {
            border-color: var(--color-primary);
            background-color: #ffffff;
        }

        /* Mobile Buttons */
        .btn-primary-mobile {
            width: 100%;
            background-color: var(--color-primary);
            color: white;
            border: none;
            padding: 14px;
            border-radius: 14px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 4px 10px rgba(26, 86, 219, 0.25);
        }
        .btn-secondary-mobile {
            flex: 1;
            background-color: #e2e8f0;
            color: #334155;
            border: none;
            padding: 14px;
            border-radius: 14px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-align: center;
        }
        .btn-success-mobile {
            flex: 2;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border: none;
            padding: 14px;
            border-radius: 14px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 4px 10px rgba(16, 185, 129, 0.3);
        }

        .info-card-mobile {
            background-color: #f8fafc;
            border: 1px solid var(--color-border);
            border-radius: 12px;
            padding: 12px 14px;
        }

        .products-list-mobile {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .product-item-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 12px;
            background: #f8fafc;
            border: 1px solid var(--color-border);
            border-radius: 10px;
        }
        .product-item-title {
            font-size: 13px;
            font-weight: 600;
            color: var(--color-text);
        }
        .product-item-price {
            font-size: 12px;
            color: var(--color-primary);
            font-weight: 600;
        }
        .btn-add-prod {
            background: var(--color-primary);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
        }

        .cart-items-container {
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-height: 220px;
            overflow-y: auto;
            margin-top: 6px;
        }
        .cart-item-card {
            background-color: #ffffff;
            border: 1px solid var(--color-border);
            border-radius: 12px;
            padding: 12px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.03);
        }
        .cart-item-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        .cart-item-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #f8fafc;
            padding: 6px 10px;
            border-radius: 8px;
        }
        .qty-stepper {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .btn-stepper {
            width: 28px;
            height: 28px;
            border-radius: 6px;
            border: 1px solid var(--color-border);
            background: white;
            font-size: 16px;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        .cart-total-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #eff6ff;
            padding: 12px 16px;
            border-radius: 12px;
            border: 1px solid #bfdbfe;
            margin-top: 12px;
        }
    </style>
</head>
<body>

    <div class="mobile-container">
        
        <!-- Header -->
        <header class="app-header" style="padding-bottom: 12px;">
            <div class="header-top">
                <div style="display:flex; align-items:center; gap:10px;">
                    <button type="button" onclick="appGoBack()" style="background:rgba(255,255,255,0.2); border:1px solid rgba(255,255,255,0.3); color:white; padding:5px 10px; border-radius:10px; font-size:12px; font-weight:600; display:flex; align-items:center; gap:4px; cursor:pointer;" title="Voltar para a página anterior">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                        Voltar
                    </button>
                    <div class="logo-title">Zé <span>Cotação</span></div>
                </div>
                <div class="user-info">
                    <span id="header-user-name">{{ auth()->user()->nome }}</span>
                    <span class="role-badge">Representante</span>
                </div>
            </div>
            
            <div class="search-box-container" id="search-container" style="margin-bottom: 8px;">
                <input type="text" id="search-input" class="search-input" placeholder="Buscar cotação por Nº ou cliente..." oninput="filterQuotes()">
                <svg class="search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            </div>

            <div class="filters-scroll" id="filters-scroll-container" style="display: flex; gap: 8px; overflow-x: auto; scrollbar-width: none; -ms-overflow-style: none; padding-top: 4px;">
                <button type="button" class="filter-pill active" onclick="setStatusFilter('ALL')">Todas</button>
                <button type="button" class="filter-pill" onclick="setStatusFilter('EM_CRIACAO')">Rascunhos</button>
                <button type="button" class="filter-pill" onclick="setStatusFilter('PENDENTE')">Pendentes</button>
                <button type="button" class="filter-pill" onclick="setStatusFilter('PDF_GERADO')">Aprovadas</button>
                <button type="button" class="filter-pill" onclick="setStatusFilter('FINALIZADA')">Faturadas</button>
                <button type="button" class="filter-pill" onclick="setStatusFilter('PERDIDA')">Perdidas</button>
            </div>
        </header>
        
        <!-- Content Area -->
        
        <!-- TAB 1: Quotations List -->
        <main id="tab-quotes" class="tab-content active">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 4px;">
                <h4 style="font-weight: 600; color: var(--color-text-muted); font-size:13px; text-transform:uppercase;">Minhas Cotações</h4>
                <span id="quotes-count" style="font-size:12px; font-weight:600; color:var(--color-primary);">0 carregadas</span>
            </div>
            <div id="quotes-list-container" style="display:flex; flex-direction:column; gap:12px;">
                <!-- Quote items loaded here -->
            </div>
        </main>
        
        <!-- TAB 2: Notifications List -->
        <main id="tab-alerts" class="tab-content">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 10px;">
                <h4 style="font-weight: 600; color: var(--color-text-muted); font-size:13px; text-transform:uppercase;">Notificações</h4>
                <button onclick="markAllAsRead()" style="border:none; background:none; color:var(--color-accent); font-weight:600; font-size:12px; cursor:pointer;">Limpar todas</button>
            </div>
            <div id="notifications-list-container" style="display:flex; flex-direction:column; gap:12px;">
                <!-- Notifications loaded here -->
            </div>
        </main>
        
        <!-- TAB 3: User Profile -->
        <main id="tab-profile" class="tab-content">
            <h4 style="font-weight: 600; color: var(--color-text-muted); font-size:13px; text-transform:uppercase; margin-bottom: 10px;">Meu Perfil</h4>
            
            <div class="profile-card">
                <div class="profile-avatar">
                    {{ strtoupper(substr(auth()->user()->nome, 0, 1)) }}
                </div>
                
                <div class="profile-info">
                    <div class="profile-info-row">
                        <span class="profile-label">Nome Completo</span>
                        <span class="profile-val">{{ auth()->user()->nome }}</span>
                    </div>
                    <div class="profile-info-row">
                        <span class="profile-label">E-mail</span>
                        <span class="profile-val">{{ auth()->user()->email }}</span>
                    </div>
                    <div class="profile-info-row">
                        <span class="profile-label">Código ERP (Sankhya)</span>
                        <span class="profile-val">{{ auth()->user()->codigo_sankhya ?? 'Sem Código' }}</span>
                    </div>
                    <div class="profile-info-row">
                        <span class="profile-label">Equipe de Vendas</span>
                        <span class="profile-val" id="profile-team-val">Carregando...</span>
                    </div>
                </div>
                
                <form action="{{ url('/logout') }}" method="POST" style="width: 100%;">
                    @csrf
                    <button type="submit" class="btn-logout">Sair da Conta</button>
                </form>
            </div>
        </main>

        <!-- TAB 4: Check-in / Visitas -->
        <main id="tab-checkin" class="tab-content">
            <h4 style="font-weight: 600; color: var(--color-text-muted); font-size:13px; text-transform:uppercase; margin-bottom: 10px;">Check-in de Visitas</h4>
            
            <div class="checkin-card">
                <div>
                    <label style="font-weight: 600; font-size: 13px; margin-bottom: 6px; display: block; color: var(--color-text);">Selecione o Cliente / Parceiro</label>
                    <select id="checkin-partner-select" class="form-control" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--color-border); font-size: 14px; outline:none; background-color:white;">
                        <option value="">-- Selecione o Parceiro --</option>
                    </select>
                </div>

                <div style="background-color: #f8fafc; border-radius: 8px; padding: 12px; border: 1px solid var(--color-border);">
                    <div style="font-weight: 600; font-size: 12px; color: var(--color-text-muted); margin-bottom: 4px;">Localização Atual (GPS):</div>
                    <div id="checkin-coords" style="font-size: 13px; font-weight: 500; color: var(--color-text);">Obtendo coordenadas GPS...</div>
                </div>

                <button type="button" id="btn-do-checkin" onclick="performCheckin()" style="width: 100%; background: linear-gradient(135deg, var(--color-primary), #115e3b); color: white; border: none; padding: 12px; border-radius: 12px; font-weight: 700; font-size: 15px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; box-shadow: var(--shadow-sm);">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    Confirmar Visita (Check-in)
                </button>
            </div>

            <h4 style="font-weight: 600; color: var(--color-text-muted); font-size:13px; text-transform:uppercase; margin-top: 15px; margin-bottom: 8px;">Check-ins Recentes</h4>
            <div id="recent-checkins-list" style="display: flex; flex-direction: column; gap: 10px; max-height: 250px; overflow-y: auto;">
                <!-- Dynamically loaded -->
            </div>
        </main>
        
        <!-- Bottom Tab Bar -->
        <nav class="tab-bar">
            <button id="nav-btn-quotes" class="tab-button active" onclick="switchTab('quotes')">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                Cotações
            </button>
            <button id="nav-btn-checkin" class="tab-button" onclick="switchTab('checkin')">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                Visitas
            </button>
            <button id="nav-btn-alerts" class="tab-button" onclick="switchTab('alerts')">
                <div id="alerts-badge" class="notification-dot">0</div>
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                Alertas
            </button>
            <button id="nav-btn-profile" class="tab-button" onclick="switchTab('profile')">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                Perfil
            </button>
        </nav>
        
        <!-- Premium Audio Chime Elements (Web Audio API Synthesized, no files needed) -->
        
        <!-- Alert Toast Banner -->
        <div id="app-toast" class="alert-toast" onclick="onToastClick()">
            <div class="alert-toast-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:white;"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
            </div>
            <div class="alert-toast-content">
                <div class="alert-toast-title" id="toast-title">Alerta!</div>
                <div class="alert-toast-desc" id="toast-desc">Nova mensagem recebida.</div>
            </div>
        </div>

        <!-- Floating Action Button (FAB) -->
        <button id="fab-new-quote" class="fab-btn" onclick="openNewQuoteModal()" title="Nova Cotação">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            <span>Nova Cotação</span>
        </button>

        <!-- New Quote Modal Overlay & Drawer -->
        <div id="new-quote-overlay" class="modal-overlay" onclick="closeNewQuoteModal()" style="display:none;"></div>
        <div id="new-quote-drawer" class="mobile-drawer" style="display:none;">
            <div class="drawer-header">
                <div class="drawer-title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
                    Incluir Nova Cotação
                </div>
                <button type="button" class="btn-close-drawer" onclick="closeNewQuoteModal()">&times;</button>
            </div>

            <!-- Stepper Navigation -->
            <div class="stepper-bar">
                <div class="step-item active" id="step-nav-1" onclick="goToStep(1)">
                    <span class="step-badge">1</span>
                    <span class="step-label">Cliente</span>
                </div>
                <div class="step-line"></div>
                <div class="step-item" id="step-nav-2" onclick="goToStep(2)">
                    <span class="step-badge">2</span>
                    <span class="step-label">Produtos (<span id="items-badge-count">0</span>)</span>
                </div>
                <div class="step-line"></div>
                <div class="step-item" id="step-nav-3" onclick="goToStep(3)">
                    <span class="step-badge">3</span>
                    <span class="step-label">Condições</span>
                </div>
            </div>

            <div class="drawer-body">
                <!-- STEP 1: Seleção de Cliente -->
                <div id="step-content-1" class="step-panel active">
                    <div class="form-group-mobile">
                        <label class="form-label-mobile">Selecione o Cliente / Parceiro <span style="color:#ef4444;">*</span></label>
                        <input type="text" id="partner-search-input" class="input-mobile" placeholder="🔍 Buscar cliente por nome ou CNPJ/CPF..." oninput="filterPartnerOptions()">
                        <select id="nq-parceiro-select" class="select-mobile" size="5" style="height: 170px; margin-top: 8px;" onchange="onPartnerSelected()">
                            <option value="">Carregando clientes...</option>
                        </select>
                    </div>
                    
                    <div id="selected-partner-card" class="info-card-mobile" style="display:none; margin-top: 10px;">
                        <div style="font-weight:600; color:var(--color-primary);" id="sp-name">-</div>
                        <div style="font-size:12px; color:var(--color-text-muted);" id="sp-doc">-</div>
                    </div>

                    <button type="button" class="btn-primary-mobile" onclick="validateStep1AndNext()" style="margin-top:20px;">
                        Avançar para Adicionar Produtos &rarr;
                    </button>
                </div>

                <!-- STEP 2: Adição de Produtos -->
                <div id="step-content-2" class="step-panel" style="display:none;">
                    <div class="form-group-mobile">
                        <label class="form-label-mobile">Adicionar Produtos ao Carrinho</label>
                        <input type="text" id="prod-search-input" class="input-mobile" placeholder="🔍 Digite código ou nome do produto..." oninput="filterProducts()">
                        
                        <div id="prod-search-results" class="products-list-mobile" style="max-height:170px; overflow-y:auto; margin-top:8px;">
                            <!-- product cards loaded dynamically -->
                        </div>
                    </div>

                    <div style="margin-top: 12px;">
                        <label class="form-label-mobile">Itens Adicionados (<span id="cart-items-count">0</span>)</label>
                        <div id="cart-items-list" class="cart-items-container">
                            <div style="text-align:center; padding:20px; color:var(--color-text-muted); font-size:13px;" id="empty-cart-msg">
                                Nenhum produto adicionado ainda.<br>Pesquise acima e clique em "Adicionar".
                            </div>
                        </div>
                    </div>

                    <div class="cart-total-bar">
                        <span>Subtotal Estimado:</span>
                        <strong id="cart-total-val" style="color:var(--color-primary); font-size:18px;">R$ 0,00</strong>
                    </div>

                    <div style="display:flex; gap:10px; margin-top:16px;">
                        <button type="button" class="btn-secondary-mobile" onclick="goToStep(1)">&larr; Voltar</button>
                        <button type="button" class="btn-primary-mobile" onclick="validateStep2AndNext()">Condições &rarr;</button>
                    </div>
                </div>

                <!-- STEP 3: Condições Comerciais & Envio -->
                <div id="step-content-3" class="step-panel" style="display:none;">
                    <div class="form-group-mobile">
                        <label class="form-label-mobile">Forma de Pagamento</label>
                        <select id="nq-forma-pagamento" class="select-mobile">
                            <option value="A combinar">A combinar</option>
                            <option value="À Vista (PIX / Dinheiro)">À Vista (PIX / Dinheiro)</option>
                            <option value="Faturado 14 dias">Faturado 14 dias</option>
                            <option value="Faturado 28 dias">Faturado 28 dias</option>
                            <option value="Faturado 30/60 dias">Faturado 30/60 dias</option>
                            <option value="Cartão de Crédito">Cartão de Crédito</option>
                        </select>
                    </div>

                    <div class="form-group-mobile">
                        <label class="form-label-mobile">Prazo de Entrega</label>
                        <input type="text" id="nq-prazo-entrega" class="input-mobile" value="3 dias uteis" placeholder="Ex: 3 dias úteis">
                    </div>

                    <div class="form-group-mobile">
                        <label class="form-label-mobile">Tipo de Frete</label>
                        <select id="nq-frete-tipo" class="select-mobile">
                            <option value="CIF">CIF (Frete por conta do emitente)</option>
                            <option value="FOB">FOB (Frete por conta do destinatário)</option>
                        </select>
                    </div>

                    <div class="form-group-mobile">
                        <label class="form-label-mobile">Observação para o Cliente (Opcional)</label>
                        <textarea id="nq-obs-cliente" class="input-mobile" rows="2" placeholder="Ex: Preços válidos enquanto durar o estoque."></textarea>
                    </div>

                    <!-- Resumo da Cotação -->
                    <div class="info-card-mobile" style="background-color:#f0f7ff; border-color:#cbd5e1;">
                        <div style="font-weight:600; font-size:13px; margin-bottom:6px; color:var(--color-primary);">Resumo da Cotação</div>
                        <div style="font-size:12px; display:flex; justify-content:space-between; margin-bottom:4px;">
                            <span>Cliente:</span> <strong id="summary-partner-name">-</strong>
                        </div>
                        <div style="font-size:12px; display:flex; justify-content:space-between; margin-bottom:4px;">
                            <span>Qtd de Itens:</span> <strong id="summary-items-count">0</strong>
                        </div>
                        <div style="font-size:14px; display:flex; justify-content:space-between; margin-top:6px; padding-top:6px; border-top:1px dashed #cbd5e1;">
                            <span>Total da Cotação:</span> <strong id="summary-total-val" style="color:var(--color-primary);">R$ 0,00</strong>
                        </div>
                    </div>

                    <div style="display:flex; gap:10px; margin-top:16px;">
                        <button type="button" class="btn-secondary-mobile" onclick="goToStep(2)">&larr; Voltar</button>
                        <button type="button" id="btn-submit-quote" class="btn-success-mobile" onclick="submitNewQuote()">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                            Gerar Cotação
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
    </div>

    <!-- Scripts -->
    <script>
        const API_URL = "{{ url('/api/v1') }}";
        const PUBLIC_URL = "{{ url('/') }}";
        let quotes = [];
        let filteredQuotesList = [];
        let notifications = [];
        let unreadCount = 0;
        let lastNotificationId = null;

        document.addEventListener("DOMContentLoaded", () => {
            loadData();
            
            // Start Notification Poller (every 10 seconds)
            setInterval(pollNotifications, 10000);
            pollNotifications(true); // first quiet run
        });

        async function loadData() {
            await Promise.all([
                loadQuotes(),
                loadNotifications()
            ]);
        }

        async function loadQuotes() {
            try {
                const res = await fetch(`${API_URL}/cotacoes/todas`);
                if (res.status === 401 || res.status === 403) {
                    window.location.href = `${PUBLIC_URL}/login`;
                    return;
                }
                const data = await res.json();
                if (data.success) {
                    quotes = data.data;
                    filteredQuotesList = [...quotes];
                    renderQuotes();
                    
                    // Bind team value in profile screen using the first quote metadata as backup
                    if (quotes.length > 0 && quotes[0].representante) {
                        const rep = quotes[0].representante;
                        document.getElementById("profile-team-val").innerText = rep.equipe ? rep.equipe.nome : 'Sem Equipe';
                    } else {
                        document.getElementById("profile-team-val").innerText = 'Autônomo / Geral';
                    }
                }
            } catch (e) {
                console.error("Error loading quotes:", e);
            }
        }

        function renderQuotes() {
            const container = document.getElementById("quotes-list-container");
            document.getElementById("quotes-count").innerText = `${filteredQuotesList.length} carregadas`;
            container.innerHTML = "";

            if (filteredQuotesList.length === 0) {
                container.innerHTML = `
                    <div style="text-align: center; color: var(--color-text-muted); padding: 40px 10px;">
                        Nenhuma cotação encontrada no momento.
                    </div>
                `;
                return;
            }

            filteredQuotesList.forEach(q => {
                const dateStr = new Date(q.created_at).toLocaleDateString('pt-BR');
                const statusClass = q.status.toLowerCase().replace(/_/g, '-');
                const statusText = q.status === 'PDF_GERADO' ? 'Liberada (Pendente PDF)' : q.status.replace(/_/g, ' ');
                const valStr = parseFloat(q.total).toLocaleString('pt-BR', { minimumFractionDigits: 2 });
                
                container.innerHTML += `
                    <a href="${PUBLIC_URL}/cotacoes/token/${q.token_representante}" class="quote-card">
                        <div class="card-row-top">
                            <span class="quote-num">${q.numero}</span>
                            <span class="status-badge status-${statusClass}">${statusText}</span>
                        </div>
                        <div class="client-name">${q.parceiro.razao_social}</div>
                        <div class="card-row-bottom">
                            <span class="quote-date">Criada em ${dateStr}</span>
                            <span class="quote-total">R$ ${valStr}</span>
                        </div>
                    </a>
                `;
            });
        }

        let activeStatusFilter = 'ALL';

        function setStatusFilter(status) {
            activeStatusFilter = status;
            
            // Highlight pill
            document.querySelectorAll(".filter-pill").forEach(btn => {
                btn.classList.remove("active");
            });
            event.currentTarget.classList.add("active");
            
            filterQuotes();
        }

        function filterQuotes() {
            const query = document.getElementById("search-input").value.toLowerCase().trim();
            
            filteredQuotesList = quotes.filter(q => {
                // 1. Filter by search query
                const matchesSearch = query === "" || 
                    q.numero.toLowerCase().includes(query) || 
                    q.parceiro.razao_social.toLowerCase().includes(query);
                
                // 2. Filter by status
                let matchesStatus = true;
                if (activeStatusFilter !== 'ALL') {
                    if (activeStatusFilter === 'PENDENTE') {
                        matchesStatus = q.status === 'AGUARDANDO_GESTOR' || q.status === 'COM_DIRETOR';
                    } else if (activeStatusFilter === 'FINALIZADA') {
                        matchesStatus = q.status === 'FINALIZADA_COM_PEDIDO' || q.status === 'FATURADA';
                    } else {
                        matchesStatus = q.status === activeStatusFilter;
                    }
                }
                
                return matchesSearch && matchesStatus;
            });
            
            renderQuotes();
        }

        async function loadNotifications() {
            try {
                const res = await fetch(`${API_URL}/notificacoes`);
                const data = await res.json();
                if (data.success) {
                    notifications = data.data;
                    renderNotifications();
                    updateBadge();
                }
            } catch (e) {
                console.error("Error loading notifications:", e);
            }
        }

        function renderNotifications() {
            const container = document.getElementById("notifications-list-container");
            container.innerHTML = "";

            if (notifications.length === 0) {
                container.innerHTML = `
                    <div style="text-align: center; color: var(--color-text-muted); padding: 40px 10px;">
                        Nenhuma notificação por aqui.
                    </div>
                `;
                return;
            }

            notifications.forEach(n => {
                const timeStr = new Date(n.created_at).toLocaleTimeString('pt-BR', {hour: '2-digit', minute:'2-digit'});
                const dateStr = new Date(n.created_at).toLocaleDateString('pt-BR');
                const unreadClass = n.lida ? '' : 'unread';
                
                container.innerHTML += `
                    <div class="notification-item ${unreadClass}" onclick="openNotification(${n.id}, '${n.link}')">
                        <div class="notification-header">
                            <span class="notification-title">${n.titulo}</span>
                            <span class="notification-time">${dateStr} às ${timeStr}</span>
                        </div>
                        <div class="notification-body">${n.mensagem}</div>
                    </div>
                `;
            });
        }

        function updateBadge() {
            unreadCount = notifications.filter(n => !n.lida).length;
            const badge = document.getElementById("alerts-badge");
            if (unreadCount > 0) {
                badge.innerText = unreadCount;
                badge.style.display = "flex";
            } else {
                badge.style.display = "none";
            }
        }

        // Poll notifications in background
        async function pollNotifications(isFirstRun = false) {
            try {
                const res = await fetch(`${API_URL}/notificacoes`);
                const data = await res.json();
                if (data.success) {
                    const newNotifications = data.data;
                    const prevUnreadCount = unreadCount;
                    
                    notifications = newNotifications;
                    updateBadge();
                    
                    // If alerts tab is active, render list
                    if (document.getElementById("tab-alerts").classList.contains("active")) {
                        renderNotifications();
                    }

                    // Check if there are new unread notifications compared to before
                    const newUnread = newNotifications.filter(n => !n.lida);
                    if (newUnread.length > 0) {
                        const newest = newUnread[0];
                        
                        // If it's a completely new notification we haven't seen in this session
                        if (newest.id !== lastNotificationId) {
                            lastNotificationId = newest.id;
                            
                            // Trigger sound and toast if it's not the initial load of page
                            if (!isFirstRun && newUnread.length > prevUnreadCount) {
                                triggerAlert(newest);
                                loadQuotes(); // refresh quotes list too
                            }
                        }
                    }
                }
            } catch (e) {
                console.error("Polling error:", e);
            }
        }

        // Web Audio API Synthesizer (double beep chime)
        function playNotificationChime() {
            try {
                const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                
                // Beep 1
                const osc1 = audioCtx.createOscillator();
                const gain1 = audioCtx.createGain();
                osc1.type = 'sine';
                osc1.frequency.setValueAtTime(880, audioCtx.currentTime); // A5 note
                gain1.gain.setValueAtTime(0.08, audioCtx.currentTime);
                gain1.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.15);
                osc1.connect(gain1);
                gain1.connect(audioCtx.destination);
                osc1.start();
                osc1.stop(audioCtx.currentTime + 0.15);

                // Beep 2 (slightly higher and after a short delay)
                setTimeout(() => {
                    const osc2 = audioCtx.createOscillator();
                    const gain2 = audioCtx.createGain();
                    osc2.type = 'sine';
                    osc2.frequency.setValueAtTime(1174.66, audioCtx.currentTime); // D6 note
                    gain2.gain.setValueAtTime(0.08, audioCtx.currentTime);
                    gain2.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.22);
                    osc2.connect(gain2);
                    gain2.connect(audioCtx.destination);
                    osc2.start();
                    osc2.stop(audioCtx.currentTime + 0.22);
                }, 110);
            } catch(e) {
                console.log("Audio not allowed yet by user interaction rules:", e);
            }
        }

        // Audio & visual alert sequence
        let activeToastLink = null;
        let activeToastId = null;

        function triggerAlert(notif) {
            // 1. Play premium chime sound
            playNotificationChime();
            
            // 2. Animate tabbar button
            const navBtn = document.getElementById("nav-btn-alerts");
            navBtn.classList.remove("bounce-nav");
            void navBtn.offsetWidth; // trigger reflow
            navBtn.classList.add("bounce-nav");
            
            // 3. Show Toast Banner
            document.getElementById("toast-title").innerText = notif.titulo;
            document.getElementById("toast-desc").innerText = notif.mensagem;
            
            activeToastLink = notif.link;
            activeToastId = notif.id;
            
            const toast = document.getElementById("app-toast");
            toast.classList.add("show");
            
            // Hide toast after 6 seconds
            setTimeout(() => {
                toast.classList.remove("show");
            }, 6000);
        }

        async function onToastClick() {
            if (activeToastId && activeToastLink) {
                await markNotificationAsRead(activeToastId);
                window.location.href = activeToastLink;
            }
        }

        async function openNotification(id, link) {
            await markNotificationAsRead(id);
            if (link) {
                window.location.href = link;
            } else {
                loadNotifications();
            }
        }

        async function markNotificationAsRead(id) {
            try {
                await fetch(`${API_URL}/notificacoes/${id}/ler`, {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
            } catch (e) {
                console.error("Error marking notification as read:", e);
            }
        }

        async function markAllAsRead() {
            try {
                const res = await fetch(`${API_URL}/notificacoes/ler-tudo`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                const data = await res.json();
                if (data.success) {
                    loadNotifications();
                }
            } catch (e) {
                console.error("Error marking all read:", e);
            }
        }

        // Tab Switching Logic
        function switchTab(tabName) {
            // Remove active classes
            document.querySelectorAll(".tab-button").forEach(btn => btn.classList.remove("active"));
            document.querySelectorAll(".tab-content").forEach(content => content.classList.remove("active"));
            
            // Add active class to target tab
            if (tabName === 'quotes') {
                document.getElementById("nav-btn-quotes").classList.add("active");
                document.getElementById("tab-quotes").classList.add("active");
                document.getElementById("search-container").style.display = "block";
                document.getElementById("filters-scroll-container").style.display = "flex";
                loadQuotes();
            } else if (tabName === 'alerts') {
                document.getElementById("nav-btn-alerts").classList.add("active");
                document.getElementById("tab-alerts").classList.add("active");
                document.getElementById("search-container").style.display = "none";
                document.getElementById("filters-scroll-container").style.display = "none";
                document.getElementById("nav-btn-alerts").classList.remove("bounce-nav");
                loadNotifications();
            } else if (tabName === 'profile') {
                document.getElementById("nav-btn-profile").classList.add("active");
                document.getElementById("tab-profile").classList.add("active");
                document.getElementById("search-container").style.display = "none";
                document.getElementById("filters-scroll-container").style.display = "none";
            } else if (tabName === 'checkin') {
                document.getElementById("nav-btn-checkin").classList.add("active");
                document.getElementById("tab-checkin").classList.add("active");
                document.getElementById("search-container").style.display = "none";
                document.getElementById("filters-scroll-container").style.display = "none";
                loadCheckinData();
            }
        }

        // Check-in Feature Logic
        let checkinPartnersLoaded = false;

        async function loadCheckinData() {
            // 1. Get GPS coordinates
            getGPSLocation();
            
            // 2. Load partners list once
            if (!checkinPartnersLoaded) {
                try {
                    const res = await fetch(`${API_URL}/clientes`);
                    const data = await res.json();
                    if (data.success) {
                        const select = document.getElementById("checkin-partner-select");
                        select.innerHTML = '<option value="">-- Selecione o Parceiro --</option>';
                        data.data.forEach(p => {
                            select.innerHTML += `<option value="${p.id}">${p.razao_social} (${p.codigo_sankhya})</option>`;
                        });
                        checkinPartnersLoaded = true;
                    }
                } catch(e) {
                    console.error("Error loading partners for check-in:", e);
                }
            }

            // 3. Load recent check-ins
            loadRecentCheckins();
        }

        function getGPSLocation() {
            const coordsEl = document.getElementById("checkin-coords");
            coordsEl.innerText = "Obtendo coordenadas GPS...";
            coordsEl.removeAttribute("data-lat");
            coordsEl.removeAttribute("data-lng");

            const setFallbackCoords = (reason) => {
                const fallbackLat = -23.550520;
                const fallbackLng = -46.633308;
                coordsEl.innerHTML = `<span style="color:#0f5132; font-weight:600;">📍 Usando Localização da Matriz (Mock): ${fallbackLat}, ${fallbackLng}</span><br><span style="font-size:10px; color:var(--color-text-muted);">Motivo: ${reason}</span>`;
                coordsEl.setAttribute("data-lat", fallbackLat);
                coordsEl.setAttribute("data-lng", fallbackLng);
            };

            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        const acc = position.coords.accuracy;
                        coordsEl.innerHTML = `<span style="color:#0f5132; font-weight:600;">📍 Lat: ${lat.toFixed(6)}, Lng: ${lng.toFixed(6)}</span><br><span style="font-size:11px; color:#157347; font-weight:600;">✓ Precisão de Posicionamento (GPS): ±${Math.round(acc)} metros</span>`;
                        coordsEl.setAttribute("data-lat", lat);
                        coordsEl.setAttribute("data-lng", lng);
                    },
                    (error) => {
                        let msg = "Não foi possível obter a localização.";
                        if (error.code === error.PERMISSION_DENIED) {
                            msg = "Permissão de localização negada.";
                        } else if (error.code === error.POSITION_UNAVAILABLE) {
                            msg = "Sinal GPS indisponível.";
                        } else if (error.code === error.TIMEOUT) {
                            msg = "Tempo limite GPS esgotado.";
                        }
                        setFallbackCoords(msg);
                    },
                    { enableHighAccuracy: true, timeout: 12000, maximumAge: 0 }
                );
            } else {
                setFallbackCoords("GPS não suportado neste navegador.");
            }
        }

        async function performCheckin() {
            const select = document.getElementById("checkin-partner-select");
            const partnerId = select.value;
            const coordsEl = document.getElementById("checkin-coords");
            const lat = coordsEl.getAttribute("data-lat");
            const lng = coordsEl.getAttribute("data-lng");

            if (!partnerId) {
                alert("Por favor, selecione um Cliente/Parceiro.");
                return;
            }

            if (!lat || !lng) {
                alert("Coordenadas GPS ausentes ou não carregadas. Por favor, aguarde.");
                return;
            }

            const btn = document.getElementById("btn-do-checkin");
            const originalText = btn.innerText;
            btn.innerText = "Registrando...";
            btn.disabled = true;

            try {
                const res = await fetch(`${API_URL}/checkin`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        parceiro_id: parseInt(partnerId),
                        latitude: parseFloat(lat),
                        longitude: parseFloat(lng)
                    })
                });

                if (res.status === 419) {
                    alert("Sessão expirada. Por favor, recarregue a página.");
                    btn.innerText = originalText;
                    btn.disabled = false;
                    return;
                }

                const data = await res.json();
                btn.innerText = originalText;
                btn.disabled = false;

                if (data.success) {
                    alert("Visita registrada com sucesso! Check-in concluído.");
                    select.value = "";
                    loadRecentCheckins();
                } else {
                    alert("Erro ao realizar check-in: " + (data.message || data.error));
                }
            } catch(e) {
                console.error("Check-in request error:", e);
                btn.innerText = originalText;
                btn.disabled = false;
                alert("Erro de conexão ao realizar check-in. Verifique o console.");
            }
        }

        async function loadRecentCheckins() {
            const listEl = document.getElementById("recent-checkins-list");
            listEl.innerHTML = '<div style="text-align:center; padding:10px; color:var(--color-text-muted);">Carregando histórico...</div>';

            try {
                const res = await fetch(`${API_URL}/checkin/recentes`);
                const data = await res.json();
                if (data.success) {
                    listEl.innerHTML = "";
                    if (data.data.length === 0) {
                        listEl.innerHTML = '<div style="text-align:center; padding:20px; color:var(--color-text-muted); font-size:12px;">Nenhuma visita recente registrada.</div>';
                        return;
                    }
                    data.data.forEach(c => {
                        const date = new Date(c.created_at).toLocaleString('pt-BR');
                        listEl.innerHTML += `
                            <div class="checkin-item">
                                <div class="checkin-item-left">
                                    <span class="checkin-item-title">${c.parceiro.razao_social}</span>
                                    <span class="checkin-item-date">Visita em ${date}</span>
                                    <span class="checkin-item-coords">📍 Lat: ${parseFloat(c.latitude).toFixed(5)}, Lng: ${parseFloat(c.longitude).toFixed(5)}</span>
                                </div>
                                <div class="checkin-item-right">
                                    <a href="https://maps.google.com/?q=${c.latitude},${c.longitude}" target="_blank">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="3 11 22 2 13 21 11 13 3 11"/></svg>
                                        Mapa
                                    </a>
                                </div>
                            </div>
                        `;
                    });
                }
            } catch(e) {
                console.error("Error loading recent checkins:", e);
                listEl.innerHTML = '<div style="text-align:center; padding:10px; color:var(--status-perdida);">Erro ao carregar histórico.</div>';
            }
        }

        function appGoBack() {
            if (window.history.length > 1 && document.referrer && document.referrer.includes(window.location.host)) {
                window.history.back();
            } else {
                window.location.href = "{{ url('/login') }}";
            }
        }

        // ==========================================
        // NEW QUOTATION MOBILE WIZARD LOGIC
        // ==========================================
        let allPartnersList = [];
        let allProductsList = [];
        let quoteCartItems = []; // [{ product_id, sku, name, unit, price, qty }]
        let selectedPartner = null;
        let currentWizardStep = 1;
        let isProductsLoading = false;

        async function openNewQuoteModal() {
            document.getElementById("new-quote-overlay").style.display = "block";
            document.getElementById("new-quote-drawer").style.display = "flex";
            
            // Reset wizard
            currentWizardStep = 1;
            quoteCartItems = [];
            selectedPartner = null;
            document.getElementById("partner-search-input").value = "";
            document.getElementById("prod-search-input").value = "";
            document.getElementById("nq-obs-cliente").value = "";
            document.getElementById("selected-partner-card").style.display = "none";
            
            updateStepView();
            renderCart();
            
            // Force load partners and products afresh
            await Promise.all([
                loadPartnersForQuote(),
                loadProductsForQuote(true)
            ]);
        }

        function closeNewQuoteModal() {
            document.getElementById("new-quote-overlay").style.display = "none";
            document.getElementById("new-quote-drawer").style.display = "none";
        }

        function goToStep(stepNum) {
            if (stepNum > 1 && !selectedPartner) {
                alert("Por favor, selecione um cliente primeiro.");
                return;
            }
            if (stepNum > 2 && quoteCartItems.length === 0) {
                alert("Por favor, adicione pelo menos 1 produto à cotação.");
                return;
            }
            currentWizardStep = stepNum;
            updateStepView();
        }

        function normalizeStr(str) {
            if (!str) return '';
            return String(str)
                .normalize("NFD")
                .replace(/[\u0300-\u036f]/g, "")
                .toLowerCase();
        }

        function updateStepView() {
            // Update step badges
            [1, 2, 3].forEach(step => {
                const navEl = document.getElementById(`step-nav-${step}`);
                const panelEl = document.getElementById(`step-content-${step}`);
                if (step === currentWizardStep) {
                    navEl.classList.add("active");
                    panelEl.style.display = "block";
                } else {
                    navEl.classList.remove("active");
                    panelEl.style.display = "none";
                }
            });

            if (currentWizardStep === 2) {
                filterProducts();
            }

            // Update summary if step 3
            if (currentWizardStep === 3) {
                document.getElementById("summary-partner-name").innerText = selectedPartner ? selectedPartner.razao_social : "-";
                document.getElementById("summary-items-count").innerText = quoteCartItems.length;
                const total = quoteCartItems.reduce((acc, item) => acc + (item.price * item.qty), 0);
                document.getElementById("summary-total-val").innerText = `R$ ${total.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
            }
        }

        function validateStep1AndNext() {
            const select = document.getElementById("nq-parceiro-select");
            const val = select.value;
            if (!val) {
                alert("Selecione um cliente para continuar.");
                return;
            }
            selectedPartner = allPartnersList.find(p => p.id == val);
            if (!selectedPartner) {
                alert("Cliente inválido selecionado.");
                return;
            }
            goToStep(2);
        }

        function validateStep2AndNext() {
            if (quoteCartItems.length === 0) {
                alert("Adicione pelo menos 1 produto ao carrinho.");
                return;
            }
            goToStep(3);
        }

        async function loadPartnersForQuote() {
            try {
                const select = document.getElementById("nq-parceiro-select");
                if (select) {
                    select.innerHTML = '<option value="">Carregando clientes...</option>';
                }
                const res = await fetch(`${API_URL}/clientes`);
                const data = await res.json();
                if (data.success && Array.isArray(data.data)) {
                    allPartnersList = data.data;
                } else if (Array.isArray(data)) {
                    allPartnersList = data;
                } else {
                    allPartnersList = [];
                }
                renderPartnerSelectOptions(allPartnersList);
            } catch(e) {
                console.error("Error loading partners for quote:", e);
                const select = document.getElementById("nq-parceiro-select");
                if (select) {
                    select.innerHTML = '<option value="">Erro ao carregar clientes</option>';
                }
            }
        }

        function renderPartnerSelectOptions(list) {
            const select = document.getElementById("nq-parceiro-select");
            if (!select) return;
            select.innerHTML = '<option value="">-- Selecione o Cliente --</option>';
            if (!list || list.length === 0) {
                select.innerHTML += '<option value="" disabled>Nenhum cliente encontrado</option>';
                return;
            }
            list.forEach(p => {
                const code = p.codigo_sankhya ? ` [Sankhya: ${p.codigo_sankhya}]` : '';
                const doc = p.cnpj_cpf ? ` - ${p.cnpj_cpf}` : '';
                select.innerHTML += `<option value="${p.id}">${p.razao_social}${code}${doc}</option>`;
            });
        }

        function filterPartnerOptions() {
            const rawQuery = document.getElementById("partner-search-input").value;
            const normalizedQuery = normalizeStr(rawQuery).trim();

            if (!allPartnersList || allPartnersList.length === 0) {
                renderPartnerSelectOptions([]);
                return;
            }

            if (!normalizedQuery) {
                renderPartnerSelectOptions(allPartnersList);
                return;
            }

            const terms = normalizedQuery.split(/\s+/).filter(Boolean);

            const filtered = allPartnersList.filter(p => {
                const searchables = [
                    normalizeStr(p.razao_social),
                    normalizeStr(p.nome_fantasia),
                    normalizeStr(p.cnpj_cpf),
                    normalizeStr(p.codigo_sankhya)
                ].join(" ");

                return terms.every(term => searchables.includes(term));
            });

            renderPartnerSelectOptions(filtered);
        }

        function onPartnerSelected() {
            const val = document.getElementById("nq-parceiro-select").value;
            const card = document.getElementById("selected-partner-card");
            if (!val) {
                selectedPartner = null;
                if (card) card.style.display = "none";
                return;
            }
            selectedPartner = allPartnersList.find(p => p.id == val);
            if (selectedPartner && card) {
                document.getElementById("sp-name").innerText = selectedPartner.razao_social;
                document.getElementById("sp-doc").innerText = `CNPJ/CPF: ${selectedPartner.cnpj_cpf || 'Não informado'} | Código: ${selectedPartner.codigo_sankhya || 'N/A'}`;
                card.style.display = "block";
            }
        }

        async function loadProductsForQuote(forceReload = false) {
            if (allProductsList && allProductsList.length > 0 && !forceReload) {
                filterProducts();
                return;
            }

            isProductsLoading = true;
            const container = document.getElementById("prod-search-results");
            if (container) {
                container.innerHTML = '<div style="font-size:13px; color:var(--color-primary); text-align:center; padding:16px; font-weight:600;">🔄 Carregando catálogo de produtos...</div>';
            }

            try {
                const res = await fetch(`${API_URL}/produtos`);
                const data = await res.json();
                isProductsLoading = false;

                if (data.success && Array.isArray(data.data)) {
                    allProductsList = data.data;
                } else if (Array.isArray(data)) {
                    allProductsList = data;
                } else if (data.data && Array.isArray(data.data)) {
                    allProductsList = data.data;
                } else {
                    allProductsList = [];
                }

                filterProducts();
            } catch(e) {
                isProductsLoading = false;
                console.error("Error loading products:", e);
                if (container) {
                    container.innerHTML = `
                        <div style="text-align:center; padding:14px; background:#fef2f2; border-radius:8px; border:1px solid #fca5a5;">
                            <div style="font-size:13px; color:#b91c1c; font-weight:600; margin-bottom:6px;">Erro ao carregar catálogo.</div>
                            <button type="button" onclick="loadProductsForQuote(true)" style="background:#ef4444; color:white; border:none; padding:6px 12px; border-radius:6px; font-size:12px; font-weight:600; cursor:pointer;">🔄 Tentar Novamente</button>
                        </div>
                    `;
                }
            }
        }

        function filterProducts() {
            const rawQuery = document.getElementById("prod-search-input").value;
            const normalizedQuery = normalizeStr(rawQuery).trim();

            if (isProductsLoading) {
                return;
            }

            if (!allProductsList || allProductsList.length === 0) {
                renderProductSearchResults([]);
                return;
            }

            if (!normalizedQuery) {
                renderProductSearchResults(allProductsList.slice(0, 30));
                return;
            }

            const terms = normalizedQuery.split(/\s+/).filter(Boolean);

            const filtered = allProductsList.filter(p => {
                const searchables = [
                    normalizeStr(p.descricao),
                    normalizeStr(p.codigo_sankhya),
                    normalizeStr(p.codprod),
                    normalizeStr(p.marca),
                    normalizeStr(p.complemento),
                    normalizeStr(p.referencia)
                ].join(" ");

                return terms.every(term => searchables.includes(term));
            });

            renderProductSearchResults(filtered);
        }

        function renderProductSearchResults(products) {
            const container = document.getElementById("prod-search-results");
            const query = document.getElementById("prod-search-input").value.trim();

            if (!container) return;

            if (!allProductsList || allProductsList.length === 0) {
                container.innerHTML = `
                    <div style="text-align:center; padding:14px; background:#f8fafc; border-radius:8px; border:1px dashed var(--color-border);">
                        <div style="font-size:12px; color:var(--color-text-muted); margin-bottom:8px;">Nenhum produto carregado no sistema.</div>
                        <button type="button" onclick="loadProductsForQuote(true)" style="background:var(--color-primary); color:white; border:none; padding:6px 12px; border-radius:6px; font-size:12px; font-weight:600; cursor:pointer;">🔄 Recarregar Produtos</button>
                    </div>
                `;
                return;
            }

            if (!products || products.length === 0) {
                container.innerHTML = `
                    <div style="text-align:center; padding:14px; background:#f8fafc; border-radius:8px; border:1px dashed var(--color-border);">
                        <div style="font-size:12px; color:var(--color-text-muted); margin-bottom:8px;">Nenhum produto encontrado para "<strong>${query}</strong>".</div>
                        <button type="button" onclick="clearProdSearch()" style="background:#e2e8f0; color:#334155; border:none; padding:6px 12px; border-radius:6px; font-size:12px; font-weight:600; cursor:pointer;">Limpar Pesquisa e Ver Todos</button>
                    </div>
                `;
                return;
            }

            container.innerHTML = "";
            products.forEach(p => {
                const price = parseFloat(p.preco_tabela || 0);
                const priceStr = price > 0 ? `R$ ${price.toLocaleString('pt-BR', {minimumFractionDigits: 2})}` : 'Sob consulta';
                const codeStr = p.codigo_sankhya || p.codprod || p.id;
                const brandStr = p.marca ? ` | ${p.marca}` : '';
                container.innerHTML += `
                    <div class="product-item-row">
                        <div style="flex-grow:1; padding-right:8px;">
                            <div class="product-item-title">${p.descricao}</div>
                            <div class="product-item-price">Cód: ${codeStr}${brandStr} &bull; ${priceStr}</div>
                        </div>
                        <button type="button" class="btn-add-prod" onclick="addToCart(${p.id})">+ Adicionar</button>
                    </div>
                `;
            });
        }

        function clearProdSearch() {
            document.getElementById("prod-search-input").value = "";
            filterProducts();
        }

        function addToCart(prodId) {
            const prod = allProductsList.find(p => p.id == prodId);
            if (!prod) return;

            const existingIndex = quoteCartItems.findIndex(item => item.product_id == prodId);
            if (existingIndex >= 0) {
                quoteCartItems[existingIndex].qty += 1;
            } else {
                quoteCartItems.push({
                    product_id: prod.id,
                    sku: prod.codigo_sankhya || '',
                    name: prod.descricao,
                    unit: prod.unidade_medida || 'UN',
                    price: parseFloat(prod.preco_tabela || 0),
                    qty: 1
                });
            }
            renderCart();
        }

        function updateCartQty(index, delta) {
            if (!quoteCartItems[index]) return;
            quoteCartItems[index].qty += delta;
            if (quoteCartItems[index].qty <= 0) {
                quoteCartItems.splice(index, 1);
            }
            renderCart();
        }

        function updateCartPrice(index, val) {
            if (!quoteCartItems[index]) return;
            const parsed = parseFloat(val.replace(',', '.'));
            if (!isNaN(parsed) && parsed >= 0) {
                quoteCartItems[index].price = parsed;
            }
            renderCartTotalOnly();
        }

        function removeFromCart(index) {
            quoteCartItems.splice(index, 1);
            renderCart();
        }

        function renderCartTotalOnly() {
            const total = quoteCartItems.reduce((acc, item) => acc + (item.price * item.qty), 0);
            const el = document.getElementById("cart-total-val");
            if (el) el.innerText = `R$ ${total.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        }

        function renderCart() {
            const listEl = document.getElementById("cart-items-list");
            const countEl = document.getElementById("cart-items-count");
            const badgeCountEl = document.getElementById("items-badge-count");
            
            if (countEl) countEl.innerText = quoteCartItems.length;
            if (badgeCountEl) badgeCountEl.innerText = quoteCartItems.length;

            if (quoteCartItems.length === 0) {
                if (listEl) {
                    listEl.innerHTML = `
                        <div style="text-align:center; padding:20px; color:var(--color-text-muted); font-size:13px;" id="empty-cart-msg">
                            Nenhum produto adicionado ainda.<br>Pesquise acima e clique em "Adicionar".
                        </div>
                    `;
                }
                renderCartTotalOnly();
                return;
            }

            if (listEl) {
                listEl.innerHTML = "";
                quoteCartItems.forEach((item, index) => {
                    const subtotal = item.price * item.qty;
                    listEl.innerHTML += `
                        <div class="cart-item-card">
                            <div class="cart-item-top">
                                <div>
                                    <div style="font-weight:600; font-size:13px; color:var(--color-text);">${item.name}</div>
                                    <div style="font-size:11px; color:var(--color-text-muted);">Cód: ${item.sku || 'N/A'} (${item.unit})</div>
                                </div>
                                <button type="button" onclick="removeFromCart(${index})" style="background:none; border:none; color:#ef4444; font-size:16px; cursor:pointer; font-weight:bold;">&times;</button>
                            </div>
                            <div class="cart-item-controls">
                                <div class="qty-stepper">
                                    <button type="button" class="btn-stepper" onclick="updateCartQty(${index}, -1)">-</button>
                                    <span style="font-weight:700; font-size:14px;">${item.qty}</span>
                                    <button type="button" class="btn-stepper" onclick="updateCartQty(${index}, 1)">+</button>
                                </div>
                                <div style="display:flex; align-items:center; gap:6px;">
                                    <span style="font-size:11px; color:var(--color-text-muted);">R$/un:</span>
                                    <input type="number" step="0.01" value="${item.price.toFixed(2)}" oninput="updateCartPrice(${index}, this.value)" style="width:75px; padding:4px 6px; border:1px solid var(--color-border); border-radius:6px; font-size:12px; font-weight:600; text-align:right;">
                                </div>
                            </div>
                            <div style="font-size:12px; text-align:right; color:var(--color-primary); font-weight:600;">
                                Subtotal: R$ ${subtotal.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})}
                            </div>
                        </div>
                    `;
                });
            }

            renderCartTotalOnly();
        }

        async function submitNewQuote() {
            if (!selectedPartner) {
                alert("Por favor, selecione um cliente no Passo 1.");
                goToStep(1);
                return;
            }
            if (quoteCartItems.length === 0) {
                alert("Por favor, adicione pelo menos 1 produto no Passo 2.");
                goToStep(2);
                return;
            }

            const btn = document.getElementById("btn-submit-quote");
            const origText = btn.innerHTML;
            btn.innerText = "Gerando Cotação...";
            btn.disabled = true;

            const repId = Number("{{ auth()->user()->id }}");
            const payload = {
                parceiro_id: selectedPartner.id,
                representante_id: repId,
                forma_pagamento: document.getElementById("nq-forma-pagamento").value,
                prazo_entrega: document.getElementById("nq-prazo-entrega").value,
                frete_tipo: document.getElementById("nq-frete-tipo").value,
                observacao_cliente: document.getElementById("nq-obs-cliente").value,
                itens: quoteCartItems.map(item => ({
                    produto_id: item.product_id,
                    qtd: item.qty,
                    preco_unit_proposto: item.price
                }))
            };

            try {
                const res = await fetch(`${API_URL}/cotacoes/manual`, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(payload)
                });

                const data = await res.json();
                btn.innerHTML = origText;
                btn.disabled = false;

                if (res.ok || data.id || data.success) {
                    closeNewQuoteModal();
                    alert("✅ Cotação criada com sucesso!");
                    loadQuotes(); // Refresh quotes list
                    switchTab('quotes');
                } else {
                    alert("Erro ao criar cotação: " + (data.message || data.error || JSON.stringify(data)));
                }
            } catch(e) {
                console.error("Error submitting quote:", e);
                btn.innerHTML = origText;
                btn.disabled = false;
                alert("Erro de conexão ao enviar cotação.");
            }
        }
    </script>
</body>
</html>
