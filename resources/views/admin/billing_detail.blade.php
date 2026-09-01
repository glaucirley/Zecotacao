@extends('layouts.app')

@section('page_title')
Conferência de Faturamento #<span id="header-quote-number">...</span>
@endsection

@section('content')
<div id="loading-spinner" style="text-align: center; padding: 100px 20px;">
    <div style="border: 4px solid rgba(15,81,50,0.1); border-top: 4px solid var(--color-primary); border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 0 auto 15px auto;"></div>
    <span style="color: var(--color-text-muted); font-weight: 500;">Carregando dados da cotação...</span>
</div>

<div id="billing-cockpit" style="display: none;">
    <!-- Client/Rep Header Card -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 15px;">
        <div>
            <h2 style="font-size: 22px;" id="client-title">Cliente: ...</h2>
            <p style="color: var(--color-text-muted); font-size: 13px;" id="rep-subtitle">Vendedor: ...</p>
        </div>
        <div>
            <span id="quote-status-badge" class="badge-status">...</span>
        </div>
    </div>

    <!-- Comparison Warning Banner -->
    <div id="comparison-banner" style="display: none; padding: 16px; border-radius: var(--radius-md); margin-bottom: 24px; font-weight: 500;">
        <!-- Dynamic content -->
    </div>

    <!-- Action Cockpit Buttons -->
    <div style="display: flex; gap: 12px; margin-bottom: 24px; flex-wrap: wrap;">
        <!-- Button 1: Registrar Pedido -->
        <button class="btn btn-secondary" onclick="openOrderModal()" style="display: flex; align-items: center; gap: 8px; font-size: 13px; padding: 10px 16px; background-color: white; border: 1px solid var(--color-border); color: var(--color-text);">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
            Registrar Pedido Externo
        </button>

        <!-- Button 2: Verificação / Conferência -->
        <button class="btn btn-secondary" onclick="openConferenceModal()" style="display: flex; align-items: center; gap: 8px; font-size: 13px; padding: 10px 16px; background-color: white; border: 1px solid var(--color-border); color: var(--color-text);">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            Conferência / Status (<span id="btn-status-label" style="font-weight: 700; text-transform: uppercase; color: var(--color-primary);">PENDENTE</span>)
        </button>

        <!-- Button 3: Auditoria de Cotações (Fullscreen redirect) -->
        <button class="btn btn-secondary" onclick="openAuditPage()" style="display: flex; align-items: center; gap: 8px; font-size: 13px; padding: 10px 16px; background-color: white; border: 1px solid var(--color-border); color: var(--color-text);">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            Auditoria da Cotação
        </button>
    </div>

    <!-- Approved Items Card -->
    <div class="card" style="margin-bottom: 24px;">
        <div class="card-header">
            <h3>Produtos Aprovados para Faturamento</h3>
            <span style="font-size: 13px; color: var(--color-text-muted);" id="totals-compare-label">Cotação: R$ 0,00</span>
        </div>

        <div class="table-responsive">
            <table class="table-premium">
                <thead>
                    <tr>
                        <th style="width: 10%;">Código</th>
                        <th style="width: 50%;">Descrição</th>
                        <th style="width: 10%; text-align: center;">Unidade</th>
                        <th style="width: 10%; text-align: center;">Qtd</th>
                        <th style="width: 20%; text-align: right;">Preço Líquido Aprovado</th>
                    </tr>
                </thead>
                <tbody id="approved-items-body">
                    <!-- Dynamic Rows -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- Release Panel -->
    <div class="card" style="display: flex; justify-content: space-between; align-items: center; background-color: var(--color-card); box-shadow: var(--shadow-lg);">
        <div>
            <button class="btn btn-outline" onclick="window.location.href='{{ url('/faturamento') }}'">Voltar à Fila</button>
        </div>
        <div>
            <button id="btn-bill" class="btn btn-primary" onclick="releaseBilling()" style="background-color: var(--status-faturada); padding: 12px 30px;">
                Confirmar Faturamento (Marcar FATURADA)
            </button>
        </div>
    </div>
</div>

<!-- Conflict Sheet Drawer -->
<div id="conflict-overlay" class="sheet-overlay" onclick="closeConflictModal()"></div>
<div id="conflict-modal" class="sheet-drawer">
    <div class="sheet-header">
        <h3 style="margin: 0; color: var(--status-perdida);">Apontar Conflito Comercial</h3>
        <button type="button" class="sheet-close-btn" onclick="closeConflictModal()">&times;</button>
    </div>
    <div class="sheet-body">
        <p style="font-size: 13px; color: var(--color-text-muted); margin-bottom: 16px;">
            Descreva detalhadamente a divergência encontrada (ex: preços no Sankhya divergindo da cotação aprovada, impostos adicionais, etc). O registro será gravado de forma imutável na trilha de auditoria.
        </p>
        <div class="form-group" style="margin-bottom: 20px;">
            <label for="conflict-motive" class="form-label">Motivo do Conflito / Divergência</label>
            <textarea id="conflict-motive" class="form-control" rows="4" placeholder="Ex: Produto X faturado no Sankhya com valor R$ 10,00 acima do aprovado na cotação..." required></textarea>
        </div>
        <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px;">
            <button class="btn btn-outline" onclick="closeConflictModal()">Cancelar</button>
            <button class="btn btn-danger" onclick="confirmConflict()">Apontar Conflito</button>
        </div>
    </div>
</div>

<!-- Order Sheet Drawer -->
<div id="order-overlay" class="sheet-overlay" onclick="closeOrderModal()"></div>
<div id="order-modal" class="sheet-drawer">
    <div class="sheet-header">
        <h3 style="margin: 0; color: var(--color-primary);">Registrar Pedido Externo (Sankhya)</h3>
        <button type="button" class="sheet-close-btn" onclick="closeOrderModal()">&times;</button>
    </div>
    <div class="sheet-body">
        <p style="font-size: 13px; color: var(--color-text-muted); margin-bottom: 20px;">
            Insira o número de pedido e o valor total faturado no ERP Sankhya correspondente a esta cotação.
        </p>
        
        <div class="form-group" style="margin-bottom: 15px;">
            <label for="numero-pedido-externo" class="form-label">Número do Pedido no Sankhya</label>
            <input type="text" id="numero-pedido-externo" class="form-control" placeholder="Ex: 509230">
        </div>
        <div class="form-group" style="margin-bottom: 20px;">
            <label for="valor-pedido" class="form-label">Valor Total do Pedido (R$)</label>
            <input type="number" id="valor-pedido" class="form-control" step="0.01" placeholder="Ex: 1500.00" oninput="checkValueMatch()">
        </div>
        
        <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px;">
            <button type="button" class="btn btn-outline" onclick="closeOrderModal()">Cancelar</button>
            <button type="button" class="btn btn-primary" onclick="saveExternalOrderAndClose()">Salvar Pedido</button>
        </div>
    </div>
</div>

<!-- Conference Sheet Drawer -->
<div id="conference-overlay" class="sheet-overlay" onclick="closeConferenceModal()"></div>
<div id="conference-modal" class="sheet-drawer">
    <div class="sheet-header">
        <h3 style="margin: 0; color: var(--color-primary);">Verificação e Conferência</h3>
        <button type="button" class="sheet-close-btn" onclick="closeConferenceModal()">&times;</button>
    </div>
    <div class="sheet-body">
        <p style="font-size: 13px; color: var(--color-text-muted); margin-bottom: 20px;">
            Altere o status de conferência dos valores faturados no ERP em relação à cotação aprovada.
        </p>
        
        <p style="font-size: 14px; margin-bottom: 15px;">
            Status Atual: <strong id="conference-status" style="text-transform: uppercase; color: var(--color-primary);">PENDENTE</strong>
        </p>
        
        <div class="form-group" style="margin-bottom: 20px;">
            <label class="form-label" style="font-size: 12px;">Mudar Conferência</label>
            <select id="conference-status-select" class="form-control" onchange="changeConferenceStatus(this.value)">
                <option value="pendente">Pendente</option>
                <option value="conforme">Conforme</option>
                <option value="divergente">Divergente (Apontar Conflito)</option>
            </select>
        </div>
        
        <div style="display: flex; flex-direction: column; gap: 12px; margin-top: 24px;">
            <button class="btn btn-danger" onclick="openConflictModalFromConf()" style="width: 100%; font-size: 13px; padding: 10px;">
                Apontar Conflito Comercial (Divergente)
            </button>
            <div style="display: flex; justify-content: flex-end; margin-top: 10px;">
                <button type="button" class="btn btn-outline" onclick="closeConferenceModal()">Fechar</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const QUOTE_ID = "{{ $id }}";
    const API_URL = "{{ url('/api/v1') }}";
    let quote = null;

    document.addEventListener("DOMContentLoaded", () => {
        loadDetails();
    });

    function openAuditPage() {
        window.location.href = `{{ url('/cotacoes') }}/${QUOTE_ID}/auditoria`;
    }

    function openOrderModal() {
        document.getElementById("order-overlay").classList.add("open");
        document.getElementById("order-modal").classList.add("open");
        checkValueMatch();
    }

    function closeOrderModal() {
        document.getElementById("order-overlay").classList.remove("open");
        document.getElementById("order-modal").classList.remove("open");
    }

    function openConferenceModal() {
        document.getElementById("conference-overlay").classList.add("open");
        document.getElementById("conference-modal").classList.add("open");
        if (quote && quote.pedido_externo) {
            document.getElementById("conference-status").innerText = quote.pedido_externo.status_conferencia;
            document.getElementById("conference-status-select").value = quote.pedido_externo.status_conferencia;
        }
    }

    function closeConferenceModal() {
        document.getElementById("conference-overlay").classList.remove("open");
        document.getElementById("conference-modal").classList.remove("open");
    }

    function openConflictModalFromConf() {
        closeConferenceModal();
        openConflictModal();
    }

    async function saveExternalOrderAndClose() {
        await saveExternalOrder();
        closeOrderModal();
    }

    async function loadDetails() {
        try {
            const res = await fetch(`${API_URL}/faturamento/${QUOTE_ID}`);
            if (res.status === 401 || res.status === 403) {
                window.location.href = "{{ url('/login') }}";
                return;
            }
            const data = await res.json();
            if (data.success) {
                quote = data.data;
                renderDetails();
            } else {
                alert("Erro ao carregar detalhes: " + data.error);
            }
        } catch (e) {
            console.error(e);
            alert("Erro ao conectar com o servidor.");
        }
    }

    function renderDetails() {
        if (!quote) return;

        document.getElementById("loading-spinner").style.display = "none";
        document.getElementById("billing-cockpit").style.display = "block";

        // Bind headers
        document.getElementById("header-quote-number").innerText = quote.numero;
        document.getElementById("client-title").innerText = "Cliente: " + quote.parceiro.razao_social;
        document.getElementById("rep-subtitle").innerText = `Vendedor: ${quote.representante.nome} | Tipo Frete: ${quote.frete_tipo}`;

        // Bind status badge
        const badge = document.getElementById("quote-status-badge");
        badge.className = "badge-status " + quote.status.toLowerCase().replace(/_/g, '-');
        badge.innerText = quote.status === 'PDF_GERADO' ? 'Liberada (Pendente PDF)' : quote.status.replace(/_/g, ' ');

        document.getElementById("totals-compare-label").innerText = `Valor Cotação Aprovada: R$ ${parseFloat(quote.total).toLocaleString('pt-BR', {minimumFractionDigits: 2})}`;

        // Disable faturar button if already FATURADA
        if (quote.status === 'FATURADA') {
            document.getElementById("btn-bill").disabled = true;
            document.getElementById("numero-pedido-externo").disabled = true;
            document.getElementById("valor-pedido").disabled = true;
            document.getElementById("conference-status-select").disabled = true;
        }

        // Bind External Order fields if present
        if (quote.pedido_externo) {
            document.getElementById("numero-pedido-externo").value = quote.pedido_externo.numero_pedido_externo;
            document.getElementById("valor-pedido").value = parseFloat(quote.pedido_externo.valor_pedido);
            
            const btnLabel = document.getElementById("btn-status-label");
            if (btnLabel) {
                btnLabel.innerText = quote.pedido_externo.status_conferencia;
            }
            
            const confStatus = document.getElementById("conference-status");
            if (confStatus) {
                confStatus.innerText = quote.pedido_externo.status_conferencia;
            }
            const confSelect = document.getElementById("conference-status-select");
            if (confSelect) {
                confSelect.value = quote.pedido_externo.status_conferencia;
            }
        }

        // Render Approved items table
        renderApprovedItems();

        // Run match checks
        checkValueMatch();
    }



    function renderApprovedItems() {
        const body = document.getElementById("approved-items-body");
        body.innerHTML = "";

        // Only display items marked as aprovado
        const approvedList = quote.itens.filter(i => i.status_item === 'aprovado');

        if (approvedList.length === 0) {
            body.innerHTML = '<tr><td colspan="5" class="text-center">Nenhum item aprovado nesta cotação.</td></tr>';
            return;
        }

        approvedList.forEach(item => {
            body.innerHTML += `
                <tr>
                    <td><strong>${item.produto.codigo_sankhya}</strong></td>
                    <td>${item.produto.descricao} ${item.mostrar_selo_campanha && item.campanha_id ? '<span class="badge-campanha">Campanha</span>' : ''}</td>
                    <td class="text-center">${item.produto.unidade}</td>
                    <td class="text-center">${item.qtd}</td>
                    <td class="text-right">R$ ${parseFloat(item.preco_unit_proposto).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</td>
                </tr>
            `;
        });
    }

    function checkValueMatch() {
        const valInput = document.getElementById("valor-pedido").value;
        const banner = document.getElementById("comparison-banner");

        if (!valInput) {
            banner.style.display = "none";
            return;
        }

        const quoteVal = parseFloat(quote.total);
        const orderVal = parseFloat(valInput);

        banner.style.display = "block";

        if (Math.abs(quoteVal - orderVal) < 0.01) {
            banner.style.backgroundColor = "#d1fae5";
            banner.style.color = "#065f46";
            banner.style.border = "1px solid #a7f3d0";
            banner.innerText = "✓ Confirmação: Os valores batem perfeitamente com a cotação aprovada.";
        } else {
            banner.style.backgroundColor = "#fee2e2";
            banner.style.color = "#991b1b";
            banner.style.border = "1px solid #fca5a5";
            banner.innerText = `⚠ Divergência Detectada! Valor Cotação: R$ ${quoteVal.toLocaleString('pt-BR', {minimumFractionDigits:2})} vs. Valor Pedido Externo: R$ ${orderVal.toLocaleString('pt-BR', {minimumFractionDigits:2})}`;
        }
    }

    async function saveExternalOrder() {
        const num = document.getElementById("numero-pedido-externo").value;
        const val = parseFloat(document.getElementById("valor-pedido").value);

        if (!num || !val || val <= 0) {
            alert("Número de pedido e valor são obrigatórios.");
            return;
        }

        try {
            const res = await fetch(`${API_URL}/faturamento/${QUOTE_ID}/pedido`, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ numero_pedido_externo: num, valor_pedido: val })
            });

            const data = await res.json();
            if (data.success) {
                alert("Pedido externo registrado!");
                loadDetails();
            } else {
                alert("Erro ao registrar pedido: " + data.message);
            }
        } catch (e) {
            alert("Erro de conexão.");
        }
    }

    async function changeConferenceStatus(status) {
        if (status === "divergente") {
            openConflictModal();
            return;
        }

        try {
            const res = await fetch(`${API_URL}/faturamento/${QUOTE_ID}/conferencia`, {
                method: "PATCH",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ status_conferencia: status })
            });

            const data = await res.json();
            if (data.success) {
                alert("Conferência atualizada com sucesso.");
                loadDetails();
            } else {
                alert("Erro: " + data.message);
            }
        } catch (e) {
            alert("Erro de conexão.");
        }
    }

    // Conflict Dialog Flow
    function openConflictModal() {
        document.getElementById("conflict-overlay").classList.add("open");
        document.getElementById("conflict-modal").classList.add("open");
    }

    function closeConflictModal() {
        document.getElementById("conflict-overlay").classList.remove("open");
        document.getElementById("conflict-modal").classList.remove("open");
        document.getElementById("conflict-motive").value = "";
    }

    async function confirmConflict() {
        const motivo = document.getElementById("conflict-motive").value;
        if (!motivo || motivo.length < 5) {
            alert("Forneça um motivo descritivo.");
            return;
        }

        try {
            const res = await fetch(`${API_URL}/faturamento/${QUOTE_ID}/divergencia`, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ motivo: motivo })
            });

            const data = await res.json();
            if (data.success) {
                alert("Conflito apontado com sucesso.");
                closeConflictModal();
                loadDetails();
            } else {
                alert("Erro: " + data.message);
            }
        } catch (e) {
            alert("Erro de conexão.");
        }
    }

    // Bill Release Action
    async function releaseBilling() {
        if (!confirm("Confirmar faturamento comercial definitivo? A cotação será encerrada.")) return;

        try {
            const res = await fetch(`${API_URL}/faturamento/${QUOTE_ID}/faturar`, {
                method: "POST"
            });
            const data = await res.json();
            if (data.success) {
                alert("Cotação faturada com sucesso! Processo encerrado.");
                window.location.href = "{{ url('/faturamento') }}";
            } else {
                alert("Erro: " + data.message);
            }
        } catch (e) {
            alert("Erro de conexão.");
        }
    }
</script>
@endsection
