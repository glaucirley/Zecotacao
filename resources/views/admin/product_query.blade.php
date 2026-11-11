@extends('layouts.app')

@section('page_title', 'Consulta de Produtos & Precificação')

@section('content')
<!-- Header & Context Box -->
<div class="card" style="margin-bottom: 20px; padding: 22px 24px; border-radius: 14px; border: 1px solid var(--color-border); box-shadow: var(--shadow-sm); background: #ffffff;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
        <div>
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 4px;">
                <span style="font-size: 24px; line-height: 1;">🔍</span>
                <h2 style="font-size: 20px; font-weight: 700; color: #0f172a; margin: 0;">Consulta de Produtos, Estoque & Preços</h2>
                <span style="background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 6px;">
                    Uso Retaguarda
                </span>
            </div>
            <p style="font-size: 13px; color: #64748b; margin: 0; max-width: 680px; line-height: 1.45;">
                Consulte estoques em tempo real, validades de lotes e preços oficiais. Selecione um cliente para simular a tabela de preços personalizada e verificar restrições comerciais (<code style="background:#f1f5f9; padding:1px 4px; border-radius:4px; font-size:11px;">AD_EXCPRODUTO</code> e <code style="background:#f1f5f9; padding:1px 4px; border-radius:4px; font-size:11px;">AD_EXCMARCA</code>).
            </p>
        </div>

        <div style="display: flex; gap: 10px; align-items: center;">
            <button type="button" class="btn btn-outline" onclick="loadCatalog()" style="font-size: 12.5px; padding: 8px 14px; display: inline-flex; align-items: center; gap: 6px;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>
                Atualizar Lista
            </button>
            <a href="{{ url('/cotacoes') }}" class="btn btn-primary" style="font-size: 12.5px; padding: 8px 16px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                Nova Cotação
            </a>
        </div>
    </div>

    <!-- Cliente Selection Banner -->
    <div style="margin-top: 18px; padding: 14px 16px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
        <div style="display: flex; align-items: center; gap: 8px; font-weight: 700; color: #334155; font-size: 13px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
            Cliente para Precificação:
        </div>

        <!-- Autocomplete input -->
        <div style="position: relative; flex: 1; min-width: 260px; max-width: 440px;">
            <input type="text" id="partner-search-input" class="form-control" placeholder="Buscar por Razão Social, Fantasia, CNPJ ou Cód..." autocomplete="off" oninput="onPartnerInput(this.value)" onfocus="showPartnerDropdown()" style="font-size: 13px; padding: 8px 12px; background: #ffffff;">
            
            <div id="partner-dropdown" style="display: none; position: absolute; top: calc(100% + 4px); left: 0; right: 0; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.15); max-height: 250px; overflow-y: auto; z-index: 1000;">
                <!-- Dynamic Partner Results -->
            </div>
        </div>

        <!-- Selected Client Pill -->
        <div id="selected-partner-badge" style="display: none; align-items: center; gap: 10px; background: #ecfdf5; border: 1px solid #a7f3d0; padding: 6px 12px; border-radius: 8px; font-size: 12.5px;">
            <div style="display: flex; align-items: center; gap: 6px;">
                <span style="color: #059669; font-size: 14px;">🏢</span>
                <strong id="selected-partner-name" style="color: #065f46;">-</strong>
                <span id="selected-partner-doc" style="color: #047857; font-size: 11.5px;"></span>
                <span id="selected-partner-table" style="background: #10b981; color: #ffffff; font-size: 10.5px; font-weight: 700; padding: 1px 6px; border-radius: 4px; margin-left: 4px;">Tabela do Cliente</span>
            </div>
            <button type="button" onclick="clearSelectedPartner()" title="Remover cliente e voltar à tabela padrão" style="background: none; border: none; color: #047857; cursor: pointer; font-size: 14px; font-weight: bold; line-height: 1; padding: 0 2px;">
                &times;
            </button>
        </div>

        <div id="no-partner-hint" style="font-size: 12px; color: #64748b; margin-left: auto;">
            <span>ℹ️ Sem cliente: aplicando <strong>Tabela Padrão (Geral)</strong>.</span>
        </div>
    </div>
</div>

<!-- Alert: Região Sem Tabela de Preços -->
<div id="partner-no-table-alert" style="display: none; margin-bottom: 16px; background: #fef2f2; border: 1px solid #fecaca; border-left: 5px solid #ef4444; border-radius: 10px; padding: 14px 18px; color: #991b1b; font-size: 13px;">
    <div style="display: flex; align-items: center; gap: 10px;">
        <span style="font-size: 20px;">⚠️</span>
        <div>
            <strong>Região sem Tabela de Preços no Sankhya:</strong>
            <span id="partner-no-table-alert-text">A região deste cliente não possui tabela de preços vinculada na coluna CODTAB da tabela TSIREG do Sankhya. Conforme regra de precificação, nenhum produto é precificado até que a tabela seja cadastrada no ERP.</span>
        </div>
    </div>
</div>

<!-- Main Table Card -->
<div class="card" style="border-radius: 14px; border: 1px solid var(--color-border); box-shadow: var(--shadow-sm); background: #ffffff; overflow: hidden;">
    
    <!-- Filter Toolbar -->
    <div style="padding: 16px 20px; background: #f8fafc; border-bottom: 1px solid var(--color-border); display: flex; gap: 14px; flex-wrap: wrap; align-items: center;">
        
        <!-- Search input -->
        <div style="flex: 1; min-width: 260px;">
            <div style="position: relative;">
                <span style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 13px;">🔍</span>
                <input type="text" id="product-search-input" class="form-control" placeholder="Buscar por código Sankhya, descrição do produto, marca ou princípio ativo..." oninput="onSearchInput()" style="padding-left: 34px; font-size: 13px; background: #ffffff;">
            </div>
        </div>

        <!-- Quick Toggles -->
        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <label style="display: inline-flex; align-items: center; gap: 6px; font-size: 12.5px; font-weight: 600; color: #334155; cursor: pointer; background: #ffffff; padding: 7px 12px; border: 1px solid #cbd5e1; border-radius: 8px; user-select: none;">
                <input type="checkbox" id="filter-stock" onchange="loadCatalog()" style="cursor: pointer;">
                <span>📦 Apenas com Estoque (> 0)</span>
            </label>

            <label style="display: inline-flex; align-items: center; gap: 6px; font-size: 12.5px; font-weight: 600; color: #334155; cursor: pointer; background: #ffffff; padding: 7px 12px; border: 1px solid #cbd5e1; border-radius: 8px; user-select: none;">
                <input type="checkbox" id="filter-campaign" onchange="loadCatalog()" style="cursor: pointer;">
                <span>🎯 Apenas em Campanha</span>
            </label>

            <select id="limit-select" class="form-control" onchange="loadCatalog()" style="width: 120px; font-size: 12.5px; padding: 7px 10px; background: #ffffff;">
                <option value="50">50 itens</option>
                <option value="100">100 itens</option>
                <option value="200">200 itens</option>
            </select>
        </div>

        <div style="margin-left: auto; font-size: 12px; color: #64748b; font-weight: 600;" id="count-info">
            Carregando produtos...
        </div>
    </div>

    <!-- Products Table -->
    <div class="table-responsive">
        <table class="table-premium" style="margin-bottom: 0;">
            <thead>
                <tr>
                    <th style="width: 12%;">Cód. Sankhya</th>
                    <th style="width: 32%;">Produto / Detalhes</th>
                    <th style="width: 12%; text-align: center;">Estoque</th>
                    <th style="width: 12%; text-align: center;">Validade Lote</th>
                    <th style="width: 12%; text-align: right;">Preço Sugerido</th>
                    <th style="width: 10%; text-align: right;">Preço Mínimo</th>
                    <th style="width: 10%; text-align: center;" id="th-cost-margin">Situação</th>
                </tr>
            </thead>
            <tbody id="catalog-table-body">
                <!-- Dynamic rows -->
            </tbody>
        </table>
    </div>

    <!-- Loading Spinner -->
    <div id="catalog-loading" style="text-align: center; padding: 50px 20px;">
        <div style="border: 3px solid rgba(15,81,50,0.1); border-top: 3px solid var(--color-primary); border-radius: 50%; width: 32px; height: 32px; animation: spin 1s linear infinite; margin: 0 auto 10px auto;"></div>
        <span style="font-size: 13px; color: #64748b; font-weight: 500;">Consultando catálogo comercial...</span>
    </div>

    <!-- Empty State -->
    <div id="catalog-empty" style="display: none; text-align: center; padding: 50px 20px;">
        <div style="font-size: 32px; margin-bottom: 10px;">📦</div>
        <h4 style="font-size: 15px; font-weight: 700; color: #1e293b; margin-bottom: 6px;">Nenhum produto encontrado</h4>
        <p style="font-size: 13px; color: #64748b; margin: 0;">Tente refinar ou alterar o termo de busca ou desmarque os filtros de estoque/campanha.</p>
    </div>
</div>

<script>
    const API_URL = "{{ url('/api/v1') }}";
    let selectedPartnerId = null;
    let selectedPartnerData = null;
    let searchTimeout = null;
    let partnerSearchTimeout = null;
    let canViewCosts = false;

    document.addEventListener("DOMContentLoaded", function () {
        loadCatalog();

        // Close partner dropdown when clicking outside
        document.addEventListener("click", function (e) {
            const dropdown = document.getElementById("partner-dropdown");
            const input = document.getElementById("partner-search-input");
            if (dropdown && !dropdown.contains(e.target) && e.target !== input) {
                dropdown.style.display = "none";
            }
        });
    });

    function onSearchInput() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            loadCatalog();
        }, 350);
    }

    async function loadCatalog() {
        const loading = document.getElementById("catalog-loading");
        const empty = document.getElementById("catalog-empty");
        const body = document.getElementById("catalog-table-body");
        const countInfo = document.getElementById("count-info");

        loading.style.display = "block";
        empty.style.display = "none";
        body.innerHTML = "";

        const search = document.getElementById("product-search-input").value;
        const apenasEstoque = document.getElementById("filter-stock").checked;
        const apenasCampanha = document.getElementById("filter-campaign").checked;
        const limit = document.getElementById("limit-select").value;

        const params = new URLSearchParams({
            search: search,
            apenas_estoque: apenasEstoque ? '1' : '0',
            apenas_campanha: apenasCampanha ? '1' : '0',
            limit: limit
        });

        if (selectedPartnerId) {
            params.append('parceiro_id', selectedPartnerId);
        }

        try {
            const res = await fetch(`${API_URL}/produtos/consulta-retaguarda?${params.toString()}`);
            if (!res.ok) {
                throw new Error(`Erro ${res.status}`);
            }

            const json = await res.json();
            loading.style.display = "none";

            // Alerta e badge de Tabela de Preço por Região (TSIREG.CODTAB)
            const alertEl = document.getElementById("partner-no-table-alert");
            const tableBadge = document.getElementById("selected-partner-table");
            if (json.partner) {
                if (json.partner.tabela_regiao_inexistente) {
                    if (alertEl) {
                        alertEl.style.display = "block";
                        const alertText = document.getElementById("partner-no-table-alert-text");
                        if (alertText) {
                            alertText.innerHTML = `A região deste cliente (Região <strong>${json.partner.codigo_regiao || 'N/D'}</strong>) não possui tabela de preços vinculada na coluna <code>CODTAB</code> da tabela <code>TSIREG</code> do Sankhya. Conforme regra comercial, nenhum produto é precificado até que a tabela seja cadastrada no ERP.`;
                        }
                    }
                    if (tableBadge) {
                        tableBadge.innerText = "⚠️ Região sem tabela no Sankhya";
                        tableBadge.style.background = "#ef4444";
                    }
                } else {
                    if (alertEl) alertEl.style.display = "none";
                    if (tableBadge) {
                        tableBadge.innerText = `Tabela Região: #${json.partner.tabela_codigo}`;
                        tableBadge.style.background = "#10b981";
                    }
                }
            } else {
                if (alertEl) alertEl.style.display = "none";
            }

            if (!json.success || !json.data || json.data.length === 0) {
                empty.style.display = "block";
                countInfo.innerText = "0 produtos encontrados";
                return;
            }

            canViewCosts = Boolean(json.can_view_costs);
            countInfo.innerText = `Mostrando ${json.data.length} de ${json.total} produto(s) encontrado(s)`;

            renderCatalogRows(json.data);

        } catch (err) {
            console.error("Erro ao carregar catálogo:", err);
            loading.style.display = "none";
            body.innerHTML = `<tr><td colspan="7" style="text-align:center; padding:30px; color:#ef4444; font-weight:600;">Falha ao carregar catálogo: ${escapeHtml(err.message || err)}. Verifique sua conexão.</td></tr>`;
        }
    }

    function renderCatalogRows(products) {
        const body = document.getElementById("catalog-table-body");
        body.innerHTML = "";

        products.forEach(p => {
            const stockVal = p.estoque !== null && p.estoque !== undefined ? parseFloat(p.estoque) : null;
            const hasStock = stockVal !== null && stockVal > 0;
            
            // Stock badge
            let stockBadge = '<span style="color:#94a3b8; font-size:12px;">--</span>';
            if (stockVal !== null) {
                const stockText = p.estoque_formatado || (Math.floor(stockVal) === stockVal ? stockVal : stockVal.toFixed(2));
                if (hasStock) {
                    stockBadge = `
                        <span style="display:inline-flex; align-items:center; gap:4px; padding:3px 8px; border-radius:6px; font-size:12px; font-weight:700; background:#dcfce7; color:#15803d; border:1px solid #86efac;">
                            📦 ${escapeHtml(String(stockText))} ${escapeHtml(p.unidade || 'UN')}
                        </span>
                    `;
                } else {
                    stockBadge = `
                        <span style="display:inline-flex; align-items:center; gap:4px; padding:3px 8px; border-radius:6px; font-size:11.5px; font-weight:700; background:#fee2e2; color:#991b1b; border:1px solid #fecaca;">
                            0 ${escapeHtml(p.unidade || 'UN')} (Esgotado)
                        </span>
                    `;
                }
            }

            // Expiration date badge
            let valBadge = '<span style="color:#94a3b8; font-size:12px;">--</span>';
            if (p.validade_proxima || p.validade_formatada) {
                const valText = p.validade_formatada || p.validade_proxima;
                valBadge = `
                    <span style="display:inline-flex; align-items:center; gap:4px; padding:3px 8px; border-radius:6px; font-size:11.5px; font-weight:600; background:#f8fafc; color:#334155; border:1px solid #cbd5e1;">
                        📅 ${escapeHtml(String(valText))}
                    </span>
                `;
            }

            // Commercial restriction badge
            let statusBadge = '';
            if (p.is_restrito) {
                statusBadge = `
                    <span style="display:inline-flex; align-items:center; gap:3px; padding:3px 8px; border-radius:6px; font-size:11px; font-weight:700; background:#fef2f2; color:#991b1b; border:1px solid #f87171;" title="${escapeHtml(p.motivo_restricao || 'Restrito')}">
                        ⛔ Restrito p/ Cliente
                    </span>
                `;
            } else if (selectedPartnerId) {
                statusBadge = `
                    <span style="display:inline-flex; align-items:center; gap:3px; padding:3px 8px; border-radius:6px; font-size:11px; font-weight:700; background:#ecfdf5; color:#047857; border:1px solid #6ee7b7;">
                        ✔️ Liberado
                    </span>
                `;
            } else {
                statusBadge = `
                    <span style="display:inline-flex; align-items:center; gap:3px; padding:3px 8px; border-radius:6px; font-size:11px; font-weight:600; background:#f1f5f9; color:#475569;">
                        Catálogo Geral
                    </span>
                `;
            }

            // Pricing formatting
            let sugFormat = '<span style="color:#ef4444; font-size:11.5px; font-weight:600;">Sem tabela</span>';
            if (p.preco_sugerido > 0) {
                sugFormat = `R$ ${formatMoney(p.preco_sugerido)}`;
            } else if (p.motivo_bloqueio_preco) {
                sugFormat = `<span style="color:#dc2626; font-size:11px; font-weight:600;" title="${escapeHtml(p.motivo_bloqueio_preco)}">❌ Sem preço (TSIREG)</span>`;
            }
            const minFormat = p.preco_minimo > 0 ? `R$ ${formatMoney(p.preco_minimo)}` : '<span style="color:#94a3b8; font-size:11px;">-</span>';

            // Cost & Margin subtext if authorized
            let costDetails = '';
            if (canViewCosts && p.custo > 0) {
                const marginColor = p.margem >= 20 ? '#15803d' : (p.margem >= 10 ? '#b45309' : '#dc2626');
                costDetails = `
                    <div style="font-size:10.5px; color:#64748b; margin-top:3px;">
                        Custo: R$ ${formatMoney(p.custo)} · Margem: <strong style="color:${marginColor};">${p.margem ? p.margem + '%' : '-'}</strong>
                    </div>
                `;
            }

            // Campaign badge
            let campaignBadge = '';
            if (p.campanha) {
                campaignBadge = `
                    <span style="display:inline-block; font-size:10.5px; font-weight:700; background:#fef3c7; color:#92400e; border:1px solid #fde68a; padding:1px 6px; border-radius:4px; margin-left:6px;">
                        🎯 ${escapeHtml(p.campanha)}
                    </span>
                `;
            }

            // Subtitle details
            const subParts = [];
            if (p.marca) subParts.push(`Marca: <strong>${escapeHtml(p.marca)}</strong>`);
            if (p.base) subParts.push(`Base: ${escapeHtml(p.base)}`);
            if (p.principio_ativo) subParts.push(`Princípio: ${escapeHtml(p.principio_ativo)}`);

            body.innerHTML += `
                <tr style="${p.is_restrito ? 'background-color:#fff1f2;' : ''}">
                    <td>
                        <strong style="color:#0f172a; font-size:13px;">${escapeHtml(p.codigo_sankhya || '-')}</strong>
                        <div style="font-size:11px; color:#64748b;">${escapeHtml(p.unidade || 'UN')}</div>
                    </td>
                    <td>
                        <div style="display:flex; align-items:center; flex-wrap:wrap; gap:4px;">
                            <strong style="color:#1e293b; font-size:13px;">${escapeHtml(p.descricao || 'Sem descrição')}</strong>
                            ${campaignBadge}
                        </div>
                        ${subParts.length > 0 ? `<div style="font-size:11.5px; color:#64748b; margin-top:2px;">${subParts.join(' · ')}</div>` : ''}
                    </td>
                    <td style="text-align: center;">
                        ${stockBadge}
                    </td>
                    <td style="text-align: center;">
                        ${valBadge}
                    </td>
                    <td style="text-align: right;">
                        <strong style="color:var(--color-primary); font-size:13.5px;">${sugFormat}</strong>
                        ${costDetails}
                    </td>
                    <td style="text-align: right;">
                        <span style="font-weight:600; color:#334155; font-size:12.5px;">${minFormat}</span>
                    </td>
                    <td style="text-align: center;">
                        ${statusBadge}
                    </td>
                </tr>
            `;
        });
    }

    // Partner search autocomplete
    function onPartnerInput(val) {
        clearTimeout(partnerSearchTimeout);
        if (!val || val.trim().length < 2) {
            document.getElementById("partner-dropdown").style.display = "none";
            return;
        }

        partnerSearchTimeout = setTimeout(() => {
            searchPartners(val.trim());
        }, 300);
    }

    function showPartnerDropdown() {
        const val = document.getElementById("partner-search-input").value;
        if (val && val.trim().length >= 2) {
            searchPartners(val.trim());
        }
    }

    async function searchPartners(q) {
        const dropdown = document.getElementById("partner-dropdown");
        try {
            const res = await fetch(`${API_URL}/clientes?search=${encodeURIComponent(q)}&limit=15`);
            if (!res.ok) return;

            const json = await res.json();
            const list = (json.success && Array.isArray(json.data)) ? json.data : (Array.isArray(json) ? json : []);

            if (list.length === 0) {
                dropdown.innerHTML = `<div style="padding: 10px 14px; font-size: 12px; color: #64748b;">Nenhum cliente encontrado com este termo.</div>`;
                dropdown.style.display = "block";
                return;
            }

            dropdown.innerHTML = list.map(c => {
                const name = c.nome_fantasia || c.razao_social || 'Sem Nome';
                const doc = c.cnpj ? 'CNPJ: ' + c.cnpj : 'Sem CNPJ';
                const cityState = (c.cidade || '') + (c.uf ? '/' + c.uf : '');
                const cod = c.codigo_sankhya ? 'Cód: ' + c.codigo_sankhya : '';
                const details = [cod, doc, cityState].filter(Boolean).join(' · ');

                return `
                    <div onclick="selectPartner(${c.id}, '${escapeHtml(name).replace(/'/g, "\\'")}', '${escapeHtml(c.cnpj || '')}')" style="padding: 9px 14px; border-bottom: 1px solid #f1f5f9; cursor: pointer; transition: background 0.15s ease;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='#ffffff'">
                        <div style="font-size: 13px; font-weight: 600; color: #1e293b;">${escapeHtml(name)}</div>
                        <div style="font-size: 11px; color: #64748b;">${escapeHtml(details)}</div>
                    </div>
                `;
            }).join('');

            dropdown.style.display = "block";
        } catch (e) {
            console.error(e);
        }
    }

    function selectPartner(id, name, cnpj) {
        selectedPartnerId = id;
        document.getElementById("partner-dropdown").style.display = "none";
        document.getElementById("partner-search-input").value = "";

        const badge = document.getElementById("selected-partner-badge");
        document.getElementById("selected-partner-name").innerText = name;
        document.getElementById("selected-partner-doc").innerText = cnpj ? `(${cnpj})` : '';
        badge.style.display = "inline-flex";

        document.getElementById("no-partner-hint").style.display = "none";

        showToast(`Cliente '${name}' selecionado! Tabela de preços e restrições aplicadas.`, "success");
        loadCatalog();
    }

    function clearSelectedPartner() {
        selectedPartnerId = null;
        selectedPartnerData = null;
        document.getElementById("selected-partner-badge").style.display = "none";
        document.getElementById("no-partner-hint").style.display = "inline";
        document.getElementById("partner-search-input").value = "";
        const alertEl = document.getElementById("partner-no-table-alert");
        if (alertEl) alertEl.style.display = "none";

        showToast("Cliente desmarcado. Tabela padrão restaurada.", "info");
        loadCatalog();
    }

    function formatMoney(val) {
        if (!val || isNaN(val)) return '0,00';
        return parseFloat(val).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        return String(text)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
</script>
@endsection
