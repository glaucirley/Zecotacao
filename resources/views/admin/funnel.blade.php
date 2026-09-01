@extends('layouts.app')

@section('page_title', 'Funil de Vendas (Kanban)')

@section('content')
<!-- Top Filters Toolbar -->
<div class="card" style="margin-bottom: 20px; padding: 15px 20px;">
    <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 15px;">
        <div>
            <h3 style="margin: 0; color: var(--color-primary); font-weight: 700; font-size: 16px;">Pipeline de Cotações Comerciais</h3>
        </div>
        
        <!-- Filters Inputs -->
        <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
            <input type="text" id="funnel-search-input" class="form-control" placeholder="Buscar por Nº ou Cliente..." style="max-width: 240px; font-size: 13px; padding: 8px 12px;" oninput="filterFunnel()">
            
            <select id="funnel-seller-filter" class="form-control" style="max-width: 220px; font-size: 13px; padding: 8px 12px;" onchange="filterFunnel()">
                <option value="">Todos os Vendedores</option>
                <!-- Dynamic Options -->
            </select>

            <select id="funnel-period-filter" class="form-control" style="max-width: 150px; font-size: 13px; padding: 8px 12px;" onchange="loadFunnelData()">
                <option value="7">Últimos 7 dias</option>
                <option value="15">Últimos 15 dias</option>
                <option value="30" selected>Últimos 30 dias</option>
                <option value="all">Geral (Todo histórico)</option>
            </select>
        </div>
    </div>
</div>

<!-- Loading indicator -->
<div id="loading-spinner" style="text-align: center; padding: 100px 20px;">
    <div style="border: 4px solid rgba(15,81,50,0.1); border-top: 4px solid var(--color-primary); border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 0 auto 15px auto;"></div>
    <span style="color: var(--color-text-muted); font-weight: 500;">Construindo pipeline comercial...</span>
</div>

<!-- Kanban Container -->
<div id="kanban-pipeline-wrapper" style="display: none;">
    <div class="kanban-container" style="display: flex; gap: 16px; overflow-x: auto; padding-bottom: 15px; align-items: start; min-height: 600px;">
        
        <!-- Column 1: Rascunho -->
        <div class="kanban-column" id="col-rascunho">
            <div class="kanban-header">
                <div>
                    <span class="kanban-title">📝 Rascunho</span>
                    <span class="kanban-badge" id="badge-rascunho">0</span>
                </div>
                <div class="kanban-value" id="val-rascunho">R$ 0,00</div>
            </div>
            <div class="kanban-cards" id="cards-rascunho"></div>
        </div>

        <!-- Column 2: Em Analise -->
        <div class="kanban-column" id="col-analise">
            <div class="kanban-header">
                <div>
                    <span class="kanban-title">🔍 Em Análise</span>
                    <span class="kanban-badge" id="badge-analise">0</span>
                </div>
                <div class="kanban-value" id="val-analise">R$ 0,00</div>
            </div>
            <div class="kanban-cards" id="cards-analise"></div>
        </div>

        <!-- Column 3: Liberada -->
        <div class="kanban-column" id="col-liberada">
            <div class="kanban-header">
                <div>
                    <span class="kanban-title">🔓 Liberada (Pendente PDF)</span>
                    <span class="kanban-badge" id="badge-liberada">0</span>
                </div>
                <div class="kanban-value" id="val-liberada">R$ 0,00</div>
            </div>
            <div class="kanban-cards" id="cards-liberada"></div>
        </div>

        <!-- Column 4: Negociacao -->
        <div class="kanban-column" id="col-negociacao">
            <div class="kanban-header">
                <div>
                    <span class="kanban-title">💬 Negociação / Enviada</span>
                    <span class="kanban-badge" id="badge-negociacao">0</span>
                </div>
                <div class="kanban-value" id="val-negociacao">R$ 0,00</div>
            </div>
            <div class="kanban-cards" id="cards-negociacao"></div>
        </div>

        <!-- Column 5: Casada -->
        <div class="kanban-column" id="col-casada">
            <div class="kanban-header">
                <div>
                    <span class="kanban-title">📦 Casada (Aguardando Fat.)</span>
                    <span class="kanban-badge" id="badge-casada">0</span>
                </div>
                <div class="kanban-value" id="val-casada">R$ 0,00</div>
            </div>
            <div class="kanban-cards" id="cards-casada"></div>
        </div>

        <!-- Column 6: Ganha -->
        <div class="kanban-column" id="col-ganha">
            <div class="kanban-header">
                <div>
                    <span class="kanban-title">🏆 Ganha (Faturada)</span>
                    <span class="kanban-badge" id="badge-ganha">0</span>
                </div>
                <div class="kanban-value" id="val-ganha">R$ 0,00</div>
            </div>
            <div class="kanban-cards" id="cards-ganha"></div>
        </div>

        <!-- Column 7: Perdida -->
        <div class="kanban-column" id="col-perdida">
            <div class="kanban-header">
                <div>
                    <span class="kanban-title">❌ Perdida</span>
                    <span class="kanban-badge" id="badge-perdida">0</span>
                </div>
                <div class="kanban-value" id="val-perdida">R$ 0,00</div>
            </div>
            <div class="kanban-cards" id="cards-perdida"></div>
        </div>

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
@endsection

@section('styles')
<style>
    /* Kanban Styles */
    .kanban-container {
        scrollbar-width: thin;
    }
    .kanban-column {
        flex: 0 0 280px;
        background-color: #f8fafc;
        border-radius: var(--radius-md);
        border: 1px solid var(--color-border);
        padding: 12px;
        display: flex;
        flex-direction: column;
        max-height: 700px;
        box-shadow: var(--shadow-sm);
    }
    .kanban-header {
        display: flex;
        flex-direction: column;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 2px solid var(--color-border);
    }
    .kanban-header > div {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 4px;
    }
    .kanban-title {
        font-weight: 700;
        font-size: 13px;
        color: var(--color-text-main);
    }
    .kanban-badge {
        font-size: 10px;
        font-weight: 700;
        background-color: #cbd5e1;
        color: var(--color-text-main);
        padding: 2px 6px;
        border-radius: 10px;
    }
    .kanban-value {
        font-size: 11px;
        font-weight: 600;
        color: var(--color-primary);
    }
    .kanban-cards {
        flex-grow: 1;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 10px;
        min-height: 150px;
        padding: 2px;
        scrollbar-width: none; /* hide default scrollbar */
    }
    .kanban-cards::-webkit-scrollbar {
        display: none;
    }
    
    /* Card Styles */
    .kanban-card {
        background-color: var(--color-card);
        border-radius: 8px;
        padding: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        border: 1px solid var(--color-border);
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .kanban-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
        border-color: var(--color-primary-light);
    }
    
    /* Inner Card Layout */
    .card-top {
        display: flex;
        justify-content: space-between;
        font-size: 11px;
        color: var(--color-text-muted);
        margin-bottom: 6px;
    }
    .card-title {
        font-size: 13px;
        font-weight: 700;
        color: var(--color-text-main);
        margin-bottom: 4px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        line-height: 1.3;
    }
    .card-rep {
        font-size: 11px;
        color: var(--color-text-muted);
        margin-bottom: 8px;
    }
    .card-bottom {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-top: 1px dashed var(--color-border);
        padding-top: 8px;
    }
    .card-price {
        font-weight: 700;
        font-size: 13px;
        color: var(--color-primary);
    }
</style>
@endsection

@section('scripts')
<script>
    const API_URL = "{{ url('/api/v1') }}";
    let rawQuotes = [];
    let sellersMap = {};

    document.addEventListener("DOMContentLoaded", () => {
        loadSellers();
        loadFunnelData();
    });

    async function loadSellers() {
        try {
            const res = await fetch(`${API_URL}/usuarios`);
            const data = await res.json();
            if (data.success) {
                const select = document.getElementById("funnel-seller-filter");
                // Filter only representatives
                const reps = data.data.filter(u => u.papel === 'representante');
                reps.forEach(r => {
                    select.innerHTML += `<option value="${r.id}">${r.nome}</option>`;
                });
            }
        } catch(e) {
            console.error("Error loading sellers:", e);
        }
    }

    async function loadFunnelData() {
        document.getElementById("loading-spinner").style.display = "block";
        document.getElementById("kanban-pipeline-wrapper").style.display = "none";
        
        const period = document.getElementById("funnel-period-filter").value;

        try {
            const res = await fetch(`${API_URL}/cotacoes/todas`);
            const data = await res.json();
            
            document.getElementById("loading-spinner").style.display = "none";
            document.getElementById("kanban-pipeline-wrapper").style.display = "block";

            if (data.success) {
                // Apply period filtering on backend datetime if selected
                rawQuotes = data.data.filter(q => {
                    if (period === 'all') return true;
                    
                    const limitDate = new Date();
                    limitDate.setDate(limitDate.getDate() - parseInt(period));
                    return new Date(q.created_at) >= limitDate;
                });
                
                renderFunnel(rawQuotes);
            } else {
                alert("Erro ao buscar cotações: " + data.error);
            }
        } catch (e) {
            console.error(e);
            alert("Erro de conexão ao buscar pipeline.");
        }
    }

    function renderFunnel(list) {
        // Categories grouping arrays
        const phases = {
            rascunho: { cards: [], val: 0.0 },
            analise: { cards: [], val: 0.0 },
            liberada: { cards: [], val: 0.0 },
            negociacao: { cards: [], val: 0.0 },
            casada: { cards: [], val: 0.0 },
            ganha: { cards: [], val: 0.0 },
            perdida: { cards: [], val: 0.0 }
        };

        list.forEach(q => {
            const price = parseFloat(q.total || 0.0);
            
            // Map internally
            if (q.status === 'EM_CRIACAO' || q.status === 'DEVOLVIDA') {
                phases.rascunho.cards.push(q);
                phases.rascunho.val += price;
            } else if (q.status === 'AGUARDANDO_GESTOR' || q.status === 'COM_DIRETOR') {
                phases.analise.cards.push(q);
                phases.analise.val += price;
            } else if (q.status === 'PDF_GERADO') {
                phases.liberada.cards.push(q);
                phases.liberada.val += price;
            } else if (q.status === 'AGUARDANDO_PEDIDO') {
                phases.negociacao.cards.push(q);
                phases.negociacao.val += price;
            } else if (q.status === 'FINALIZADA_COM_PEDIDO') {
                phases.casada.cards.push(q);
                phases.casada.val += price;
            } else if (q.status === 'FATURADA') {
                phases.ganha.cards.push(q);
                phases.ganha.val += price;
            } else if (q.status === 'PERDIDA') {
                phases.perdida.cards.push(q);
                phases.perdida.val += price;
            }
        });

        // Loop render columns
        Object.keys(phases).forEach(key => {
            // Update Headers
            document.getElementById(`badge-${key}`).innerText = phases[key].cards.length;
            document.getElementById(`val-${key}`).innerText = "R$ " + phases[key].val.toLocaleString('pt-BR', {minimumFractionDigits: 2});

            // Update Cards List
            const listContainer = document.getElementById(`cards-${key}`);
            listContainer.innerHTML = "";

            if (phases[key].cards.length === 0) {
                listContainer.innerHTML = `
                    <div style="text-align: center; color: #94a3b8; font-size: 11px; padding: 30px 10px; border: 1px dashed #cbd5e1; border-radius: 8px;">
                        Sem cotações
                    </div>
                `;
            } else {
                phases[key].cards.forEach(q => {
                    const date = new Date(q.created_at).toLocaleDateString('pt-BR');
                    const val = parseFloat(q.total).toLocaleString('pt-BR', {minimumFractionDigits: 2});
                    const priorityTag = q.prioridade ? '<span style="font-size:10px; font-weight:700; color:#ef4444;">⚡ GRANDE CONTA</span>' : '';
                    
                    listContainer.innerHTML += `
                        <div class="kanban-card" onclick="openDetailModal(${q.id})">
                            <div class="card-top">
                                <strong>#${q.numero}</strong>
                                <span>${date}</span>
                            </div>
                            <div class="card-title">${q.parceiro.razao_social}</div>
                            <div class="card-rep">Vendedor: ${q.representante.nome}</div>
                            <div class="card-bottom">
                                <span class="card-price">R$ ${val}</span>
                                ${priorityTag}
                            </div>
                        </div>
                    `;
                });
            }
        });
    }

    function filterFunnel() {
        const query = document.getElementById("funnel-search-input").value.toLowerCase().trim();
        const sellerId = document.getElementById("funnel-seller-filter").value;

        const filtered = rawQuotes.filter(q => {
            const matchesSearch = query === "" || 
                q.numero.toLowerCase().includes(query) || 
                q.parceiro.razao_social.toLowerCase().includes(query);
            
            const matchesSeller = sellerId === "" || q.representante_id == sellerId;

            return matchesSearch && matchesSeller;
        });

        renderFunnel(filtered);
    }

    // Modal Details Logic (Consistent with quotes.blade.php)
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
                        <div style="border-bottom:1px solid rgba(0,0,0,0.02); padding-bottom:4px; margin-bottom: 4px;">
                            <span style="color:var(--color-primary); font-weight:600;">[${hDate}]</span>
                            <strong>${h.usuario ? h.usuario.nome : 'Sistema'} (${h.papel.toUpperCase()})</strong>:
                            ${h.evento.replace(/_/g, ' ')} - <span style="color:#555;">${h.condicao || ''}</span>
                        </div>
                    `;
                });
            }

            document.getElementById("detail-overlay").classList.add("open");
            document.getElementById("detail-modal").classList.add("open");
        } catch (e) {
            console.error(e);
            alert("Erro ao conectar com o servidor.");
        }
    }

    function closeDetailModal() {
        document.getElementById("detail-overlay").classList.remove("open");
        document.getElementById("detail-modal").classList.remove("open");
    }
</script>
@endsection
