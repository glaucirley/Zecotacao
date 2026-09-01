@extends('layouts.public')

@section('content')
<div id="representative-panel" style="display: none;">
    <!-- Title and Status Row -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 15px;">
        <div>
            <h1 style="font-size: 28px; margin-bottom: 4px;">Cotação <span id="quote-number" style="color: var(--color-primary);">...</span></h1>
            <p style="color: var(--color-text-muted); font-size: 13px;">
                Origem: <span id="quote-origin" style="font-weight: 600;">...</span> | 
                Emitido em: <span id="quote-emission">...</span> | 
                Válido até: <span id="quote-validity">...</span>
            </p>
        </div>
        <div style="display: flex; align-items: center; gap: 10px;">
            <span id="quote-status-badge" class="badge-status">...</span>
        </div>
    </div>

    <!-- Info Cards Row -->
    <div class="grid-2" style="margin-bottom: 24px;">
        <!-- Client Card -->
        <div class="card" style="margin-bottom: 0;">
            <div class="card-header" style="margin-bottom: 12px; padding-bottom: 8px;">
                <h3 style="font-size: 16px;">Dados do Cliente</h3>
            </div>
            <p style="font-size: 15px; font-weight: 600; margin-bottom: 8px;" id="client-name">...</p>
            <p style="font-size: 13px; color: var(--color-text-muted);" id="client-cnpj">CNPJ: ...</p>
            <p style="font-size: 13px; color: var(--color-text-muted);" id="client-location">Cidade: ...</p>
            <p style="font-size: 13px; color: var(--color-text-muted);" id="client-contact">Contato: ...</p>
        </div>

        <!-- Sales Rep Card -->
        <div class="card" style="margin-bottom: 0;">
            <div class="card-header" style="margin-bottom: 12px; padding-bottom: 8px;">
                <h3 style="font-size: 16px;">Vendedor & Equipe</h3>
            </div>
            <p style="font-size: 15px; font-weight: 600; margin-bottom: 8px;" id="rep-name">...</p>
            <p style="font-size: 13px; color: var(--color-text-muted);" id="rep-team">Equipe: ...</p>
            <p style="font-size: 13px; color: var(--color-text-muted);" id="rep-email">E-mail: ...</p>
            <p style="font-size: 13px; color: var(--color-text-muted);" id="rep-phone">Celular: ...</p>
        </div>
    </div>

    <!-- Items Grid Card -->
    <div class="card">
        <div class="card-header">
            <h3>Itens da Cotação</h3>
            <span style="font-size: 13px; color: var(--color-text-muted);" id="items-count">0 itens</span>
        </div>

        <div class="table-responsive">
            <table class="table-premium" id="items-table">
                <thead>
                    <tr>
                        <th style="width: 8%;">Código</th>
                        <th style="width: 32%;">Descrição</th>
                        <th style="width: 8%; text-align: center;">Un.</th>
                        <th style="width: 10%; text-align: center;">Qtd</th>
                        <th style="width: 12%; text-align: right;">Preço Sugerido</th>
                        <th style="width: 12%; text-align: right;">Preço Mínimo</th>
                        <th style="width: 12%; text-align: right;">Preço Proposto</th>
                        <th style="width: 10%; text-align: center;">Situação</th>
                        <th style="width: 6%; text-align: center;">Ações</th>
                    </tr>
                </thead>
                <tbody id="items-table-body">
                    <!-- Dynamic Rows -->
                </tbody>
            </table>
        </div>

        <!-- Add Item Row -->
        <div id="add-item-form-container" style="margin-top: 20px; background-color: var(--color-bg); padding: 20px; border-radius: var(--radius-md); border: 1px solid var(--color-border);">
            <h4 style="font-size: 14px; margin-bottom: 12px; color: var(--color-primary);">Adicionar Novo Produto</h4>
            <div style="display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end;">
                <div style="flex-grow: 1; min-width: 250px;">
                    <label class="form-label" style="font-size: 12px;">Selecione o Produto</label>
                    <select id="new-item-product" class="form-control" onchange="onProductSelect()">
                        <option value="">Selecione um produto...</option>
                        <!-- Dynamic Options -->
                    </select>
                </div>
                <div style="width: 100px;">
                    <label class="form-label" style="font-size: 12px;">Quantidade</label>
                    <input type="number" id="new-item-qtd" class="form-control" value="1" min="1" oninput="calculateNewItemSubtotal()">
                </div>
                <div style="width: 130px;">
                    <label class="form-label" style="font-size: 12px;">Preço Sugerido</label>
                    <input type="number" id="new-item-sugerido" class="form-control" readonly style="background-color: #e2e8f0;">
                </div>
                <div style="width: 130px;">
                    <label class="form-label" style="font-size: 12px;">Preço Mínimo</label>
                    <input type="number" id="new-item-minimo" class="form-control" readonly style="background-color: #e2e8f0;">
                </div>
                <div style="width: 130px;">
                    <label class="form-label" style="font-size: 12px;">Preço Proposto</label>
                    <input type="number" id="new-item-proposto" class="form-control" step="0.01" min="0.01" oninput="calculateNewItemSubtotal()">
                </div>
                <div>
                    <button class="btn btn-secondary" onclick="addNewItem()" style="padding: 10px 16px;">Adicionar</button>
                </div>
            </div>
            <div style="margin-top: 8px; font-size: 12px; color: var(--color-text-muted);" id="new-item-subtotal-label">
                Subtotal Proposto: R$ 0,00
            </div>
        </div>
    </div>

    <!-- Totals & Notes Section -->
    <div class="grid-2">
        <!-- Notes Card -->
        <div class="card">
            <div class="card-header">
                <h3>Observações da Cotação</h3>
            </div>
            <div class="form-group">
                <label for="obs-cliente" class="form-label">Observações para o Cliente (Visível no PDF)</label>
                <textarea id="obs-cliente" class="form-control" rows="3" placeholder="Ex: Prazo de entrega de 5 dias úteis."></textarea>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label for="obs-interna" class="form-label">Observações Internas (Exclusivo da Empresa)</label>
                <textarea id="obs-interna" class="form-control" rows="3" placeholder="Ex: Cliente solicita prioridade no faturamento."></textarea>
            </div>
        </div>

        <!-- Totals Card -->
        <div class="card" style="display: flex; flex-direction: column; justify-content: space-between;">
            <div class="card-header">
                <h3>Valores Finais</h3>
            </div>
            <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 20px;">
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--color-text-muted);">Subtotal Sugerido:</span>
                    <span style="font-weight: 500;" id="total-sugerido">R$ 0,00</span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--color-text-muted);">Desconto Comercial:</span>
                    <span style="font-weight: 500; color: #dc2626;" id="total-desconto">- R$ 0,00</span>
                </div>
                <div style="display: flex; justify-content: space-between; border-top: 2px solid var(--color-border); padding-top: 15px;">
                    <span style="font-size: 16px; font-weight: 600;">Total Proposto:</span>
                    <span style="font-size: 20px; font-weight: 700; color: var(--color-primary);" id="total-liquido">R$ 0,00</span>
                </div>
            </div>

            <!-- Conditions Form -->
            <div style="background-color: var(--color-bg); padding: 15px; border-radius: var(--radius-md); border: 1px solid var(--color-border);">
                <h4 style="font-size: 13px; margin-bottom: 10px; font-weight: 600;">Condições de Entrega / Pagamento</h4>
                <div class="grid-2" style="gap: 10px;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label" style="font-size: 11px;">Forma Pagamento</label>
                        <input type="text" id="forma-pagamento" class="form-control" style="padding: 6px 10px; font-size: 12px;">
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label" style="font-size: 11px;">Prazo Entrega</label>
                        <input type="text" id="prazo-entrega" class="form-control" style="padding: 6px 10px; font-size: 12px;">
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label" style="font-size: 11px;">Frete Tipo</label>
                        <select id="frete-tipo" class="form-control" style="padding: 6px 10px; font-size: 12px;">
                            <option value="CIF">CIF (Por conta do remetente)</option>
                            <option value="FOB">FOB (Por conta do destinatário)</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label" style="font-size: 11px;">Transportadora</label>
                        <input type="text" id="transportadora" class="form-control" style="padding: 6px 10px; font-size: 12px;">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Justification panel (Visible if items below minimum) -->
    <div id="justification-panel" class="card" style="display: none; border: 1px solid #f59e0b; background-color: #fffbeb;">
        <div class="card-header" style="border-bottom: 1px solid #fef3c7;">
            <h3 style="color: #d97706; display: flex; align-items: center; gap: 8px;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                Justificativa Necessária — Desconto Especial Detectado
            </h3>
        </div>
        <p style="font-size: 13px; color: #b45309; margin-bottom: 15px;">
            Existem produtos abaixo do preço mínimo configurado. É obrigatório registrar uma justificativa e anexar comprovantes para enviar a cotação ao gestor.
        </p>
        
        <div class="form-group">
            <label class="form-label" style="color: #b45309;">Justificativa por Escrito</label>
            <textarea id="just-texto" class="form-control" rows="3" placeholder="Justifique o motivo do desconto especial (ex: equiparação de preço com concorrente X)..." style="background-color: #ffffff; border-color: #fcd34d;"></textarea>
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label class="form-label" style="color: #b45309;">Anexos / Documentos (Comprovante Concorrente, etc)</label>
                <input type="file" id="just-anexos" class="form-control" multiple style="background-color: #ffffff; border-color: #fcd34d; padding: 6px 10px;">
            </div>
            <div class="form-group">
                <label class="form-label" style="color: #b45309;">Upload de Justificativa por Áudio (Gravador/Arquivo)</label>
                <input type="file" id="just-audio" class="form-control" accept="audio/*" style="background-color: #ffffff; border-color: #fcd34d; padding: 6px 10px;">
            </div>
        </div>
    </div>

    <!-- Floating Actions Panel -->
    <div class="card" style="display: flex; justify-content: space-between; align-items: center; background-color: var(--color-card); box-shadow: var(--shadow-lg);">
        <div>
            <button id="btn-lost" class="btn btn-danger" onclick="openLostModal()">Marcar como Perdida</button>
        </div>
        <div style="display: flex; gap: 12px; align-items: center;">
            <button id="btn-draft" class="btn btn-outline" onclick="saveDraft(true)">Salvar Rascunho</button>
            <button id="btn-pdf" class="btn btn-secondary" onclick="downloadPdf()" disabled>Gerar PDF</button>
            <button id="btn-release" class="btn btn-primary" onclick="openReleaseModal()" style="background-color: #0d9488; border-color: #0d9488; display: none;">Liberar para Faturamento</button>
            <button id="btn-submit" class="btn btn-primary" onclick="submitQuote()">Enviar para Aprovação</button>
        </div>
    </div>
</div>

<!-- Loading indicator -->
<div id="loading-spinner" class="centered-layout" style="min-height: 50vh;">
    <div style="text-align: center;">
        <div style="border: 4px solid rgba(15,81,50,0.1); border-top: 4px solid var(--color-primary); border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 0 auto 15px auto;"></div>
        <span style="font-weight: 500; color: var(--color-text-muted);">Carregando cotação comercial...</span>
    </div>
</div>

<!-- Lost quote modal -->
<div id="lost-modal" style="display: none; position: fixed; inset: 0; background-color: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 1000; padding: 20px;">
    <div class="auth-card" style="max-width: 500px; padding: 30px;">
        <h3 style="margin-bottom: 12px; color: var(--status-perdida);">Marcar Cotação como Perdida</h3>
        <p style="font-size: 13px; color: var(--color-text-muted); margin-bottom: 16px;">
            Você está prestes a encerrar esta cotação como perdida comercialmente. Esta ação é irreversível e exige justificativa.
        </p>
        <div class="form-group" style="margin-bottom: 20px;">
            <label for="lost-reason" class="form-label">Motivo do Fechamento Negativo</label>
            <textarea id="lost-reason" class="form-control" rows="3" placeholder="Descreva por que o cliente declinou a proposta..." required></textarea>
        </div>
        <div style="display: flex; justify-content: flex-end; gap: 12px;">
            <button class="btn btn-outline" onclick="closeLostModal()">Cancelar</button>
            <button class="btn btn-danger" onclick="confirmLost()">Confirmar Perda</button>
        </div>
    </div>
</div>

<!-- Release for Billing Modal -->
<div id="release-modal" style="display: none; position: fixed; inset: 0; background-color: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 1000; padding: 20px;">
    <div class="auth-card" style="max-width: 500px; padding: 30px;">
        <h3 style="margin-bottom: 12px; color: #0d9488;">Liberar para Faturamento</h3>
        <p style="font-size: 13px; color: var(--color-text-muted); margin-bottom: 16px;">
            Esta cotação foi aprovada. Preencha os dados do pedido no Sankhya para liberá-la para o faturamento.
        </p>
        
        <div class="form-group" style="margin-bottom: 12px;">
            <label for="release-pedido-externo" class="form-label">Número do Pedido no Sankhya</label>
            <input type="text" id="release-pedido-externo" class="form-control" placeholder="Ex: 509230" required>
        </div>

        <div class="form-group" style="margin-bottom: 12px;">
            <label for="release-tipo-faturamento" class="form-label">Tipo de Faturamento</label>
            <select id="release-tipo-faturamento" class="form-control" onchange="onReleaseTypeChange(this.value)">
                <option value="total">Faturamento Total (Valor Integral)</option>
                <option value="parcial">Faturamento Parcial</option>
            </select>
        </div>

        <div class="form-group" style="margin-bottom: 20px;">
            <label for="release-valor-pedido" class="form-label">Valor do Pedido (R$)</label>
            <input type="number" id="release-valor-pedido" class="form-control" step="0.01" min="0.01" required>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 12px;">
            <button class="btn btn-outline" onclick="closeReleaseModal()">Cancelar</button>
            <button class="btn btn-primary" onclick="confirmRelease()" style="background-color: #0d9488; border-color: #0d9488;">Liberar Faturamento</button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const TOKEN = "{{ $token }}";
    const API_URL = "{{ url('/api/v1') }}";
    let quote = null;
    let productsList = [];
    let isEditingLocked = false;

    document.addEventListener("DOMContentLoaded", () => {
        loadData();
    });

    async function loadData() {
        try {
            // 1. Fetch Quote Details
            const quoteRes = await fetch(`${API_URL}/cotacoes/token/${TOKEN}`);
            const quoteData = await quoteRes.json();
            if (!quoteData.success) {
                alert("Erro ao carregar a cotação: " + (quoteData.error || "Token inválido"));
                return;
            }
            quote = quoteData.data;

            // 2. Fetch Products Catalog for addition
            const prodRes = await fetch(`${API_URL}/produtos`, {
                headers: { "X-API-Key": "default_n8n_key_123" } // public auth check
            });
            const prodData = await prodRes.json();
            if (prodData.success) {
                productsList = prodData.data;
            }

            renderView();
        } catch (e) {
            console.error(e);
            alert("Falha na conexão com o servidor.");
        }
    }

    function renderView() {
        if (!quote) return;

        // Hide spinner & show panel
        document.getElementById("loading-spinner").style.display = "none";
        document.getElementById("representative-panel").style.display = "block";

        // Bind header details
        document.getElementById("quote-number").innerText = quote.numero;
        document.getElementById("quote-origin").innerText = quote.origem.toUpperCase();
        document.getElementById("quote-emission").innerText = new Date(quote.data_emissao).toLocaleString('pt-BR');
        document.getElementById("quote-validity").innerText = quote.data_validade ? new Date(quote.data_validade).toLocaleString('pt-BR') : 'Sem data';

        // Check if locked from editing
        const lockedStatuses = ['FINALIZADA_COM_PEDIDO', 'FATURADA', 'PERDIDA'];
        isEditingLocked = lockedStatuses.includes(quote.status);

        // Bind status badge
        const badge = document.getElementById("quote-status-badge");
        badge.className = "badge-status " + quote.status.toLowerCase().replace(/_/g, '-');
        badge.innerText = quote.status === 'PDF_GERADO' ? 'Liberada (Pendente PDF)' : quote.status.replace(/_/g, ' ');

        // Bind Client profile
        document.getElementById("client-name").innerText = quote.parceiro.razao_social;
        document.getElementById("client-cnpj").innerText = "CNPJ/CPF: " + (quote.parceiro.cnpj || 'Não cadastrado');
        document.getElementById("client-location").innerText = "Localidade: " + quote.parceiro.cidade + " - " + quote.parceiro.uf;
        document.getElementById("client-contact").innerText = "Contato: " + (quote.parceiro.telefone || quote.parceiro.email || 'N/A');

        // Bind Rep profile
        document.getElementById("rep-name").innerText = quote.representante.nome;
        document.getElementById("rep-team").innerText = "Equipe: " + (quote.representante.equipe ? quote.representante.equipe.nome : 'Sem Equipe');
        document.getElementById("rep-email").innerText = "E-mail: " + quote.representante.email;
        document.getElementById("rep-phone").innerText = "Celular: " + (quote.representante.telefone || 'Não cadastrado');

        // Bind Commercial Conditions
        document.getElementById("forma-pagamento").value = quote.forma_pagamento || "";
        document.getElementById("prazo-entrega").value = quote.prazo_entrega || "";
        document.getElementById("frete-tipo").value = quote.frete_tipo || "CIF";
        document.getElementById("transportadora").value = quote.transportadora || "";
        document.getElementById("obs-cliente").value = quote.observacao_cliente || "";
        document.getElementById("obs-interna").value = quote.observacao_interna || "";

        const btnRelease = document.getElementById("btn-release");
        if (quote.status === 'PDF_GERADO') {
            btnRelease.style.display = "inline-block";
            document.getElementById("btn-submit").style.display = "none";
        } else {
            btnRelease.style.display = "none";
            document.getElementById("btn-submit").style.display = "inline-block";
        }

        if (isEditingLocked) {
            document.getElementById("forma-pagamento").disabled = true;
            document.getElementById("prazo-entrega").disabled = true;
            document.getElementById("frete-tipo").disabled = true;
            document.getElementById("transportadora").disabled = true;
            document.getElementById("obs-cliente").disabled = true;
            document.getElementById("obs-interna").disabled = true;
            document.getElementById("add-item-form-container").style.display = "none";
            document.getElementById("btn-lost").style.display = "none";
            document.getElementById("btn-draft").style.display = "none";
            document.getElementById("btn-submit").style.display = "none";
            btnRelease.style.display = "none";
        }

        // Enable PDF only if ready
        const pdfStatuses = ['PDF_GERADO', 'AGUARDANDO_PEDIDO', 'FINALIZADA_COM_PEDIDO', 'FATURADA'];
        document.getElementById("btn-pdf").disabled = !pdfStatuses.includes(quote.status);

        // Bind Items List
        renderItems();

        // Populate Products Select catalog
        const select = document.getElementById("new-item-product");
        select.innerHTML = '<option value="">Selecione um produto...</option>';
        productsList.forEach(p => {
            select.innerHTML += `<option value="${p.id}">${p.codigo_sankhya} - ${p.descricao}</option>`;
        });
    }

    function renderItems() {
        const body = document.getElementById("items-table-body");
        body.innerHTML = "";
        
        let hasItemBelowMin = false;

        document.getElementById("items-count").innerText = `${quote.itens.length} itens`;

        quote.itens.forEach(item => {
            const isBelowMin = parseFloat(item.preco_unit_proposto) < parseFloat(item.preco_minimo);
            if (isBelowMin && item.status_item !== 'aprovado') {
                hasItemBelowMin = true;
            }

            const inputClass = isBelowMin ? "price-below-min" : "";
            const isItemLocked = isEditingLocked || item.status_item === 'recusado';
            const rowClass = item.status_item === 'recusado' ? "recusado" : (item.status_item === 'aprovado' ? "aprovado" : "");

            body.innerHTML += `
                <tr class="item-card-row ${rowClass}" id="row-${item.id}">
                    <td><strong>${item.produto.codigo_sankhya}</strong></td>
                    <td>
                        ${item.produto.descricao}
                        ${item.mostrar_selo_campanha && item.campanha_id ? '<span class="badge-campanha" style="display:inline-block; margin-left:8px;">Campanha</span>' : ''}
                    </td>
                    <td class="text-center">${item.produto.unidade}</td>
                    <td class="text-center">
                        <input type="number" class="form-control text-center" value="${item.qtd}" min="1" 
                            style="width: 70px; padding: 4px;" 
                            ${isItemLocked ? 'disabled' : ''} 
                            oninput="updateItemCalculations(${item.id}, this.value, null)">
                    </td>
                    <td class="text-right">R$ ${parseFloat(item.preco_unit_sugerido).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</td>
                    <td class="text-right" style="color: #64748b;">R$ ${parseFloat(item.preco_minimo).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</td>
                    <td class="text-right">
                        <div style="display: flex; align-items: center; justify-content: flex-end; gap: 6px;">
                            <input type="number" class="form-control text-right ${inputClass}" 
                                value="${parseFloat(item.preco_unit_proposto)}" step="0.01" min="0.01"
                                style="width: 100px; padding: 4px;" 
                                ${isItemLocked ? 'disabled' : ''} 
                                oninput="updateItemCalculations(${item.id}, null, this.value)">
                            ${!isItemLocked && isBelowMin ? `<button class="btn btn-outline" style="padding: 4px 8px; font-size:10px;" onclick="resetToMin(${item.id}, ${item.preco_minimo})">Mín</button>` : ''}
                        </div>
                    </td>
                    <td class="text-center">
                        <span class="badge-status ${item.status_item}">${item.status_item}</span>
                    </td>
                    <td class="text-center">
                        ${!isItemLocked ? `
                            <button class="btn btn-outline" style="padding: 6px 10px; border-color: #fecaca; color: #ef4444;" onclick="deleteItem(${item.id})">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                            </button>
                        ` : '-'}
                    </td>
                </tr>
            `;
        });

        // Show/hide justification box
        document.getElementById("justification-panel").style.display = hasItemBelowMin && !isEditingLocked ? "block" : "none";
        
        recalculateTotalsFromState();
    }

    function updateItemCalculations(itemId, newQtd, newPrice) {
        const item = quote.itens.find(i => i.id === itemId);
        if (!item) return;

        if (newQtd !== null) item.qtd = parseInt(newQtd) || 1;
        if (newPrice !== null) item.preco_unit_proposto = parseFloat(newPrice) || 0.00;

        item.subtotal = item.qtd * item.preco_unit_proposto;
        
        // Recalculate discount percentage
        const sugerido = parseFloat(item.preco_unit_sugerido);
        item.ajuste_percentual = sugerido > 0 ? ((item.preco_unit_proposto - sugerido) / sugerido) * 100 : 0;

        // Apply price-below-min warning class dynamically in DOM
        const row = document.getElementById(`row-${itemId}`);
        if (row) {
            const inputPrice = row.querySelector("td:nth-child(7) input[type='number']");
            const isBelowMin = parseFloat(item.preco_unit_proposto) < parseFloat(item.preco_minimo);
            
            if (isBelowMin) {
                inputPrice.classList.add("price-below-min");
            } else {
                inputPrice.classList.remove("price-below-min");
            }
        }

        // Evaluate if any active item triggers the justification requirement
        let hasItemBelowMin = false;
        quote.itens.forEach(i => {
            if (parseFloat(i.preco_unit_proposto) < parseFloat(i.preco_minimo) && i.status_item !== 'aprovado') {
                hasItemBelowMin = true;
            }
        });
        document.getElementById("justification-panel").style.display = hasItemBelowMin && !isEditingLocked ? "block" : "none";

        recalculateTotalsFromState();
    }

    function resetToMin(itemId, minPrice) {
        const row = document.getElementById(`row-${itemId}`);
        if (row) {
            const inputPrice = row.querySelector("td:nth-child(7) input[type='number']");
            if (inputPrice) {
                inputPrice.value = minPrice;
                updateItemCalculations(itemId, null, minPrice);
                renderItems(); // re-render to update warning highlights
            }
        }
    }

    function recalculateTotalsFromState() {
        let subtotal = 0;
        let total = 0;

        quote.itens.forEach(item => {
            if (item.status_item === 'recusado') {
                return;
            }
            subtotal += item.qtd * Math.max(parseFloat(item.preco_unit_sugerido), parseFloat(item.preco_unit_proposto));
            total += parseFloat(item.subtotal);
        });

        const desconto = subtotal - total;

        document.getElementById("total-sugerido").innerText = "R$ " + subtotal.toLocaleString('pt-BR', {minimumFractionDigits: 2});
        document.getElementById("total-desconto").innerText = "- R$ " + (desconto > 0 ? desconto : 0).toLocaleString('pt-BR', {minimumFractionDigits: 2});
        document.getElementById("total-liquido").innerText = "R$ " + total.toLocaleString('pt-BR', {minimumFractionDigits: 2});
    }

    // Add Item Flow
    function onProductSelect() {
        const productId = document.getElementById("new-item-product").value;
        if (!productId) {
            clearNewItemForm();
            return;
        }

        // Mock default pricing for selected product
        // In a real application, pricing rules (suggested/min) come from ERP payload
        // We simulate basic calculations: suggested = 150, min = 120, proposed = 150
        const defaultSuggested = 150.00;
        const defaultMin = 120.00;

        document.getElementById("new-item-sugerido").value = defaultSuggested;
        document.getElementById("new-item-minimo").value = defaultMin;
        document.getElementById("new-item-proposto").value = defaultSuggested;

        calculateNewItemSubtotal();
    }

    function calculateNewItemSubtotal() {
        const qtd = parseInt(document.getElementById("new-item-qtd").value) || 0;
        const proposto = parseFloat(document.getElementById("new-item-proposto").value) || 0.00;
        const sub = qtd * proposto;
        document.getElementById("new-item-subtotal-label").innerText = "Subtotal Proposto: R$ " + sub.toLocaleString('pt-BR', {minimumFractionDigits: 2});
    }

    function clearNewItemForm() {
        document.getElementById("new-item-sugerido").value = "";
        document.getElementById("new-item-minimo").value = "";
        document.getElementById("new-item-proposto").value = "";
        document.getElementById("new-item-qtd").value = "1";
        document.getElementById("new-item-subtotal-label").innerText = "Subtotal Proposto: R$ 0,00";
    }

    async function addNewItem() {
        const productId = document.getElementById("new-item-product").value;
        if (!productId) {
            alert("Selecione um produto.");
            return;
        }

        const payload = {
            produto_id: parseInt(productId),
            qtd: parseInt(document.getElementById("new-item-qtd").value),
            preco_unit_proposto: parseFloat(document.getElementById("new-item-proposto").value),
            preco_unit_sugerido: parseFloat(document.getElementById("new-item-sugerido").value),
            preco_minimo: parseFloat(document.getElementById("new-item-minimo").value),
            margem_calculada: 30.00, // mock margin
            custo: parseFloat(document.getElementById("new-item-minimo").value) * 0.7, // mock cost
            imposto: 18.00, // mock tax
        };

        try {
            const res = await fetch(`${API_URL}/cotacoes/token/${TOKEN}/itens`, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (data.success) {
                quote.itens = data.data.itens;
                renderItems();
                clearNewItemForm();
                document.getElementById("new-item-product").value = "";
            } else {
                alert("Erro ao adicionar item: " + data.message);
            }
        } catch (e) {
            alert("Erro de conexão.");
        }
    }

    async function deleteItem(itemId) {
        if (!confirm("Deseja realmente remover este item?")) return;

        try {
            const res = await fetch(`${API_URL}/cotacoes/token/${TOKEN}/itens/${itemId}`, {
                method: "DELETE"
            });
            const data = await res.json();
            if (data.success) {
                quote.itens = data.data.itens;
                renderItems();
            } else {
                alert("Erro ao remover item: " + data.message);
            }
        } catch (e) {
            alert("Erro de conexão.");
        }
    }

    // Save and Submit Actions
    async function saveDraft(showNotification = false) {
        // Collect current values from page
        const payload = {
            forma_pagamento: document.getElementById("forma-pagamento").value,
            prazo_entrega: document.getElementById("prazo-entrega").value,
            frete_tipo: document.getElementById("frete-tipo").value,
            transportadora: document.getElementById("transportadora").value,
            observacao_cliente: document.getElementById("obs-cliente").value,
            observacao_interna: document.getElementById("obs-interna").value,
            itens: quote.itens.map(i => ({
                id: i.id,
                qtd: i.qtd,
                preco_unit_proposto: i.preco_unit_proposto
            }))
        };

        try {
            const res = await fetch(`${API_URL}/cotacoes/token/${TOKEN}`, {
                method: "PATCH",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (data.success) {
                quote = data.data;
                if (showNotification) {
                    alert("Rascunho salvo com sucesso.");
                }
                return true;
            } else {
                alert("Erro ao salvar rascunho: " + data.error);
                return false;
            }
        } catch (e) {
            alert("Erro de conexão ao salvar.");
            return false;
        }
    }

    async function submitQuote() {
        // 1. Save draft changes first
        const saved = await saveDraft(false);
        if (!saved) return;

        // 2. If justifications are visible (needed), upload them first
        const justificationPanel = document.getElementById("justification-panel");
        if (justificationPanel.style.display !== "none") {
            const texto = document.getElementById("just-texto").value;
            const fileInput = document.getElementById("just-anexos");
            const audioInput = document.getElementById("just-audio");

            if (!texto && !fileInput.files.length && !audioInput.files.length) {
                alert("Justificativa é obrigatória por haver itens abaixo do preço mínimo.");
                return;
            }

            // Perform multipart submit for attachments/justification
            const formData = new FormData();
            formData.append("texto", texto);
            if (fileInput.files.length) {
                for (let i = 0; i < fileInput.files.length; i++) {
                    formData.append("anexos[]", fileInput.files[i]);
                }
            }
            if (audioInput.files.length) {
                formData.append("audio", audioInput.files[0]);
            }

            const justRes = await fetch(`${API_URL}/cotacoes/token/${TOKEN}/justificativa`, {
                method: "POST",
                body: formData
            });
            const justData = await justRes.json();
            if (!justData.success) {
                alert("Erro ao enviar justificativa: " + justData.message);
                return;
            }
        }

        // 3. Trigger submit to workflow routing
        try {
            const res = await fetch(`${API_URL}/cotacoes/token/${TOKEN}/enviar`, {
                method: "POST"
            });
            const data = await res.json();
            if (data.success) {
                alert("Cotação enviada com sucesso! Situação: " + data.status.replace(/_/g, ' '));
                window.location.reload();
            } else {
                alert("Erro ao enviar cotação: " + data.message);
            }
        } catch (e) {
            alert("Erro de conexão ao enviar.");
        }
    }

    function downloadPdf() {
        window.open(`${API_URL}/cotacoes/token/${TOKEN}/pdf`, '_blank');
    }

    // Mark as Lost Flow
    function openLostModal() {
        document.getElementById("lost-modal").style.display = "flex";
    }

    function closeLostModal() {
        document.getElementById("lost-modal").style.display = "none";
        document.getElementById("lost-reason").value = "";
    }

    async function confirmLost() {
        const justificativa = document.getElementById("lost-reason").value;
        if (!justificativa || justificativa.length < 5) {
            alert("Digite um motivo válido (mínimo 5 caracteres).");
            return;
        }

        try {
            const res = await fetch(`${API_URL}/cotacoes/token/${TOKEN}/perdida`, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ justificativa })
            });
            const data = await res.json();
            if (data.success) {
                alert("Cotação encerrada como PERDIDA.");
                closeLostModal();
                window.location.reload();
            } else {
                alert("Erro ao encerrar cotação: " + data.message);
            }
        } catch (e) {
            alert("Erro de conexão.");
        }
    }

    // Faturamento Release Flow
    function openReleaseModal() {
        document.getElementById("release-pedido-externo").value = "";
        document.getElementById("release-tipo-faturamento").value = "total";
        
        // Default the total field to the quotation proposed total
        document.getElementById("release-valor-pedido").value = parseFloat(quote.total).toFixed(2);
        document.getElementById("release-valor-pedido").readOnly = true;
        document.getElementById("release-valor-pedido").style.backgroundColor = "#e2e8f0";

        document.getElementById("release-modal").style.display = "flex";
    }

    function closeReleaseModal() {
        document.getElementById("release-modal").style.display = "none";
    }

    function onReleaseTypeChange(val) {
        const valueInput = document.getElementById("release-valor-pedido");
        if (val === 'parcial') {
            valueInput.readOnly = false;
            valueInput.style.backgroundColor = "#ffffff";
            valueInput.value = "";
            valueInput.placeholder = "Digite o valor parcial (R$)...";
        } else {
            valueInput.readOnly = true;
            valueInput.style.backgroundColor = "#e2e8f0";
            valueInput.value = parseFloat(quote.total).toFixed(2);
        }
    }

    async function confirmRelease() {
        const orderNum = document.getElementById("release-pedido-externo").value;
        const releaseType = document.getElementById("release-tipo-faturamento").value;
        const orderValue = parseFloat(document.getElementById("release-valor-pedido").value);

        if (!orderNum) {
            alert("O número do pedido no Sankhya é obrigatório.");
            return;
        }

        if (isNaN(orderValue) || orderValue <= 0) {
            alert("Por favor, preencha um valor válido para o faturamento.");
            return;
        }

        try {
            const res = await fetch(`${API_URL}/cotacoes/token/${TOKEN}/faturar`, {
                method: "POST",
                headers: { 
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    numero_pedido_externo: orderNum,
                    valor_pedido: orderValue,
                    tipo_faturamento: releaseType
                })
            });

            if (!res.ok) {
                const errText = await res.text();
                try {
                    const errJson = JSON.parse(errText);
                    alert("Erro: " + (errJson.message || errJson.error));
                } catch(e) {
                    alert(`Erro ${res.status}: ` + errText.substring(0, 200));
                }
                return;
            }

            const data = await res.json();
            if (data.success) {
                alert("Cotação liberada para faturamento com sucesso!");
                closeReleaseModal();
                loadData(); // reload details and status
            } else {
                alert("Erro: " + data.message);
            }
        } catch (e) {
            console.error(e);
            alert("Erro de conexão.");
        }
    }
</script>
@endsection
