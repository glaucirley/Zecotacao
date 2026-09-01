<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zé Cotação — Cotação Comercial</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
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

    @yield('scripts')
</body>
</html>
