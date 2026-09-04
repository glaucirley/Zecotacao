<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zé Cotação — Painel</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
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
        <aside class="sidebar">
            <div class="sidebar-brand">
                <span class="brand-full">Zé <span>Cotação</span></span>
                <span class="brand-mini">ZC</span>
            </div>
            
            <nav style="flex-grow: 1;">
                <ul class="sidebar-menu">
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
                    <li>
                        <a href="{{ url('/funil') }}" class="sidebar-link {{ request()->is('funil*') ? 'active' : '' }}">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 3H2l8 9v6l4 3v-9L22 3z"/></svg>
                            <span class="sidebar-text">Funil</span>
                        </a>
                    </li>
                    @if(auth()->user()->hasChatAccess())
                        <li>
                            <a href="{{ url('/conversas') }}" class="sidebar-link {{ request()->is('conversas*') ? 'active' : '' }}">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                                <span class="sidebar-text">Conversas</span>
                            </a>
                        </li>
                    @endif
                    @if(auth()->user()->isGestor() || auth()->user()->isDiretor() || auth()->user()->isAdministrador())
                        <li>
                            <a href="{{ url('/aprovacoes') }}" class="sidebar-link {{ request()->is('aprovacoes*') ? 'active' : '' }}">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                                <span class="sidebar-text">Aprovações</span>
                            </a>
                        </li>
                    @endif
                    
                    @if(auth()->user()->isFaturamento() || auth()->user()->isDiretor() || auth()->user()->isAdministrador())
                        <li>
                            <a href="{{ url('/faturamento') }}" class="sidebar-link {{ request()->is('faturamento*') ? 'active' : '' }}">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M12 4v16"/><path d="M2 12h20"/></svg>
                                <span class="sidebar-text">Faturamento</span>
                            </a>
                        </li>
                    @endif
                    
                    @if(auth()->user()->isAdministrador())
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
                        @if(auth()->user()->isAdministrador() || auth()->user()->isDiretor())
                        <li>
                            <a href="{{ url('/parametros') }}" class="sidebar-link {{ request()->is('parametros*') ? 'active' : '' }}">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                                <span class="sidebar-text">Parâmetros</span>
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

        <!-- Main Wrapper -->
        <div class="main-wrapper">
            <!-- Navbar -->
            <header class="navbar">
                <div style="display: flex; align-items: center; gap: 14px;">
                    <button type="button" id="sidebar-toggle" style="background: none; border: none; cursor: pointer; color: var(--color-text-muted); display: flex; align-items: center; justify-content: center; padding: 6px; border-radius: 8px; transition: var(--transition);" onmouseover="this.style.color='var(--color-primary)'" onmouseout="this.style.color='var(--color-text-muted)'" onclick="toggleSidebar()">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                    </button>
                    <button type="button" class="btn-go-back" onclick="appGoBack()" title="Voltar para a página anterior">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                        <span>Voltar</span>
                    </button>
                    <h2 class="page-title" style="margin: 0;">@yield('page_title', 'Painel Geral')</h2>
                </div>
                
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
            
            // Dispatch a window resize event so charts adapt layout instantly
            setTimeout(() => {
                window.dispatchEvent(new Event('resize'));
            }, 250);
        }
    </script>
</body>
</html>
