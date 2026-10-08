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

        .btn-mark-item-read {
            background: #e0f2fe;
            color: #0369a1;
            border: 1px solid #bae6fd;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s ease;
            white-space: nowrap;
        }
        .btn-mark-item-read:hover {
            background: #0284c7;
            color: #ffffff;
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

        /* App Toast System */
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
            background: #059669;
            border: 1px solid #10b981;
        }

        .app-toast-error {
            background: #dc2626;
            border: 1px solid #ef4444;
        }

        .app-toast-warning {
            background: #d97706;
            border: 1px solid #f59e0b;
        }

        .app-toast-info {
            background: #2563eb;
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
        .partner-item-card {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 14px;
            background: #ffffff;
            border: 1px solid var(--color-border);
            border-radius: 12px;
            cursor: pointer;
            transition: background 0.15s, border-color 0.15s, transform 0.1s;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }
        .partner-item-card:active {
            background: #f0f7ff;
            border-color: var(--color-primary);
            transform: scale(0.99);
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
            gap: 8px;
            overflow-y: auto;
            margin-top: 4px;
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
                    <div class="logo-title">Zé <span>Cotação</span></div>
                </div>
                <div class="user-info">
                    <button type="button" onclick="switchTab('alerts')" style="background:none; border:none; color:white; cursor:pointer; position:relative; display:flex; align-items:center; justify-content:center; padding:4px 6px; border-radius:8px;" title="Ver Alertas">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                        <div id="header-alerts-badge" class="notification-dot" style="display:none; top:-2px; right:-2px;">0</div>
                    </button>
                    <span id="header-user-name">{{ auth()->user()->nome }}</span>
                    <span class="role-badge">Representante</span>
                </div>
            </div>
            
            <div class="search-box-container" id="search-container" style="margin-bottom: 8px;">
                <input type="text" id="search-input" class="search-input" placeholder="Buscar cotação por Nº ou cliente..." oninput="filterQuotes()">
                <svg class="search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            </div>

            <div class="filters-scroll" id="filters-scroll-container" style="display: flex; gap: 8px; overflow-x: auto; scrollbar-width: none; -ms-overflow-style: none; padding-top: 4px;">
                <button type="button" class="filter-pill active" id="pill-filter-ativas" onclick="setStatusFilter('ATIVAS')">Ativas <span id="count-pill-ativas" style="font-size: 11px; opacity: 0.9;">(0)</span></button>
                <button type="button" class="filter-pill" id="pill-filter-rascunhos" onclick="setStatusFilter('EM_CRIACAO')">Rascunhos <span id="count-pill-rascunhos" style="font-size: 11px; opacity: 0.9;">(0)</span></button>
                <button type="button" class="filter-pill" id="pill-filter-pendentes" onclick="setStatusFilter('PENDENTE')">Pendentes <span id="count-pill-pendentes" style="font-size: 11px; opacity: 0.9;">(0)</span></button>
                <button type="button" class="filter-pill" id="pill-filter-aprovadas" onclick="setStatusFilter('APROVADA')">Aprovadas <span id="count-pill-aprovadas" style="font-size: 11px; opacity: 0.9;">(0)</span></button>
                <button type="button" class="filter-pill" id="pill-filter-encerradas" onclick="setStatusFilter('ENCERRADAS')">Encerradas <span id="count-pill-encerradas" style="font-size: 11px; opacity: 0.9;">(0)</span></button>
                <button type="button" class="filter-pill" id="pill-filter-todas" onclick="setStatusFilter('ALL')">Todas <span id="count-pill-todas" style="font-size: 11px; opacity: 0.9;">(0)</span></button>
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
        <div id="new-quote-overlay" class="modal-overlay" onclick="handleDrawerClose()" style="display:none;"></div>
        <div id="new-quote-drawer" class="mobile-drawer" style="display:none;">
            <div class="drawer-header">
                <div class="drawer-title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
                    Incluir Nova Cotação
                </div>
                <div style="display:flex; align-items:center; gap:8px;">
                    <button type="button" onclick="saveDraftQuote(false)" style="background:#0284c7; color:white; border:none; padding:5px 10px; border-radius:6px; font-size:12px; font-weight:600; cursor:pointer; display:flex; align-items:center; gap:4px; box-shadow:0 1px 3px rgba(0,0,0,0.15);">
                        💾 Salvar Rascunho
                    </button>
                    <button type="button" class="btn-close-drawer" onclick="handleDrawerClose()">&times;</button>
                </div>
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
                        <input type="text" id="partner-search-input" class="input-mobile" placeholder="🔍 Buscar cliente por nome, CNPJ ou código..." oninput="filterPartnerOptions()" onkeyup="filterPartnerOptions()">
                        
                        <!-- Selected Client Confirmation Card -->
                        <div id="selected-partner-card" style="display:none; margin-top:10px; background:#f0fdf4; border:1px solid #86efac; border-radius:12px; padding:12px 14px;">
                            <div style="display:flex; justify-content:space-between; align-items:center;">
                                <div>
                                    <div style="font-size:11px; font-weight:700; color:#166534; text-transform:uppercase; letter-spacing:0.5px; display:flex; align-items:center; gap:4px;">
                                        <span>✓</span> Cliente Selecionado
                                    </div>
                                    <div style="font-weight:700; font-size:14px; color:#14532d; margin-top:3px;" id="sp-name">-</div>
                                    <div style="font-size:11px; color:#15803d; margin-top:2px;" id="sp-doc">-</div>
                                </div>
                                <button type="button" onclick="clearSelectedPartner()" style="background:#dcfce7; color:#15803d; border:1px solid #86efac; padding:6px 12px; border-radius:8px; font-size:12px; font-weight:700; cursor:pointer;">Alterar</button>
                            </div>
                        </div>

                        <!-- Results List Container (Replaces old HTML select) -->
                        <div id="partner-search-results" class="products-list-mobile" style="max-height:220px; overflow-y:auto; margin-top:8px; gap:8px;">
                            <!-- partner micro-cards loaded dynamically -->
                        </div>
                    </div>

                    <button type="button" class="btn-primary-mobile" onclick="validateStep1AndNext()" style="margin-top:16px;">
                        Avançar para Adicionar Produtos &rarr;
                    </button>
                </div>

                <!-- STEP 2: Adição e Gestão de Produtos -->
                <div id="step-content-2" class="step-panel" style="display:none;">
                    <!-- Navegação entre Catálogo e Pedido -->
                    <div style="display:flex; background:#f1f5f9; border:1px solid var(--color-border); border-radius:12px; padding:3px; gap:4px; margin-bottom:12px;">
                        <button type="button" id="tab-btn-catalog" onclick="switchStep2Tab('catalog')" style="flex:1; border:none; padding:9px 12px; border-radius:9px; font-weight:700; font-size:13px; cursor:pointer; background:#ffffff; color:var(--color-primary); box-shadow:0 1px 3px rgba(0,0,0,0.08); display:flex; align-items:center; justify-content:center; gap:6px; transition:all 0.15s;">
                            <span>🔍</span> Adicionar Produtos
                        </button>
                        <button type="button" id="tab-btn-cart" onclick="switchStep2Tab('cart')" style="flex:1; border:none; padding:9px 12px; border-radius:9px; font-weight:600; font-size:13px; cursor:pointer; background:transparent; color:#64748b; display:flex; align-items:center; justify-content:center; gap:6px; transition:all 0.15s;">
                            <span>🛒</span> Pedido (<span id="cart-tab-badge" style="font-weight:700;">0</span>)
                        </button>
                    </div>

                    <!-- VIEW 1: Catálogo / Busca de Produtos -->
                    <div id="step2-view-catalog">
                        <div class="form-group-mobile" style="margin-bottom:8px;">
                            <div style="position:relative;">
                                <input type="text" id="prod-search-input" class="input-mobile" placeholder="🔍 Digite código ou nome do produto..." oninput="filterProducts()" style="padding-right:36px;">
                                <button type="button" onclick="clearProdSearch()" style="position:absolute; right:8px; top:50%; transform:translateY(-50%); background:none; border:none; color:#94a3b8; font-size:18px; cursor:pointer; padding:4px;" title="Limpar busca">&times;</button>
                            </div>
                        </div>

                        <!-- Lista de produtos com boa altura livre e scroll suave -->
                        <div id="prod-search-results" class="products-list-mobile" style="max-height: 44vh; min-height: 200px; overflow-y:auto; margin-bottom:12px; padding-right:2px;">
                            <!-- product cards loaded dynamically -->
                        </div>

                        <!-- Barra de Resumo Rápido no rodapé do Catálogo -->
                        <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:12px; padding:10px 14px; display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; box-shadow:0 2px 5px rgba(22,101,52,0.06);">
                            <div>
                                <div style="font-size:11px; font-weight:700; color:#166534; text-transform:uppercase; letter-spacing:0.4px;">No Pedido</div>
                                <div style="font-size:14px; font-weight:800; color:#14532d; margin-top:1px;">
                                    <span id="cat-summary-count">0</span> itens &bull; <span id="cat-summary-val">R$ 0,00</span>
                                </div>
                            </div>
                            <button type="button" onclick="switchStep2Tab('cart')" style="background:#16a34a; color:white; border:none; padding:8px 14px; border-radius:8px; font-weight:700; font-size:12px; cursor:pointer; display:flex; align-items:center; gap:6px; box-shadow:0 2px 4px rgba(22,163,74,0.3);">
                                🛒 Ver Pedido &rarr;
                            </button>
                        </div>

                        <div style="display:flex; gap:8px;">
                            <button type="button" class="btn-secondary-mobile" onclick="goToStep(1)">&larr; Voltar</button>
                            <button type="button" style="background:#0284c7; color:white; border:none; padding:8px 12px; border-radius:8px; font-weight:600; font-size:12px; cursor:pointer;" onclick="saveDraftQuote(false)">💾 Salvar</button>
                            <button type="button" class="btn-primary-mobile" style="flex:1;" onclick="validateStep2AndNext()">Condições &rarr;</button>
                        </div>
                    </div>

                    <!-- VIEW 2: Pedido / Carrinho de Conferência (Alta Densidade p/ até 50 itens) -->
                    <div id="step2-view-cart" style="display:none;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                            <span style="font-size:13px; font-weight:700; color:var(--color-text);">Itens no Pedido (<span id="cart-items-count">0</span>)</span>
                            <button type="button" onclick="clearCartConfirm()" style="background:#fee2e2; border:none; color:#b91c1c; font-size:11px; font-weight:700; padding:4px 8px; border-radius:6px; cursor:pointer;">Limpar Tudo</button>
                        </div>

                        <!-- Busca rápida dentro do pedido para quando tiver muitos itens -->
                        <div style="margin-bottom:8px;">
                            <input type="text" id="cart-filter-input" class="input-mobile" placeholder="🔍 Filtrar entre os itens lançados..." oninput="filterCartDisplay(this.value)" style="padding:8px 12px; font-size:12.5px;">
                        </div>

                        <div id="cart-items-list" class="cart-items-container" style="max-height: 44vh; min-height: 200px; overflow-y:auto; padding-right:2px;">
                            <div style="text-align:center; padding:24px 12px; color:var(--color-text-muted); font-size:13px;" id="empty-cart-msg">
                                Nenhum produto adicionado ainda.<br>Clique em "Adicionar Produtos" para escolher do catálogo.
                            </div>
                        </div>

                        <div class="cart-total-bar" style="margin-top:10px; margin-bottom:12px; padding:10px 14px;">
                            <div>
                                <span style="font-size:11px; color:#1e40af; font-weight:600; display:block;">SUBTOTAL ESTIMADO</span>
                                <strong id="cart-total-val" style="color:var(--color-primary); font-size:17px;">R$ 0,00</strong>
                            </div>
                            <button type="button" onclick="switchStep2Tab('catalog')" style="background:#e0f2fe; color:#0369a1; border:1px solid #bae6fd; padding:6px 12px; border-radius:8px; font-size:12px; font-weight:700; cursor:pointer;">
                                + Adicionar Mais
                            </button>
                        </div>

                        <div style="display:flex; gap:8px;">
                            <button type="button" class="btn-secondary-mobile" onclick="switchStep2Tab('catalog')">&larr; Catálogo</button>
                            <button type="button" style="background:#0284c7; color:white; border:none; padding:8px 12px; border-radius:8px; font-weight:600; font-size:12px; cursor:pointer;" onclick="saveDraftQuote(false)">💾 Salvar</button>
                            <button type="button" class="btn-primary-mobile" style="flex:1;" onclick="validateStep2AndNext()">Condições &rarr;</button>
                        </div>
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

                    <!-- Inline Error Banner for New Quote -->
                    <div id="new-quote-error-banner" style="display:none; margin-top:14px; padding:12px 14px; background:#fef2f2; border:1px solid #f87171; border-radius:12px; color:#991b1b; font-size:13px; line-height:1.4; box-shadow:0 2px 6px rgba(239,68,68,0.12);">
                        <div style="font-weight:700; display:flex; align-items:center; gap:6px;">
                            <span style="font-size:16px;">⚠️</span>
                            <span id="new-quote-error-title">Atenção ao gerar cotação</span>
                        </div>
                        <div id="new-quote-error-msg" style="margin-top:4px; font-size:12px; color:#b91c1c;"></div>
                    </div>

                    <div style="display:flex; gap:8px; margin-top:16px;">
                        <button type="button" class="btn-secondary-mobile" onclick="goToStep(2)">&larr; Voltar</button>
                        <button type="button" id="btn-draft-quote" style="background:#0284c7; color:white; border:none; padding:8px 12px; border-radius:8px; font-weight:600; font-size:12px; cursor:pointer;" onclick="saveDraftQuote(false)">💾 Rascunho</button>
                        <button type="button" id="btn-submit-quote" class="btn-success-mobile" style="flex:1;" onclick="submitNewQuote()">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                            Gerar Cotação
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
    </div>

    <!-- Toast Container -->
    <div id="toast-container" class="toast-container"></div>

    <!-- Scripts -->
    <script>
        // Responsive In-Screen Toast Notifications with message deduplication
        let lastDashboardToastMsg = "";
        let lastDashboardToastTime = 0;

        function showToast(message, type = 'info', title = null) {
            const now = Date.now();
            const strMsg = String(message || '').trim();
            if (strMsg === lastDashboardToastMsg && (now - lastDashboardToastTime) < 2000) {
                return;
            }
            lastDashboardToastMsg = strMsg;
            lastDashboardToastTime = now;

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

        const API_URL = "/api/v1";
        const PUBLIC_URL = "";
        let quotes = [];
        let filteredQuotesList = [];
        let notifications = [];
        let unreadCount = 0;
        let lastNotificationId = null;

        document.addEventListener("DOMContentLoaded", () => {
            loadData();

            // Sincroniza aba solicitada via parâmetro de URL (?tab=quotes|checkin|alerts|profile)
            const urlParams = new URLSearchParams(window.location.search);
            const tabParam = urlParams.get('tab');
            if (tabParam && ['quotes', 'checkin', 'alerts', 'profile'].includes(tabParam)) {
                switchTab(tabParam);
            }
            
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

        function getFriendlyStatusInfo(status) {
            const map = {
                'EM_CRIACAO': { label: 'Em criação', color: '#475569', bg: '#f1f5f9', border: '#cbd5e1' },
                'AGUARDANDO_GESTOR': { label: 'Em análise (Gestor)', color: '#92400e', bg: '#fef3c7', border: '#fcd34d' },
                'COM_DIRETOR': { label: 'Em análise (Diretoria)', color: '#9a3412', bg: '#ffedd5', border: '#fdba74' },
                'DEVOLVIDA': { label: 'Devolvida', color: '#991b1b', bg: '#fee2e2', border: '#fca5a5' },
                'APROVADA': { label: 'Aprovada (Pendente PDF)', color: '#166534', bg: '#dcfce7', border: '#86efac' },
                'PDF_GERADO': { label: 'PDF Gerado', color: '#115e59', bg: '#ccfbf1', border: '#5eead4' },
                'AGUARDANDO_PEDIDO': { label: 'Aguardando pedido', color: '#075985', bg: '#e0f2fe', border: '#7dd3fc' },
                'FINALIZADA_COM_PEDIDO': { label: 'Pedido registrado', color: '#3730a3', bg: '#e0e7ff', border: '#a5b4fc' },
                'FATURADA': { label: 'Faturada', color: '#14532d', bg: '#dcfce7', border: '#86efac' },
                'PERDIDA': { label: 'Perdida', color: '#b91c1c', bg: '#fee2e2', border: '#fca5a5' },
                'EXPIRADA': { label: 'Expirada', color: '#64748b', bg: '#f1f5f9', border: '#cbd5e1' }
            };
            return map[status] || { label: (status || '').replace(/_/g, ' '), color: '#475569', bg: '#f1f5f9', border: '#cbd5e1' };
        }

        function getCardValidityBadge(validityDateStr, status) {
            if (['FATURADA', 'FINALIZADA_COM_PEDIDO', 'PERDIDA'].includes(status)) {
                return '';
            }
            if (!validityDateStr) return '';
            const now = new Date();
            const valDate = new Date(validityDateStr);
            const diffMs = valDate.getTime() - now.getTime();
            if (diffMs <= 0 || status === 'EXPIRADA') {
                return '<span style="font-size:11px; color:#ef4444; font-weight:600; display:inline-flex; align-items:center; gap:2px;">⚠️ Expirada</span>';
            }
            const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
            const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));
            if (diffHours < 24) {
                const timeStr = valDate.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
                return `<span style="font-size:11px; color:#d97706; font-weight:600; display:inline-flex; align-items:center; gap:2px;">⏳ vence hoje ${timeStr}</span>`;
            } else if (diffDays === 1) {
                return '<span style="font-size:11px; color:#2563eb; font-weight:600; display:inline-flex; align-items:center; gap:2px;">⏳ vence amanhã</span>';
            } else {
                return `<span style="font-size:11px; color:#64748b; display:inline-flex; align-items:center; gap:2px;">⏳ vence em ${diffDays}d</span>`;
            }
        }

        function updateFilterPillCounters() {
            let countAtivas = 0;
            let countRascunhos = 0;
            let countPendentes = 0;
            let countAprovadas = 0;
            let countEncerradas = 0;

            const ativasStatuses = ['EM_CRIACAO', 'AGUARDANDO_GESTOR', 'COM_DIRETOR', 'DEVOLVIDA', 'APROVADA', 'PDF_GERADO', 'AGUARDANDO_PEDIDO'];
            const encerradasStatuses = ['FINALIZADA_COM_PEDIDO', 'FATURADA', 'PERDIDA', 'EXPIRADA'];

            quotes.forEach(q => {
                if (ativasStatuses.includes(q.status)) countAtivas++;
                if (q.status === 'EM_CRIACAO') countRascunhos++;
                if (q.status === 'AGUARDANDO_GESTOR' || q.status === 'COM_DIRETOR') countPendentes++;
                if (q.status === 'APROVADA' || q.status === 'PDF_GERADO') countAprovadas++;
                if (encerradasStatuses.includes(q.status)) countEncerradas++;
            });

            const elAtivas = document.getElementById("count-pill-ativas");
            const elRascunhos = document.getElementById("count-pill-rascunhos");
            const elPendentes = document.getElementById("count-pill-pendentes");
            const elAprovadas = document.getElementById("count-pill-aprovadas");
            const elEncerradas = document.getElementById("count-pill-encerradas");
            const elTodas = document.getElementById("count-pill-todas");

            if (elAtivas) elAtivas.innerText = `(${countAtivas})`;
            if (elRascunhos) elRascunhos.innerText = `(${countRascunhos})`;
            if (elPendentes) elPendentes.innerText = `(${countPendentes})`;
            if (elAprovadas) elAprovadas.innerText = `(${countAprovadas})`;
            if (elEncerradas) elEncerradas.innerText = `(${countEncerradas})`;
            if (elTodas) elTodas.innerText = `(${quotes.length})`;
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
                    updateFilterPillCounters();
                    filterQuotes();
                    
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
            document.getElementById("quotes-count").innerText = `${filteredQuotesList.length} exibidas`;
            container.innerHTML = "";

            if (filteredQuotesList.length === 0) {
                container.innerHTML = `
                    <div style="text-align: center; color: var(--color-text-muted); padding: 40px 10px; background: white; border-radius: 12px; border: 1px dashed var(--color-border);">
                        Nenhuma cotação encontrada nesta categoria.
                    </div>
                `;
                return;
            }

            filteredQuotesList.forEach(q => {
                const dateStr = new Date(q.created_at).toLocaleDateString('pt-BR');
                const stInfo = getFriendlyStatusInfo(q.status);
                const validityBadge = getCardValidityBadge(q.data_validade, q.status);
                const valStr = parseFloat(q.total).toLocaleString('pt-BR', { minimumFractionDigits: 2 });
                const partnerName = (q.parceiro && q.parceiro.razao_social) ? q.parceiro.razao_social : 'Cliente não informado';
                
                container.innerHTML += `
                    <a href="${PUBLIC_URL}/cotacoes/id/${q.id}" class="quote-card">
                        <div class="card-row-top" style="align-items: center;">
                            <span class="quote-num">${q.numero}</span>
                            <span class="status-badge" style="background:${stInfo.bg}; color:${stInfo.color}; border:1px solid ${stInfo.border};">${stInfo.label}</span>
                        </div>
                        <div class="client-name" style="margin: 6px 0;">${partnerName}</div>
                        <div class="card-row-bottom" style="align-items: center;">
                            <div style="display:flex; align-items:center; gap:8px;">
                                <span class="quote-date">${dateStr}</span>
                                ${validityBadge ? `<span>·</span>${validityBadge}` : ''}
                            </div>
                            <span class="quote-total">R$ ${valStr}</span>
                        </div>
                    </a>
                `;
            });
        }

        let activeStatusFilter = 'ATIVAS';

        function setStatusFilter(status) {
            activeStatusFilter = status;
            
            // Highlight pill
            document.querySelectorAll(".filter-pill").forEach(btn => {
                btn.classList.remove("active");
            });
            if (event && event.currentTarget) {
                event.currentTarget.classList.add("active");
            }
            
            filterQuotes();
        }

        function filterQuotes() {
            const query = (document.getElementById("search-input") ? document.getElementById("search-input").value : "").toLowerCase().trim();
            const ativasStatuses = ['EM_CRIACAO', 'AGUARDANDO_GESTOR', 'COM_DIRETOR', 'DEVOLVIDA', 'APROVADA', 'PDF_GERADO', 'AGUARDANDO_PEDIDO'];
            const encerradasStatuses = ['FINALIZADA_COM_PEDIDO', 'FATURADA', 'PERDIDA', 'EXPIRADA'];

            filteredQuotesList = quotes.filter(q => {
                // 1. Filter by search query
                const matchesSearch = query === "" || 
                    (q.numero && q.numero.toLowerCase().includes(query)) || 
                    (q.parceiro && q.parceiro.razao_social && q.parceiro.razao_social.toLowerCase().includes(query));
                
                // 2. Filter by status
                let matchesStatus = true;
                if (activeStatusFilter === 'ATIVAS') {
                    matchesStatus = ativasStatuses.includes(q.status);
                } else if (activeStatusFilter === 'PENDENTE') {
                    matchesStatus = q.status === 'AGUARDANDO_GESTOR' || q.status === 'COM_DIRETOR';
                } else if (activeStatusFilter === 'APROVADA') {
                    matchesStatus = q.status === 'APROVADA' || q.status === 'PDF_GERADO';
                } else if (activeStatusFilter === 'ENCERRADAS') {
                    matchesStatus = encerradasStatuses.includes(q.status);
                } else if (activeStatusFilter !== 'ALL') {
                    matchesStatus = q.status === activeStatusFilter;
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

            if (!notifications || notifications.length === 0) {
                container.innerHTML = `
                    <div style="text-align: center; color: var(--color-text-muted); padding: 40px 10px;">
                        Nenhuma notificação encontrada nos últimos 30 dias.
                    </div>
                `;
                return;
            }

            // Agrupa notificações de cotações expiradas do mesmo dia
            const groupedExpiredByDate = {};
            const regularNotifications = [];

            notifications.forEach(n => {
                const titleLower = (n.titulo || '').toLowerCase();
                const msgLower = (n.mensagem || '').toLowerCase();
                const isExpired = titleLower.includes('expirada') || msgLower.includes('expirou') || titleLower.includes('validade');

                if (isExpired) {
                    const d = new Date(n.created_at);
                    const dayKey = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
                    if (!groupedExpiredByDate[dayKey]) {
                        groupedExpiredByDate[dayKey] = [];
                    }
                    groupedExpiredByDate[dayKey].push(n);
                } else {
                    regularNotifications.push(n);
                }
            });

            // Constrói lista ordenada de itens a renderizar
            const displayItems = [];

            regularNotifications.forEach(n => {
                displayItems.push({
                    type: 'single',
                    timestamp: new Date(n.created_at).getTime(),
                    data: n
                });
            });

            Object.keys(groupedExpiredByDate).forEach(dayKey => {
                const items = groupedExpiredByDate[dayKey];
                if (items.length === 1) {
                    displayItems.push({
                        type: 'single',
                        timestamp: new Date(items[0].created_at).getTime(),
                        data: items[0]
                    });
                } else {
                    const maxTimestamp = Math.max(...items.map(i => new Date(i.created_at).getTime()));
                    displayItems.push({
                        type: 'group_expired',
                        dayKey: dayKey,
                        timestamp: maxTimestamp,
                        items: items
                    });
                }
            });

            displayItems.sort((a, b) => b.timestamp - a.timestamp);

            displayItems.forEach(item => {
                if (item.type === 'single') {
                    const n = item.data;
                    const dateObj = new Date(n.created_at);
                    const timeStr = dateObj.toLocaleTimeString('pt-BR', {hour: '2-digit', minute:'2-digit'});
                    const dateStr = dateObj.toLocaleDateString('pt-BR');
                    const unreadClass = n.lida ? '' : 'unread';

                    container.innerHTML += `
                        <div class="notification-item ${unreadClass}" onclick="openNotification(${n.id}, '${n.link || ''}')">
                            <div class="notification-header">
                                <span class="notification-title">${n.titulo}</span>
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <span class="notification-time">${dateStr} às ${timeStr}</span>
                                    ${!n.lida ? `
                                        <button type="button" class="btn-mark-item-read" onclick="event.stopPropagation(); markSingleRead(${n.id})" title="Marcar esta notificação como lida">
                                            ✓ Marcar lida
                                        </button>
                                    ` : `
                                        <span style="font-size:11px; color:#10b981; font-weight:600;">✓ Lida</span>
                                    `}
                                </div>
                            </div>
                            <div class="notification-body">${n.mensagem}</div>
                        </div>
                    `;
                } else if (item.type === 'group_expired') {
                    const items = item.items;
                    const anyUnread = items.some(i => !i.lida);
                    const unreadClass = anyUnread ? 'unread' : '';
                    const dateObj = new Date(item.timestamp);
                    const dateStr = dateObj.toLocaleDateString('pt-BR');
                    const groupIds = items.map(i => i.id);

                    const quotesDetails = items.map(i => {
                        const match = (i.mensagem || '').match(/COT-[A-Za-z0-9-]+/) || (i.titulo || '').match(/COT-[A-Za-z0-9-]+/);
                        const cotNum = match ? match[0] : `Cotação #${i.id}`;
                        return { id: i.id, num: cotNum, link: i.link, lida: i.lida };
                    });

                    container.innerHTML += `
                        <div class="notification-item ${unreadClass}" style="border-left-color: #ef4444;">
                            <div class="notification-header">
                                <div style="display:flex; align-items:center; gap:6px;">
                                    <span class="notification-title" style="color:#b91c1c;">⏰ ${items.length} Cotações Expiradas</span>
                                    <span style="font-size:10px; background:#fee2e2; color:#dc2626; font-weight:700; padding:1px 6px; border-radius:10px;">${items.length} agrupadas</span>
                                </div>
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <span class="notification-time">${dateStr}</span>
                                    ${anyUnread ? `
                                        <button type="button" class="btn-mark-item-read" onclick="event.stopPropagation(); markBatchRead([${groupIds.join(',')}])" title="Marcar todas as expiradas deste dia como lidas">
                                            ✓ Marcar todas
                                        </button>
                                    ` : `
                                        <span style="font-size:11px; color:#10b981; font-weight:600;">✓ Lidas</span>
                                    `}
                                </div>
                            </div>
                            <div class="notification-body" style="margin-top:2px;">
                                ${items.length} cotações ultrapassaram a validade em ${dateStr}.
                            </div>
                            <div style="display:flex; flex-wrap:wrap; gap:6px; margin-top:8px;">
                                ${quotesDetails.map(q => `
                                    <a href="${q.link || 'javascript:void(0)'}" onclick="event.stopPropagation(); markSingleRead(${q.id});" style="font-size:11px; font-weight:600; text-decoration:none; padding:3px 8px; border-radius:6px; background:${q.lida ? '#f1f5f9' : '#fee2e2'}; color:${q.lida ? '#475569' : '#b91c1c'}; border:1px solid ${q.lida ? '#cbd5e1' : '#fca5a5'}; display:inline-flex; align-items:center; gap:3px;">
                                        ${q.num} ↗
                                    </a>
                                `).join('')}
                            </div>
                        </div>
                    `;
                }
            });
        }

        function updateBadge() {
            unreadCount = notifications.filter(n => !n.lida).length;
            const badge = document.getElementById("alerts-badge");
            const headerBadge = document.getElementById("header-alerts-badge");
            
            if (badge) {
                if (unreadCount > 0) {
                    badge.innerText = unreadCount > 99 ? '99+' : unreadCount;
                    badge.style.display = "flex";
                } else {
                    badge.style.display = "none";
                }
            }

            if (headerBadge) {
                if (unreadCount > 0) {
                    headerBadge.innerText = unreadCount > 99 ? '99+' : unreadCount;
                    headerBadge.style.display = "flex";
                } else {
                    headerBadge.style.display = "none";
                }
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
            await markSingleRead(id);
            if (link) {
                window.location.href = link;
            }
        }

        async function markSingleRead(id) {
            try {
                // Atualização otimista na tela
                const n = notifications.find(item => item.id == id);
                if (n) {
                    n.lida = true;
                    updateBadge();
                    renderNotifications();
                }

                await fetch(`${API_URL}/notificacoes/${id}/ler`, {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
            } catch (e) {
                console.error("Error marking notification as read:", e);
                loadNotifications();
            }
        }

        async function markBatchRead(ids) {
            try {
                // Atualização otimista na tela
                ids.forEach(id => {
                    const n = notifications.find(item => item.id == id);
                    if (n) n.lida = true;
                });
                updateBadge();
                renderNotifications();

                await fetch(`${API_URL}/notificacoes/marcar-lidas`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ ids: ids })
                });
                showToast("Notificações marcadas como lidas.", "success");
            } catch (e) {
                console.error("Error marking batch notifications as read:", e);
                loadNotifications();
            }
        }

        async function markNotificationAsRead(id) {
            return markSingleRead(id);
        }

        async function markAllAsRead() {
            try {
                notifications.forEach(n => n.lida = true);
                updateBadge();
                renderNotifications();

                const res = await fetch(`${API_URL}/notificacoes/ler-tudo`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                showToast("Todas as notificações foram marcadas como lidas.", "success");
            } catch (e) {
                console.error("Error marking all read:", e);
                loadNotifications();
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
                showToast("Por favor, selecione um Cliente/Parceiro.", 'warning');
                return;
            }

            if (!lat || !lng) {
                showToast("Coordenadas GPS ausentes ou não carregadas. Por favor, aguarde.", 'warning');
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
                    showToast("Sessão expirada. Por favor, recarregue a página.", 'error');
                    btn.innerText = originalText;
                    btn.disabled = false;
                    return;
                }

                const data = await res.json();
                btn.innerText = originalText;
                btn.disabled = false;

                if (data.success) {
                    showToast("Visita registrada com sucesso! Check-in concluído.", 'success');
                    select.value = "";
                    loadRecentCheckins();
                } else {
                    showToast("Erro ao realizar check-in: " + (data.message || data.error), 'error');
                }
            } catch(e) {
                console.error("Check-in request error:", e);
                btn.innerText = originalText;
                btn.disabled = false;
                showToast("Erro de conexão ao realizar check-in. Verifique o console.", 'error');
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

        function showQuoteFormError(message, title = 'Não foi possível gerar a cotação') {
            const banner = document.getElementById("new-quote-error-banner");
            const titleEl = document.getElementById("new-quote-error-title");
            const msgEl = document.getElementById("new-quote-error-msg");
            if (banner && msgEl) {
                if (titleEl) titleEl.innerText = title;
                msgEl.innerHTML = String(message || '').replace(/\n/g, '<br>');
                banner.style.display = "block";
                banner.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
            showToast(String(message || '').replace(/<br>/g, ' '), 'error', title);
        }

        function clearQuoteFormError() {
            const banner = document.getElementById("new-quote-error-banner");
            if (banner) banner.style.display = "none";
        }

        async function openNewQuoteModal() {
            clearQuoteFormError();
            document.getElementById("new-quote-overlay").style.display = "block";
            document.getElementById("new-quote-drawer").style.display = "flex";
            
            // Reset wizard
            currentWizardStep = 1;
            quoteCartItems = [];
            currentStep2Tab = 'catalog';
            clearSelectedPartner();
            document.getElementById("partner-search-input").value = "";
            document.getElementById("prod-search-input").value = "";
            document.getElementById("nq-obs-cliente").value = "";
            
            updateStepView();
            switchStep2Tab('catalog');
            renderCart();
            
            // Force load partners and products afresh
            await Promise.all([
                loadPartnersForQuote(),
                loadProductsForQuote(true)
            ]);
        }

        let isSavingDraft = false;

        async function saveDraftQuote(isClosing = false) {
            if (isSavingDraft) return false;

            if (!selectedPartner) {
                if (!isClosing) {
                    showToast("Por favor, selecione um cliente no Passo 1 para salvar como rascunho.", 'warning');
                    goToStep(1);
                }
                return false;
            }

            if (quoteCartItems.length === 0) {
                if (!isClosing) {
                    showToast("Por favor, adicione pelo menos 1 produto no Passo 2 para salvar como rascunho.", 'warning');
                    goToStep(2);
                }
                return false;
            }

            isSavingDraft = true;
            clearQuoteFormError();
            const repId = Number("{{ auth()->user()->id }}");

            let freteVal = document.getElementById("nq-frete-tipo") ? document.getElementById("nq-frete-tipo").value : 'CIF';
            if (!['CIF', 'FOB'].includes(freteVal)) {
                freteVal = 'CIF';
            }

            const payload = {
                parceiro_id: selectedPartner.id,
                representante_id: repId,
                forma_pagamento: (document.getElementById("nq-forma-pagamento") && document.getElementById("nq-forma-pagamento").value) || "A combinar",
                prazo_entrega: (document.getElementById("nq-prazo-entrega") && document.getElementById("nq-prazo-entrega").value) || "3 dias uteis",
                frete_tipo: freteVal,
                observacao_cliente: (document.getElementById("nq-obs-cliente") && document.getElementById("nq-obs-cliente").value) || null,
                itens: quoteCartItems.map(item => ({
                    produto_id: item.product_id,
                    qtd: Math.max(1, parseInt(item.qty || 1)),
                    preco_unit_proposto: Math.max(0.01, parseFloat(item.price || 0))
                }))
            };

            try {
                const res = await fetch(`${API_URL}/cotacoes/manual`, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "Accept": "application/json",
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(payload)
                });

                let data = null;
                try {
                    data = await res.json();
                } catch(e) {
                    data = null;
                }
                isSavingDraft = false;

                if (res.ok && (data?.id || data?.success || data?.data)) {
                    selectedPartner = null;
                    quoteCartItems = [];
                    clearQuoteFormError();
                    closeNewQuoteModal();
                    showToast("💾 Cotação salva com sucesso em 'Em Criação'!", 'success');
                    loadQuotes();
                    switchTab('quotes');
                    return true;
                } else {
                    if (!isClosing) {
                        let errMsg = "Não foi possível salvar o rascunho.";
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
                        showQuoteFormError(errMsg, 'Falha ao salvar rascunho');
                    }
                    return false;
                }
            } catch(e) {
                console.error("Error saving draft quote:", e);
                isSavingDraft = false;
                if (!isClosing) {
                    showQuoteFormError("Erro de comunicação ao salvar rascunho. Tente novamente.", 'Erro de Conexão');
                }
                return false;
            }
        }

        async function handleDrawerClose() {
            if (selectedPartner && quoteCartItems.length > 0) {
                const saved = await saveDraftQuote(true);
                if (saved) return;
            }
            closeNewQuoteModal();
        }

        function closeNewQuoteModal() {
            clearQuoteFormError();
            document.getElementById("new-quote-overlay").style.display = "none";
            document.getElementById("new-quote-drawer").style.display = "none";
        }

        function goToStep(stepNum) {
            clearQuoteFormError();
            if (stepNum > 1 && !selectedPartner) {
                showToast("Por favor, selecione um cliente primeiro.", 'warning');
                return;
            }
            if (stepNum > 2 && quoteCartItems.length === 0) {
                showToast("Por favor, adicione pelo menos 1 produto à cotação.", 'warning');
                return;
            }
            currentWizardStep = stepNum;
            updateStepView();
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
                switchStep2Tab(currentStep2Tab || 'catalog');
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
            if (!selectedPartner) {
                showToast("Por favor, selecione um cliente para continuar.", 'warning');
                return;
            }
            goToStep(2);
        }

        function validateStep2AndNext() {
            if (quoteCartItems.length === 0) {
                showToast("Adicione pelo menos 1 produto ao carrinho.", 'warning');
                return;
            }
            goToStep(3);
        }

        async function loadPartnersForQuote() {
            try {
                const container = document.getElementById("partner-search-results");
                if (container) {
                    container.innerHTML = '<div style="font-size:12px; color:var(--color-primary); text-align:center; padding:16px; font-weight:600;">🔄 Carregando clientes...</div>';
                }
                const res = await fetch(`${API_URL}/clientes?limit=50`);
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
                const container = document.getElementById("partner-search-results");
                if (container) {
                    container.innerHTML = '<div style="font-size:12px; color:#ef4444; text-align:center; padding:16px;">Erro ao carregar clientes</div>';
                }
            }
        }

        let currentDisplayedPartners = [];

        function renderPartnerSelectOptions(list, isSearching = false) {
            const container = document.getElementById("partner-search-results");
            if (!container) return;

            currentDisplayedPartners = Array.isArray(list) ? list : [];

            if (selectedPartner) {
                container.style.display = "none";
                return;
            } else {
                container.style.display = "flex";
            }

            const query = document.getElementById("partner-search-input").value.trim();

            if (!list || list.length === 0) {
                container.innerHTML = `
                    <div style="text-align:center; padding:16px; background:#f8fafc; border-radius:10px; border:1px dashed var(--color-border); font-size:12px; color:var(--color-text-muted);">
                        ${query ? (isSearching ? `🔍 Buscando clientes para "<strong>${query}</strong>"...` : `Nenhum cliente encontrado para "<strong>${query}</strong>".`) : 'Sem clientes para exibir.'}
                    </div>
                `;
                return;
            }

            container.innerHTML = "";
            list.forEach(p => {
                let rawName = p.razao_social || p.nome_fantasia || 'Cliente Sem Nome';
                let displayName = rawName.replace(/^[\?\s\-\.]+/g, '').trim();
                if (!displayName && p.nome_fantasia) displayName = p.nome_fantasia.trim();
                if (!displayName) displayName = rawName;

                const code = p.codigo_sankhya ? `Cód: ${p.codigo_sankhya}` : 'Cód: N/A';
                const docVal = p.cnpj || p.cnpj_cpf;
                const docStr = docVal ? `CNPJ/CPF: ${docVal}` : 'Sem documento';
                const pUf = (p.uf === '2' || (p.cidade && p.cidade.trim().toUpperCase() === 'UBERLANDIA')) ? 'MG' : (p.uf || '');
                const cityStr = (p.cidade || pUf) ? ` &bull; ${p.cidade || ''}${pUf ? '/' + pUf : ''}` : '';

                container.innerHTML += `
                    <div class="partner-item-card" onclick="selectPartnerById(${p.id})">
                        <div style="flex-grow:1; padding-right:8px;">
                            <div style="font-weight:700; font-size:13.5px; color:var(--color-text); line-height:1.3;">${displayName}</div>
                            <div style="font-size:11px; color:var(--color-text-muted); margin-top:3px;">${code}${cityStr}</div>
                            <div style="font-size:11px; color:var(--color-primary); font-weight:600; margin-top:2px;">${docStr}</div>
                        </div>
                        <div style="background:#e0f2fe; color:#0284c7; padding:6px 12px; border-radius:8px; font-size:12px; font-weight:700; flex-shrink:0;">
                            Selecionar
                        </div>
                    </div>
                `;
            });
        }

        function selectPartnerById(id) {
            selectedPartner = (currentDisplayedPartners || []).find(p => p.id == id) || (allPartnersList || []).find(p => p.id == id);
            if (!selectedPartner) return;

            const card = document.getElementById("selected-partner-card");
            const container = document.getElementById("partner-search-results");

            let rawName = selectedPartner.razao_social || selectedPartner.nome_fantasia || 'Cliente Sem Nome';
            let displayName = rawName.replace(/^[\?\s\-\.]+/g, '').trim();
            if (!displayName && selectedPartner.nome_fantasia) displayName = selectedPartner.nome_fantasia.trim();
            if (!displayName) displayName = rawName;

            const docStr = selectedPartner.cnpj || selectedPartner.cnpj_cpf || 'Não informado';
            const codeStr = selectedPartner.codigo_sankhya || 'N/A';
            const spUf = (selectedPartner.uf === '2' || (selectedPartner.cidade && selectedPartner.cidade.trim().toUpperCase() === 'UBERLANDIA')) ? 'MG' : (selectedPartner.uf || '');
            const cityStr = selectedPartner.cidade ? ` | ${selectedPartner.cidade}${spUf ? '/' + spUf : ''}` : '';

            document.getElementById("sp-name").innerText = displayName;
            document.getElementById("sp-doc").innerText = `CNPJ/CPF: ${docStr} | Código: ${codeStr}${cityStr}`;
            
            if (card) card.style.display = "block";
            if (container) container.style.display = "none";
        }

        function clearSelectedPartner() {
            selectedPartner = null;
            const card = document.getElementById("selected-partner-card");
            const container = document.getElementById("partner-search-results");
            if (card) card.style.display = "none";
            if (container) container.style.display = "flex";
            document.getElementById("partner-search-input").value = "";
            renderPartnerSelectOptions(allPartnersList ? allPartnersList.slice(0, 50) : []);
            document.getElementById("partner-search-input").focus();
        }

        let partnerSearchTimer = null;

        function filterPartnerOptions() {
            try {
                if (partnerSearchTimer) {
                    clearTimeout(partnerSearchTimer);
                    partnerSearchTimer = null;
                }

                const inputEl = document.getElementById("partner-search-input");
                if (!inputEl) return;

                const rawQuery = inputEl.value || "";
                const normalizedQuery = normalizeStr(rawQuery).trim();

                if (!normalizedQuery) {
                    renderPartnerSelectOptions(allPartnersList ? allPartnersList.slice(0, 50) : []);
                    return;
                }

                // Perform instant local search across pre-loaded items
                const terms = normalizedQuery.split(/\s+/).filter(Boolean);
                const localMatches = (allPartnersList || []).filter(p => {
                    if (!p) return false;
                    const searchables = [
                        normalizeStr(p.razao_social),
                        normalizeStr(p.nome_fantasia),
                        normalizeStr(p.cnpj || p.cnpj_cpf),
                        normalizeStr(p.codigo_sankhya),
                        normalizeStr(p.cidade),
                        normalizeStr(p.bairro)
                    ].join(" ");

                    return terms.every(term => searchables.includes(term));
                });

                // Immediately render local matches (or searching state if empty)
                renderPartnerSelectOptions(localMatches, true);

                // Live debounced server query across ALL partners in MySQL
                partnerSearchTimer = setTimeout(() => {
                    executeServerPartnerSearch(rawQuery.trim());
                }, 180);
            } catch (err) {
                console.error("Erro ao filtrar parceiros:", err);
            }
        }

        let partnerSearchReqId = 0;

        async function executeServerPartnerSearch(query) {
            if (!query) return;
            const trimmedQuery = query.trim();
            if (!trimmedQuery) return;

            const thisReqId = ++partnerSearchReqId;

            try {
                const res = await fetch(`${API_URL}/clientes?search=${encodeURIComponent(trimmedQuery)}&limit=50`);
                if (!res.ok) {
                    throw new Error(`Servidor retornou erro ${res.status}`);
                }
                const data = await res.json();
                
                // If a newer search was executed while this was in-flight, discard
                if (thisReqId !== partnerSearchReqId) return;

                let rawResults = [];
                if (data.success && Array.isArray(data.data)) {
                    rawResults = data.data;
                } else if (Array.isArray(data)) {
                    rawResults = data;
                }

                if (Array.isArray(rawResults)) {
                    rawResults.forEach(partner => {
                        if (!allPartnersList.some(p => p.id == partner.id)) {
                            allPartnersList.push(partner);
                        }
                    });

                    renderPartnerSelectOptions(rawResults, false);
                }
            } catch(e) {
                console.error("Error searching server partners:", e);
                const container = document.getElementById("partner-search-results");
                if (container && thisReqId === partnerSearchReqId) {
                    container.innerHTML = `<div style="text-align:center; padding:16px; color:#ef4444; font-size:12px;">⚠️ Erro na consulta (${e.message}). Verifique a conexão ou tente novamente.</div>`;
                }
            }
        }

        let prodSearchTimer = null;

        async function loadProductsForQuote(forceReload = false) {
            if (allProductsList && allProductsList.length > 0 && !forceReload) {
                renderProductSearchResults(allProductsList.slice(0, 40));
                return;
            }

            isProductsLoading = true;
            const container = document.getElementById("prod-search-results");
            if (container) {
                container.innerHTML = '<div style="font-size:13px; color:var(--color-primary); text-align:center; padding:16px; font-weight:600;">🔄 Carregando catálogo...</div>';
            }

            try {
                const res = await fetch(`${API_URL}/produtos?limit=40`);
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

                renderProductSearchResults(allProductsList);
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
            clearTimeout(prodSearchTimer);
            const rawQuery = document.getElementById("prod-search-input").value;
            const query = rawQuery.trim();

            if (!query) {
                renderProductSearchResults(allProductsList ? allProductsList.slice(0, 40) : []);
                return;
            }

            // Perform local search first on pre-loaded items
            const normalizedQuery = normalizeStr(query);
            const terms = normalizedQuery.split(/\s+/).filter(Boolean);
            const localMatches = (allProductsList || []).filter(p => {
                const searchables = [
                    normalizeStr(p.descricao),
                    normalizeStr(p.codigo_sankhya),
                    normalizeStr(p.codprod),
                    normalizeStr(p.marca),
                ].join(" ");
                return terms.every(term => searchables.includes(term));
            });

            // Immediately render local matches (or empty state)
            renderProductSearchResults(localMatches, true);

            // Debounced live server query across ALL 6,141 products in MySQL
            prodSearchTimer = setTimeout(() => {
                executeServerProductSearch(query);
            }, 250);
        }

        async function executeServerProductSearch(query) {
            try {
                const res = await fetch(`${API_URL}/produtos?search=${encodeURIComponent(query)}&limit=40`);
                const data = await res.json();
                
                let results = [];
                if (data.success && Array.isArray(data.data)) {
                    results = data.data;
                } else if (Array.isArray(data)) {
                    results = data;
                }

                // Cache all search results into allProductsList so addToCart can find them by ID
                if (Array.isArray(results)) {
                    results.forEach(prod => {
                        if (!allProductsList.some(p => p.id == prod.id)) {
                            allProductsList.push(prod);
                        }
                    });
                }

                const currentQuery = document.getElementById("prod-search-input").value.trim();
                if (normalizeStr(currentQuery) === normalizeStr(query)) {
                    renderProductSearchResults(results, false);
                }
            } catch(e) {
                console.error("Error searching server products:", e);
            }
        }

        let currentStep2Tab = 'catalog';

        function switchStep2Tab(tabName) {
            currentStep2Tab = tabName;
            const catView = document.getElementById("step2-view-catalog");
            const cartView = document.getElementById("step2-view-cart");
            const btnCat = document.getElementById("tab-btn-catalog");
            const btnCart = document.getElementById("tab-btn-cart");

            if (tabName === 'catalog') {
                if (catView) catView.style.display = "block";
                if (cartView) cartView.style.display = "none";
                if (btnCat) {
                    btnCat.style.background = "#ffffff";
                    btnCat.style.color = "var(--color-primary)";
                    btnCat.style.fontWeight = "700";
                    btnCat.style.boxShadow = "0 1px 3px rgba(0,0,0,0.08)";
                }
                if (btnCart) {
                    btnCart.style.background = "transparent";
                    btnCart.style.color = "#64748b";
                    btnCart.style.fontWeight = "600";
                    btnCart.style.boxShadow = "none";
                }
            } else {
                if (catView) catView.style.display = "none";
                if (cartView) cartView.style.display = "block";
                if (btnCat) {
                    btnCat.style.background = "transparent";
                    btnCat.style.color = "#64748b";
                    btnCat.style.fontWeight = "600";
                    btnCat.style.boxShadow = "none";
                }
                if (btnCart) {
                    btnCart.style.background = "#ffffff";
                    btnCart.style.color = "var(--color-primary)";
                    btnCart.style.fontWeight = "700";
                    btnCart.style.boxShadow = "0 1px 3px rgba(0,0,0,0.08)";
                }
                renderCart();
                const filterInput = document.getElementById("cart-filter-input");
                if (filterInput && quoteCartItems.length > 5) {
                    filterInput.value = "";
                }
            }
        }

        function showMiniToast(message, isSuccess = true) {
            showToast(message, isSuccess ? 'success' : 'error');
        }

        function syncCatalogProductRow(prodId) {
            const rowEl = document.getElementById(`prod-row-${prodId}`);
            if (!rowEl) return;
            const prod = (allProductsList || []).find(p => p.id == prodId);
            if (!prod) return;

            const price = parseFloat(prod.preco_sugerido || prod.preco_tabela || prod.preco_venda || prod.preco || 0);
            const priceStr = price > 0 ? `R$ ${price.toLocaleString('pt-BR', {minimumFractionDigits: 2})}` : 'R$ 100,00 (padrão)';
            const codeStr = prod.codigo_sankhya || prod.codprod || prod.id;
            const brandStr = prod.marca ? ` | ${prod.marca}` : '';

            const cartItem = (quoteCartItems || []).find(item => item.product_id == prodId);
            const cartQty = cartItem ? cartItem.qty : 0;

            if (cartQty > 0) {
                rowEl.style.borderColor = "#86efac";
                rowEl.style.backgroundColor = "#f0fdf4";
                rowEl.innerHTML = `
                    <div style="flex-grow:1; padding-right:8px;">
                        <div style="display:flex; align-items:center; gap:6px;">
                            <span style="background:#16a34a; color:white; font-size:10px; font-weight:700; padding:1px 6px; border-radius:4px;">No Pedido</span>
                            <div class="product-item-title">${prod.descricao}</div>
                        </div>
                        <div class="product-item-price" style="margin-top:2px;">Cód: ${codeStr}${brandStr} &bull; ${priceStr}</div>
                    </div>
                    <div style="display:flex; align-items:center; gap:4px; background:#ffffff; border:1px solid #86efac; border-radius:8px; padding:3px 6px;">
                        <button type="button" onclick="changeQtyFromCatalog(${prod.id}, -1)" style="width:26px; height:26px; border-radius:6px; border:1px solid #cbd5e1; background:#f8fafc; font-weight:bold; font-size:14px; color:#1e293b; cursor:pointer; display:flex; align-items:center; justify-content:center;">-</button>
                        <span style="font-weight:800; font-size:13px; color:#15803d; min-width:22px; text-align:center;">${cartQty}</span>
                        <button type="button" onclick="changeQtyFromCatalog(${prod.id}, 1)" style="width:26px; height:26px; border-radius:6px; border:1px solid #cbd5e1; background:#f8fafc; font-weight:bold; font-size:14px; color:#1e293b; cursor:pointer; display:flex; align-items:center; justify-content:center;">+</button>
                    </div>
                `;
            } else {
                rowEl.style.borderColor = "var(--color-border)";
                rowEl.style.backgroundColor = "#f8fafc";
                rowEl.innerHTML = `
                    <div style="flex-grow:1; padding-right:8px;">
                        <div class="product-item-title">${prod.descricao}</div>
                        <div class="product-item-price" style="margin-top:2px;">Cód: ${codeStr}${brandStr} &bull; ${priceStr}</div>
                    </div>
                    <button type="button" class="btn-add-prod" onclick="addToCart(${prod.id})">+ Adicionar</button>
                `;
            }
        }

        function renderProductSearchResults(products) {
            const container = document.getElementById("prod-search-results");
            const query = (document.getElementById("prod-search-input") ? document.getElementById("prod-search-input").value : "").trim();

            if (!container) return;

            if (!products || products.length === 0) {
                container.innerHTML = `
                    <div style="text-align:center; padding:18px; background:#f8fafc; border-radius:10px; border:1px dashed var(--color-border);">
                        <div style="font-size:12px; color:var(--color-text-muted); margin-bottom:8px;">Nenhum produto encontrado para "<strong>${query}</strong>".</div>
                        <button type="button" onclick="clearProdSearch()" style="background:#e2e8f0; color:#334155; border:none; padding:6px 14px; border-radius:8px; font-size:12px; font-weight:600; cursor:pointer;">Limpar Pesquisa e Ver Todos</button>
                    </div>
                `;
                return;
            }

            container.innerHTML = "";
            products.forEach(p => {
                const price = parseFloat(p.preco_sugerido || p.preco_tabela || p.preco_venda || p.preco || 0);
                const priceStr = price > 0 ? `R$ ${price.toLocaleString('pt-BR', {minimumFractionDigits: 2})}` : 'R$ 100,00 (padrão)';
                const codeStr = p.codigo_sankhya || p.codprod || p.id;
                const brandStr = p.marca ? ` | ${p.marca}` : '';

                const cartItem = (quoteCartItems || []).find(item => item.product_id == p.id);
                const cartQty = cartItem ? cartItem.qty : 0;

                if (cartQty > 0) {
                    container.innerHTML += `
                        <div class="product-item-row" id="prod-row-${p.id}" style="border-color:#86efac; background:#f0fdf4;">
                            <div style="flex-grow:1; padding-right:8px;">
                                <div style="display:flex; align-items:center; gap:6px;">
                                    <span style="background:#16a34a; color:white; font-size:10px; font-weight:700; padding:1px 6px; border-radius:4px;">No Pedido</span>
                                    <div class="product-item-title">${p.descricao}</div>
                                </div>
                                <div class="product-item-price" style="margin-top:2px;">Cód: ${codeStr}${brandStr} &bull; ${priceStr}</div>
                            </div>
                            <div style="display:flex; align-items:center; gap:4px; background:#ffffff; border:1px solid #86efac; border-radius:8px; padding:3px 6px;">
                                <button type="button" onclick="changeQtyFromCatalog(${p.id}, -1)" style="width:26px; height:26px; border-radius:6px; border:1px solid #cbd5e1; background:#f8fafc; font-weight:bold; font-size:14px; color:#1e293b; cursor:pointer; display:flex; align-items:center; justify-content:center;">-</button>
                                <span style="font-weight:800; font-size:13px; color:#15803d; min-width:22px; text-align:center;">${cartQty}</span>
                                <button type="button" onclick="changeQtyFromCatalog(${p.id}, 1)" style="width:26px; height:26px; border-radius:6px; border:1px solid #cbd5e1; background:#f8fafc; font-weight:bold; font-size:14px; color:#1e293b; cursor:pointer; display:flex; align-items:center; justify-content:center;">+</button>
                            </div>
                        </div>
                    `;
                } else {
                    container.innerHTML += `
                        <div class="product-item-row" id="prod-row-${p.id}">
                            <div style="flex-grow:1; padding-right:8px;">
                                <div class="product-item-title">${p.descricao}</div>
                                <div class="product-item-price" style="margin-top:2px;">Cód: ${codeStr}${brandStr} &bull; ${priceStr}</div>
                            </div>
                            <button type="button" class="btn-add-prod" onclick="addToCart(${p.id})">+ Adicionar</button>
                        </div>
                    `;
                }
            });
        }

        function clearProdSearch() {
            const input = document.getElementById("prod-search-input");
            if (input) input.value = "";
            renderProductSearchResults(allProductsList ? allProductsList.slice(0, 40) : []);
        }

        function addToCart(prodId) {
            const prod = allProductsList.find(p => p.id == prodId);
            if (!prod) {
                showToast("Produto não encontrado no catálogo.", "error");
                return;
            }

            const rawPrice = parseFloat(prod.preco_sugerido || prod.preco_tabela || prod.preco_venda || prod.preco || 0);
            const unitPrice = (isNaN(rawPrice) || rawPrice <= 0) ? 100.00 : rawPrice;

            const existingIndex = quoteCartItems.findIndex(item => item.product_id == prodId);
            if (existingIndex >= 0) {
                quoteCartItems[existingIndex].qty += 1;
            } else {
                quoteCartItems.push({
                    product_id: prod.id,
                    sku: prod.codigo_sankhya || '',
                    name: prod.descricao,
                    unit: prod.unidade || prod.unidade_medida || 'UN',
                    price: unitPrice,
                    qty: 1
                });
            }
            renderCart();
            syncCatalogProductRow(prodId);
            showMiniToast(`✓ 1x adicionado: ${prod.descricao.substring(0, 22)}...`);
        }

        function changeQtyFromCatalog(prodId, delta) {
            const idx = quoteCartItems.findIndex(item => item.product_id == prodId);
            if (idx >= 0) {
                updateCartQty(idx, delta);
            } else if (delta > 0) {
                addToCart(prodId);
            }
        }

        function updateCartQty(index, delta) {
            if (!quoteCartItems[index]) return;
            const prodId = quoteCartItems[index].product_id;
            quoteCartItems[index].qty += delta;
            if (quoteCartItems[index].qty <= 0) {
                quoteCartItems.splice(index, 1);
            }
            renderCart();
            syncCatalogProductRow(prodId);
        }

        function setCartItemExactQty(index, val) {
            if (!quoteCartItems[index]) return;
            const parsed = parseInt(val, 10);
            if (!isNaN(parsed) && parsed > 0) {
                quoteCartItems[index].qty = parsed;
            } else {
                quoteCartItems[index].qty = 1;
            }
            const prodId = quoteCartItems[index].product_id;
            renderCart();
            syncCatalogProductRow(prodId);
        }

        function updateCartPrice(index, val) {
            if (!quoteCartItems[index]) return;
            const parsed = parseFloat(String(val).replace(',', '.'));
            if (!isNaN(parsed) && parsed >= 0) {
                quoteCartItems[index].price = parsed;
            }
            renderCartTotalOnly();
        }

        function removeFromCart(index) {
            if (!quoteCartItems[index]) return;
            const prodId = quoteCartItems[index].product_id;
            quoteCartItems.splice(index, 1);
            renderCart();
            syncCatalogProductRow(prodId);
            showMiniToast("Item removido do pedido");
        }

        function clearCartConfirm() {
            if (quoteCartItems.length === 0) return;
            if (confirm(`Deseja realmente remover todos os ${quoteCartItems.length} produtos do pedido?`)) {
                const removedIds = quoteCartItems.map(item => item.product_id);
                quoteCartItems = [];
                renderCart();
                removedIds.forEach(id => syncCatalogProductRow(id));
                showMiniToast("Pedido limpo com sucesso");
            }
        }

        function filterCartDisplay(filterTerm) {
            const term = normalizeStr(filterTerm).trim();
            const cards = document.querySelectorAll("#cart-items-list .cart-item-card");
            cards.forEach(card => {
                const text = normalizeStr(card.innerText);
                if (!term || text.includes(term)) {
                    card.style.display = "flex";
                } else {
                    card.style.display = "none";
                }
            });
        }

        function renderCartTotalOnly() {
            const total = quoteCartItems.reduce((acc, item) => acc + (item.price * item.qty), 0);
            const formatted = `R$ ${total.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;

            const el = document.getElementById("cart-total-val");
            if (el) el.innerText = formatted;

            const catValEl = document.getElementById("cat-summary-val");
            if (catValEl) catValEl.innerText = formatted;
        }

        function renderCart() {
            const listEl = document.getElementById("cart-items-list");
            const countEl = document.getElementById("cart-items-count");
            const badgeCountEl = document.getElementById("items-badge-count");
            const tabBadgeEl = document.getElementById("cart-tab-badge");
            const catCountEl = document.getElementById("cat-summary-count");

            const count = quoteCartItems.length;
            if (countEl) countEl.innerText = count;
            if (badgeCountEl) badgeCountEl.innerText = count;
            if (tabBadgeEl) tabBadgeEl.innerText = count;
            if (catCountEl) catCountEl.innerText = count;

            if (count === 0) {
                if (listEl) {
                    listEl.innerHTML = `
                        <div style="text-align:center; padding:32px 14px; color:var(--color-text-muted); font-size:13px;" id="empty-cart-msg">
                            <div style="font-size:32px; margin-bottom:8px;">🛒</div>
                            Nenhum produto adicionado ainda.<br>Clique em <strong>"Adicionar Produtos"</strong> para escolher do catálogo.
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
                        <div class="cart-item-card" data-cart-idx="${index}">
                            <div class="cart-item-top" style="display:flex; justify-content:space-between; align-items:flex-start;">
                                <div style="flex-grow:1; padding-right:8px;">
                                    <div style="font-weight:700; font-size:13px; color:var(--color-text); line-height:1.25;">
                                        <span style="display:inline-block; background:#e2e8f0; color:#475569; font-size:10px; font-weight:800; padding:1px 5px; border-radius:4px; margin-right:4px;">#${index + 1}</span>${item.name}
                                    </div>
                                    <div style="font-size:11px; color:var(--color-text-muted); margin-top:3px;">
                                        Cód: ${item.sku || 'N/A'} &bull; Unid: ${item.unit}
                                    </div>
                                </div>
                                <button type="button" onclick="removeFromCart(${index})" title="Remover item" style="background:#fee2e2; border:none; color:#ef4444; width:28px; height:28px; border-radius:8px; cursor:pointer; font-weight:bold; font-size:16px; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                    &times;
                                </button>
                            </div>
                            <div class="cart-item-controls" style="margin-top:8px; display:flex; justify-content:space-between; align-items:center; background:#f8fafc; border:1px solid #e2e8f0; padding:6px 10px; border-radius:8px;">
                                <div class="qty-stepper" style="display:flex; align-items:center; gap:5px;">
                                    <button type="button" class="btn-stepper" onclick="updateCartQty(${index}, -1)">-</button>
                                    <input type="number" min="1" value="${item.qty}" inputmode="numeric" onchange="setCartItemExactQty(${index}, this.value)" style="width:44px; text-align:center; font-weight:800; font-size:13px; border:1px solid #cbd5e1; border-radius:6px; padding:3px 2px;">
                                    <button type="button" class="btn-stepper" onclick="updateCartQty(${index}, 1)">+</button>
                                </div>
                                <div style="display:flex; align-items:center; gap:4px;">
                                    <span style="font-size:11px; color:var(--color-text-muted); font-weight:600;">R$/un:</span>
                                    <input type="number" step="0.01" value="${item.price.toFixed(2)}" inputmode="decimal" oninput="updateCartPrice(${index}, this.value)" style="width:78px; padding:4px 6px; border:1px solid var(--color-border); border-radius:6px; font-size:12px; font-weight:700; text-align:right;">
                                </div>
                            </div>
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-top:6px; padding-top:4px; border-top:1px dashed #e2e8f0;">
                                <span style="font-size:11px; color:#64748b;">${item.qty} un &times; R$ ${item.price.toFixed(2)}</span>
                                <span style="font-size:13px; color:var(--color-primary); font-weight:800;">Subtotal: R$ ${subtotal.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
                            </div>
                        </div>
                    `;
                });
            }

            renderCartTotalOnly();
        }

        async function submitNewQuote() {
            clearQuoteFormError();

            if (!selectedPartner) {
                showToast("Por favor, selecione um cliente no Passo 1.", "warning");
                goToStep(1);
                return;
            }
            if (quoteCartItems.length === 0) {
                showToast("Por favor, adicione pelo menos 1 produto no Passo 2.", "warning");
                goToStep(2);
                return;
            }

            const btn = document.getElementById("btn-submit-quote");
            const origText = btn.innerHTML;
            btn.innerHTML = `
                <span style="display:inline-block; animation:spin 0.8s linear infinite; margin-right:6px;">⏳</span>
                Gerando Cotação...
            `;
            btn.disabled = true;

            const repId = Number("{{ auth()->user()->id }}");

            let freteVal = document.getElementById("nq-frete-tipo").value;
            if (!['CIF', 'FOB'].includes(freteVal)) {
                freteVal = 'CIF';
            }

            const payload = {
                parceiro_id: selectedPartner.id,
                representante_id: repId,
                forma_pagamento: document.getElementById("nq-forma-pagamento").value || "A combinar",
                prazo_entrega: document.getElementById("nq-prazo-entrega").value || "3 dias uteis",
                frete_tipo: freteVal,
                observacao_cliente: document.getElementById("nq-obs-cliente").value || null,
                itens: quoteCartItems.map(item => ({
                    produto_id: item.product_id,
                    qtd: Math.max(1, parseInt(item.qty || 1)),
                    preco_unit_proposto: Math.max(0.01, parseFloat(item.price || 0))
                }))
            };

            try {
                const res = await fetch(`${API_URL}/cotacoes/manual`, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "Accept": "application/json",
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(payload)
                });

                let data = null;
                try {
                    data = await res.json();
                } catch(parseErr) {
                    data = null;
                }

                btn.innerHTML = origText;
                btn.disabled = false;

                if (res.ok && (data?.id || data?.success || data?.data)) {
                    selectedPartner = null;
                    quoteCartItems = [];
                    clearQuoteFormError();
                    closeNewQuoteModal();
                    showToast("Cotação criada com sucesso!", "success");
                    loadQuotes(); // Refresh quotes list
                    switchTab('quotes');
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
                    showQuoteFormError(errMsg, "Falha ao gerar cotação");
                }
            } catch(e) {
                console.error("Error submitting quote:", e);
                btn.innerHTML = origText;
                btn.disabled = false;
                showQuoteFormError("Erro de comunicação com o servidor. Verifique sua conexão e tente novamente.", "Erro de Conexão");
            }
        }
    </script>
</body>
</html>
