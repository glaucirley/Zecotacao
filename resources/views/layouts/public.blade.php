<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zé Cotação — Cotação Comercial</title>
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
        @media (max-width: 576px) {
            .toast-container {
                top: 15px;
                left: 15px;
                right: 15px;
                width: calc(100% - 30px);
                max-width: none;
            }
        }
        .app-toast {
            pointer-events: auto;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 12px 16px;
            border-radius: 12px;
            color: #ffffff;
            font-size: 13.5px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.25), 0 8px 10px -6px rgba(0, 0, 0, 0.15);
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
    </style>
    @yield('styles')
</head>
<body style="background-color: var(--color-bg);">

    <!-- Header bar for representative cotação -->
    <header class="navbar" style="padding: 0 40px; box-shadow: var(--shadow-sm); margin-bottom: 24px;">
        <div class="logo-header" style="margin-bottom: 0; text-align: left; display: flex; align-items: center; gap: 8px;">
            <h2 style="color: var(--color-primary); font-size: 20px; font-weight: 700;">Zé <span style="font-weight: 400; color: var(--color-text-muted);">Cotação</span></h2>
        </div>
        <div>
            <span class="user-role-label" style="background-color: #3b82f6;">Representante</span>
        </div>
    </header>

    <!-- Main Container -->
    <main style="max-width: 1200px; width: 100%; margin: 0 auto; padding: 0 20px 40px 20px;">
        @yield('content')
    </main>

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
        window.showToast = showToast;
    </script>
    @yield('scripts')
</body>
</html>
