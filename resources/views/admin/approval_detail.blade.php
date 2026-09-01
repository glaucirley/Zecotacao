@extends('layouts.app')

@section('page_title')
Análise da Cotação #<span id="header-quote-number">...</span> <span id="header-priority" class="badge-status priority" style="display:none; background-color:rgba(239,68,68,0.1); color:#ef4444; border:1px solid rgba(239,68,68,0.2); font-size:12px; font-weight:700; margin-left:8px; padding: 2px 8px; border-radius: 4px; vertical-align: middle;">⚡ GRANDE CONTA</span>
@endsection

@section('content')
<div id="loading-spinner" style="text-align: center; padding: 100px 20px;">
    <div style="border: 4px solid rgba(15,81,50,0.1); border-top: 4px solid var(--color-primary); border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 0 auto 15px auto;"></div>
    <span style="color: var(--color-text-muted); font-weight: 500;">Carregando cotação comercial...</span>
</div>

<div id="approval-cockpit" style="display: none;">
    <!-- Top Action Cards -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 15px;">
        <div>
            <h2 style="font-size: 22px;" id="client-title">Cliente: ...</h2>
            <p style="color: var(--color-text-muted); font-size: 13px;" id="rep-subtitle">Vendedor: ...</p>
        </div>
        <div style="display: flex; gap: 12px;">
            <button class="btn btn-outline" onclick="openDevolverModal()" style="border-color: var(--status-devolvida); color: var(--status-devolvida);">
                Devolver para Representante
            </button>
            <button id="btn-escalar" class="btn btn-outline" onclick="openEscalarModal()" style="border-color: var(--status-com-diretor); color: var(--status-com-diretor);">
                Escalar para Diretoria
            </button>
        </div>
    </div>

    <!-- Action Cockpit Buttons -->
    <div style="display: flex; gap: 12px; margin-bottom: 24px; flex-wrap: wrap;">
        <!-- Button 1: Justificativas -->
        <button class="btn btn-secondary" onclick="openJustificationsDrawer()" style="display: flex; align-items: center; gap: 8px; font-size: 13px; padding: 10px 16px; background-color: white; border: 1px solid var(--color-border); color: var(--color-text);">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            Justificativas de Desconto
        </button>

        <!-- Button 2: Auditoria -->
        <button class="btn btn-secondary" onclick="openAuditDrawer()" style="display: flex; align-items: center; gap: 8px; font-size: 13px; padding: 10px 16px; background-color: white; border: 1px solid var(--color-border); color: var(--color-text);">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            Histórico de Auditoria
        </button>

        <!-- Button 3: Vincular Itens -->
        <button class="btn btn-secondary" onclick="openBindDrawer()" style="display: flex; align-items: center; gap: 8px; font-size: 13px; padding: 10px 16px; background-color: white; border: 1px solid var(--color-border); color: var(--color-text);">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
            Vínculo de Itens
        </button>
    </div>

    <!-- Justifications Drawer -->
    <div id="justifications-overlay" class="sheet-overlay" onclick="closeJustificationsDrawer()"></div>
    <div id="justifications-drawer" class="sheet-drawer">
        <div class="sheet-header">
            <h3 style="margin: 0; color: var(--color-primary);">Justificativas de Desconto</h3>
            <button type="button" class="sheet-close-btn" onclick="closeJustificationsDrawer()">&times;</button>
        </div>
        <div class="sheet-body" id="justifications-container" style="padding-top: 10px;">
            <!-- Dynamic Justifications -->
        </div>
    </div>

    <!-- Audit Drawer -->
    <div id="audit-overlay" class="sheet-overlay" onclick="closeAuditDrawer()"></div>
    <div id="audit-drawer" class="sheet-drawer">
        <div class="sheet-header">
            <h3 style="margin: 0; color: var(--color-primary);">Histórico de Auditoria</h3>
            <div style="display: flex; gap: 8px; align-items: center;">
                <button type="button" class="btn btn-outline" onclick="openAuditPage()" style="padding: 4px 8px; font-size: 11px; margin: 0; height: auto;">Ver Completa</button>
                <button type="button" class="sheet-close-btn" onclick="closeAuditDrawer()">&times;</button>
            </div>
        </div>
        <div class="sheet-body" style="padding-top: 10px;">
            <div class="table-responsive">
                <ul class="stepper-list" id="stepper-container" style="padding-left: 0;">
                    <!-- Dynamic Log Steps -->
                </ul>
            </div>
        </div>
    </div>

    <!-- Bind Items Drawer -->
    <div id="bind-overlay" class="sheet-overlay" onclick="closeBindDrawer()"></div>
    <div id="bind-drawer" class="sheet-drawer">
        <div class="sheet-header">
            <h3 style="margin: 0; color: var(--color-primary);">Vincular Itens da Cotação</h3>
            <button type="button" class="sheet-close-btn" onclick="closeBindDrawer()">&times;</button>
        </div>
        <div class="sheet-body" style="padding-top: 10px;">
            <p style="font-size: 13px; color: var(--color-text-muted); margin-bottom: 16px; line-height: 1.4;">
                Marque os checkboxes dos itens desejados na tabela principal de cotação, dê um nome ao grupo identificador abaixo, e vincule-os para que sejam faturados obrigatoriamente juntos.
            </p>
            <div class="form-group" style="margin-bottom: 20px;">
                <label for="bind-group-name" class="form-label" style="font-weight: 600;">Identificador do Grupo (Ex: COMBO_VET_01)</label>
                <input type="text" id="bind-group-name" class="form-control" placeholder="Ex: COMBO_VET_01">
            </div>
            <button class="btn btn-primary" onclick="applyBind()" style="width: 100%; font-weight: 600; padding: 12px;">
                Vincular Selecionados
            </button>
        </div>
    </div>

    <!-- Items Grid Cockpit -->
    <div class="card">
        <div class="card-header">
            <h3>Itens para Decisão Granular (Item a Item)</h3>
            <span style="font-size: 12px; color: var(--color-text-muted);" id="evaluation-mode-label">Modo: ...</span>
        </div>

        <div class="table-responsive">
            <table class="table-premium" id="items-decision-table">
                <thead>
                    <tr>
                        <th style="width: 4%; text-align: center;"><input type="checkbox" id="check-all-items" onchange="toggleAllChecks(this)"></th>
                        <th style="width: 8%;">Código</th>
                        <th style="width: 25%;">Descrição</th>
                        <th style="width: 6%; text-align: center;">Qtd</th>
                        <th style="width: 10%; text-align: right;">Preço Mínimo</th>
                        <th style="width: 12%; text-align: right;">Preço Proposto</th>
                        <th style="width: 8%; text-align: center;">Margem</th>
                        <th style="width: 11%; text-align: right;">Total</th>
                        <th style="width: 16%; text-align: center;">Decisão</th>
                    </tr>
                </thead>
                <tbody id="items-decision-body">
                    <!-- Dynamic Rows -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- Summary Bar -->
    <div class="card" style="display: flex; justify-content: flex-end; gap: 30px; font-size: 14px; margin-bottom: 20px; padding: 18px; background: var(--color-bg); border-radius: 8px; border: 1px solid var(--color-border); flex-wrap: wrap;">
        <div>Subtotal Sugerido: <strong id="summary-subtotal" style="font-size:15px; color:#555;">R$ 0,00</strong></div>
        <div>Desconto Total: <strong id="summary-discount" style="color: var(--status-recusada); font-size:15px;">R$ 0,00</strong></div>
        <div>Valor Líquido: <strong id="summary-total" style="color: var(--color-primary); font-size:15px;">R$ 0,00</strong></div>
        <div style="border-left: 1px solid var(--color-border); padding-left: 30px;">
            Margem Geral da Cotação: <strong id="summary-overall-margin" style="font-size:16px;">0,00%</strong>
        </div>
    </div>

    <div class="card" style="display: flex; justify-content: flex-end; gap: 12px; background-color: var(--color-card); box-shadow: var(--shadow-lg);">
        <button class="btn btn-outline" onclick="window.location.href='{{ url('/aprovacoes') }}'">Voltar à Fila</button>
        <button class="btn btn-primary" onclick="submitDecision()" style="padding: 12px 24px;">Aplicar Decisão e Encerrar</button>
    </div>
</div>

<!-- Devolver Modal -->
<div id="devolver-modal" style="display: none; position: fixed; inset: 0; background-color: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 1000; padding: 20px;">
    <div class="auth-card" style="max-width: 500px; padding: 30px;">
        <h3 style="margin-bottom: 12px; color: var(--status-devolvida);">Devolver Cotação</h3>
        <p style="font-size: 13px; color: var(--color-text-muted); margin-bottom: 16px;">
            Insira a justificativa ou instruções de ajuste para o representante comercial. A cotação voltará a ser editável por ele.
        </p>
        <div class="form-group" style="margin-bottom: 20px;">
            <label for="devolver-reason" class="form-label">Instruções para o Representante</label>
            <textarea id="devolver-reason" class="form-control" rows="3" placeholder="Ex: Diminuir o desconto dos itens X e Y ou adicionar anexo..." required></textarea>
        </div>
        <div style="display: flex; justify-content: flex-end; gap: 12px;">
            <button class="btn btn-outline" onclick="closeDevolverModal()">Cancelar</button>
            <button class="btn btn-primary" onclick="confirmDevolver()" style="background-color: var(--status-devolvida);">Confirmar Devolução</button>
        </div>
    </div>
</div>

<!-- Escalar Modal -->
<div id="escalar-modal" style="display: none; position: fixed; inset: 0; background-color: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 1000; padding: 20px;">
    <div class="auth-card" style="max-width: 500px; padding: 30px;">
        <h3 style="margin-bottom: 12px; color: var(--status-com-diretor);">Escalar para Diretoria</h3>
        <p style="font-size: 13px; color: var(--color-text-muted); margin-bottom: 16px;">
            A cotação será enviada diretamente para a fila de decisões do Diretor Comercial.
        </p>
        <div class="form-group" style="margin-bottom: 20px;">
            <label for="escalar-reason" class="form-label">Motivo da Escalação</label>
            <textarea id="escalar-reason" class="form-control" rows="3" placeholder="Ex: Solicitação de desconto estratégico que excede limite da alçada do gestor..." required></textarea>
        </div>
        <div style="display: flex; justify-content: flex-end; gap: 12px;">
            <button class="btn btn-outline" onclick="closeEscalarModal()">Cancelar</button>
            <button class="btn btn-primary" onclick="confirmEscalar()" style="background-color: var(--status-com-diretor);">Confirmar Escalação</button>
        </div>
    </div>
</div>

<!-- Simulation modal -->
<div id="sim-modal" style="display: none; position: fixed; inset: 0; background-color: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 1000; padding: 20px;">
    <div class="auth-card" style="max-width: 450px; padding: 30px;">
        <h3 style="margin-bottom: 12px; color: var(--color-primary);">Simular Novo Preço Proposto</h3>
        <p style="font-size: 13px; color: var(--color-text-muted); margin-bottom: 16px;" id="sim-product-info">
            Produto: ...
        </p>
        <div class="form-group" style="margin-bottom: 20px;">
            <label for="sim-price-input" class="form-label">Novo Preço Unitário Proposto (R$)</label>
            <input type="number" id="sim-price-input" class="form-control" step="0.01" min="0.01">
        </div>
        <div style="display: flex; justify-content: flex-end; gap: 12px;">
            <button class="btn btn-outline" onclick="closeSimModal()">Cancelar</button>
            <button class="btn btn-primary" onclick="confirmSimulation()">Salvar Simulação</button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const QUOTE_ID = "{{ $id }}";
    const API_URL = "{{ url('/api/v1') }}";
    let quote = null;
    let currentSimItemId = null;

    document.addEventListener("DOMContentLoaded", () => {
        loadQuoteDetails();
    });

    function openAuditPage() {
        window.location.href = `{{ url('/cotacoes') }}/${QUOTE_ID}/auditoria`;
    }

    async function loadQuoteDetails() {
        try {
            const res = await fetch(`${API_URL}/aprovacoes/${QUOTE_ID}`);
            if (res.status === 401 || res.status === 403) {
                window.location.href = "{{ url('/login') }}";
                return;
            }
            const data = await res.json();
            if (data.success) {
                quote = data.data;
                renderCockpit();
            } else {
                alert("Erro ao carregar cotação: " + data.error);
            }
        } catch (e) {
            console.error(e);
            alert("Erro de conexão ao buscar dados.");
        }
    }

    async function renderCockpit() {
        if (!quote) return;

        // Fetch configurations to display evaluation mode info
        let evaluationMode = "ITEM_A_ITEM";
        try {
            const configRes = await fetch(`${API_URL}/parametros`);
            if (configRes.ok) {
                const configData = await configRes.json();
                const param = configData.data.find(p => p.chave === 'DESCONTO_AVALIACAO_MODO');
                if (param) evaluationMode = param.valor;
            }
        } catch(e) {}

        document.getElementById("loading-spinner").style.display = "none";
        document.getElementById("approval-cockpit").style.display = "block";

        // Bind Titles
        document.getElementById("header-quote-number").innerText = quote.numero;
        const priorityBadge = document.getElementById("header-priority");
        if (priorityBadge) {
            if (quote.prioridade) {
                priorityBadge.style.display = "inline-block";
            } else {
                priorityBadge.style.display = "none";
            }
        }
        document.getElementById("client-title").innerText = "Cliente: " + quote.parceiro.razao_social;
        document.getElementById("rep-subtitle").innerText = `Vendedor: ${quote.representante.nome} | Equipe: ${quote.representante.equipe ? quote.representante.equipe.nome : 'Sem equipe'} | Status: ${quote.status}`;
        document.getElementById("evaluation-mode-label").innerText = "Critério de Alçada: " + (evaluationMode === "ITEM_A_ITEM" ? "Item a Item (Maior Desconto)" : "Média Total da Cotação");

        // Hide Escalar button if director logged in
        const userRes = await fetch(`${API_URL}/auth/me`);
        const userData = await userRes.json();
        if (userData.success && userData.user.papel === 'diretor') {
            document.getElementById("btn-escalar").style.display = "none";
        }

        // Render Justifications Box
        renderJustifications();

        // Render Stepper Audit Trail
        renderStepper();

        // Render Items table
        renderDecisionItems();
    }

    function renderJustifications() {
        const container = document.getElementById("justifications-container");
        container.innerHTML = "";

        // Get unique justifications
        if (quote.justificativas.length === 0) {
            container.innerHTML = '<p style="color: var(--color-text-muted); font-size:13px;">Nenhuma justificativa anexada pelo representante.</p>';
            return;
        }

        quote.justificativas.forEach(j => {
            const date = new Date(j.created_at).toLocaleString('pt-BR');
            let itemDesc = "Geral da Cotação";
            if (j.cotacao_item_id) {
                const item = quote.itens.find(i => i.id === j.cotacao_item_id);
                if (item) {
                    itemDesc = `Produto: ${item.produto.descricao}`;
                }
            }

            let attachmentsMarkup = "";
            if (j.anexos && j.anexos.length > 0) {
                attachmentsMarkup += '<div style="margin-top: 10px; display:flex; gap:8px; flex-wrap:wrap;">';
                j.anexos.forEach(a => {
                    attachmentsMarkup += `
                        <a href="${a.arquivo_url}" target="_blank" class="btn btn-outline" style="padding: 4px 8px; font-size:11px;">
                            Ver Anexo (${a.tipo})
                        </a>
                    `;
                });
                attachmentsMarkup += '</div>';
            }

            let audioMarkup = "";
            if (j.audio_url) {
                audioMarkup = `
                    <div style="margin-top: 10px;">
                        <audio src="${j.audio_url}" controls style="width: 100%; max-width: 300px; height: 32px;"></audio>
                    </div>
                `;
            }

            container.innerHTML += `
                <div style="border: 1px solid var(--color-border); padding: 12px; border-radius: var(--radius-md); background-color: var(--color-bg); margin-bottom: 12px;">
                    <div style="display:flex; justify-content:space-between; font-size:11px; color: var(--color-text-muted); margin-bottom: 6px;">
                        <strong>Contexto: ${itemDesc}</strong>
                        <span>${date}</span>
                    </div>
                    <p style="font-size:13px; white-space:pre-line;">${j.texto || '(Sem justificativa em texto)'}</p>
                    ${audioMarkup}
                    ${attachmentsMarkup}
                </div>
            `;
        });
    }

    function renderStepper() {
        const container = document.getElementById("stepper-container");
        container.innerHTML = "";

        if (quote.historico.length === 0) {
            container.innerHTML = '<li>Nenhum evento registrado.</li>';
            return;
        }

        quote.historico.forEach(h => {
            const date = new Date(h.created_at).toLocaleString('pt-BR');
            container.innerHTML += `
                <li class="stepper-item">
                    <div class="stepper-time">${date} | Papel: ${h.papel}</div>
                    <div class="stepper-desc"><strong>${h.evento.replace(/_/g, ' ')}</strong> - ${h.condicao || ''}</div>
                </li>
            `;
        });
    }

    function renderDecisionItems() {
        const body = document.getElementById("items-decision-body");
        body.innerHTML = "";

        quote.itens.forEach(item => {
            const isBelowMin = parseFloat(item.preco_unit_proposto) < parseFloat(item.preco_minimo);
            
            // Margin Color coding
            const margin = parseFloat(item.margem_calculada || 0);
            let marginColor = "#ef4444"; // red
            if (margin >= 25) marginColor = "#10b981"; // green
            else if (margin >= 15) marginColor = "#d97706"; // yellow

            // Bind check state
            const linkedClass = item.vinculo ? "style='background-color: #f0fdf4; border-left:3px solid #10b981;'" : "";
            const bindGroupLabel = item.vinculo ? `<br><span style="font-size:10px; color:#15803d; font-weight:600;">Grupo: ${item.vinculo.grupo_vinculo}</span>` : "";

            body.innerHTML += `
                <tr id="item-row-${item.id}" ${linkedClass}>
                    <td class="text-center">
                        <input type="checkbox" class="item-checkbox" value="${item.id}">
                    </td>
                    <td><strong>${item.produto.codigo_sankhya}</strong></td>
                    <td>
                        ${item.produto.descricao}
                        ${item.mostrar_selo_campanha && item.campanha_id ? '<span class="badge-campanha">Campanha</span>' : ''}
                        ${bindGroupLabel}
                    </td>
                    <td class="text-center">${item.qtd}</td>
                    <td class="text-right">R$ ${parseFloat(item.preco_minimo).toLocaleString('pt-BR', {minimumFractionDigits:2})}</td>
                    <td class="text-right">
                        <div style="display:flex; align-items:center; justify-content:flex-end; gap:6px;">
                            <span style="font-weight:600; ${isBelowMin ? 'color:#dc2626;' : ''}">R$ ${parseFloat(item.preco_unit_proposto).toLocaleString('pt-BR', {minimumFractionDigits:2})}</span>
                            <button class="btn btn-outline" style="padding: 2px 6px; font-size:10px;" onclick="openSimulatePrice(${item.id}, '${item.produto.descricao}', ${item.preco_unit_proposto})">Simular</button>
                        </div>
                    </td>
                    <td class="text-center">
                        <span style="color: ${marginColor}; font-weight:600;">${margin}%</span>
                    </td>
                    <td class="text-right">R$ ${parseFloat(item.subtotal).toLocaleString('pt-BR', {minimumFractionDigits:2})}</td>
                    <td class="text-center">
                        <div style="display:flex; flex-direction:column; gap:4px; align-items:center;">
                            <select class="form-control item-action-select" style="font-size:12px; padding:4px 8px; width:110px;" onchange="onItemActionChange(${item.id}, this.value)">
                                <option value="aprovado">Aprovar</option>
                                <option value="recusado">Recusar</option>
                            </select>
                            <input type="text" id="rej-reason-${item.id}" class="form-control item-rej-reason" 
                                placeholder="Motivo da recusa..." 
                                style="font-size:11px; padding:4px 8px; width:140px; display:none; border-color:#fca5a5;">
                        </div>
                    </td>
                </tr>
            `;
        });

        calculateOverallTotals();
    }

    function calculateOverallTotals() {
        let totalProposedRevenue = 0;
        let totalNetRevenue = 0;
        let totalCost = 0;
        let totalSuggestedRevenue = 0;

        quote.itens.forEach(item => {
            const row = document.getElementById(`item-row-${item.id}`);
            let isRejected = false;
            if (row) {
                const select = row.querySelector(".item-action-select");
                if (select && select.value === "recusado") {
                    isRejected = true;
                }
            } else if (item.status_item === "recusado") {
                isRejected = true;
            }

            if (isRejected) {
                return;
            }

            const proposedPrice = parseFloat(item.preco_unit_proposto || 0);
            const suggestedPrice = parseFloat(item.preco_unit_sugerido || 0);
            const cost = parseFloat(item.custo || 0);
            const tax = parseFloat(item.imposto || 0);
            const qty = parseInt(item.qtd || 1);

            const proposedSubtotal = qty * proposedPrice;
            const suggestedSubtotal = qty * Math.max(suggestedPrice, proposedPrice);
            const itemNetRevenue = qty * proposedPrice * (1 - (tax / 100));
            const itemTotalCost = qty * cost;

            totalProposedRevenue += proposedSubtotal;
            totalSuggestedRevenue += suggestedSubtotal;
            totalNetRevenue += itemNetRevenue;
            totalCost += itemTotalCost;
        });

        const overallDiscount = totalSuggestedRevenue - totalProposedRevenue;

        // Calculate overall margin percentage
        let overallMargin = 0;
        if (totalNetRevenue > 0) {
            overallMargin = ((totalNetRevenue - totalCost) / totalNetRevenue) * 100;
        }

        // Update elements in DOM
        document.getElementById("summary-subtotal").innerText = "R$ " + totalSuggestedRevenue.toLocaleString('pt-BR', {minimumFractionDigits: 2});
        document.getElementById("summary-discount").innerText = "R$ " + (overallDiscount > 0 ? overallDiscount : 0).toLocaleString('pt-BR', {minimumFractionDigits: 2});
        document.getElementById("summary-total").innerText = "R$ " + totalProposedRevenue.toLocaleString('pt-BR', {minimumFractionDigits: 2});

        const marginEl = document.getElementById("summary-overall-margin");
        marginEl.innerText = overallMargin.toFixed(2) + "%";

        // Color code margin
        if (overallMargin >= 25) {
            marginEl.style.color = "#10b981"; // green
        } else if (overallMargin >= 15) {
            marginEl.style.color = "#d97706"; // yellow
        } else {
            marginEl.style.color = "#ef4444"; // red
        }
    }

    function onItemActionChange(itemId, action) {
        const reasonInput = document.getElementById(`rej-reason-${itemId}`);
        if (action === "recusado") {
            reasonInput.style.display = "block";
            reasonInput.setAttribute("required", "true");
        } else {
            reasonInput.style.display = "none";
            reasonInput.removeAttribute("required");
        }
    }

    function toggleAllChecks(master) {
        const checks = document.querySelectorAll(".item-checkbox");
        checks.forEach(c => c.checked = master.checked);
    }

    // Bind items action
    async function applyBind() {
        const groupName = document.getElementById("bind-group-name").value;
        if (!groupName) {
            alert("Insira um nome/código para o grupo de vínculo.");
            return;
        }

        const checkedBoxes = document.querySelectorAll(".item-checkbox:checked");
        const itemIds = Array.from(checkedBoxes).map(c => parseInt(c.value));

        if (itemIds.length < 2) {
            alert("Selecione pelo menos 2 produtos para vincular.");
            return;
        }

        try {
            const res = await fetch(`${API_URL}/aprovacoes/${QUOTE_ID}/vincular`, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ grupo_vinculo: groupName, item_ids: itemIds })
            });

            const data = await res.json();
            if (data.success) {
                alert("Itens vinculados comercialmente com sucesso!");
                closeBindDrawer();
                loadQuoteDetails();
            } else {
                alert("Erro ao vincular itens: " + data.message);
            }
        } catch(e) {
            alert("Erro de conexão.");
        }
    }

    // Drawer Toggles
    function openJustificationsDrawer() {
        document.getElementById("justifications-overlay").classList.add("open");
        document.getElementById("justifications-drawer").classList.add("open");
    }

    function closeJustificationsDrawer() {
        document.getElementById("justifications-overlay").classList.remove("open");
        document.getElementById("justifications-drawer").classList.remove("open");
    }

    function openAuditDrawer() {
        document.getElementById("audit-overlay").classList.add("open");
        document.getElementById("audit-drawer").classList.add("open");
    }

    function closeAuditDrawer() {
        document.getElementById("audit-overlay").classList.remove("open");
        document.getElementById("audit-drawer").classList.remove("open");
    }

    function openBindDrawer() {
        document.getElementById("bind-overlay").classList.add("open");
        document.getElementById("bind-drawer").classList.add("open");
    }

    function closeBindDrawer() {
        document.getElementById("bind-overlay").classList.remove("open");
        document.getElementById("bind-drawer").classList.remove("open");
        document.getElementById("bind-group-name").value = "";
    }

    // Simular price actions
    function openSimulatePrice(itemId, desc, currentPrice) {
        currentSimItemId = itemId;
        document.getElementById("sim-product-info").innerText = "Produto: " + desc;
        document.getElementById("sim-price-input").value = currentPrice;
        document.getElementById("sim-modal").style.display = "flex";
    }

    function closeSimModal() {
        document.getElementById("sim-modal").style.display = "none";
        currentSimItemId = null;
    }

    async function confirmSimulation() {
        const newPrice = parseFloat(document.getElementById("sim-price-input").value);
        if (!newPrice || newPrice <= 0) {
            alert("Preço inválido.");
            return;
        }

        try {
            const res = await fetch(`${API_URL}/aprovacoes/${QUOTE_ID}/itens/${currentSimItemId}`, {
                method: "PATCH",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ preco_unit_proposto: newPrice })
            });

            const data = await res.json();
            if (data.success) {
                closeSimModal();
                loadQuoteDetails();
            } else {
                alert("Erro na simulação: " + data.message);
            }
        } catch(e) {
            alert("Erro de conexão.");
        }
    }

    // Devolver & Escalar Flow
    function openDevolverModal() {
        document.getElementById("devolver-modal").style.display = "flex";
    }
    function closeDevolverModal() {
        document.getElementById("devolver-modal").style.display = "none";
        document.getElementById("devolver-reason").value = "";
    }
    async function confirmDevolver() {
        const reason = document.getElementById("devolver-reason").value;
        if (!reason) {
            alert("Justificativa obrigatória.");
            return;
        }

        try {
            const res = await fetch(`${API_URL}/aprovacoes/${QUOTE_ID}/decisao`, {
                method: "POST",
                headers: { 
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ acao: 'devolver', justificativa: reason })
            });

            if (!res.ok) {
                const errText = await res.text();
                try {
                    const errJson = JSON.parse(errText);
                    const details = errJson.messages ? Object.entries(errJson.messages).map(([key, val]) => `${val.join(', ')}`).join('\n') : null;
                    alert("Erro de Validação:\n" + (details || errJson.message || errJson.error));
                } catch(e) {
                    alert(`Erro ${res.status}: ` + errText.substring(0, 200));
                }
                return;
            }

            const data = await res.json();
            if (data.success) {
                alert("Cotação devolvida com sucesso!");
                window.location.href = "{{ url('/aprovacoes') }}";
            } else {
                alert("Erro: " + (data.message || data.error));
            }
        } catch (e) {
            console.error(e);
            alert("Erro de conexão.");
        }
    }

    function openEscalarModal() {
        document.getElementById("escalar-modal").style.display = "flex";
    }
    function closeEscalarModal() {
        document.getElementById("escalar-modal").style.display = "none";
        document.getElementById("escalar-reason").value = "";
    }
    async function confirmEscalar() {
        const reason = document.getElementById("escalar-reason").value;
        if (!reason) {
            alert("Justificativa obrigatória.");
            return;
        }

        try {
            const res = await fetch(`${API_URL}/aprovacoes/${QUOTE_ID}/decisao`, {
                method: "POST",
                headers: { 
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ acao: 'escalar', justificativa: reason })
            });

            if (!res.ok) {
                const errText = await res.text();
                try {
                    const errJson = JSON.parse(errText);
                    const details = errJson.messages ? Object.entries(errJson.messages).map(([key, val]) => `${val.join(', ')}`).join('\n') : null;
                    alert("Erro de Validação:\n" + (details || errJson.message || errJson.error));
                } catch(e) {
                    alert(`Erro ${res.status}: ` + errText.substring(0, 200));
                }
                return;
            }

            const data = await res.json();
            if (data.success) {
                alert("Cotação escalada com sucesso!");
                window.location.href = "{{ url('/aprovacoes') }}";
            } else {
                alert("Erro: " + (data.message || data.error));
            }
        } catch (e) {
            console.error(e);
            alert("Erro de conexão.");
        }
    }

    // Submit Final Item decisions
    async function submitDecision() {
        const itemsPayload = [];
        let hasRejectedWithoutReason = false;

        quote.itens.forEach(item => {
            const row = document.getElementById(`item-row-${item.id}`);
            if (row) {
                const select = row.querySelector(".item-action-select");
                const reason = row.querySelector(".item-rej-reason").value;

                if (select.value === "recusado" && !reason) {
                    hasRejectedWithoutReason = true;
                }

                itemsPayload.push({
                    id: item.id,
                    status: select.value,
                    justificativa: select.value === "recusado" ? reason : ""
                });
            }
        });

        if (hasRejectedWithoutReason) {
            alert("Por favor, preencha o motivo da recusa em todos os itens rejeitados.");
            return;
        }

        try {
            const res = await fetch(`${API_URL}/aprovacoes/${QUOTE_ID}/decisao`, {
                method: "POST",
                headers: { 
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ acao: 'decidir_itens', itens: itemsPayload })
            });

            if (!res.ok) {
                const errText = await res.text();
                try {
                    const errJson = JSON.parse(errText);
                    const details = errJson.messages ? Object.entries(errJson.messages).map(([key, val]) => `${val.join(', ')}`).join('\n') : null;
                    alert("Erro de Validação:\n" + (details || errJson.message || errJson.error));
                } catch(e) {
                    alert(`Erro ${res.status}: ` + errText.substring(0, 200));
                }
                return;
            }

            const data = await res.json();
            if (data.success) {
                alert("Decisões aplicadas com sucesso!");
                window.location.href = "{{ url('/aprovacoes') }}";
            } else {
                alert("Erro: " + (data.message || data.error));
            }
        } catch (e) {
            console.error(e);
            alert("Erro de conexão.");
        }
    }
</script>
@endsection
