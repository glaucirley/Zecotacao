<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Proposta Comercial #{{ $quote->numero }} · Central Veterinária</title>
    
    @php
        $scheme = (request()->isSecure() || strtolower(request()->header('X-Forwarded-Proto', '')) === 'https') ? 'https' : 'http';
        $host = request()->getHttpHost();
        $currentDomainUrl = $scheme . '://' . $host;
        $ogImageUrl = $currentDomainUrl . '/images/preview.png';
    @endphp

    <!-- Segurança e Privacidade (Permite preview nos mensageiros sem indexar no Google) -->
    <meta name="robots" content="noindex, nofollow">
    <meta name="referrer" content="no-referrer">
    
    <!-- Prévia Rica no WhatsApp e Redes Sociais (Open Graph & Twitter Card) -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Central Veterinária">
    <meta property="og:title" content="Proposta Comercial {{ $quote->numero }} · Central Veterinária">
    <meta property="og:description" content="Proposta Comercial {{ $quote->numero }} · Central Veterinária">
    <meta name="description" content="Proposta Comercial {{ $quote->numero }} · Central Veterinária">
    <meta property="og:url" content="{{ $currentDomainUrl . request()->getRequestUri() }}">
    <meta property="og:image" content="{{ $ogImageUrl }}">
    <meta property="og:image:secure_url" content="{{ $ogImageUrl }}">
    <meta property="og:image:type" content="image/png">
    <meta property="og:image:width" content="600">
    <meta property="og:image:height" content="600">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Proposta Comercial {{ $quote->numero }} · Central Veterinária">
    <meta name="twitter:description" content="Proposta Comercial {{ $quote->numero }} · Central Veterinária">
    <meta name="twitter:image" content="{{ $ogImageUrl }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0f5132;
            --primary-dark: #0a3622;
            --primary-light: #e8f5e9;
            --accent: #16a34a;
            --bg: #f8fafc;
            --card-bg: #ffffff;
            --border: #e2e8f0;
            --text-dark: #0f172a;
            --text-muted: #64748b;
            --radius-md: 12px;
            --radius-lg: 16px;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.06);
            --shadow-lg: 0 10px 25px rgba(0,0,0,0.08);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            background-color: var(--bg);
            color: var(--text-dark);
            line-height: 1.5;
            padding-bottom: 90px;
        }

        .container {
            max-width: 720px;
            margin: 0 auto;
            padding: 16px;
        }

        /* Top Brand Header */
        .brand-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: var(--card-bg);
            padding: 16px 20px;
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            margin-bottom: 16px;
        }
        .brand-logo-area {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .brand-icon {
            width: 42px;
            height: 42px;
            background: linear-gradient(135deg, #0f5132 0%, #16a34a 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 20px;
            font-weight: 800;
            box-shadow: 0 2px 8px rgba(15, 81, 50, 0.25);
        }
        .brand-text h1 {
            font-size: 15px;
            font-weight: 800;
            color: var(--primary);
            letter-spacing: -0.2px;
            text-transform: uppercase;
        }
        .brand-text p {
            font-size: 11.5px;
            color: var(--text-muted);
            font-weight: 500;
        }
        .quote-badge-top {
            background: #dcfce7;
            color: #15803d;
            border: 1px solid #bbf7d0;
            font-size: 11px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        /* Hero Quote Summary Card */
        .hero-card {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #ffffff;
            border-radius: var(--radius-lg);
            padding: 24px 20px;
            margin-bottom: 16px;
            box-shadow: var(--shadow-md);
            position: relative;
            overflow: hidden;
        }
        .hero-card::after {
            content: "";
            position: absolute;
            top: -30px;
            right: -30px;
            width: 140px;
            height: 140px;
            background: radial-gradient(circle, rgba(22, 163, 74, 0.25) 0%, transparent 70%);
            border-radius: 50%;
        }
        .hero-sub {
            font-size: 12px;
            color: #94a3b8;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .hero-quote-num {
            font-size: 22px;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 14px;
            display: flex;
            align-items: baseline;
            gap: 8px;
        }
        .hero-total-box {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 12px;
            padding: 14px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            backdrop-filter: blur(8px);
        }
        .hero-total-label {
            font-size: 12px;
            color: #cbd5e1;
            font-weight: 600;
        }
        .hero-total-val {
            font-size: 24px;
            font-weight: 800;
            color: #4ade80;
            letter-spacing: -0.5px;
        }

        /* Section Cards */
        .section-card {
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            padding: 18px 20px;
            margin-bottom: 16px;
            box-shadow: var(--shadow-sm);
        }
        .section-title {
            font-size: 13.5px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        @media (max-width: 480px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
        .info-item {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .info-label {
            font-size: 11px;
            color: var(--text-muted);
            font-weight: 600;
            text-transform: uppercase;
        }
        .info-value {
            font-size: 13px;
            font-weight: 700;
            color: var(--text-dark);
            word-break: break-word;
        }

        /* Items List */
        .item-row {
            padding: 12px 0;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
        }
        .item-row:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        .item-desc-box {
            flex: 1;
            min-width: 0;
        }
        .item-name {
            font-size: 13px;
            font-weight: 700;
            color: var(--text-dark);
            line-height: 1.3;
        }
        .item-meta {
            font-size: 11.5px;
            color: var(--text-muted);
            margin-top: 3px;
        }
        .item-calc-box {
            text-align: right;
            white-space: nowrap;
        }
        .item-subtotal {
            font-size: 13.5px;
            font-weight: 800;
            color: var(--text-dark);
        }
        .item-unit-price {
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 2px;
        }

        /* Totals Summary */
        .totals-block {
            margin-top: 14px;
            padding-top: 12px;
            border-top: 2px dashed var(--border);
        }
        .total-line {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
            padding: 4px 0;
            color: var(--text-muted);
        }
        .total-line.final {
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px solid var(--border);
            font-size: 16px;
            font-weight: 800;
            color: var(--primary);
        }
        .total-line.discount {
            color: #dc2626;
            font-weight: 600;
        }

        /* Floating Action Bar */
        .floating-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: #ffffff;
            border-top: 1px solid var(--border);
            padding: 12px 16px;
            display: flex;
            gap: 10px;
            z-index: 100;
            box-shadow: 0 -4px 15px rgba(0,0,0,0.06);
            max-width: 720px;
            margin: 0 auto;
        }
        .btn-action {
            flex: 1;
            padding: 14px 16px;
            border-radius: var(--radius-md);
            font-size: 13.5px;
            font-weight: 700;
            text-align: center;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.15s ease;
            cursor: pointer;
            border: none;
        }
        .btn-whatsapp {
            background: #25d366;
            color: #ffffff;
            box-shadow: 0 2px 8px rgba(37, 211, 102, 0.3);
        }
        .btn-whatsapp:hover {
            background: #22c55e;
            color: #ffffff;
        }
        .btn-pdf {
            background: var(--primary);
            color: #ffffff;
            box-shadow: 0 2px 8px rgba(15, 81, 50, 0.25);
        }
        .btn-pdf:hover {
            background: var(--primary-dark);
            color: #ffffff;
        }

        /* Alert Box */
        .expired-banner {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
            padding: 12px 16px;
            border-radius: var(--radius-md);
            font-size: 12.5px;
            font-weight: 600;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
    </style>
</head>
<body>

    @php
        $expired = !empty($isExpired)
            || $quote->status === 'EXPIRADA'
            || ($quote->data_validade && $quote->data_validade->isPast())
            || (isset($envio) && $envio->expiracao_em && $envio->expiracao_em->isPast());
        $internalUserName = Auth::check() ? (Auth::user()->nome . ' (' . Auth::user()->papel . ')') : 'Usuário Interno / Representante';
    @endphp

    <div class="container">

        <div id="internal-preview-banner" style="background: #eff6ff; border: 1.5px solid #bfdbfe; color: #1e40af; font-size: 12.5px; font-weight: 600; padding: 12px 16px; border-radius: var(--radius-md); margin-bottom: 16px; display: {{ !empty($isInternalUser) ? 'flex' : 'none' }}; align-items: center; gap: 10px; box-shadow: var(--shadow-sm);">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
            <div>
                <div><strong>Modo de Pré-visualização Interna:</strong> Você está conectado como {{ $internalUserName }}.</div>
                <div style="font-size: 11.5px; color: #3b82f6; font-weight: 500; margin-top: 2px;">Esta visualização não conta no rastreamento e não confirma o contato do cliente.</div>
            </div>
        </div>

        <!-- Header da Marca -->
        <header class="brand-header">
            <div class="brand-logo-area">
                <img src="/images/brand-logo.png" 
                     onerror="if (this.src.indexOf('logo.png') === -1) { this.src='/images/logo.png'; } else if (this.src.indexOf('preview.png') === -1) { this.src='/images/preview.png'; }" 
                     alt="Central Veterinária" 
                     style="height: 42px; width: auto; object-fit: contain; max-width: 160px;">
                <div class="brand-text">
                    <h1>Central Veterinária</h1>
                    <p>Proposta Comercial Oficial</p>
                </div>
            </div>
            <div>
                @if($expired)
                    <span class="quote-badge-top" style="background: #fee2e2; color: #b91c1c; border-color: #fecaca;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        Expirada
                    </span>
                @else
                    <span class="quote-badge-top">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        Aprovada
                    </span>
                @endif
            </div>
        </header>

        @if($expired)
            <!-- Tela Exclusiva de Proposta Expirada (Sem Selo Aprovada, Sem Preços e Sem Download de PDF) -->
            <div class="section-card" style="text-align: center; padding: 32px 24px; border: 1.5px solid #fecaca; background: #fffafb;">
                <div style="width: 56px; height: 56px; border-radius: 50%; background: #fee2e2; color: #dc2626; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
                <div style="font-size: 12px; font-weight: 700; color: #991b1b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">
                    Proposta #{{ $quote->numero }}
                </div>
                <h2 style="font-size: 19px; font-weight: 800; color: #7f1d1d; margin-bottom: 10px;">
                    Proposta Comercial Expirada
                </h2>
                <p style="font-size: 13.5px; color: #475569; max-width: 480px; margin: 0 auto 18px; line-height: 1.55;">
                    Esta proposta comercial excedeu o prazo de validade original{{ $quote->data_validade ? ' (' . $quote->data_validade->format('d/m/Y \à\s H:i') . ')' : '' }} e suas condições e valores não estão mais disponíveis para consulta ou emissão de PDF.
                </p>
                <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px 16px; text-align: left; max-width: 440px; margin: 0 auto;">
                    <div style="font-size: 11px; color: var(--text-muted); font-weight: 700; text-transform: uppercase; margin-bottom: 4px;">Cliente</div>
                    <div style="font-size: 13.5px; font-weight: 700; color: var(--text-dark); margin-bottom: 10px;">
                        {{ $quote->parceiro->nome_fantasia ?: $quote->parceiro->razao_social }}
                        @if($quote->parceiro->nome_fantasia && $quote->parceiro->nome_fantasia !== $quote->parceiro->razao_social)
                            <div style="font-size: 11.5px; font-weight: 500; color: #64748b;">{{ $quote->parceiro->razao_social }}</div>
                        @endif
                        @if(!empty($envio?->nome_contato_snapshot) && $envio->nome_contato_snapshot !== 'Cliente')
                            <div style="font-size: 11.5px; font-weight: 600; color: #2563eb; margin-top: 3px;">A/C: {{ $envio->nome_contato_snapshot }}</div>
                        @endif
                    </div>
                    <div style="font-size: 11px; color: var(--text-muted); font-weight: 700; text-transform: uppercase; margin-bottom: 4px;">Representante Comercial</div>
                    <div style="font-size: 13.5px; font-weight: 700; color: var(--text-dark);">{{ $quote->representante->nome }}</div>
                </div>
                <p style="font-size: 12.5px; color: #64748b; margin-top: 16px;">
                    Fale com seu representante comercial pelo botão abaixo para solicitar a renovação da proposta.
                </p>
            </div>
        @else
            <!-- Card de Destaque / Total -->
            <div class="hero-card">
                <div class="hero-sub">Proposta Comercial</div>
                <div class="hero-quote-num">
                    <span>#{{ $quote->numero }}</span>
                </div>

                <div class="hero-total-box">
                    <div>
                        <div class="hero-total-label">Valor Total da Proposta</div>
                        <div class="hero-total-val">R$ {{ number_format((float)$quote->total, 2, ',', '.') }}</div>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-size: 10.5px; color: #94a3b8; font-weight: 600; text-transform: uppercase;">Validade</div>
                        <div style="font-size: 13px; font-weight: 700; color: #ffffff;">
                            {{ $quote->data_validade ? $quote->data_validade->format('d/m/Y') : now()->addHours($quote->validade_horas ?: 24)->format('d/m/Y') }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dados do Cliente -->
            <div class="section-card">
                <h2 class="section-title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    Cliente
                </h2>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Cliente / Razão Social</span>
                        <span class="info-value">
                            <strong>{{ $quote->parceiro->nome_fantasia ?: $quote->parceiro->razao_social }}</strong>
                            @if($quote->parceiro->nome_fantasia && $quote->parceiro->nome_fantasia !== $quote->parceiro->razao_social)
                                <br><span style="font-size: 11.5px; color: #64748b; font-weight: normal;">{{ $quote->parceiro->razao_social }}</span>
                            @endif
                            @if(!empty($envio?->nome_contato_snapshot) && $envio->nome_contato_snapshot !== 'Cliente')
                                <br><span style="font-size: 11.5px; color: #2563eb; font-weight: 600;">Aos cuidados de: {{ $envio->nome_contato_snapshot }}</span>
                            @endif
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">CNPJ / CPF</span>
                        <span class="info-value">{{ $quote->parceiro->cnpj ?: 'Não informado' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Cidade / UF</span>
                        <span class="info-value">{{ $quote->parceiro->cidade ?: 'Uberlândia' }}/{{ $quote->parceiro->uf ?: 'MG' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Representante Comercial</span>
                        <span class="info-value">{{ $quote->representante->nome }}</span>
                    </div>
                </div>
            </div>

            <!-- Condições Comerciais -->
            <div class="section-card">
                <h2 class="section-title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                    Condições Comerciais
                </h2>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Condição de Pagamento</span>
                        <span class="info-value">{{ $quote->forma_pagamento ?: 'À Combinar' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Prazo de Entrega</span>
                        <span class="info-value">{{ $quote->prazo_entrega ?: 'Imediato' }}</span>
                    </div>
                </div>

                @if($quote->observacao_cliente)
                    <div style="margin-top: 14px; padding: 10px 12px; background: #f8fafc; border: 1px solid var(--border); border-radius: 8px;">
                        <span class="info-label" style="display:block; margin-bottom: 2px;">Observações da Proposta</span>
                        <span style="font-size: 12.5px; color: var(--text-dark);">{{ $quote->observacao_cliente }}</span>
                    </div>
                @endif
            </div>

            <!-- Itens da Proposta -->
            <div class="section-card">
                <h2 class="section-title" style="justify-content: space-between;">
                    <span style="display: flex; align-items: center; gap: 8px;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                        Produtos Cotados
                    </span>
                    <span style="font-size: 11.5px; color: var(--text-muted); font-weight: 600;">{{ $items->count() }} {{ $items->count() === 1 ? 'item' : 'itens' }}</span>
                </h2>

                <div>
                    @foreach($items as $item)
                        <div class="item-row">
                            <div class="item-desc-box">
                                <div class="item-name">{{ $item->produto->descricao }}</div>
                                <div class="item-meta">
                                    Cód: {{ $item->produto->codigo_sankhya }} · {{ $item->qtd }} {{ $item->produto->unidade }}
                                </div>
                            </div>
                            <div class="item-calc-box">
                                <div class="item-subtotal">R$ {{ number_format((float)$item->subtotal, 2, ',', '.') }}</div>
                                <div class="item-unit-price">R$ {{ number_format((float)$item->preco_unit_proposto, 2, ',', '.') }} un</div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Resumo Financeiro -->
                <div class="totals-block">
                    @if($quote->desconto > 0.009)
                        <div class="total-line">
                            <span>Subtotal de Tabela:</span>
                            <span>R$ {{ number_format((float)$quote->subtotal, 2, ',', '.') }}</span>
                        </div>
                        <div class="total-line discount">
                            <span>Desconto Comercial:</span>
                            <span>− R$ {{ number_format((float)$quote->desconto, 2, ',', '.') }}</span>
                        </div>
                    @endif
                    <div class="total-line final">
                        <span>Total da Proposta:</span>
                        <span>R$ {{ number_format((float)$quote->total, 2, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        @endif

        <div style="text-align: center; color: var(--text-muted); font-size: 11.5px; padding: 10px 0 20px;">
            Central Veterinária · Distribuidora de Produtos Veterinários Ltda.<br>
            Proposta gerada em {{ $quote->created_at ? $quote->created_at->format('d/m/Y às H:i') : now()->format('d/m/Y') }}
        </div>

    </div>

    <!-- Barra de Ações Fixa no Rodapé (Mobile First) -->
    @if(!$expired || $repWhatsappUrl)
        <div class="floating-footer">
            @if(!$expired)
                <a href="/p/{{ $token }}/pdf" class="btn-action btn-pdf" target="_blank" rel="noopener">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                    <span>Baixar PDF</span>
                </a>
            @endif

            @if($repWhatsappUrl)
                <a href="{{ $repWhatsappUrl }}" class="btn-action btn-whatsapp" target="_blank" rel="noopener">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                    <span>{{ $expired ? 'Solicitar Renovação no WhatsApp' : 'Falar no WhatsApp' }}</span>
                </a>
            @endif
        </div>
    @endif

    <!-- Rastreamento de visualização real pelo navegador do cliente (ignora crawlers e usuários internos) -->
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            let hasInternalLocalFlag = false;
            try {
                hasInternalLocalFlag = localStorage.getItem('zecotacao_internal_actor') === '1'
                    || document.cookie.includes('zecotacao_internal=1');
            } catch (e) {}

            const isInternal = @json(!empty($isInternalUser)) || hasInternalLocalFlag;
            if (isInternal) {
                const banner = document.getElementById('internal-preview-banner');
                if (banner) banner.style.display = 'flex';
                return;
            }
            if (navigator.webdriver) return;
            try {
                fetch('/p/{{ $token }}/track', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        screen: `${window.screen?.width}x${window.screen?.height}`,
                        tz: Intl.DateTimeFormat().resolvedOptions().timeZone || '',
                        is_internal: isInternal
                    })
                }).catch(() => {});
            } catch(e) {}
        });
    </script>
</body>
</html>
