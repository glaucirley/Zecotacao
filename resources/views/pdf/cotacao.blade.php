<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Cotacao #{{ $quote->numero }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #333333;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }
        .header {
            margin-bottom: 20px;
            border-bottom: 2px solid #0f5132;
            padding-bottom: 10px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-logo {
            font-size: 24px;
            font-weight: bold;
            color: #0f5132;
            text-transform: uppercase;
        }
        .header-subtitle {
            font-size: 9px;
            color: #666666;
            margin-top: 2px;
        }
        .header-details {
            text-align: right;
            font-size: 11px;
        }
        .section-title {
            background-color: #f8f9fa;
            border-left: 3px solid #0f5132;
            padding: 5px 8px;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10px;
            margin-bottom: 10px;
            margin-top: 15px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .info-table td {
            padding: 4px 8px;
            vertical-align: top;
        }
        .info-label {
            font-weight: bold;
            color: #555555;
            width: 25%;
        }
        .info-value {
            color: #111111;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 20px;
        }
        .items-table th {
            background-color: #0f5132;
            color: #ffffff;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9px;
            padding: 6px 8px;
            text-align: left;
            border: 1px solid #0f5132;
        }
        .items-table td {
            padding: 6px 8px;
            border-bottom: 1px solid #dddddd;
            border-left: 1px solid #eeeeee;
            border-right: 1px solid #eeeeee;
            vertical-align: middle;
        }
        .text-right {
            text-align: right !important;
        }
        .text-center {
            text-align: center !important;
        }
        .badge-campanha {
            background-color: #e2f0d9;
            color: #385723;
            font-size: 8px;
            font-weight: bold;
            padding: 2px 4px;
            border-radius: 3px;
            border: 1px solid #a9d18e;
            display: inline-block;
            margin-left: 5px;
        }
        .totals-table {
            width: 40%;
            margin-left: 60%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .totals-table td {
            padding: 6px 8px;
            border-bottom: 1px solid #eeeeee;
        }
        .totals-label {
            font-weight: bold;
            color: #555555;
        }
        .totals-value {
            text-align: right;
            font-weight: bold;
            font-size: 12px;
        }
        .totals-value.total-final {
            color: #0f5132;
            font-size: 14px;
            border-top: 2px solid #0f5132;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 8px;
            color: #888888;
            border-top: 1px solid #eeeeee;
            padding-top: 10px;
        }
        .obs-box {
            background-color: #fafafa;
            border: 1px dashed #cccccc;
            padding: 10px;
            margin-top: 15px;
            border-radius: 4px;
        }
    </style>
</head>
<body>

    <div class="header">
        <table class="header-table">
            <tr>
                <td>
                    <div class="header-logo">Zé Cotação</div>
                    <div class="header-subtitle">Central Veterinária — Soluções em Saúde Animal</div>
                </td>
                <td class="header-details">
                    <strong>Cotação Nº:</strong> {{ $quote->numero }}<br>
                    <strong>Emissão:</strong> {{ $quote->data_emissao ? $quote->data_emissao->format('d/m/Y H:i') : now()->format('d/m/Y H:i') }}<br>
                    <strong>Validade:</strong> {{ $quote->data_validade ? $quote->data_validade->format('d/m/Y H:i') : '' }} ({{ $quote->validade_horas }}h)
                </td>
            </tr>
        </table>
    </div>

    <div class="section-title">Dados do Cliente</div>
    <table class="info-table">
        <tr>
            <td class="info-label">Razão Social:</td>
            <td class="info-value" colspan="3"><strong>{{ $quote->parceiro->razao_social }}</strong></td>
        </tr>
        <tr>
            <td class="info-label">Nome Fantasia:</td>
            <td class="info-value">{{ $quote->parceiro->nome_fantasia ?? 'N/A' }}</td>
            <td class="info-label">CNPJ/CPF:</td>
            <td class="info-value">{{ $quote->parceiro->cnpj ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="info-label">Endereço:</td>
            <td class="info-value" colspan="3">
                {{ $quote->parceiro->endereco }}, {{ $quote->parceiro->cidade }} - {{ $quote->parceiro->uf }}
                @if($quote->parceiro->cep) | CEP: {{ $quote->parceiro->cep }} @endif
            </td>
        </tr>
        <tr>
            <td class="info-label">Contato:</td>
            <td class="info-value">{{ $quote->parceiro->telefone ?? 'N/A' }}</td>
            <td class="info-label">E-mail:</td>
            <td class="info-value">{{ $quote->parceiro->email ?? 'N/A' }}</td>
        </tr>
    </table>

    <div class="section-title">Dados do Vendedor</div>
    <table class="info-table">
        <tr>
            <td class="info-label">Representante:</td>
            <td class="info-value"><strong>{{ $quote->representante->nome }}</strong></td>
            <td class="info-label">Equipe:</td>
            <td class="info-value">{{ $quote->representante->equipe->nome ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="info-label">Telefone:</td>
            <td class="info-value">{{ $quote->representante->telefone ?? 'N/A' }}</td>
            <td class="info-label">E-mail:</td>
            <td class="info-value">{{ $quote->representante->email }}</td>
        </tr>
    </table>

    <div class="section-title">Produtos Cotados</div>
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 10%;">Código</th>
                <th style="width: 45%;">Descrição</th>
                <th style="width: 8%; text-align: center;">Un.</th>
                <th style="width: 8%; text-align: center;">Qtd.</th>
                <th style="width: 13%; text-align: right;">Preço Unit.</th>
                <th style="width: 16%; text-align: right;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $index => $item)
                <tr>
                    <td>{{ $item->produto->codigo_sankhya }}</td>
                    <td>
                        {{ $item->produto->descricao }}
                        @if($item->mostrar_selo_campanha && $item->campanha_id)
                            <span class="badge-campanha">Campanha</span>
                        @endif
                    </td>
                    <td class="text-center">{{ $item->produto->unidade }}</td>
                    <td class="text-center">{{ $item->qtd }}</td>
                    <td class="text-right">R$ {{ number_format($item->preco_unit_proposto, 2, ',', '.') }}</td>
                    <td class="text-right">R$ {{ number_format($item->subtotal, 2, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals-table">
        <tr>
            <td class="totals-label">Subtotal Sugerido:</td>
            <td class="totals-value">R$ {{ number_format($quote->subtotal, 2, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="totals-label">Desconto Comercial:</td>
            <td class="totals-value" style="color: #c00;">- R$ {{ number_format($quote->desconto, 2, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="totals-label" style="font-size: 12px; vertical-align: middle;">Total Líquido:</td>
            <td class="totals-value total-final">R$ {{ number_format($quote->total, 2, ',', '.') }}</td>
        </tr>
    </table>

    <div class="section-title">Condições Comerciais</div>
    <table class="info-table">
        <tr>
            <td class="info-label">Forma de Pagamento:</td>
            <td class="info-value">{{ $quote->forma_pagamento ?? 'A combinar' }}</td>
            <td class="info-label">Prazo de Entrega:</td>
            <td class="info-value">{{ $quote->prazo_entrega ?? 'A combinar' }}</td>
        </tr>
        <tr>
            <td class="info-label">Tipo de Frete:</td>
            <td class="info-value">{{ $quote->frete_tipo }}</td>
            <td class="info-label">Transportadora:</td>
            <td class="info-value">{{ $quote->transportadora ?? 'Cliente retira' }}</td>
        </tr>
    </table>

    @if($quote->observacao_cliente)
        <div class="obs-box">
            <strong>Observações do Cliente:</strong><br>
            <span style="font-size: 10px; color: #555555; white-space: pre-line;">{{ $quote->observacao_cliente }}</span>
        </div>
    @endif

    <div class="footer">
        Esta é uma proposta comercial sujeita a confirmação de estoque e crédito no momento do faturamento.<br>
        Obrigado pela preferência! Zé Cotação Central Veterinária.
    </div>

</body>
</html>
