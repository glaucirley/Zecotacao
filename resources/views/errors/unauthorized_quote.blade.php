<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acesso Não Autorizado — Zé Cotação</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <style>
        .unauthorized-card {
            max-width: 520px;
            width: 100%;
            background: #ffffff;
            border-radius: var(--radius-lg, 16px);
            padding: 36px 30px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08), 0 8px 10px -6px rgba(0, 0, 0, 0.04);
            border: 1px solid var(--color-border, #e5e7eb);
            text-align: center;
        }
        .icon-circle {
            width: 64px;
            height: 64px;
            margin: 0 auto 20px;
            border-radius: 50%;
            background-color: #fee2e2;
            color: #dc2626;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
        }
        .user-chip {
            background-color: var(--color-bg, #f3f4f6);
            border: 1px solid var(--color-border, #e5e7eb);
            border-radius: 8px;
            padding: 12px 16px;
            margin: 20px 0;
            font-size: 13px;
            color: var(--color-text, #374151);
            text-align: left;
        }
        .btn-group {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin-top: 24px;
            flex-wrap: wrap;
        }
    </style>
</head>
<body class="centered-layout">

    <div class="unauthorized-card">
        <div class="icon-circle">
            <svg width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
        </div>

        <h2 style="font-size: 20px; font-weight: 700; color: #111827; margin-bottom: 10px;">
            Esta cotação pertence a outro representante
        </h2>

        <p style="font-size: 14px; color: #6b7280; line-height: 1.5; margin-bottom: 16px;">
            Você está conectado ao sistema, porém esta cotação foi designada a outro vendedor da equipe e seu perfil não tem permissão para editá-la.
        </p>

        @if(auth()->check())
            <div class="user-chip">
                <div><strong>Usuário conectado:</strong> {{ auth()->user()->nome }}</div>
                <div style="font-size: 12px; color: #6b7280; margin-top: 4px;">
                    <strong>Perfil:</strong> {{ ucfirst(auth()->user()->papel) }} &bull; <strong>E-mail:</strong> {{ auth()->user()->email }}
                </div>
            </div>
        @endif

        <p style="font-size: 12px; color: #9ca3af; margin-bottom: 20px;">
            Esta tentativa de acesso foi registrada na trilha de auditoria para conformidade e controle interno.
        </p>

        <div class="btn-group">
            @if(auth()->check() && auth()->user()->isRepresentante())
                <a href="{{ url('/painel-representante') }}" class="btn btn-primary" style="padding: 10px 18px; text-decoration: none;">
                    Meu Painel de Cotações
                </a>
            @else
                <a href="{{ url('/dashboard') }}" class="btn btn-primary" style="padding: 10px 18px; text-decoration: none;">
                    Ir para Dashboard
                </a>
            @endif

            <form action="{{ url('/logout') }}" method="POST" style="margin: 0; display: inline;">
                @csrf
                <button type="submit" class="btn btn-secondary" style="padding: 10px 18px; border: 1px solid #d1d5db; background: #fff; color: #374151; cursor: pointer;">
                    Trocar de Usuário
                </button>
            </form>
        </div>
    </div>

</body>
</html>
