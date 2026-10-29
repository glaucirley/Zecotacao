<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Cotação #{{ $quote->numero }} - Central Veterinária</title>
    <style>
        @page {
            margin: 20px 25px 25px 25px;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 9.5px;
            color: #1e293b;
            line-height: 1.35;
            margin: 0;
            padding: 0;
        }
        
        /* Header Table */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            border-bottom: 2px solid #0f5132;
            padding-bottom: 8px;
        }
        .company-title {
            font-size: 15px;
            font-weight: bold;
            color: #0f5132;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .company-info {
            font-size: 8.5px;
            color: #475569;
            margin-top: 2px;
            line-height: 1.3;
        }
        .quote-title-box {
            text-align: right;
        }
        .quote-number {
            font-size: 18px;
            font-weight: bold;
            color: #0f172a;
        }
        .quote-meta {
            font-size: 9px;
            color: #475569;
            margin-top: 3px;
        }

        /* Client Details Matrix Table */
        .client-card-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            border: 1px solid #cbd5e1;
            background-color: #ffffff;
        }
        .client-card-table td {
            border: 1px solid #cbd5e1;
            padding: 5px 8px;
            vertical-align: top;
        }
        .field-label {
            font-size: 7.5px;
            font-weight: bold;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            display: block;
            margin-bottom: 2px;
        }
        .field-value {
            font-size: 9px;
            font-weight: bold;
            color: #0f172a;
        }

        /* Products Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .items-table th {
            background-color: #ffffff;
            color: #475569;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 8px;
            letter-spacing: 0.4px;
            padding: 6px 6px;
            border-top: 1px solid #cbd5e1;
            border-bottom: 1px solid #cbd5e1;
            text-align: left;
        }
        .items-table td {
            padding: 5px 6px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
            font-size: 9px;
        }
        .items-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .prod-code-badge {
            font-weight: bold;
            color: #334155;
        }
        .prod-desc-title {
            font-weight: bold;
            color: #0f172a;
        }
        .prod-campaign-subtext {
            font-size: 8px;
            color: #059669;
            font-weight: bold;
            margin-top: 1px;
        }
        .text-right {
            text-align: right !important;
        }
        .text-center {
            text-align: center !important;
        }

        /* Totals & Brand Summary Block */
        .totals-container {
            width: 100%;
            margin-bottom: 12px;
        }
        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }
        .totals-table td {
            padding: 4px 8px;
            font-size: 9.5px;
        }
        .totals-table .label {
            font-weight: bold;
            color: #475569;
            text-align: right;
        }
        .totals-table .val {
            text-align: right;
            font-weight: bold;
            color: #0f172a;
        }
        .totals-table .total-row td {
            border-top: 2px solid #0f5132;
            padding-top: 6px;
            font-size: 12px;
        }
        .totals-table .total-row .val {
            color: #0f5132;
            font-size: 14px;
            font-weight: bold;
        }

        /* Brand Summary Table */
        .brand-summary-table {
            width: 100%;
            border-collapse: collapse;
        }
        .brand-summary-table th {
            font-size: 7.5px;
            font-weight: bold;
            color: #64748b;
            text-transform: uppercase;
            padding: 3px 4px;
            border-bottom: 1px solid #cbd5e1;
            background-color: #f8fafc;
        }
        .brand-summary-table td {
            font-size: 8px;
            padding: 3px 4px;
            border-bottom: 1px solid #f1f5f9;
        }
        .brand-summary-table .brand-name {
            font-weight: bold;
            color: #1e293b;
        }

        /* Commercial Conditions Box */
        .conditions-card {
            clear: both;
            width: 100%;
            border-collapse: collapse;
            border-top: 1px solid #cbd5e1;
            margin-bottom: 12px;
            padding-top: 8px;
        }
        .conditions-card td {
            padding: 4px 0;
            font-size: 9px;
            vertical-align: top;
        }
        .conditions-card .cond-label {
            font-weight: bold;
            color: #0f172a;
        }
        .conditions-card .cond-val {
            color: #334155;
        }

        /* Validity Banner Box */
        .validity-banner {
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 8px;
            text-align: center;
            font-size: 9.5px;
            font-weight: bold;
            color: #0f172a;
            background-color: #ffffff;
            margin-bottom: 12px;
        }

        /* Footer */
        .footer-note {
            text-align: center;
            font-size: 8px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 6px;
        }
    </style>
</head>
<body>

    <!-- Header Section -->
    <table class="header-table">
        <tr>
            <td style="width: 62%; vertical-align: top;">
                <div class="company-title">CENTRAL DE ABASTECIMENTO E VETERINÁRIA LTDA</div>
                <div class="company-info">
                    Rua Ignez Favato, 302 · Distrito Industrial<br>
                    38402-340 · Uberlândia - MG<br>
                    CNPJ: 38.510.210/0001-00 · Insc. Est.: 702835763.00-58<br>
                    Tel: (34) 3211-0500
                </div>
            </td>
            <td style="width: 38%; vertical-align: top;" class="quote-title-box">
                <div class="quote-number">Cotação {{ $quote->numero }}</div>
                <div class="quote-meta">
                    <strong>Emissão:</strong> {{ $quote->data_emissao ? $quote->data_emissao->format('d/m/Y H:i') : now()->format('d/m/Y H:i') }}<br>
                    <strong>Validade:</strong> {{ $quote->validade_horas ?? 24 }} horas (até {{ $quote->data_validade ? $quote->data_validade->format('d/m/Y H:i') : now()->addHours(24)->format('d/m/Y H:i') }})
                </div>
            </td>
        </tr>
    </table>

    <!-- Client Matrix Grid -->
    <table class="client-card-table">
        <tr>
            <td style="width: 20%;">
                <span class="field-label">CÓDIGO</span>
                <span class="field-value">{{ $quote->parceiro->codigo_sankhya ?? 'N/A' }}</span>
            </td>
            <td style="width: 45%;">
                <span class="field-label">NOME / RAZÃO SOCIAL</span>
                <span class="field-value">{{ $quote->parceiro->razao_social ?? 'N/A' }}</span>
            </td>
            <td style="width: 35%;">
                <span class="field-label">NOME FANTASIA</span>
                <span class="field-value">{{ $quote->parceiro->nome_fantasia ?: ($quote->parceiro->razao_social ?? 'N/A') }}</span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="field-label">CNPJ</span>
                <span class="field-value">{{ $quote->parceiro->cnpj ?? 'N/A' }}</span>
            </td>
            <td>
                <span class="field-label">TELEFONE</span>
                <span class="field-value">{{ $quote->parceiro->telefone ?? 'N/A' }}</span>
            </td>
            <td>
                <span class="field-label">E-MAIL</span>
                <span class="field-value">{{ $quote->parceiro->email ?? 'N/A' }}</span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="field-label">ENDEREÇO</span>
                <span class="field-value">{{ $quote->parceiro->logradouro ?: ($quote->parceiro->endereco ?: 'N/A') }}{{ $quote->parceiro->numero ? ', ' . $quote->parceiro->numero : '' }}</span>
            </td>
            <td>
                <span class="field-label">BAIRRO</span>
                <span class="field-value">{{ $quote->parceiro->bairro ?? 'N/A' }}</span>
            </td>
            <td>
                <span class="field-label">CIDADE / CEP</span>
                <span class="field-value">
                    {{ $quote->parceiro->cidade ?? '' }}{{ $quote->parceiro->uf ? '/' . $quote->parceiro->uf : '' }}{{ $quote->parceiro->cep ? ' · ' . $quote->parceiro->cep : '' }}
                </span>
            </td>
        </tr>
    </table>

    <!-- Products Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 9%;">CÓD</th>
                <th style="width: 45%;">DESCRIÇÃO</th>
                <th style="width: 11%; text-align: center;">VALIDADE</th>
                <th style="width: 6%; text-align: center;">UN</th>
                <th style="width: 7%; text-align: center;">QTD</th>
                <th style="width: 11%; text-align: right;">VL UN</th>
                <th style="width: 11%; text-align: right;">SUBTOTAL</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
                <tr>
                    <td class="prod-code-badge">{{ $item->produto->codigo_sankhya ?? '---' }}</td>
                    <td>
                        <div class="prod-desc-title">{{ $item->produto->descricao ?? 'Produto indisponível' }}</div>
                        @if(!empty($item->produto?->marca))
                            <span style="font-size: 7.5px; color: #64748b; font-weight: bold; text-transform: uppercase;">Marca: {{ $item->produto->marca }}</span>
                        @endif
                        @if($item->mostrar_selo_campanha || $item->campanha_id)
                            <div class="prod-campaign-subtext">Campanha: {{ $item->campanha_id ?: 'Condição Especial Aplicada' }}</div>
                        @endif
                    </td>
                    <td class="text-center" style="color: #475569; font-weight: 500;">
                        {{ $item->validade_formatada ?? '--' }}
                    </td>
                    <td class="text-center" style="font-weight: bold; color: #475569;">{{ $item->produto->unidade ?? 'UN' }}</td>
                    <td class="text-center" style="font-weight: bold; color: #0f172a;">{{ $item->qtd }}</td>
                    <td class="text-right">R$ {{ number_format($item->preco_unit_proposto, 2, ',', '.') }}</td>
                    <td class="text-right" style="font-weight: bold;">R$ {{ number_format($item->subtotal, 2, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Totals & Brand Summary Container -->
    <div class="totals-container">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 58%; vertical-align: top; padding-right: 15px;">
                    @if(!empty($brandSummary) && count($brandSummary) > 0)
                        <div style="font-size: 8px; font-weight: bold; color: #475569; text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 3px; border-bottom: 1px solid #cbd5e1; padding-bottom: 2px;">
                            Resumo por Marca
                        </div>
                        <table class="brand-summary-table">
                            <thead>
                                <tr>
                                    <th style="width: 48%; text-align: left;">MARCA</th>
                                    <th style="width: 16%; text-align: center;">QTD</th>
                                    <th style="width: 22%; text-align: right;">TOTAL</th>
                                    <th style="width: 14%; text-align: right;">PART. (%)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($brandSummary as $b)
                                    <tr>
                                        <td class="brand-name">{{ $b['marca'] }}</td>
                                        <td style="text-align: center; color: #475569;">{{ $b['qtd_itens'] }}</td>
                                        <td style="text-align: right; font-weight: bold; color: #0f172a;">R$ {{ number_format($b['total'], 2, ',', '.') }}</td>
                                        <td style="text-align: right; color: #475569;">{{ number_format(($b['total'] / max($quote->total, 0.01)) * 100, 1, ',', '.') }}%</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </td>
                <td style="width: 42%; vertical-align: top;">
                    <table class="totals-table">
                        <tr>
                            <td class="label">Subtotal</td>
                            <td class="val">R$ {{ number_format($quote->subtotal, 2, ',', '.') }}</td>
                        </tr>
                        @if($quote->desconto > 0)
                            <tr>
                                <td class="label">Desconto</td>
                                <td class="val" style="color: #dc2626;">− R$ {{ number_format($quote->desconto, 2, ',', '.') }}</td>
                            </tr>
                        @endif
                        <tr class="total-row">
                            <td class="label" style="font-weight: bold;">Total da cotação</td>
                            <td class="val">R$ {{ number_format($quote->total, 2, ',', '.') }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    <!-- Commercial Conditions & Observations -->
    <table class="conditions-card">
        <tr>
            <td style="width: 50%;">
                <span class="cond-label">Forma de pagamento:</span> <span class="cond-val">{{ $quote->forma_pagamento ?: 'A combinar' }}</span>
            </td>
            <td style="width: 50%; vertical-align: top;">
                <span class="cond-label">Representante:</span> <span class="cond-val">{{ $quote->representante->nome ?? 'Atendimento Central' }}</span>
            </td>
        </tr>
        @if($quote->observacao_cliente)
            <tr>
                <td colspan="2" style="border-top: 1px solid #e2e8f0; padding-top: 4px; margin-top: 4px;">
                    <span class="cond-label">Observações:</span> <span class="cond-val">{{ $quote->observacao_cliente }}</span>
                </td>
            </tr>
        @endif
    </table>

    <!-- Validity Banner -->
    <div class="validity-banner">
        Cotação válida por {{ $quote->validade_horas ?? 24 }} horas ou enquanto durar o estoque.
    </div>

    <!-- Footer Note -->
    <div class="footer-note">
        Central Veterinária · documento gerado automaticamente para conferência do cliente.
    </div>

</body>
</html>
