@extends('layouts.app')

@section('page_title')
Análise da Cotação #<span id="header-quote-number">...</span> <span id="header-priority" class="badge-status priority" style="display:none; background-color:rgba(239,68,68,0.1); color:#ef4444; border:1px solid rgba(239,68,68,0.2); font-size:12px; font-weight:700; margin-left:8px; padding: 2px 8px; border-radius: 4px; vertical-align: middle;">⚡ GRANDE CONTA</span><span id="header-low-margin" class="badge-status" style="display:none; background-color:rgba(245,158,11,0.1); color:#d97706; border:1px solid rgba(245,158,11,0.3); font-size:12px; font-weight:700; margin-left:8px; padding: 2px 8px; border-radius: 4px; vertical-align: middle;">⚠️ MARGEM BAIXA</span>
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

    <!-- Validity Status & Auto-Extension Notice Banner -->
    <div id="validity-approval-banner" style="display: none; margin-bottom: 16px; padding: 12px 16px; border-radius: 10px; font-size: 13px; font-weight: 500; align-items: center; justify-content: space-between; gap: 10px;">
        <div id="validity-approval-banner-content" style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;"></div>
    </div>

    <!-- Negative Margin Critical Alert Banner -->
    <div id="negative-margin-alert-banner" style="display: none; margin-bottom: 18px; padding: 16px 20px; border-radius: 10px; background: #fef2f2; border: 2px solid #ef4444; color: #991b1b; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.12);">
        <div style="display: flex; align-items: flex-start; gap: 12px;">
            <span style="font-size: 22px; line-height: 1;">🚨</span>
            <div style="flex: 1;">
                <div style="font-weight: 800; font-size: 14.5px; color: #991b1b; margin-bottom: 4px;" id="negative-margin-alert-title">
                    ALERTA CRÍTICO: VENDA ABAIXO DO CUSTO (MARGEM NEGATIVA)
                </div>
                <div style="font-size: 13px; color: #7f1d1d; line-height: 1.45;" id="negative-margin-alert-body">
                    Esta cotação possui itens com preço líquido inferior ao custo. A aprovação exigirá confirmação explícita de responsabilidade.
                </div>
            </div>
        </div>
    </div>

    <!-- Justificativas de Desconto (Aberto por padrão para decisão do aprovador) -->
    <div class="card" id="approval-justifications-card" style="margin-bottom: 18px; border: 1.5px solid #fcd34d; border-radius: 12px; background: #fffdf5; box-shadow: var(--shadow-sm); padding: 18px 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; border-bottom: 1px solid #fef3c7; padding-bottom: 10px; flex-wrap: wrap; gap: 8px;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="font-size: 18px; line-height: 1;">💬</span>
                <h3 style="font-size: 15px; margin: 0; font-weight: 700; color: #92400e;">
                    Justificativas de Desconto do Vendedor
                </h3>
            </div>
            <span style="font-size: 11.5px; color: #b45309; font-weight: 600; background: #fef3c7; padding: 2px 8px; border-radius: 6px; border: 1px solid #fde68a;">
                Essencial para Decisão
            </span>
        </div>
        <div id="approval-inline-justifications">
            <!-- Dynamic Justifications -->
        </div>
    </div>

    <!-- Trilha de Auditoria & Histórico (Recolhida por padrão) -->
    <div class="card" style="margin-bottom: 20px; padding: 0; overflow: hidden; border: 1px solid var(--color-border); border-radius: 12px; background: #ffffff; box-shadow: var(--shadow-sm);">
        <div onclick="toggleAuditAccordion()" style="padding: 14px 18px; display: flex; justify-content: space-between; align-items: center; cursor: pointer; background: #f8fafc; transition: background 0.2s ease;">
            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; font-size: 13px;">
                <span style="font-size: 16px; line-height: 1;">🕘</span>
                <span style="font-weight: 700; color: #0f172a;">Trilha de Auditoria</span>
                <span style="color: #94a3b8;">·</span>
                <span id="audit-summary-details" style="color: #64748b; font-size: 12.5px;">Carregando eventos...</span>
            </div>
            <div style="display: flex; align-items: center; gap: 6px; font-size: 12px; color: #2563eb; font-weight: 600; white-space: nowrap;">
                <span id="audit-toggle-text">Ver ⌄</span>
                <svg id="audit-toggle-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="transition: transform 0.2s ease;"><polyline points="6 9 12 15 18 9"/></svg>
            </div>
        </div>

        <div id="audit-accordion-body" style="display: none; padding: 18px; border-top: 1px solid var(--color-border); background: white;">
            <div id="approval-audit-trail" style="display: flex; flex-direction: column; gap: 10px;">
                <div style="font-size: 12.5px; color: #64748b; text-align: center; padding: 12px;">Carregando histórico da cotação...</div>
            </div>
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

<!-- Negative Margin Confirmation Modal -->
<div id="negative-margin-modal" style="display: none; position: fixed; inset: 0; background-color: rgba(15, 23, 42, 0.65); align-items: center; justify-content: center; z-index: 1100; padding: 20px;">
    <div class="auth-card" style="max-width: 540px; width: 100%; padding: 28px; border: 2px solid #ef4444; box-shadow: 0 20px 40px rgba(0,0,0,0.25);">
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px; color: #b91c1c;">
            <span style="font-size: 24px;">🚨</span>
            <h3 style="margin: 0; color: #b91c1c; font-size: 18px; font-weight: 800;">Confirmação Obrigatória — Margem Negativa</h3>
        </div>
        <p style="font-size: 13.5px; color: #334155; margin-bottom: 12px; line-height: 1.45;">
            Você está prestes a aprovar uma venda <strong>abaixo do custo</strong> (margem negativa). Revise o resumo abaixo antes de autorizar:
        </p>
        <div id="negative-margin-modal-summary" style="background: #fef2f2; border: 1px solid #fca5a5; border-radius: 8px; padding: 12px 14px; margin-bottom: 16px; font-size: 12.5px; color: #7f1d1d; max-height: 170px; overflow-y: auto;"></div>

        <label style="display: flex; align-items: flex-start; gap: 10px; background: #fff1f2; border: 1px solid #fecdd3; padding: 10px 12px; border-radius: 8px; cursor: pointer; margin-bottom: 14px; font-size: 12.5px; color: #881337; font-weight: 600;">
            <input type="checkbox" id="chk-confirm-negative-margin" onchange="toggleNegativeMarginConfirmBtn(this.checked)" style="margin-top: 2px; width: 16px; height: 16px; accent-color: #dc2626; flex-shrink: 0;">
            <span>Estou ciente de que esta aprovação autoriza venda com margem negativa (abaixo do custo) e confirmo a exceção comercial.</span>
        </label>

        <div class="form-group" style="margin-bottom: 18px;">
            <label for="negative-margin-obs" class="form-label" style="font-size: 12px; font-weight: 600; color: #475569;">Observação / Justificativa da Autorização (Opcional)</label>
            <textarea id="negative-margin-obs" class="form-control" rows="2" placeholder="Ex: Liquidação estratégica de lote / negociação especial autorizada pela diretoria..." style="font-size: 12.5px;"></textarea>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 10px; flex-wrap: wrap;">
            <button type="button" class="btn btn-outline" onclick="closeNegativeMarginModal()">Cancelar e Revisar</button>
            <button type="button" id="btn-confirm-negative-margin" class="btn btn-primary" onclick="confirmNegativeMarginApproval()" disabled style="background-color: #dc2626; border-color: #dc2626; opacity: 0.5; cursor: not-allowed; font-weight: 700;">
                Confirmar Aprovação c/ Margem Negativa
            </button>
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
            if (res.status === 404) {
                const spinner = document.getElementById("loading-spinner");
                if (spinner) {
                    spinner.innerHTML = `
                        <div style="text-align:center; padding:50px 20px;">
                            <div style="font-size:36px; margin-bottom:12px;">🔍</div>
                            <h3 style="color:#0f172a; margin-bottom:8px;">Cotação não encontrada</h3>
                            <p style="color:#64748b; font-size:13px; margin-bottom:16px;">O registro solicitado não existe ou já foi finalizado.</p>
                            <a href="{{ url('/aprovacoes') }}" class="btn btn-primary" style="text-decoration:none;">Voltar para Aprovações</a>
                        </div>
                    `;
                }
                showToast("Cotação não encontrada.", "error");
                return;
            }
            const data = await res.json();
            if (data.success) {
                quote = data.data;
                renderCockpit();
            } else {
                showToast("Erro ao carregar cotação: " + (data.error || 'Erro desconhecido'), "error");
            }
        } catch (e) {
            console.error(e);
            showToast("Erro de conexão ao buscar dados.", "error");
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
        const pageTitleEl = document.querySelector(".page-title");
        if (pageTitleEl) pageTitleEl.setAttribute("title", "Análise da Cotação #" + quote.numero);
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

        // Render Validity Status Banner while awaiting approval
        renderValidityApprovalBanner();

        // Render Justifications Box
        renderJustifications();

        // Render Stepper Audit Trail
        renderStepper();

        // Render Items table
        renderDecisionItems();
    }

    function renderValidityApprovalBanner() {
        const banner = document.getElementById("validity-approval-banner");
        const content = document.getElementById("validity-approval-banner-content");
        if (!banner || !content || !quote) return;

        const isPendingApproval = ['AGUARDANDO_GESTOR', 'COM_DIRETOR'].includes(quote.status);
        if (!isPendingApproval) {
            banner.style.display = "none";
            return;
        }

        const wasExtended = Array.isArray(quote.historico) && quote.historico.some(h => h.evento === 'VALIDADE_ESTENDIDA_EM_APROVACAO');
        const valDate = quote.data_validade ? new Date(quote.data_validade) : null;
        const now = new Date();
        const diffHours = valDate ? ((valDate.getTime() - now.getTime()) / (1000 * 60 * 60)) : 24;
        const valStr = valDate ? valDate.toLocaleString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '24h';

        banner.style.display = "flex";
        if (wasExtended || diffHours <= 4) {
            banner.style.background = "#fffbeb";
            banner.style.border = "1.5px solid #f59e0b";
            banner.style.color = "#92400e";
            content.innerHTML = `
                <span style="font-size: 18px;">⏰</span>
                <div>
                    <strong>Atenção ao Prazo Comercial:</strong> ${wasExtended ? 'Esta cotação atingiu o prazo inicial na fila e teve sua validade estendida automaticamente.' : 'O prazo inicial desta cotação está próximo do vencimento.'}
                    A expiração automática está <strong>pausada</strong> enquanto aguarda sua análise e a validade comercial (+${quote.validade_horas || 24}h) será renovada ao aplicar a decisão (atual: ${valStr}).
                </div>
            `;
        } else {
            banner.style.background = "#f0f9ff";
            banner.style.border = "1px solid #bae6fd";
            banner.style.color = "#0369a1";
            content.innerHTML = `
                <span style="font-size: 16px;">⏸️</span>
                <div>
                    <strong>Validade protegida durante aprovação:</strong> A cotação não expira enquanto estiver na fila de avaliação. Ao aprovar, o prazo de validade (+${quote.validade_horas || 24}h) será renovado para envio ao cliente.
                </div>
            `;
        }
    }

    function toggleAuditAccordion(forceState) {
        const body = document.getElementById("audit-accordion-body");
        const arrow = document.getElementById("audit-toggle-arrow");
        const text = document.getElementById("audit-toggle-text");
        if (!body) return;

        let shouldOpen;
        if (typeof forceState === 'boolean') {
            shouldOpen = forceState;
        } else {
            shouldOpen = body.style.display === "none";
        }

        body.style.display = shouldOpen ? "block" : "none";
        if (arrow) arrow.style.transform = shouldOpen ? "rotate(180deg)" : "rotate(0deg)";
        if (text) text.innerText = shouldOpen ? "Recolher ⌃" : "Ver ⌄";

        try {
            sessionStorage.setItem('zecotacao_audit_accordion_open', shouldOpen ? 'true' : 'false');
        } catch(e) {}
    }

    function formatAuditTime(d) {
        if (!d || isNaN(d.getTime())) return '';
        const h = String(d.getHours()).padStart(2, '0');
        const m = String(d.getMinutes()).padStart(2, '0');
        return `${h}:${m}`;
    }

    function formatAuditDate(d) {
        if (!d || isNaN(d.getTime())) return '';
        const day = String(d.getDate()).padStart(2, '0');
        const mo = String(d.getMonth() + 1).padStart(2, '0');
        return `${day}/${mo}`;
    }

    function formatAuditDateTime(d) {
        if (!d || isNaN(d.getTime())) return '';
        return `${formatAuditDate(d)} ${formatAuditTime(d)}`;
    }

    function getEventFriendlyTitle(evento, condicao) {
        const ev = String(evento || '').toUpperCase();
        const map = {
            'CRIADA': 'Cotação criada',
            'SALVA_RASCUNHO': 'Rascunho salvo',
            'EDITADA': 'Cotação editada',
            'SUBMETIDA_APROVACAO': 'Enviada para aprovação',
            'ENVIADA_PARA_APROVACAO': 'Enviada para aprovação',
            'APROVADA': 'Cotação aprovada',
            'APROVADA_AUTOMATICA': 'Aprovada automaticamente',
            'APROVADA_DIRETORIA': 'Aprovada pela diretoria',
            'DEVOLVIDA': 'Devolvida para ajustes',
            'DEVOLVIDA_AO_REPRESENTANTE': 'Devolvida para ajustes',
            'COTACAO_DEVOLVIDA': 'Devolvida para ajustes',
            'ESCALADA_DIRETORIA': 'Escalada para diretoria',
            'RECUSADA': 'Cotação recusada',
            'CANCELADA': 'Cotação cancelada',
            'PERDIDA': 'Marcada como perdida',
            'PDF_GERADO': 'PDF comercial gerado',
            'PROPOSTA_ENVIADA_WHATSAPP': 'Proposta enviada via WhatsApp',
            'PROPOSTA_ENVIADA_EMAIL': 'Proposta enviada por e-mail',
            'PROPOSTA_ENVIADA_LINK': 'Link compartilhado',
            'PROPOSTA_ABERTA_CLIENTE': 'Proposta aberta pelo cliente',
            'PEDIDO_GERADO': 'Pedido gerado no ERP',
            'FATURADA': 'Cotação faturada',
            'VALIDADE_ESTENDIDA_EM_APROVACAO': 'Validade estendida em aprovação'
        };
        if (map[ev]) return map[ev];
        return (evento || 'Evento de Workflow').replace(/_/g, ' ');
    }

    function groupAuditEvents(rawHistory) {
        if (!Array.isArray(rawHistory) || rawHistory.length === 0) return [];

        const sorted = rawHistory.slice().sort((a, b) => {
            const da = new Date(a.created_at || 0).getTime();
            const db = new Date(b.created_at || 0).getTime();
            return (da - db) || (a.id - b.id);
        });

        const groups = [];
        let currentGroup = null;

        sorted.forEach(item => {
            const evName = String(item.evento || item.descricao || '').toUpperCase();
            const userId = item.usuario_id || (item.usuario ? item.usuario.id : null);
            const userRole = (item.usuario && item.usuario.papel) ? item.usuario.papel : (item.papel || '');
            const userName = item.usuario ? (item.usuario.nome || item.usuario.name) : (item.papel || 'Sistema');
            const itemDate = new Date(item.created_at || Date.now());

            if (!currentGroup) {
                currentGroup = {
                    evento: evName,
                    userId: userId,
                    userName: userName,
                    userRole: userRole,
                    items: [item],
                    startDate: itemDate,
                    endDate: itemDate
                };
            } else {
                const sameEv = currentGroup.evento === evName;
                const sameUser = (currentGroup.userId && userId && currentGroup.userId === userId) ||
                                 (currentGroup.userName === userName && currentGroup.userRole === userRole);

                if (sameEv && sameUser) {
                    currentGroup.items.push(item);
                    currentGroup.endDate = itemDate;
                } else {
                    groups.push(currentGroup);
                    currentGroup = {
                        evento: evName,
                        userId: userId,
                        userName: userName,
                        userRole: userRole,
                        items: [item],
                        startDate: itemDate,
                        endDate: itemDate
                    };
                }
            }
        });

        if (currentGroup) {
            groups.push(currentGroup);
        }

        return groups.reverse();
    }

    function renderJustifications() {
        const drawerContainer = document.getElementById("justifications-container");
        const inlineContainer = document.getElementById("approval-inline-justifications");
        
        let html = "";
        const justifs = Array.isArray(quote.justificativas) ? quote.justificativas : [];

        if (justifs.length === 0) {
            html = '<div style="color: var(--color-text-muted); font-size:13px; font-style:italic; padding:6px 0;">Nenhuma justificativa formal anexada pelo representante para esta cotação.</div>';
            if (drawerContainer) drawerContainer.innerHTML = html;
            if (inlineContainer) inlineContainer.innerHTML = html;
            return;
        }

        justifs.forEach(j => {
            const date = j.created_at ? new Date(j.created_at).toLocaleString('pt-BR') : '';
            let itemDesc = "Geral da Cotação";
            if (j.cotacao_item_id) {
                const item = (quote.itens || []).find(i => i.id === j.cotacao_item_id);
                if (item) {
                    itemDesc = `Item específico: ${item.produto ? item.produto.descricao : (item.descricao_original || 'Produto')}`;
                }
            }

            let attachmentsMarkup = "";
            if (j.anexos && j.anexos.length > 0) {
                attachmentsMarkup += '<div style="margin-top: 10px; display:flex; gap:8px; flex-wrap:wrap;">';
                j.anexos.forEach(a => {
                    attachmentsMarkup += `
                        <a href="${a.arquivo_url}" target="_blank" class="btn btn-outline" style="padding: 4px 10px; font-size:11.5px; display:inline-flex; align-items:center; gap:5px; background:#ffffff; text-decoration:none;">
                            <span>📎</span> <span>Ver Anexo (${escapeHtml(a.tipo || 'Arquivo')})</span>
                        </a>
                    `;
                });
                attachmentsMarkup += '</div>';
            }

            let audioMarkup = "";
            if (j.audio_url) {
                audioMarkup = `
                    <div style="margin-top: 10px; display:flex; align-items:center; gap:8px;">
                        <span style="font-size:16px;">🎙️</span>
                        <audio src="${j.audio_url}" controls style="width: 100%; max-width: 340px; height: 34px;"></audio>
                    </div>
                `;
            }

            html += `
                <div style="border: 1px solid #fde68a; padding: 14px 16px; border-radius: 10px; background-color: #ffffff; margin-bottom: 12px; box-shadow: 0 1px 2px rgba(0,0,0,0.03);">
                    <div style="display:flex; justify-content:space-between; align-items:center; font-size:12px; color: #b45309; margin-bottom: 8px; flex-wrap:wrap; gap:6px;">
                        <span style="font-weight:700; color:#92400e; background:#fef3c7; padding:2px 8px; border-radius:4px;">${escapeHtml(itemDesc)}</span>
                        <span style="color:#94a3b8; font-size:11.5px;">📅 ${date}</span>
                    </div>
                    <p style="font-size:13px; color:#1e293b; margin:0; line-height:1.5; white-space:pre-line;">${escapeHtml(j.texto || '(Sem justificativa textual preenchida)')}</p>
                    ${audioMarkup}
                    ${attachmentsMarkup}
                </div>
            `;
        });

        if (drawerContainer) drawerContainer.innerHTML = html;
        if (inlineContainer) inlineContainer.innerHTML = html;
    }

    function renderStepper() {
        const drawerContainer = document.getElementById("stepper-container");
        const inlineContainer = document.getElementById("approval-audit-trail");
        const summaryEl = document.getElementById("audit-summary-details");

        const rawHistory = Array.isArray(quote.historico) ? quote.historico : [];
        const totalCount = rawHistory.length;

        // Restore accordion state
        try {
            const savedState = sessionStorage.getItem('zecotacao_audit_accordion_open');
            toggleAuditAccordion(savedState === 'true');
        } catch(e) {
            toggleAuditAccordion(false);
        }

        if (totalCount === 0) {
            if (summaryEl) summaryEl.innerText = "0 eventos registrados";
            const emptyHtml = '<div style="font-size:12.5px; color:#94a3b8; text-align:center; padding:14px; background:#f8fafc; border-radius:8px; border:1px dashed #e2e8f0;">Nenhum evento registrado no histórico até o momento.</div>';
            if (inlineContainer) inlineContainer.innerHTML = emptyHtml;
            if (drawerContainer) drawerContainer.innerHTML = '<li>Nenhum evento registrado.</li>';
            return;
        }

        // Summary header
        const sortedChronological = rawHistory.slice().sort((a, b) => {
            const da = new Date(a.created_at || 0).getTime();
            const db = new Date(b.created_at || 0).getTime();
            return (da - db) || (a.id - b.id);
        });
        const latest = sortedChronological[sortedChronological.length - 1];
        const latestTitle = getEventFriendlyTitle(latest.evento, latest.condicao);
        const latestDateStr = formatAuditDateTime(new Date(latest.created_at || Date.now()));

        if (summaryEl) {
            summaryEl.innerHTML = `<strong>${totalCount} evento${totalCount > 1 ? 's' : ''}</strong> · Último: ${escapeHtml(latestTitle)} — ${latestDateStr}`;
        }

        // Group consecutive duplicate events and reverse (newest first)
        const grouped = groupAuditEvents(rawHistory);
        
        // Inline Accordion rendering
        if (inlineContainer) {
            inlineContainer.innerHTML = "";
            grouped.forEach(group => {
                const isGrouped = group.items.length > 1;
                const lastItem = group.items[group.items.length - 1];
                const cleanUser = group.userName || 'Sistema';
                const cleanRole = group.userRole ? `(${group.userRole})` : '';
                const cleanUserInitial = String(cleanUser).replace(/<[^>]*>/g, '').trim().charAt(0).toUpperCase() || 'U';

                let actionText = "";
                if (isGrouped) {
                    if (group.evento === 'EDITADA') {
                        actionText = `Editada ${group.items.length}× por ${escapeHtml(cleanUser)}`;
                    } else {
                        actionText = `${getEventFriendlyTitle(group.evento)} ${group.items.length}× por ${escapeHtml(cleanUser)}`;
                    }
                } else {
                    actionText = getEventFriendlyTitle(group.evento, lastItem.condicao);
                }

                // Date Range
                let dateDisplay = "";
                const startStr = formatAuditDateTime(group.startDate);
                const endStr = formatAuditDateTime(group.endDate);
                if (formatAuditDate(group.startDate) === formatAuditDate(group.endDate)) {
                    if (formatAuditTime(group.startDate) === formatAuditTime(group.endDate)) {
                        dateDisplay = startStr;
                    } else {
                        dateDisplay = `${startStr}–${formatAuditTime(group.endDate)}`;
                    }
                } else {
                    dateDisplay = `${startStr} – ${endStr}`;
                }

                const details = lastItem.justificativa || lastItem.observacao || lastItem.condicao || '';

                const row = document.createElement("div");
                row.style.cssText = "display: flex; gap: 12px; align-items: flex-start; padding: 10px 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 12.5px;";
                row.innerHTML = `
                    <div style="width: 28px; height: 28px; border-radius: 50%; background: #dbeafe; color: #1d4ed8; font-weight: 700; display: flex; align-items: center; justify-content: center; font-size: 12px; flex-shrink: 0;">
                        ${escapeHtml(cleanUserInitial)}
                    </div>
                    <div style="flex-grow: 1;">
                        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 4px;">
                            <span style="font-weight: 700; color: #0f172a;">
                                ${escapeHtml(cleanUser)} <span style="font-weight: 500; color: #64748b; font-size: 11.5px;">${escapeHtml(cleanRole)}</span>
                                ${isGrouped ? `<span style="background: #e2e8f0; color: #334155; font-size: 10.5px; font-weight: 700; padding: 1px 7px; border-radius: 999px; margin-left: 6px;">${group.items.length}× repetido</span>` : ''}
                            </span>
                            <span style="color: #64748b; font-size: 11px; font-weight: 500;">📅 ${dateDisplay}</span>
                        </div>
                        <div style="color: #2563eb; font-weight: 600; margin-top: 2px;">${actionText}</div>
                        ${details ? `<div style="color: #475569; margin-top: 4px; background: #ffffff; padding: 6px 10px; border-radius: 6px; border: 1px solid #e2e8f0; font-size: 12px;">${escapeHtml(details)}</div>` : ''}
                    </div>
                `;
                inlineContainer.appendChild(row);
            });
        }

        // Drawer stepper list
        if (drawerContainer) {
            drawerContainer.innerHTML = "";
            grouped.forEach(group => {
                const isGrouped = group.items.length > 1;
                const lastItem = group.items[group.items.length - 1];
                const actionTitle = isGrouped 
                    ? `${getEventFriendlyTitle(group.evento)} (${group.items.length}×)`
                    : getEventFriendlyTitle(group.evento, lastItem.condicao);
                const dateDisplay = formatAuditDateTime(group.endDate);
                drawerContainer.innerHTML += `
                    <li class="stepper-item">
                        <div class="stepper-time">${dateDisplay} | ${escapeHtml(group.userName)} (${escapeHtml(group.userRole || 'Usuário')})</div>
                        <div class="stepper-desc"><strong>${escapeHtml(actionTitle)}</strong> - ${escapeHtml(lastItem.condicao || lastItem.justificativa || '')}</div>
                    </li>
                `;
            });
        }
    }

    function computeEffectiveItemMargin(item) {
        const sug = parseFloat(item.preco_unit_sugerido || 0);
        const min = parseFloat(item.preco_minimo || 0);
        const prop = parseFloat(item.preco_unit_proposto || 0);
        const cost = parseFloat(item.custo || 0);
        const tax = parseFloat(item.imposto || 0);
        const isInconsistent = (sug > 0 && min > sug) || (cost <= 0 || (sug > 0 && cost > sug)) || (tax < 0 || tax >= 100) || (prop <= 0);
        if (!isInconsistent) {
            const net = prop * (1 - (tax / 100));
            return net > 0 ? ((net - cost) / net) * 100 : -100;
        }
        return parseFloat(item.margem_calculada || 0);
    }

    function renderDecisionItems() {
        const body = document.getElementById("items-decision-body");
        body.innerHTML = "";

        quote.itens.forEach(item => {
            const sug = parseFloat(item.preco_unit_sugerido || 0);
            const min = parseFloat(item.preco_minimo || 0);
            const prop = parseFloat(item.preco_unit_proposto || 0);
            const cost = parseFloat(item.custo || 0);
            const isInconsistent = (sug > 0 && min > sug);
            const minEf = isInconsistent ? sug : min;
            const isBelowMin = prop < minEf;
            
            // Margin Color coding
            const margin = parseFloat(computeEffectiveItemMargin(item).toFixed(2));
            const isNegativeMargin = margin < 0;
            let marginColor = "#ef4444"; // red
            if (margin >= 25) marginColor = "#10b981"; // green
            else if (margin >= 15) marginColor = "#d97706"; // yellow

            // Bind check state or negative margin row highlight
            let rowStyle = "";
            if (isNegativeMargin) {
                rowStyle = "style='background-color: #fff1f2; border-left: 4px solid #dc2626;'";
            } else if (item.vinculo) {
                rowStyle = "style='background-color: #f0fdf4; border-left:3px solid #10b981;'";
            }
            const bindGroupLabel = item.vinculo ? `<br><span style="font-size:10px; color:#15803d; font-weight:600;">Grupo: ${escapeHtml(item.vinculo.grupo_vinculo)}</span>` : "";
            const prodDesc = item.produto ? item.produto.descricao : (item.descricao_original || 'Item');
            const prodCode = item.produto ? item.produto.codigo_sankhya : '-';

            body.innerHTML += `
                <tr id="item-row-${item.id}" ${rowStyle}>
                    <td class="text-center">
                        <input type="checkbox" class="item-checkbox" value="${item.id}">
                    </td>
                    <td><strong>${escapeHtml(prodCode)}</strong></td>
                    <td>
                        ${escapeHtml(prodDesc)}
                        ${item.mostrar_selo_campanha && item.campanha_id ? '<span class="badge-campanha">Campanha</span>' : ''}
                        ${isInconsistent ? '<span class="badge" style="background-color: #fef3c7; color: #92400e; font-size: 11px; padding: 2px 6px; border-radius: 4px; border: 1px solid #fcd34d; margin-left:6px; font-weight:600;" title="Preço mínimo cadastral maior que o sugerido.">⚠️ Mín > Sugerido</span>' : ''}
                        ${isNegativeMargin ? `<span class="badge" style="background-color: #fee2e2; color: #991b1b; font-size: 11px; padding: 2px 7px; border-radius: 4px; border: 1px solid #f87171; margin-left:6px; font-weight:700;" title="Venda abaixo do custo unitário (Custo: R$ ${cost.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})})">🚨 Abaixo do custo</span>` : ''}
                        ${bindGroupLabel}
                    </td>
                    <td class="text-center">${item.qtd}</td>
                    <td class="text-right">
                        R$ ${parseFloat(item.preco_minimo).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})}
                        ${isInconsistent ? '<span title="Cadastro com mínimo maior que sugerido." style="color:#d97706; font-size:12px; margin-left:2px;">⚠️</span>' : ''}
                    </td>
                    <td class="text-right">
                        <div style="display:flex; align-items:center; justify-content:flex-end; gap:6px;">
                            <span style="font-weight:600; ${isBelowMin ? 'color:#dc2626;' : ''}">R$ ${parseFloat(item.preco_unit_proposto).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
                            <button class="btn btn-outline" style="padding: 2px 6px; font-size:10px;" onclick="openSimulatePrice(${item.id}, ${escapeHtml(JSON.stringify(prodDesc))}, ${item.preco_unit_proposto})">Simular</button>
                        </div>
                    </td>
                    <td class="text-center">
                        <span style="color: ${marginColor}; font-weight:${isNegativeMargin ? '800' : '600'};">${margin.toFixed(2).replace('.', ',')}%</span>
                        ${isNegativeMargin && cost > 0 ? `<div style="font-size:10px; color:#b91c1c; font-weight:600;">Custo: R$ ${cost.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>` : ''}
                    </td>
                    <td class="text-right">R$ ${parseFloat(item.subtotal).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
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

    let currentNegativeMarginState = {
        hasNegativeMargin: false,
        overallMargin: 0,
        negativeItems: [],
        estimatedLoss: 0
    };

    function calculateOverallTotals() {
        let totalProposedRevenue = 0;
        let totalNetRevenue = 0;
        let totalCost = 0;
        let totalSuggestedRevenue = 0;
        let validItemsForMargin = 0;
        const negativeItems = [];
        let estimatedLoss = 0;

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

            if (isRejected || item.nao_encontrado) {
                return;
            }

            const proposedPrice = parseFloat(item.preco_unit_proposto || 0);
            const suggestedPrice = parseFloat(item.preco_unit_sugerido || 0);
            const minPrice = parseFloat(item.preco_minimo || 0);
            const cost = parseFloat(item.custo || 0);
            const tax = parseFloat(item.imposto || 0);
            const qty = parseInt(item.qtd || 1);

            const proposedSubtotal = qty * proposedPrice;
            const suggestedSubtotal = qty * Math.max(suggestedPrice, proposedPrice);

            totalProposedRevenue += proposedSubtotal;
            totalSuggestedRevenue += suggestedSubtotal;

            // Ignorar itens com dados inconsistentes no cálculo da margem:
            const isInconsistent = (suggestedPrice > 0 && minPrice > suggestedPrice)
                || (cost <= 0 || (suggestedPrice > 0 && cost > suggestedPrice))
                || (tax < 0 || tax >= 100)
                || (proposedPrice <= 0);

            if (!isInconsistent) {
                const itemNetRevenue = qty * proposedPrice * (1 - (tax / 100));
                const itemTotalCost = qty * cost;
                totalNetRevenue += itemNetRevenue;
                totalCost += itemTotalCost;
                validItemsForMargin++;

                const itemMargin = itemNetRevenue > 0 ? ((itemNetRevenue - itemTotalCost) / itemNetRevenue) * 100 : -100;
                if (itemMargin < -0.001) {
                    const prodDesc = item.produto ? item.produto.descricao : (item.descricao_original || 'Item');
                    const loss = Math.max(0, itemTotalCost - itemNetRevenue);
                    estimatedLoss += loss;
                    negativeItems.push({
                        id: item.id,
                        descricao: prodDesc,
                        qtd: qty,
                        custo: cost,
                        proposto: proposedPrice,
                        margem: itemMargin,
                        prejuizo: loss
                    });
                }
            } else if (parseFloat(item.margem_calculada || 0) < -0.001) {
                const prodDesc = item.produto ? item.produto.descricao : (item.descricao_original || 'Item');
                negativeItems.push({
                    id: item.id,
                    descricao: prodDesc,
                    qtd: qty,
                    custo: cost,
                    proposto: proposedPrice,
                    margem: parseFloat(item.margem_calculada || 0),
                    prejuizo: 0
                });
            }
        });

        const overallDiscount = totalSuggestedRevenue - totalProposedRevenue;

        // Calculate overall margin percentage
        let overallMargin = 0;
        const marginEl = document.getElementById("summary-overall-margin");
        const lowMarginBadge = document.getElementById("header-low-margin");
        const negBanner = document.getElementById("negative-margin-alert-banner");
        const negBannerTitle = document.getElementById("negative-margin-alert-title");
        const negBannerBody = document.getElementById("negative-margin-alert-body");

        if (validItemsForMargin > 0 && totalNetRevenue > 0) {
            overallMargin = ((totalNetRevenue - totalCost) / totalNetRevenue) * 100;
            marginEl.innerText = overallMargin.toFixed(2).replace('.', ',') + "%";

            // Color code margin
            if (overallMargin >= 25) {
                marginEl.style.color = "#10b981"; // green
            } else if (overallMargin >= 15) {
                marginEl.style.color = "#d97706"; // yellow
            } else {
                marginEl.style.color = "#ef4444"; // red
            }

            if (lowMarginBadge) {
                if (overallMargin < 0) {
                    lowMarginBadge.style.display = "inline-block";
                    lowMarginBadge.style.backgroundColor = "#fef2f2";
                    lowMarginBadge.style.color = "#b91c1c";
                    lowMarginBadge.style.borderColor = "#ef4444";
                    lowMarginBadge.innerText = `🚨 MARGEM NEGATIVA (${overallMargin.toFixed(1).replace('.', ',')}%)`;
                } else if (overallMargin <= 15) {
                    lowMarginBadge.style.display = "inline-block";
                    lowMarginBadge.style.backgroundColor = "rgba(245,158,11,0.1)";
                    lowMarginBadge.style.color = "#d97706";
                    lowMarginBadge.style.borderColor = "rgba(245,158,11,0.3)";
                    lowMarginBadge.innerText = "⚠️ MARGEM BAIXA";
                } else {
                    lowMarginBadge.style.display = "none";
                }
            }
        } else {
            marginEl.innerText = "N/D";
            marginEl.style.color = "#64748b";
            if (lowMarginBadge) lowMarginBadge.style.display = "none";
        }

        const hasNegative = (validItemsForMargin > 0 && overallMargin < -0.001) || negativeItems.length > 0;
        currentNegativeMarginState = {
            hasNegativeMargin: hasNegative,
            overallMargin: overallMargin,
            negativeItems: negativeItems,
            estimatedLoss: Math.max(estimatedLoss, totalCost > totalNetRevenue ? (totalCost - totalNetRevenue) : 0)
        };

        if (negBanner) {
            if (hasNegative) {
                negBanner.style.display = "block";
                const formattedMargin = overallMargin.toFixed(2).replace('.', ',');
                if (negBannerTitle) {
                    negBannerTitle.innerText = `🚨 ALERTA CRÍTICO: VENDA ABAIXO DO CUSTO (MARGEM GERAL: ${formattedMargin}%)`;
                }
                if (negBannerBody) {
                    const lossStr = currentNegativeMarginState.estimatedLoss > 0
                        ? ` · Prejuízo líquido estimado: <strong>R$ ${currentNegativeMarginState.estimatedLoss.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</strong>`
                        : '';
                    negBannerBody.innerHTML = `Há <strong>${negativeItems.length} item(ns)</strong> marcados para aprovação com preço líquido abaixo do custo${lossStr}. Caso deseje aprovar mesmo assim, será exigida confirmação explícita de responsabilidade.`;
                }
            } else {
                negBanner.style.display = "none";
            }
        }

        // Update elements in DOM
        document.getElementById("summary-subtotal").innerText = "R$ " + totalSuggestedRevenue.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById("summary-discount").innerText = "R$ " + (overallDiscount > 0 ? overallDiscount : 0).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById("summary-total").innerText = "R$ " + totalProposedRevenue.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
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
        calculateOverallTotals();
    }

    function toggleAllChecks(master) {
        const checks = document.querySelectorAll(".item-checkbox");
        checks.forEach(c => c.checked = master.checked);
    }

    // Bind items action
    async function applyBind() {
        const groupName = document.getElementById("bind-group-name").value;
        if (!groupName) {
            showToast("Insira um nome/código para o grupo de vínculo.", "warning");
            return;
        }

        const checkedBoxes = document.querySelectorAll(".item-checkbox:checked");
        const itemIds = Array.from(checkedBoxes).map(c => parseInt(c.value));

        if (itemIds.length < 2) {
            showToast("Selecione pelo menos 2 produtos para vincular.", "warning");
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
                showToast("Itens vinculados comercialmente com sucesso!", "success");
                closeBindDrawer();
                loadQuoteDetails();
            } else {
                showToast("Erro ao vincular itens: " + (data.message || data.error), "error");
            }
        } catch(e) {
            showToast("Erro de conexão ao vincular itens.", "error");
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
            showToast("Preço inválido.", "warning");
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
                showToast("Erro na simulação: " + (data.message || data.error), "error");
            }
        } catch(e) {
            showToast("Erro de conexão ao simular preço.", "error");
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
            showToast("Justificativa obrigatória.", "warning");
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
                    const details = errJson.messages ? Object.entries(errJson.messages).map(([key, val]) => `${val.join(', ')}`).join('. ') : null;
                    showToast("Erro de Validação: " + (details || errJson.message || errJson.error), "error");
                } catch(e) {
                    showToast(`Erro ${res.status}: ` + errText.substring(0, 100), "error");
                }
                return;
            }

            const data = await res.json();
            if (data.success) {
                showToast("Cotação devolvida com sucesso!", "success");
                setTimeout(() => {
                    window.location.href = "{{ url('/aprovacoes') }}";
                }, 800);
            } else {
                showToast("Erro: " + (data.message || data.error), "error");
            }
        } catch (e) {
            console.error(e);
            showToast("Erro de conexão ao devolver cotação.", "error");
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
            showToast("Justificativa obrigatória.", "warning");
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
                    const details = errJson.messages ? Object.entries(errJson.messages).map(([key, val]) => `${val.join(', ')}`).join('. ') : null;
                    showToast("Erro de Validação: " + (details || errJson.message || errJson.error), "error");
                } catch(e) {
                    showToast(`Erro ${res.status}: ` + errText.substring(0, 100), "error");
                }
                return;
            }

            const data = await res.json();
            if (data.success) {
                showToast("Cotação escalada com sucesso!", "success");
                setTimeout(() => {
                    window.location.href = "{{ url('/aprovacoes') }}";
                }, 800);
            } else {
                showToast("Erro: " + (data.message || data.error), "error");
            }
        } catch (e) {
            console.error(e);
            showToast("Erro de conexão ao escalar cotação.", "error");
        }
    }

    function openNegativeMarginModal() {
        const summaryEl = document.getElementById("negative-margin-modal-summary");
        const chk = document.getElementById("chk-confirm-negative-margin");
        const obs = document.getElementById("negative-margin-obs");
        if (chk) chk.checked = false;
        if (obs) obs.value = "";
        toggleNegativeMarginConfirmBtn(false);

        if (summaryEl) {
            const formattedOverall = currentNegativeMarginState.overallMargin.toFixed(2).replace('.', ',');
            const lossHtml = currentNegativeMarginState.estimatedLoss > 0
                ? `<div style="margin-top:4px;"><strong>Prejuízo líquido estimado:</strong> R$ ${currentNegativeMarginState.estimatedLoss.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>`
                : '';
            const itemsListHtml = currentNegativeMarginState.negativeItems.map(it => `
                <li style="margin-bottom:4px;">
                    <strong>${escapeHtml(it.descricao)}</strong> (${it.qtd} un) — Proposto: R$ ${it.proposto.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})} | Custo: R$ ${it.custo.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})} | Margem: <strong>${it.margem.toFixed(2).replace('.', ',')}%</strong>
                </li>
            `).join('');

            summaryEl.innerHTML = `
                <div><strong>Margem Geral da Cotação:</strong> <span style="font-size:14px; font-weight:800; color:#dc2626;">${formattedOverall}%</span></div>
                ${lossHtml}
                ${itemsListHtml ? `<ul style="margin: 8px 0 0 18px; padding: 0;">${itemsListHtml}</ul>` : ''}
            `;
        }

        document.getElementById("negative-margin-modal").style.display = "flex";
    }

    function closeNegativeMarginModal() {
        document.getElementById("negative-margin-modal").style.display = "none";
    }

    function toggleNegativeMarginConfirmBtn(checked) {
        const btn = document.getElementById("btn-confirm-negative-margin");
        if (!btn) return;
        btn.disabled = !checked;
        btn.style.opacity = checked ? "1" : "0.5";
        btn.style.cursor = checked ? "pointer" : "not-allowed";
    }

    function confirmNegativeMarginApproval() {
        const chk = document.getElementById("chk-confirm-negative-margin");
        if (!chk || !chk.checked) {
            showToast("Marque a caixa de ciência para confirmar a aprovação com margem negativa.", "warning");
            return;
        }
        const obs = document.getElementById("negative-margin-obs") ? document.getElementById("negative-margin-obs").value.trim() : "";
        closeNegativeMarginModal();
        executeSubmitDecision(true, obs);
    }

    // Submit Final Item decisions
    async function submitDecision() {
        calculateOverallTotals();
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
            showToast("Por favor, preencha o motivo da recusa em todos os itens rejeitados.", "warning");
            return;
        }

        if (currentNegativeMarginState.hasNegativeMargin) {
            openNegativeMarginModal();
            return;
        }

        await executeSubmitDecision(false, "");
    }

    async function executeSubmitDecision(confirmNegativeMargin = false, negativeMarginObs = "") {
        const itemsPayload = [];
        quote.itens.forEach(item => {
            const row = document.getElementById(`item-row-${item.id}`);
            if (row) {
                const select = row.querySelector(".item-action-select");
                const reason = row.querySelector(".item-rej-reason").value;
                itemsPayload.push({
                    id: item.id,
                    status: select.value,
                    justificativa: select.value === "recusado" ? reason : ""
                });
            }
        });

        try {
            const res = await fetch(`${API_URL}/aprovacoes/${QUOTE_ID}/decisao`, {
                method: "POST",
                headers: { 
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    acao: 'decidir_itens',
                    itens: itemsPayload,
                    confirmar_margem_negativa: confirmNegativeMargin,
                    observacao_margem_negativa: negativeMarginObs
                })
            });

            if (!res.ok) {
                const errText = await res.text();
                try {
                    const errJson = JSON.parse(errText);
                    if (errJson.code === 'NEGATIVE_MARGIN_CONFIRMATION_REQUIRED') {
                        openNegativeMarginModal();
                        return;
                    }
                    const details = errJson.messages ? Object.entries(errJson.messages).map(([key, val]) => `${val.join(', ')}`).join('. ') : null;
                    showToast("Erro de Validação: " + (details || errJson.message || errJson.error), "error");
                } catch(e) {
                    showToast(`Erro ${res.status}: ` + errText.substring(0, 100), "error");
                }
                return;
            }

            const data = await res.json();
            if (data.success) {
                showToast("Decisões aplicadas com sucesso!", "success");
                setTimeout(() => {
                    window.location.href = "{{ url('/aprovacoes') }}";
                }, 800);
            } else {
                showToast("Erro: " + (data.message || data.error), "error");
            }
        } catch (e) {
            console.error(e);
            showToast("Erro de conexão ao enviar decisão.", "error");
        }
    }
</script>
@endsection
