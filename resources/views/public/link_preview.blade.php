<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cotação Zé Cotação</title>
    
    <!-- Open Graph / WhatsApp Preview (Genérico, sem dados sensíveis) -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="Cotação Zé Cotação">
    <meta property="og:description" content="Acesse o painel do Zé Cotação para visualizar os detalhes desta cotação comercial.">
    <meta property="og:image" content="{{ asset('preview.png') }}">
    <meta property="og:url" content="{{ url()->current() }}">
    
    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Cotação Zé Cotação">
    <meta name="twitter:description" content="Acesse o painel do Zé Cotação para visualizar os detalhes desta cotação comercial.">
    <meta name="twitter:image" content="{{ asset('preview.png') }}">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body class="centered-layout">
    <div class="auth-card" style="text-align: center;">
        <div class="logo-header">
            <h1>Zé <span>Cotação</span></h1>
            <span>Painel Administrativo Comercial</span>
        </div>
        <p style="margin-top: 20px; color: var(--color-text-muted); font-size: 14px;">
            Carregando cotação segura...
        </p>
        <a href="{{ route('login', ['redirect' => request()->getRequestUri()]) }}" class="btn btn-primary" style="margin-top: 15px; display: inline-block; text-decoration: none;">
            Acessar Sistema
        </a>
    </div>
</body>
</html>
