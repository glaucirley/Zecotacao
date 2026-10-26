<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Cotação Indisponível' }} — Zé Cotação</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <style>
        .error-card {
            max-width: 500px;
            width: 100%;
            background: #ffffff;
            border-radius: var(--radius-lg, 16px);
            padding: 36px 30px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08);
            border: 1px solid var(--color-border, #e5e7eb);
            text-align: center;
        }
        .icon-circle-warning {
            width: 64px;
            height: 64px;
            margin: 0 auto 20px;
            border-radius: 50%;
            background-color: #fef3c7;
            color: #d97706;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
        }
    </style>
</head>
<body class="centered-layout">
    <div class="error-card">
        <div class="icon-circle-warning">
            <svg width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
        </div>
        <h2 style="font-size: 20px; font-weight: 700; color: #111827; margin-bottom: 10px;">
            {{ $title ?? 'Cotação Indisponível' }}
        </h2>
        <p style="font-size: 14px; color: #6b7280; line-height: 1.5; margin-bottom: 24px;">
            {{ $message ?? 'O link acessado é inválido, foi cancelado ou já expirou.' }}
        </p>
        <div>
            @if(auth()->check())
                <a href="{{ auth()->user()->isRepresentante() ? url('/painel-representante') : url('/dashboard') }}" class="btn btn-primary" style="text-decoration: none; padding: 10px 20px;">
                    Voltar ao Início
                </a>
            @else
                <a href="{{ route('login') }}" class="btn btn-primary" style="text-decoration: none; padding: 10px 20px;">
                    Acessar o Sistema
                </a>
            @endif
        </div>
    </div>
</body>
</html>
