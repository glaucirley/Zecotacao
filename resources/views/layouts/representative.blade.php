<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Zé Cotação — @yield('page_title', 'Painel do Representante')</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    
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
            background-color: #0f172a; /* Fundo elegante de contêiner escuro */
            color: var(--color-text);
            display: flex;
            justify-content: center;
            min-height: 100vh;
        }
        
        /* Contêiner Mobile do Representante */
        .mobile-container {
            width: 100%;
            max-width: 520px;
            background-color: var(--color-background);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            position: relative;
            box-shadow: 0 0 40px rgba(0,0,0,0.5);
            overflow-x: hidden;
            padding-bottom: 75px; /* espaçamento da barra inferior */
        }
        
        /* Cabeçalho do App */
        .app-header {
            background: linear-gradient(135deg, var(--color-primary), #115e3b);
            color: white;
            padding: 16px 16px;
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
        }
        
        .btn-header-back {
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            width: 34px;
            height: 34px;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.15);
            transition: background 0.15s ease;
        }
        .btn-header-back:hover {
            background: rgba(255, 255, 255, 0.25);
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
        
        /* Conteúdo Principal */
        .content-body {
            padding: 16px;
            flex-grow: 1;
        }
        
        /* Barra de Navegação Inferior (Mobile Tabbar) */
        .tab-bar {
            position: fixed;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100%;
            max-width: 520px;
            background-color: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-top: 1px solid var(--color-border);
            display: flex;
            justify-content: space-around;
            padding: 10px 0 15px 0;
            z-index: 1000;
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
            text-decoration: none;
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

        .notification-dot {
            position: absolute;
            top: -2px;
            right: 12px;
            background-color: var(--status-perdida);
            color: white;
            font-size: 9px;
            font-weight: 700;
            padding: 1px 5px;
            border-radius: 10px;
            border: 2px solid white;
        }

        /* Adaptações do layout mobile para componentes de cotação */
        .mobile-container .card {
            background: #ffffff;
            border-radius: 14px;
            border: 1px solid var(--color-border);
            padding: 16px;
            margin-bottom: 16px;
            box-shadow: var(--shadow-sm);
        }

        .mobile-container .grid-2 {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .mobile-container #items-table table, 
        .mobile-container #items-table thead, 
        .mobile-container #items-table tbody, 
        .mobile-container #items-table th, 
        .mobile-container #items-table td, 
        .mobile-container #items-table tr {
            display: block;
        }
        .mobile-container #items-table thead {
            display: none;
        }
        .mobile-container .desktop-only {
            display: none !important;
        }
        .mobile-container .mobile-sub-info {
            display: block;
        }
        .mobile-container .mobile-label {
            display: inline-block;
            margin-right: 4px;
        }

        .mobile-container #add-item-row {
            flex-direction: column;
            align-items: stretch !important;
        }
        .mobile-container #add-item-row > div {
            width: 100% !important;
        }

        .mobile-container .actions-panel-card {
            flex-direction: column-reverse !important;
            align-items: stretch !important;
            gap: 12px !important;
            padding: 16px !important;
        }
        .mobile-container .actions-panel-card .left-actions,
        .mobile-container .actions-panel-card .right-actions {
            flex-direction: column-reverse !important;
            width: 100% !important;
            gap: 10px !important;
        }
        .mobile-container .actions-panel-card button {
            width: 100% !important;
            justify-content: center !important;
            padding: 12px 16px !important;
            font-size: 14px !important;
            margin: 0 !important;
        }

        .mobile-container .sticky-mobile-total-bar {
            display: none !important;
        }
    </style>
    @yield('styles')
</head>
<body>
    <div class="mobile-container">
        <!-- Cabeçalho Mobile -->
        <header class="app-header">
            <div class="header-top">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <a href="{{ url('/painel-representante') }}" class="btn-header-back" title="Voltar para Minhas Cotações">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                    </a>
                    <div class="logo-title">Zé <span>Cotação</span></div>
                </div>
                <div class="user-info">
                    <a href="{{ url('/painel-representante?tab=alerts') }}" style="color:white; text-decoration:none; position:relative; display:flex; align-items:center; justify-content:center; padding:4px 6px; border-radius:8px;" title="Ver Alertas">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                        <div id="rep-header-alerts-badge" class="notification-dot" style="display:none; top:-2px; right:-2px;">0</div>
                    </a>
                    <span id="header-user-name">{{ auth()->user()->nome ?? 'Representante' }}</span>
                    <span class="role-badge">Representante</span>
                </div>
            </div>
        </header>

        <!-- Conteúdo da Página -->
        <main class="content-body">
            @yield('content')
        </main>

        <!-- Barra Inferior de Navegação Mobile -->
        <nav class="tab-bar">
            <a href="{{ url('/painel-representante?tab=quotes') }}" class="tab-button {{ request()->is('cotacoes*') || request()->is('painel-representante*') ? 'active' : '' }}">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                <span>Cotações</span>
            </a>
            <a href="{{ url('/painel-representante?tab=checkin') }}" class="tab-button">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                <span>Visitas</span>
            </a>
            <a href="{{ url('/painel-representante?tab=alerts') }}" class="tab-button">
                <div id="rep-alerts-badge" class="notification-dot" style="display: none;">0</div>
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                <span>Alertas</span>
            </a>
            <a href="{{ url('/painel-representante?tab=profile') }}" class="tab-button">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                <span>Perfil</span>
            </a>
        </nav>
    </div>

    <script>
        // Sincroniza contador de alertas no layout do representante
        document.addEventListener("DOMContentLoaded", async () => {
            try {
                const res = await fetch("{{ url('/api/notificacoes') }}");
                if (res.ok) {
                    const data = await res.json();
                    const unread = (data.data || []).filter(n => !n.lida).length;
                    const badge = document.getElementById("rep-alerts-badge");
                    const headerBadge = document.getElementById("rep-header-alerts-badge");
                    if (badge) {
                        if (unread > 0) {
                            badge.innerText = unread > 99 ? '99+' : unread;
                            badge.style.display = 'block';
                        } else {
                            badge.style.display = 'none';
                        }
                    }
                    if (headerBadge) {
                        if (unread > 0) {
                            headerBadge.innerText = unread > 99 ? '99+' : unread;
                            headerBadge.style.display = 'flex';
                        } else {
                            headerBadge.style.display = 'none';
                        }
                    }
                }
            } catch(e) {}
        });
    </script>
    @yield('scripts')
</body>
</html>
