@extends('layouts.app')

@section('page_title', 'Painel Geral de Cotações')

@section('content')
<div class="card" style="margin-bottom: 20px;">
    <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 15px; padding-bottom: 15px; border-bottom: 1px solid rgba(0,0,0,0.05);">
        <div style="display: flex; gap: 15px; align-items: center;">
            <h3 style="margin: 0; color: var(--color-primary);">Histórico Geral de Operações</h3>
            <button class="btn btn-primary" onclick="openCreateModal()" style="font-size: 13px; padding: 6px 14px; font-weight: 600;">
                + Nova Cotação
            </button>
        </div>
        
        <!-- Filters -->
        <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
            <input type="text" id="search-input" class="form-control" placeholder="Buscar por Nº ou Cliente..." style="max-width: 250px; font-size: 13px; padding: 8px 12px;" oninput="filterQuotes()">
            
            <select id="status-filter" class="form-control" style="max-width: 220px; font-size: 13px; padding: 8px 12px;" onchange="filterQuotes()">
                <option value="">Todos os Status</option>
                <option value="EM_CRIACAO">Rascunho (Vendedor)</option>
                <option value="AGUARDANDO_GESTOR">Pendente (Gestor)</option>
                <option value="COM_DIRETOR">Pendente (Diretor)</option>
                <option value="PDF_GERADO">Liberada (Pendente PDF)</option>
                <option value="AGUARDANDO_PEDIDO">Enviado (Em Faturamento)</option>
                <option value="FINALIZADA_COM_PEDIDO">Pedido Casado (Pendente Fat.)</option>
                <option value="FATURADA">Faturada (Concluída)</option>
                <option value="PERDIDA">Perdida</option>
            </select>
        </div>
    </div>

    <!-- Table Quotes -->
    <div class="table-responsive" style="margin-top: 15px;">
        <table class="table-premium">
            <thead>
                <tr>
                    <th style="width: 12%;">Cotação Nº</th>
                    <th style="width: 26%;">Cliente / Parceiro</th>
                    <th style="width: 18%;">Vendedor / Equipe</th>
                    <th style="width: 12%;">Emissão</th>
                    <th style="width: 13%; text-align: right;">Total Proposto</th>
                    <th style="width: 11%; text-align: center;">Status</th>
                    <th style="width: 8%; text-align: center;">Ações</th>
                </tr>
            </thead>
            <tbody id="quotes-table-body">
                <!-- Dynamic rows -->
            </tbody>
        </table>
    </div>

    <!-- Loading spinner -->
    <div id="loading-spinner" style="text-align: center; padding: 40px 20px;">
        <div style="border: 3px solid rgba(15,81,50,0.1); border-top: 3px solid var(--color-primary); border-radius: 50%; width: 30px; height: 30px; animation: spin 1s linear infinite; margin: 0 auto;"></div>
    </div>

    <!-- Empty state -->
    <div id="empty-state" style="display: none; text-align: center; padding: 40px 20px; color: var(--color-text-muted);">
        Nenhuma cotação localizada com os filtros aplicados.
    </div>
</div>

<!-- Read-only Detail Sheet Drawer -->
<div id="detail-overlay" class="sheet-overlay" onclick="closeDetailModal()"></div>
<div id="detail-modal" class="sheet-drawer" style="width: 600px;">
    <div class="sheet-header">
        <h3 style="margin: 0; color: var(--color-primary);">Detalhes da Cotação <span id="modal-quote-num">...</span></h3>
        <button type="button" class="sheet-close-btn" onclick="closeDetailModal()">&times;</button>
    </div>
    <div class="sheet-body">

        <div class="grid-2" style="gap: 20px; margin-bottom: 20px; font-size: 13px;">
            <div>
                <p><strong>Cliente:</strong> <span id="modal-client">...</span></p>
                <p><strong>CNPJ:</strong> <span id="modal-cnpj">...</span></p>
                <p><strong>Vendedor:</strong> <span id="modal-rep">...</span></p>
                <p><strong>Emissão:</strong> <span id="modal-date">...</span></p>
            </div>
            <div>
                <p><strong>Forma Pagamento:</strong> <span id="modal-payment">...</span></p>
                <p><strong>Prazo de Entrega:</strong> <span id="modal-delivery">...</span></p>
                <p><strong>Frete:</strong> <span id="modal-freight">...</span></p>
                <p><strong>Status Atual:</strong> <span id="modal-status" class="badge-status">...</span><span id="modal-priority" class="badge-status priority" style="display:none; background-color:rgba(239,68,68,0.1); color:#ef4444; border:1px solid rgba(239,68,68,0.2); font-size:11px; font-weight:700; margin-left:8px; padding: 2px 6px; border-radius: 4px;">⚡ GRANDE CONTA</span></p>
            </div>
        </div>

        <h4 style="margin-top: 20px; margin-bottom: 10px; color: var(--color-primary);">Itens da Cotação</h4>
        <table class="table-premium" style="font-size: 12px; margin-bottom: 20px;">
            <thead>
                <tr>
                    <th>Produto</th>
                    <th style="text-align: center;">Qtd</th>
                    <th style="text-align: right;">Sugerido</th>
                    <th style="text-align: right;">Proposto</th>
                    <th style="text-align: center;">Ajuste</th>
                    <th style="text-align: center;">Status Item</th>
                </tr>
            </thead>
            <tbody id="modal-items-body">
                <!-- Dynamic items -->
            </tbody>
        </table>

        <div style="display: flex; justify-content: flex-end; gap: 20px; font-size: 14px; margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
            <div>Subtotal Sugerido: <strong id="modal-subtotal">R$ 0,00</strong></div>
            <div>Desconto Total: <strong id="modal-discount" style="color: var(--status-recusada);">R$ 0,00</strong></div>
            <div>Valor Final: <strong id="modal-total" style="color: var(--color-primary);">R$ 0,00</strong></div>
        </div>

        <h4 style="margin-top: 20px; margin-bottom: 10px; color: var(--color-primary);">Histórico de Auditoria / Workflow</h4>
        <div id="modal-history-list" style="font-size: 12px; display: flex; flex-direction: column; gap: 8px; max-height: 150px; overflow-y: auto; background: #f8f9fa; padding: 12px; border-radius: 8px;">
            <!-- History -->
        </div>

        <div style="display: flex; justify-content: flex-end; margin-top: 24px;">
            <button class="btn btn-outline" onclick="closeDetailModal()">Fechar Visualização</button>
        </div>
    </div>
</div>

<!-- Create Manual Quote Sheet Drawer -->
<div id="create-overlay" class="sheet-overlay" onclick="closeCreateModal()"></div>
<div id="create-modal" class="sheet-drawer" style="width: 550px;">
    <div class="sheet-header">
        <h3 style="margin: 0; color: var(--color-primary);">Incluir Nova Cotação Manual</h3>
        <button type="button" class="sheet-close-btn" onclick="closeCreateModal()">&times;</button>
    </div>
    <div class="sheet-body">

        <form id="create-quote-form" onsubmit="submitManualQuote(event)">
            <div class="grid-2" style="gap: 12px; margin-bottom: 12px;">
                <div class="form-group" style="margin-bottom:0;">
                    <label for="create-parceiro" class="form-label">Cliente (Parceiro)</label>
                    <select id="create-parceiro" class="form-control" required style="font-size: 13px;">
                        <option value="">Selecione um cliente...</option>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom:0;" id="rep-group-container">
                    <label for="create-representante" class="form-label">Representante Comercial</label>
                    <select id="create-representante" class="form-control" required style="font-size: 13px;">
                        <option value="">Selecione um vendedor...</option>
                    </select>
                </div>
            </div>

            <div class="grid-3" style="gap: 12px; margin-bottom: 12px;">
                <div class="form-group" style="margin-bottom:0;">
                    <label for="create-pagamento" class="form-label">Forma Pagamento</label>
                    <input type="text" id="create-pagamento" class="form-control" placeholder="Ex: 30/60 dias" value="A combinar" style="font-size: 13px;">
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label for="create-prazo" class="form-label">Prazo de Entrega</label>
                    <input type="text" id="create-prazo" class="form-control" placeholder="Ex: 3 dias" value="3 dias" style="font-size: 13px;">
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label for="create-frete" class="form-label">Tipo Frete</label>
                    <select id="create-frete" class="form-control" style="font-size: 13px;">
                        <option value="CIF">CIF</option>
                        <option value="FOB">FOB</option>
                    </select>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 15px;">
                <label for="create-obs-cliente" class="form-label">Observação Cliente</label>
                <textarea id="create-obs-cliente" class="form-control" rows="2" placeholder="Ex: Horário de recebimento das 8h às 17h..." style="font-size: 13px;"></textarea>
            </div>

            <h4 style="margin-top: 20px; margin-bottom: 10px; color: var(--color-primary); display: flex; justify-content: space-between; align-items: center;">
                Produtos da Cotação
                <button type="button" class="btn btn-secondary" style="font-size: 11px; padding: 4px 10px;" onclick="addManualProductRow()">+ Adicionar Item</button>
            </h4>

            <div style="background: #f8f9fa; padding: 12px; border-radius: 8px; margin-bottom: 20px;">
                <div id="manual-items-container" style="display: flex; flex-direction: column; gap: 10px;">
                    <!-- Dynamic product rows here -->
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px;">
                <button type="button" class="btn btn-outline" onclick="closeCreateModal()">Cancelar</button>
                <button type="submit" class="btn btn-primary">Salvar e Gerar Cotação</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const API_URL = "{{ url('/api/v1') }}";
    const CURRENT_USER = @json(auth()->user());
    let rawQuotes = [];
    let metaProducts = [];

    document.addEventListener("DOMContentLoaded", () => {
        loadQuotes();
    });

    async function loadQuotes() {
        try {
            const res = await fetch(`${API_URL}/cotacoes/todas`);
            if (res.status === 401 || res.status === 403) {
                window.location.href = "{{ url('/login') }}";
                return;
            }

            const data = await res.json();
            document.getElementById("loading-spinner").style.display = "none";

            if (data.success) {
                rawQuotes = data.data;
                renderQuotes(rawQuotes);
            } else {
                alert("Erro ao buscar cotações: " + data.error);
            }
        } catch (e) {
            console.error(e);
            alert("Erro ao conectar no servidor.");
        }
    }

    function renderQuotes(list) {
        const body = document.getElementById("quotes-table-body");
        body.innerHTML = "";

        if (list.length === 0) {
            document.getElementById("empty-state").style.display = "block";
            return;
        } else {
            document.getElementById("empty-state").style.display = "none";
        }

        list.forEach(q => {
            const date = new Date(q.created_at).toLocaleDateString('pt-BR');
            const statusClass = q.status.toLowerCase().replace(/_/g, '-');
            const statusText = q.status === 'PDF_GERADO' ? 'Liberada (Pendente PDF)' : q.status.replace(/_/g, ' ');
            const teamName = q.representante.equipe ? q.representante.equipe.nome : 'Sem Equipe';

            // Authorization logic for Editing
            const canEdit = q.status === 'EM_CRIACAO' && (
                CURRENT_USER.papel === 'administrador' || 
                CURRENT_USER.id == q.representante_id ||
                (CURRENT_USER.papel === 'gestor' && q.representante.equipe && q.representante.equipe.gestor_id == CURRENT_USER.id)
            );

            // Authorization logic for Deletion
            const canDelete = CURRENT_USER.papel === 'administrador' || (
                q.status === 'EM_CRIACAO' && (
                    CURRENT_USER.id == q.representante_id ||
                    (CURRENT_USER.papel === 'gestor' && q.representante.equipe && q.representante.equipe.gestor_id == CURRENT_USER.id)
                )
            );

            let actionButtons = "";
            if (canEdit) {
                actionButtons = `
                    <a href="{{ url('/cotacoes/token') }}/${q.token_representante}" class="btn btn-primary" style="padding: 6px 12px; font-size:11px; text-decoration:none; font-weight:600;">
                        Editar
                    </a>
                `;
            } else {
                actionButtons = `
                    <button class="btn btn-secondary" style="padding: 6px 12px; font-size:11px; font-weight: 600;" onclick="handleDetailClick(${q.id}, '${q.status}', '${q.token_representante}')">
                        Analisar
                    </button>
                `;
            }

            if (canDelete) {
                actionButtons += `
                    <button class="btn btn-outline" style="padding: 6px 12px; font-size:11px; font-weight: 600; color:var(--status-recusada); border-color:var(--status-recusada); margin-left:5px;" onclick="deleteQuote(${q.id})">
                        Excluir
                    </button>
                `;
            }

            body.innerHTML += `
                <tr>
                    <td><strong>${q.numero}</strong></td>
                    <td>
                        <strong>${q.parceiro.razao_social}</strong><br>
                        <span style="font-size:11px; color:var(--color-text-muted);">Sankhya Code: ${q.parceiro.codigo_sankhya}</span>
                    </td>
                    <td>
                        <strong>${q.representante.nome}</strong><br>
                        <span style="font-size:11px; color:var(--color-text-muted);">${teamName}</span>
                    </td>
                    <td>${date}</td>
                    <td class="text-right" style="font-weight:600; color:var(--color-primary);">R$ ${parseFloat(q.total).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</td>
                    <td class="text-center" style="white-space: nowrap;">
                        <span class="badge-status ${statusClass}">${statusText}</span>
                        ${q.prioridade ? '<br><span class="badge-status priority" style="background-color:rgba(239,68,68,0.1); color:#ef4444; border:1px solid rgba(239,68,68,0.2); font-size:10px; font-weight:700; margin-top:4px; display:inline-block; border-radius:4px; padding:2px 6px;">⚡ GRANDE CONTA</span>' : ''}
                    </td>
                    <td class="text-center" style="white-space: nowrap;">
                        ${actionButtons}
                    </td>
                </tr>
            `;
        });
    }

    function filterQuotes() {
        const search = document.getElementById("search-input").value.toLowerCase();
        const status = document.getElementById("status-filter").value;

        const filtered = rawQuotes.filter(q => {
            const matchesStatus = status === "" || q.status === status;
            
            const num = q.numero.toLowerCase();
            const client = q.parceiro.razao_social.toLowerCase();
            const rep = q.representante.nome.toLowerCase();
            const matchesSearch = search === "" || num.includes(search) || client.includes(search) || rep.includes(search);

            return matchesStatus && matchesSearch;
        });

        renderQuotes(filtered);
    }

    function handleDetailClick(id, status, token) {
        if (CURRENT_USER.papel === 'representante') {
            window.location.href = `{{ url('/cotacoes/token') }}/${token}`;
            return;
        }

        if (status === 'AGUARDANDO_GESTOR' || status === 'COM_DIRETOR') {
            window.location.href = `{{ url('/aprovacoes') }}/${id}`;
        } else if (status === 'PDF_GERADO' || status === 'AGUARDANDO_PEDIDO' || status === 'FINALIZADA_COM_PEDIDO' || status === 'FATURADA') {
            window.location.href = `{{ url('/faturamento') }}/${id}`;
        } else {
            openDetailModal(id);
        }
    }

    async function openDetailModal(id) {
        try {
            const res = await fetch(`${API_URL}/faturamento/${id}`);
            const data = await res.json();
            if (!data.success) {
                alert("Erro ao carregar detalhes: " + data.message);
                return;
            }

            const q = data.data;
            document.getElementById("modal-quote-num").innerText = q.numero;
            document.getElementById("modal-client").innerText = q.parceiro.razao_social;
            document.getElementById("modal-cnpj").innerText = q.parceiro.cnpj || '-';
            document.getElementById("modal-rep").innerText = q.representante.nome;
            document.getElementById("modal-date").innerText = new Date(q.created_at).toLocaleDateString('pt-BR');
            document.getElementById("modal-payment").innerText = q.forma_pagamento || 'Faturamento Direto';
            document.getElementById("modal-delivery").innerText = q.prazo_entrega ? `${q.prazo_entrega} dias` : '-';
            document.getElementById("modal-freight").innerText = q.frete_tipo || 'CIF';
            
            const statusEl = document.getElementById("modal-status");
            statusEl.className = `badge-status ${q.status.toLowerCase().replace(/_/g, '-')}`;
            statusEl.innerText = q.status === 'PDF_GERADO' ? 'Liberada (Pendente PDF)' : q.status.replace(/_/g, ' ');

            const priorityEl = document.getElementById("modal-priority");
            if (q.prioridade) {
                priorityEl.style.display = 'inline-block';
            } else {
                priorityEl.style.display = 'none';
            }

            // Render items
            const itemsBody = document.getElementById("modal-items-body");
            itemsBody.innerHTML = "";
            q.itens.forEach(it => {
                const adjClass = it.ajuste_percentual < 0 ? 'color: var(--status-recusada);' : 'color: var(--color-primary);';
                const adjSign = it.ajuste_percentual > 0 ? '+' : '';
                itemsBody.innerHTML += `
                    <tr>
                        <td><strong>${it.produto.descricao}</strong></td>
                        <td class="text-center">${it.qtd}</td>
                        <td class="text-right">R$ ${parseFloat(it.preco_unit_sugerido).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</td>
                        <td class="text-right">R$ ${parseFloat(it.preco_unit_proposto).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</td>
                        <td class="text-center" style="font-weight:600; ${adjClass}">${adjSign}${parseFloat(it.ajuste_percentual)}%</td>
                        <td class="text-center"><span style="text-transform:uppercase; font-size:10px; font-weight:700;">${it.status_item}</span></td>
                    </tr>
                `;
            });

            // Totals
            document.getElementById("modal-subtotal").innerText = `R$ ${parseFloat(q.subtotal).toLocaleString('pt-BR', {minimumFractionDigits: 2})}`;
            document.getElementById("modal-discount").innerText = `R$ ${parseFloat(q.desconto).toLocaleString('pt-BR', {minimumFractionDigits: 2})}`;
            document.getElementById("modal-total").innerText = `R$ ${parseFloat(q.total).toLocaleString('pt-BR', {minimumFractionDigits: 2})}`;

            // Audit history
            const histList = document.getElementById("modal-history-list");
            histList.innerHTML = "";
            if (q.historico.length === 0) {
                histList.innerHTML = `<span style="color:var(--color-text-muted);">Nenhum histórico registrado ainda.</span>`;
            } else {
                q.historico.forEach(h => {
                    const hDate = new Date(h.created_at).toLocaleString('pt-BR');
                    histList.innerHTML += `
                        <div style="border-bottom:1px solid rgba(0,0,0,0.02); padding-bottom:4px;">
                            <span style="color:var(--color-primary); font-weight:600;">[${hDate}]</span>
                            <strong>${h.usuario ? h.usuario.nome : 'Integração'} (${h.papel.toUpperCase()})</strong>:
                            ${h.evento.replace(/_/g, ' ')} - <span style="color:#555;">${h.condicao || ''}</span>
                        </div>
                    `;
                });
            }

            document.getElementById("detail-overlay").classList.add("open");
            document.getElementById("detail-modal").classList.add("open");
        } catch (e) {
            console.error(e);
            alert("Erro ao buscar detalhes da cotação.");
        }
    }

    function closeDetailModal() {
        document.getElementById("detail-overlay").classList.remove("open");
        document.getElementById("detail-modal").classList.remove("open");
    }

    // Delete quote action
    async function deleteQuote(id) {
        if (!confirm("Deseja realmente excluir esta cotação permanentemente do sistema?")) return;

        try {
            const res = await fetch(`${API_URL}/cotacoes/${id}`, {
                method: 'DELETE',
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });
            const data = await res.json();
            if (data.success) {
                alert("Cotação excluída com sucesso!");
                loadQuotes();
            } else {
                alert("Erro ao excluir: " + data.message);
            }
        } catch(e) {
            alert("Erro de conexão.");
        }
    }

    // Manual Creation flow modal controls
    async function openCreateModal() {
        document.getElementById("create-quote-form").reset();
        document.getElementById("manual-items-container").innerHTML = "";

        // Show loading state in selectors
        document.getElementById("create-parceiro").innerHTML = '<option value="">Carregando...</option>';
        document.getElementById("create-representante").innerHTML = '<option value="">Carregando...</option>';

        document.getElementById("create-overlay").classList.add("open");
        document.getElementById("create-modal").classList.add("open");

        try {
            const res = await fetch(`${API_URL}/cotacoes/meta`);
            const data = await res.json();
            if (data.success) {
                // Populate Clients dropdown
                const pSelect = document.getElementById("create-parceiro");
                pSelect.innerHTML = '<option value="">Selecione um cliente...</option>';
                data.data.partners.forEach(p => {
                    pSelect.innerHTML += `<option value="${p.id}">${p.razao_social} (${p.cnpj || 'Sem CNPJ'})</option>`;
                });

                // Populate Representatives dropdown
                const rSelect = document.getElementById("create-representante");
                rSelect.innerHTML = "";
                data.data.representatives.forEach(r => {
                    rSelect.innerHTML += `<option value="${r.id}">${r.nome}</option>`;
                });

                // If user is Representative, lock and auto-select
                if (CURRENT_USER.papel === 'representante') {
                    rSelect.value = CURRENT_USER.id;
                    document.getElementById("rep-group-container").style.display = "none";
                } else {
                    document.getElementById("rep-group-container").style.display = "block";
                    rSelect.innerHTML = '<option value="">Selecione um vendedor...</option>' + rSelect.innerHTML;
                }

                // Cache active products
                metaProducts = data.data.products;

                // Add first item row automatically
                addManualProductRow();

            } else {
                alert("Erro ao buscar dados cadastrais.");
                closeCreateModal();
            }
        } catch (e) {
            alert("Erro ao carregar metadados.");
            closeCreateModal();
        }
    }

    function closeCreateModal() {
        document.getElementById("create-overlay").classList.remove("open");
        document.getElementById("create-modal").classList.remove("open");
    }

    function addManualProductRow() {
        const container = document.getElementById("manual-items-container");
        
        let productOptions = '<option value="">Selecione um produto...</option>';
        metaProducts.forEach(p => {
            productOptions += `<option value="${p.id}">${p.descricao}</option>`;
        });

        const row = document.createElement("div");
        row.style.display = "flex";
        row.style.gap = "10px";
        row.style.alignItems = "center";
        row.className = "manual-product-row";
        
        row.innerHTML = `
            <select class="form-control item-product-id" required style="flex: 2; font-size:12px; padding: 6px;">
                ${productOptions}
            </select>
            <input type="number" class="form-control item-product-qtd" required min="1" value="1" style="flex: 0.5; max-width: 80px; font-size:12px; text-align:center; padding: 6px;">
            <input type="number" class="form-control item-product-price" required min="0.01" step="0.01" placeholder="Preço R$" style="flex: 1; max-width: 130px; font-size:12px; padding: 6px;">
            <button type="button" class="btn btn-outline" style="color: var(--status-recusada); border-color: var(--status-recusada); font-size:11px; padding: 6px 10px; font-weight:600;" onclick="this.parentElement.remove()">Remover</button>
        `;
        container.appendChild(row);
    }

    async function submitManualQuote(e) {
        e.preventDefault();

        // Build items array
        const rows = document.querySelectorAll(".manual-product-row");
        const items = [];
        let hasDuplicateProduct = false;
        const productIdsSet = new Set();

        rows.forEach(r => {
            const pId = r.querySelector(".item-product-id").value;
            const qtd = parseInt(r.querySelector(".item-product-qtd").value);
            const price = parseFloat(r.querySelector(".item-product-price").value);

            if (pId) {
                if (productIdsSet.has(pId)) {
                    hasDuplicateProduct = true;
                }
                productIdsSet.add(pId);

                items.push({
                    produto_id: parseInt(pId),
                    qtd: qtd,
                    preco_unit_proposto: price
                });
            }
        });

        if (items.length === 0) {
            alert("Adicione pelo menos um produto na cotação.");
            return;
        }

        if (hasDuplicateProduct) {
            alert("Por favor, não adicione o mesmo produto mais de uma vez. Apenas incremente a quantidade.");
            return;
        }

        const payload = {
            parceiro_id: parseInt(document.getElementById("create-parceiro").value),
            representante_id: parseInt(document.getElementById("create-representante").value),
            forma_pagamento: document.getElementById("create-pagamento").value,
            prazo_entrega: document.getElementById("create-prazo").value,
            frete_tipo: document.getElementById("create-frete").value,
            observacao_cliente: document.getElementById("create-obs-cliente").value,
            itens: items
        };

        try {
            const res = await fetch(`${API_URL}/cotacoes/manual`, {
                method: 'POST',
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(payload)
            });

            const data = await res.json();
            if (data.success) {
                alert("Cotação gerada com sucesso!");
                closeCreateModal();
                loadQuotes();
                
                // Immediately open it in editor for the representative to inline edit if desired!
                const newQuote = data.data;
                const canEdit = newQuote.status === 'EM_CRIACAO' && (
                    CURRENT_USER.papel === 'administrador' || 
                    CURRENT_USER.id == newQuote.representante_id
                );
                if (canEdit && confirm("Deseja abrir a cotação no editor de itens para refinar os detalhes?")) {
                    window.location.href = `{{ url('/cotacoes/token') }}/${newQuote.token_representante}`;
                }
            } else {
                alert("Erro ao criar cotação: " + (data.message || JSON.stringify(data.messages)));
            }
        } catch (e) {
            alert("Erro de conexão.");
        }
    }
</script>
@endsection
