<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zé Cotação — Painel</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 999999;
            display: flex;
            flex-direction: column;
            gap: 10px;
            pointer-events: none;
            max-width: 400px;
            width: calc(100% - 40px);
        }
        .app-toast {
            pointer-events: auto;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 12px 16px;
            border-radius: 10px;
            color: #ffffff;
            font-size: 13.5px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.2), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            transform: translateY(-20px);
            opacity: 0;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .app-toast.show {
            transform: translateY(0);
            opacity: 1;
        }
        .app-toast.hide {
            transform: translateY(-15px);
            opacity: 0;
        }
        .app-toast-success { background: #059669; border: 1px solid #10b981; }
        .app-toast-error   { background: #dc2626; border: 1px solid #ef4444; }
        .app-toast-warning { background: #d97706; border: 1px solid #f59e0b; }
        .app-toast-info    { background: #2563eb; border: 1px solid #3b82f6; }
        .app-toast-icon    { font-size: 18px; line-height: 1; margin-top: 1px; }
        .app-toast-body    { flex-grow: 1; }
        .app-toast-title   { font-weight: 700; font-size: 13.5px; margin-bottom: 2px; }
        .app-toast-msg     { font-size: 12.5px; word-break: break-word; opacity: 0.95; }
        .app-toast-close   { background: transparent; border: none; color: #fff; font-size: 18px; line-height: 1; cursor: pointer; padding: 0 4px; opacity: 0.8; }
        .app-toast-close:hover { opacity: 1; }

        /* Grouped Sidebar Nav */
        .sidebar-section-title {
            padding: 16px 16px 6px 16px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #64748b;
            user-select: none;
            list-style: none;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .sidebar-collapsed .sidebar-section-title {
            display: none;
        }
        .sidebar-badge {
            margin-left: auto;
            background: #ef4444;
            color: #ffffff;
            font-size: 11px;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 999px;
            line-height: 1.2;
            display: inline-block;
            box-shadow: 0 1px 3px rgba(239, 68, 68, 0.3);
        }
        .sidebar-badge.info {
            background: #2563eb;
            box-shadow: 0 1px 3px rgba(37, 99, 235, 0.3);
        }

        /* Navbar Global Search */
        .btn-global-search {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 8px 14px;
            font-size: 12.5px;
            color: #64748b;
            cursor: pointer;
            width: 100%;
            max-width: 360px;
            transition: all 0.2s ease;
        }
        .btn-global-search:hover {
            background: #ffffff;
            border-color: #cbd5e1;
            color: #1e293b;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }
        .btn-global-search span {
            flex-grow: 1;
            text-align: left;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .btn-global-search kbd {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 5px;
            padding: 2px 6px;
            font-size: 10.5px;
            font-weight: 600;
            color: #475569;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }

        /* Global Search Modal */
        .global-search-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
            z-index: 999999;
            display: none;
            align-items: flex-start;
            justify-content: center;
            padding-top: 12vh;
        }
        .global-search-modal {
            background: #ffffff;
            width: 580px;
            max-width: calc(100% - 32px);
            border-radius: 14px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            overflow: hidden;
            border: 1px solid #e2e8f0;
            animation: searchModalIn 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes searchModalIn {
            from { opacity: 0; transform: translateY(-16px) scale(0.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .global-search-header {
            display: flex;
            align-items: center;
            padding: 14px 18px;
            border-bottom: 1px solid #f1f5f9;
            gap: 12px;
            background: #ffffff;
        }
        .global-search-header input {
            flex-grow: 1;
            border: none;
            outline: none;
            font-size: 15px;
            color: #0f172a;
            font-family: inherit;
        }
        .global-search-results {
            max-height: 380px;
            overflow-y: auto;
            padding: 8px;
        }
        .search-result-item {
            display: flex;
            flex-direction: column;
            gap: 3px;
            padding: 10px 14px;
            border-radius: 8px;
            text-decoration: none;
            cursor: pointer;
            transition: background 0.15s ease;
        }
        .search-result-item:hover, .search-result-item.active {
            background: #f1f5f9;
        }
        .search-result-title {
            font-size: 13.5px;
            font-weight: 600;
            color: #1e293b;
        }
        .search-result-sub {
            font-size: 12px;
            color: #64748b;
        }
        .global-search-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 16px;
            background: #f8fafc;
            border-top: 1px solid #f1f5f9;
            font-size: 11.5px;
            color: #64748b;
        }

        /* Universal Confirm Modal */
        .app-modal-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(3px);
            z-index: 999998;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }
        .app-modal-box {
            background: #ffffff;
            width: 440px;
            max-width: 100%;
            border-radius: 14px;
            padding: 24px;
            box-shadow: 0 20px 35px -5px rgba(0,0,0,0.25);
            border: 1px solid #e2e8f0;
            animation: searchModalIn 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .app-modal-title {
            font-size: 16px;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 8px 0;
        }
        .app-modal-msg {
            font-size: 13.5px;
            color: #475569;
            line-height: 1.5;
            margin-bottom: 20px;
        }
        .app-modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
    </style>
    @yield('styles')
</head>
<body>

    <div class="app-container">
        <script>
            (function() {
                const saved = localStorage.getItem('sidebar-collapsed');
                if (saved === 'true') {
                    document.querySelector('.app-container').classList.add('sidebar-collapsed');
                }
            })();
        </script>
        <!-- Mobile Sidebar Backdrop Overlay -->
        <div class="sidebar-mobile-backdrop" onclick="toggleSidebar()"></div>
        
        <!-- Sidebar Navigation -->
        @if(auth()->check() && !auth()->user()->isRepresentante())
        <aside class="sidebar">
            <div class="sidebar-brand">
                <span class="brand-full">Zé <span>Cotação</span></span>
                <span class="brand-mini">ZC</span>
            </div>
            
            <nav style="flex-grow: 1; overflow-y: auto;">
                <ul class="sidebar-menu">
                    <!-- SEÇÃO: OPERAÇÃO -->
                    <li class="sidebar-section-title"><span>Operação</span></li>

                    <li>
                        <a href="{{ url('/dashboard') }}" class="sidebar-link {{ request()->is('dashboard*') ? 'active' : '' }}">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg>
                            <span class="sidebar-text">Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ url('/cotacoes') }}" class="sidebar-link {{ request()->is('cotacoes') ? 'active' : '' }}">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                            <span class="sidebar-text">Cotações</span>
                        </a>
                    </li>
                    @if(!auth()->user()->isFaturamento())
                    <li>
                        <a href="{{ url('/funil') }}" class="sidebar-link {{ request()->is('funil*') ? 'active' : '' }}">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 3H2l8 9v6l4 3v-9L22 3z"/></svg>
                            <span class="sidebar-text">Funil</span>
                        </a>
                    </li>
                    @endif
                    @if(auth()->user()->isGestor() || auth()->user()->isDiretor() || auth()->user()->isAdministrador())
                        <li>
                            <a href="{{ url('/aprovacoes') }}" class="sidebar-link {{ request()->is('aprovacoes*') ? 'active' : '' }}">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                                <span class="sidebar-text">Aprovações</span>
                                <span id="sidebar-badge-approvals" class="sidebar-badge" style="display:none;">0</span>
                            </a>
                        </li>
                    @endif
                    
                    @if(auth()->user()->isFaturamento() || auth()->user()->isDiretor() || auth()->user()->isAdministrador())
                        <li>
                            <a href="{{ url('/faturamento') }}" class="sidebar-link {{ request()->is('faturamento*') ? 'active' : '' }}">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M12 4v16"/><path d="M2 12h20"/></svg>
                                <span class="sidebar-text">Faturamento</span>
                                <span id="sidebar-badge-billing" class="sidebar-badge" style="display:none;">0</span>
                            </a>
                        </li>
                    @endif
                    <!-- SEÇÃO: CADASTROS -->
                    @if(auth()->user()->isAdministrador() || auth()->user()->isDiretor())
                    <li class="sidebar-section-title"><span>Cadastros</span></li>
                    <li>
                        <a href="{{ url('/clientes') }}" class="sidebar-link {{ request()->is('clientes*') ? 'active' : '' }}">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="2" width="16" height="20" rx="2" ry="2"/><line x1="9" y1="22" x2="9" y2="16"/><line x1="15" y1="22" x2="15" y2="16"/><line x1="9" y1="16" x2="15" y2="16"/><path d="M8 6h.01"/><path d="M16 6h.01"/><path d="M8 10h.01"/><path d="M16 10h.01"/></svg>
                                <span class="sidebar-text">Clientes</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ url('/produtos') }}" class="sidebar-link {{ request()->is('produtos*') ? 'active' : '' }}">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                                <span class="sidebar-text">Produtos</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ url('/usuarios') }}" class="sidebar-link {{ request()->is('usuarios*') ? 'active' : '' }}">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                <span class="sidebar-text">Usuários</span>
                            </a>
                        </li>
                    @endif

                    <!-- SEÇÃO: SISTEMA -->
                    @if(auth()->user()->isAdministrador() || auth()->user()->isDiretor() || auth()->user()->hasChatAccess())
                    <li class="sidebar-section-title"><span>Sistema</span></li>
                        @if(auth()->user()->isAdministrador() || auth()->user()->isDiretor())
                        <li>
                            <a href="{{ url('/parametros') }}" class="sidebar-link {{ request()->is('parametros*') ? 'active' : '' }}">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                                <span class="sidebar-text">Parâmetros</span>
                            </a>
                        </li>
                        @endif
                        @if(auth()->user()->hasChatAccess())
                        <li>
                            <a href="{{ url('/conversas') }}" class="sidebar-link {{ request()->is('conversas*') ? 'active' : '' }}">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                                <span class="sidebar-text">Conversas</span>
                            </a>
                        </li>
                        @endif
                    @endif
                </ul>
            </nav>
            
            <div class="sidebar-footer">
                <form action="{{ url('/logout') }}" method="POST" id="logout-form">
                    @csrf
                    <button type="submit" class="sidebar-link" style="width: 100%; border: none; background: none; cursor: pointer; text-align: left;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                        <span class="sidebar-text">Sair</span>
                    </button>
                </form>
            </div>
        </aside>
        @endif

        <!-- Main Wrapper -->
        <div class="main-wrapper">
            <!-- Navbar -->
            <header class="navbar">
                <div style="display: flex; align-items: center; gap: 14px;">
                    @if(auth()->check() && !auth()->user()->isRepresentante())
                    <button type="button" id="sidebar-toggle" style="background: none; border: none; cursor: pointer; color: var(--color-text-muted); display: flex; align-items: center; justify-content: center; padding: 6px; border-radius: 8px; transition: var(--transition);" onmouseover="this.style.color='var(--color-primary)'" onmouseout="this.style.color='var(--color-text-muted)'" onclick="toggleSidebar()">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                    </button>
                    @endif
                    <button type="button" class="btn-go-back" onclick="appGoBack()" title="Voltar para a página anterior">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                        <span>Voltar</span>
                    </button>
                    <h2 class="page-title" style="margin: 0;">@yield('page_title', 'Painel Geral')</h2>
                </div>
                
                <!-- Global Search Box -->
                @if(auth()->check() && !auth()->user()->isRepresentante())
                <div style="flex-grow: 1; max-width: 380px; margin: 0 16px;">
                    <button type="button" class="btn-global-search" onclick="openGlobalSearch()" title="Pressione Ctrl+K para buscar">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <span>Buscar cotação, cliente, pedido...</span>
                        <kbd>Ctrl+K</kbd>
                    </button>
                </div>
                @endif

                <div class="user-profile-badge">
                    <span style="font-weight: 500;">{{ auth()->user()->nome }}</span>
                    <span class="user-role-label">{{ auth()->user()->papel }}</span>
                </div>
            </header>

            <!-- Page Content -->
            <main class="content-body">
                @yield('content')
            </main>
        </div>
    </div>

    <script>
        let lastToastMessage = null;
        let lastToastTime = 0;
        function showToast(message, type = 'info', title = null) {
            const now = Date.now();
            const strMsg = String(message || '').trim();
            if (strMsg === lastToastMessage && (now - lastToastTime) < 2000) return;
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

            if (closeBtn) {
                closeBtn.onclick = (e) => { e.stopPropagation(); dismiss(); };
            }
            toast.onclick = dismiss;

            container.appendChild(toast);
            requestAnimationFrame(() => toast.classList.add('show'));
            setTimeout(dismiss, 5000);
        }
    </script>
    <!-- Global Search Modal HTML -->
    <div id="global-search-overlay" class="global-search-overlay" onclick="handleSearchOverlayClick(event)">
        <div class="global-search-modal">
            <div class="global-search-header">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" id="global-search-input" placeholder="Digite número da cotação, cliente, pedido ou CNPJ..." autocomplete="off">
                <kbd style="font-size:10px; background:#f1f5f9; padding:2px 6px; border-radius:4px; border:1px solid #cbd5e1;">ESC</kbd>
            </div>
            <div id="global-search-results" class="global-search-results">
                <div style="padding: 24px; text-align: center; color: #94a3b8; font-size: 13px;">
                    Digite ao menos 2 caracteres para pesquisar...
                </div>
            </div>
            <div class="global-search-footer">
                <span>Dica: Use <strong>Ctrl + K</strong> para abrir em qualquer página</span>
                <span>Navegue e clique para acessar</span>
            </div>
        </div>
    </div>

    <!-- Universal App Confirmation Modal HTML -->
    <div id="app-confirm-overlay" class="app-modal-overlay">
        <div class="app-modal-box">
            <h3 id="app-confirm-title" class="app-modal-title">Confirmação</h3>
            <div id="app-confirm-msg" class="app-modal-msg">Tem certeza de que deseja realizar esta ação?</div>
            <div class="app-modal-actions">
                <button type="button" id="app-confirm-cancel" class="btn btn-outline" style="padding: 8px 16px; font-size: 13px;">Cancelar</button>
                <button type="button" id="app-confirm-ok" class="btn btn-primary" style="padding: 8px 18px; font-size: 13px; font-weight:600;">Confirmar</button>
            </div>
        </div>
    </div>

    @yield('scripts')
    <script>
        function appGoBack() {
            if (window.history.length > 1 && document.referrer && document.referrer.includes(window.location.host)) {
                window.history.back();
            } else {
                window.location.href = "{{ url('/dashboard') }}";
            }
        }

        function toggleSidebar() {
            const container = document.querySelector('.app-container');
            const isMobile = window.innerWidth <= 768;
            
            if (isMobile) {
                container.classList.toggle('sidebar-mobile-open');
            } else {
                const isCollapsed = container.classList.toggle('sidebar-collapsed');
                localStorage.setItem('sidebar-collapsed', isCollapsed ? 'true' : 'false');
            }
            
            setTimeout(() => {
                window.dispatchEvent(new Event('resize'));
            }, 250);
        }

        // Global Search Logic
        let searchDebounceTimer = null;
        function openGlobalSearch() {
            const overlay = document.getElementById('global-search-overlay');
            if (!overlay) return;
            overlay.style.display = 'flex';
            const input = document.getElementById('global-search-input');
            if (input) {
                input.value = '';
                input.focus();
            }
            document.getElementById('global-search-results').innerHTML = `
                <div style="padding: 24px; text-align: center; color: #94a3b8; font-size: 13px;">
                    Digite ao menos 2 caracteres para pesquisar...
                </div>
            `;
        }

        function closeGlobalSearch() {
            const overlay = document.getElementById('global-search-overlay');
            if (overlay) overlay.style.display = 'none';
        }

        function handleSearchOverlayClick(e) {
            if (e.target.id === 'global-search-overlay') {
                closeGlobalSearch();
            }
        }

        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && (e.key === 'k' || e.key === 'K')) {
                e.preventDefault();
                openGlobalSearch();
            } else if (e.key === 'Escape') {
                closeGlobalSearch();
                closeAppConfirm();
            }
        });

        const globalSearchInput = document.getElementById('global-search-input');
        if (globalSearchInput) {
            globalSearchInput.addEventListener('input', (e) => {
                clearTimeout(searchDebounceTimer);
                const q = e.target.value.trim();
                const container = document.getElementById('global-search-results');
                if (q.length < 2) {
                    container.innerHTML = `
                        <div style="padding: 24px; text-align: center; color: #94a3b8; font-size: 13px;">
                            Digite ao menos 2 caracteres para pesquisar...
                        </div>
                    `;
                    return;
                }

                container.innerHTML = `
                    <div style="padding: 20px; text-align: center; color: #64748b; font-size: 13px;">
                        Buscando...
                    </div>
                `;

                searchDebounceTimer = setTimeout(async () => {
                    try {
                        const res = await fetch(`/api/v1/dashboard/search?q=${encodeURIComponent(q)}`);
                        const data = await res.json();
                        if (data.success && data.results && data.results.length > 0) {
                            container.innerHTML = data.results.map(r => `
                                <a href="${r.url}" class="search-result-item" onclick="closeGlobalSearch()">
                                    <div class="search-result-title">${r.title}</div>
                                    <div class="search-result-sub">${r.subtitle}</div>
                                </a>
                            `).join('');
                        } else {
                            container.innerHTML = `
                                <div style="padding: 24px; text-align: center; color: #94a3b8; font-size: 13px;">
                                    Nenhum resultado encontrado para "<strong>${escapeHtml(q)}</strong>"
                                </div>
                            `;
                        }
                    } catch (err) {
                        container.innerHTML = `
                            <div style="padding: 24px; text-align: center; color: #ef4444; font-size: 13px;">
                                Erro ao realizar pesquisa.
                            </div>
                        `;
                    }
                }, 250);
            });
        }

        function escapeHtml(str) {
            return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        }

        // Universal Confirmation Modal (Promise + Callback compatible)
        let onConfirmCallback = null;
        let onCancelCallback = null;
        function appConfirmModal(arg1 = 'Confirmação', arg2 = 'Tem certeza?', arg3 = 'Confirmar', arg4 = false) {
            let title = 'Confirmação';
            let message = 'Tem certeza?';
            let confirmText = 'Confirmar';
            let cancelText = 'Cancelar';
            let isDanger = false;
            let onConfirm = null;

            if (typeof arg1 === 'object' && arg1 !== null) {
                title = arg1.title || title;
                message = arg1.message || message;
                confirmText = arg1.confirmText || confirmText;
                cancelText = arg1.cancelText || cancelText;
                isDanger = !!arg1.isDanger;
                onConfirm = arg1.onConfirm || null;
            } else {
                title = arg1 || title;
                message = arg2 || message;
                confirmText = arg3 || confirmText;
                isDanger = !!arg4;
            }

            return new Promise((resolve) => {
                const titleEl = document.getElementById('app-confirm-title');
                const msgEl = document.getElementById('app-confirm-msg');
                const okBtn = document.getElementById('app-confirm-ok');
                const cancelBtn = document.getElementById('app-confirm-cancel');
                
                if (titleEl) titleEl.innerText = title;
                if (msgEl) msgEl.innerText = message;
                if (okBtn) okBtn.innerText = confirmText;
                if (cancelBtn) cancelBtn.innerText = cancelText;

                if (okBtn) {
                    if (isDanger) {
                        okBtn.className = 'btn';
                        okBtn.style.background = '#dc2626';
                        okBtn.style.color = '#ffffff';
                    } else {
                        okBtn.className = 'btn btn-primary';
                        okBtn.style.background = '';
                        okBtn.style.color = '';
                    }
                }

                onConfirmCallback = () => {
                    resolve(true);
                    if (typeof onConfirm === 'function') onConfirm();
                };
                onCancelCallback = () => {
                    resolve(false);
                };

                const overlay = document.getElementById('app-confirm-overlay');
                if (overlay) overlay.style.display = 'flex';
            });
        }

        function closeAppConfirm() {
            const overlay = document.getElementById('app-confirm-overlay');
            if (overlay) overlay.style.display = 'none';
            if (typeof onCancelCallback === 'function') {
                const cb = onCancelCallback;
                onCancelCallback = null;
                cb();
            }
            onConfirmCallback = null;
        }

        document.getElementById('app-confirm-cancel')?.addEventListener('click', closeAppConfirm);
        document.getElementById('app-confirm-ok')?.addEventListener('click', () => {
            const cb = onConfirmCallback;
            const overlay = document.getElementById('app-confirm-overlay');
            if (overlay) overlay.style.display = 'none';
            onConfirmCallback = null;
            onCancelCallback = null;
            if (typeof cb === 'function') cb();
        });

        // Load sidebar queue badges dynamically
        async function updateSidebarQueueBadges() {
            try {
                const res = await fetch('/api/v1/dashboard/queue');
                const json = await res.json();
                if (json.success && json.data) {
                    const appBadge = document.getElementById('sidebar-badge-approvals');
                    if (appBadge) {
                        if (json.data.approvals > 0) {
                            appBadge.innerText = json.data.approvals;
                            appBadge.style.display = 'inline-block';
                        } else {
                            appBadge.style.display = 'none';
                        }
                    }

                    const billBadge = document.getElementById('sidebar-badge-billing');
                    if (billBadge) {
                        if (json.data.billing > 0) {
                            billBadge.innerText = json.data.billing;
                            billBadge.style.display = 'inline-block';
                        } else {
                            billBadge.style.display = 'none';
                        }
                    }
                }
            } catch (e) {
                // Ignore queue fetch errors silently
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            updateSidebarQueueBadges();
        });
    </script>
</body>
</html>
