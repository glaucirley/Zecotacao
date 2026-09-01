<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zé Cotação — Login</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body class="centered-layout">

    <div class="auth-card">
        <div class="logo-header">
            <h1>Zé <span>Cotação</span></h1>
            <span>Painel Administrativo Comercial</span>
        </div>

        @if($errors->any())
            <div style="background-color: #fde8e8; border: 1px solid #f8b4b4; color: #9b1c1c; padding: 12px; border-radius: var(--radius-md); font-size: 13px; margin-bottom: 20px;">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ url('/login') }}" method="POST">
            @csrf
            
            <div class="form-group">
                <label for="email" class="form-label">E-mail</label>
                <input type="email" name="email" id="email" class="form-control" placeholder="admin@zecotacao.com.br" required autofocusValue>
            </div>

            <div class="form-group" style="margin-bottom: 24px;">
                <label for="password" class="form-label">Senha</label>
                <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px;">
                Entrar no Sistema
            </button>
        </form>

        <div style="margin-top: 24px; text-align: center; font-size: 12px; color: var(--color-text-muted);">
            Se você for um representante, acesse a plataforma diretamente através do link enviado pelo WhatsApp.
        </div>
    </div>

</body>
</html>
