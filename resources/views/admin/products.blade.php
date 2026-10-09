@extends('layouts.app')

@section('page_title', 'Gestão de Produtos')

@section('content')
<!-- Product Quality & Catalog Health Banner (Point 7) -->
<div style="background: #fffbeb; border: 1px solid #fef3c7; border-radius: 12px; padding: 14px 18px; margin-bottom: 18px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; box-shadow: var(--shadow-sm);">
    <div style="display: flex; align-items: center; gap: 12px;">
        <span style="font-size: 22px;">⚠️</span>
        <div>
            <div style="font-weight: 700; color: #92400e; font-size: 13.5px;">Atenção: 64 produtos com preço padrão (R$ 100,00) ou sem tabela cadastrada</div>
            <div style="font-size: 12px; color: #b45309; margin-top: 2px;">Itens sem preço de tabela ativo no ERP estão automaticamente bloqueados para novas cotações por segurança.</div>
        </div>
    </div>
    <div style="display: flex; align-items: center; gap: 8px;">
        <span style="background: #fef3c7; color: #92400e; border: 1px solid #fde68a; padding: 4px 10px; border-radius: 6px; font-size: 11.5px; font-weight: 700;">
            Sincronizado Sankhya
        </span>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div style="display: flex; align-items: center; gap: 8px;">
            <h3 style="margin: 0;">Catálogo de Produtos</h3>
            <span style="font-size: 11px; color: #16a34a; background: #dcfce7; border: 1px solid #86efac; padding: 2px 8px; border-radius: 12px; font-weight: 600;">Sincronizado via ERP</span>
        </div>
        <button class="btn btn-primary" onclick="openCreateModal()" style="font-size:13px; padding: 8px 16px;">
            + Novo Produto
        </button>
    </div>

    <!-- Filter Bar -->
    <div style="padding: 15px 24px; background: #f8fafc; border-bottom: 1px solid var(--color-border); display: flex; gap: 15px; flex-wrap: wrap; align-items: center;">
        <div style="flex-grow: 1; min-width: 250px;">
            <input type="text" id="search-input" class="form-control" placeholder="🔍 Buscar por código Sankhya, descrição do produto, marca ou NCM..." onkeyup="filterProductsDebounced()">
        </div>
        <div style="width: 160px;">
            <select id="status-filter-select" class="form-control" onchange="loadProducts()">
                <option value="">Status: Todos</option>
                <option value="ativo">Ativos</option>
                <option value="inativo">Inativos</option>
            </select>
        </div>
        <div style="width: 100%; font-size: 12px; color: var(--color-text-muted); margin-top: 2px;">
            💡 Exibindo até 20 resultados por vez. Digite no campo de busca para encontrar qualquer produto do catálogo.
        </div>
    </div>

    <!-- Table Produtos -->
    <div class="table-responsive">
        <table class="table-premium">
            <thead>
                <tr>
                    <th style="width: 15%;">Cód. Sankhya</th>
                    <th style="width: 50%;">Descrição</th>
                    <th style="width: 15%;">Unidade</th>
                    <th style="width: 10%; text-align: center;">Status</th>
                    <th style="width: 10%; text-align: center;">Ações</th>
                </tr>
            </thead>
            <tbody id="products-table-body">
                <!-- Dynamic rows -->
            </tbody>
        </table>
    </div>

    <!-- Loading spinner -->
    <div id="loading-spinner" style="text-align: center; padding: 40px 20px;">
        <div style="border: 3px solid rgba(15,81,50,0.1); border-top: 3px solid var(--color-primary); border-radius: 50%; width: 30px; height: 30px; animation: spin 1s linear infinite; margin: 0 auto;"></div>
    </div>
</div>

<!-- Create / Edit Product Sheet Drawer -->
<div id="product-overlay" class="sheet-overlay" onclick="closeProductModal()"></div>
<div id="product-modal" class="sheet-drawer">
    <div class="sheet-header">
        <h3 id="modal-title" style="margin: 0; color: var(--color-primary);">Cadastrar Novo Produto</h3>
        <button type="button" class="sheet-close-btn" onclick="closeProductModal()">&times;</button>
    </div>
    <div class="sheet-body">
        
        <form id="product-form" onsubmit="saveProduct(event)">
            <input type="hidden" id="product-id">
            
            <div class="form-group" style="margin-bottom: 12px;">
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <label for="prod-codigo" class="form-label" style="margin-bottom:4px;">Código Sankhya</label>
                    <span id="prod-erp-badge" style="display:none; font-size:11px; background:#dcfce7; color:#15803d; border:1px solid #86efac; border-radius:4px; padding:1px 6px; font-weight:700;">Sincronizado do ERP (Somente Leitura)</span>
                </div>
                <input type="text" id="prod-codigo" class="form-control" required placeholder="Ex: PROD005">
            </div>

            <div class="form-group" style="margin-bottom: 12px;">
                <label for="prod-descricao" class="form-label">Descrição do Produto</label>
                <input type="text" id="prod-descricao" class="form-control" required placeholder="Ex: Ração Canina Filhote 15kg">
            </div>

            <div class="form-group" style="margin-bottom: 12px;">
                <label for="prod-unidade" class="form-label">Unidade de Medida</label>
                <input type="text" id="prod-unidade" class="form-control" required placeholder="Ex: UN, KG, LITRO, FRASCO">
            </div>

            <div class="form-group" style="margin-bottom: 24px;">
                <label for="prod-ativo" class="form-label">Status do Produto</label>
                <select id="prod-ativo" class="form-control">
                    <option value="1">Ativo (Visível para cotações)</option>
                    <option value="0">Inativo (Bloqueado para cotações)</option>
                </select>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px;">
                <button type="button" class="btn btn-outline" onclick="closeProductModal()">Cancelar</button>
                <button type="submit" class="btn btn-primary">Salvar Produto</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const API_URL = "{{ url('/api/v1') }}";
    let productsList = [];
    let filteredList = [];

    let searchTimer = null;

    document.addEventListener("DOMContentLoaded", () => {
        loadProducts();
    });

    function filterProductsDebounced() {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
            loadProducts();
        }, 300);
    }

    async function loadProducts() {
        document.getElementById("loading-spinner").style.display = "block";
        const search = document.getElementById("search-input") ? document.getElementById("search-input").value : '';
        const status = document.getElementById("status-filter-select") ? document.getElementById("status-filter-select").value : '';

        const params = new URLSearchParams();
        if (search) params.append('search', search);
        if (status) params.append('status', status);

        try {
            const res = await fetch(`${API_URL}/produtos-admin?${params.toString()}`);
            if (res.status === 401 || res.status === 403) {
                window.location.href = "{{ url('/login') }}";
                return;
            }

            const data = await res.json();
            document.getElementById("loading-spinner").style.display = "none";

            if (data.success) {
                productsList = data.data;
                filteredList = [...productsList];
                renderProducts();
            } else {
                showToast("Erro ao carregar produtos: " + data.error, "error");
            }
        } catch (e) {
            console.error(e);
            document.getElementById("loading-spinner").style.display = "none";
            showToast("Erro de conexão ao buscar catálogo de produtos.", "error");
        }
    }

    function renderProducts() {
        const body = document.getElementById("products-table-body");
        body.innerHTML = "";

        if (filteredList.length === 0) {
            body.innerHTML = `
                <tr>
                    <td colspan="5" style="text-align: center; color: var(--color-text-muted); padding: 30px;">
                        Nenhum produto encontrado.
                    </td>
                </tr>
            `;
            return;
        }

        filteredList.forEach(p => {
            const statusClass = p.ativo ? 'badge-status aprovado' : 'badge-status recusado';
            const statusText = p.ativo ? 'Ativo' : 'Inativo';
            const toggleActionText = p.ativo ? 'Inativar' : 'Ativar';
            const toggleColor = p.ativo ? '#ef4444' : '#16a34a';
            
            body.innerHTML += `
                <tr>
                    <td>
                        <strong>${p.codigo_sankhya}</strong>
                        <div style="font-size: 10px; color: #16a34a; font-weight:600;">Sankhya ERP</div>
                    </td>
                    <td><strong>${p.descricao}</strong></td>
                    <td>${p.unidade}</td>
                    <td class="text-center">
                        <span class="${statusClass}">${statusText}</span>
                    </td>
                    <td class="text-center" style="white-space: nowrap;">
                        <button class="btn btn-secondary" style="padding: 5px 10px; font-size: 11px; font-weight: 600;" onclick="openEditModal(${p.id})">
                            Editar
                        </button>
                        <button class="btn btn-outline" style="padding: 5px 10px; font-size: 11px; font-weight: 600; color: ${toggleColor}; border-color: ${toggleColor}; margin-left: 5px;" onclick="toggleProductStatus(${p.id}, ${p.ativo ? 1 : 0})">
                            ${toggleActionText}
                        </button>
                    </td>
                </tr>
            `;
        });
    }

    function filterProducts() {
        const query = document.getElementById("search-input").value.toLowerCase().trim();
        if (query === "") {
            filteredList = [...productsList];
        } else {
            filteredList = productsList.filter(p => 
                p.descricao.toLowerCase().includes(query) || 
                p.codigo_sankhya.toLowerCase().includes(query)
            );
        }
        renderProducts();
    }

    function openCreateModal() {
        document.getElementById("modal-title").innerText = "Cadastrar Novo Produto";
        document.getElementById("product-id").value = "";
        document.getElementById("product-form").reset();
        document.getElementById("prod-codigo").readOnly = false;
        document.getElementById("prod-erp-badge").style.display = "none";
        document.getElementById("prod-ativo").value = "1";
        document.getElementById("product-overlay").classList.add("open");
        document.getElementById("product-modal").classList.add("open");
    }

    function openEditModal(id) {
        const p = productsList.find(item => item.id === id);
        if (!p) return;

        document.getElementById("modal-title").innerText = "Editar Produto";
        document.getElementById("product-id").value = p.id;
        document.getElementById("prod-codigo").value = p.codigo_sankhya;
        document.getElementById("prod-codigo").readOnly = true;
        document.getElementById("prod-erp-badge").style.display = "inline-block";
        document.getElementById("prod-descricao").value = p.descricao;
        document.getElementById("prod-unidade").value = p.unidade;
        document.getElementById("prod-ativo").value = p.ativo ? "1" : "0";
        document.getElementById("product-overlay").classList.add("open");
        document.getElementById("product-modal").classList.add("open");
    }

    function closeProductModal() {
        document.getElementById("product-overlay").classList.remove("open");
        document.getElementById("product-modal").classList.remove("open");
    }

    async function saveProduct(event) {
        event.preventDefault();

        const id = document.getElementById("product-id").value;
        const payload = {
            codigo_sankhya: document.getElementById("prod-codigo").value.trim(),
            descricao: document.getElementById("prod-descricao").value.trim(),
            unidade: document.getElementById("prod-unidade").value.trim(),
            ativo: document.getElementById("prod-ativo").value === "1"
        };

        const method = id ? "PATCH" : "POST";
        const url = id ? `${API_URL}/produtos-admin/${id}` : `${API_URL}/produtos-admin`;

        try {
            const res = await fetch(url, {
                method: method,
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },
                body: JSON.stringify(payload)
            });

            if (!res.ok) {
                const errText = await res.text();
                try {
                    const errJson = JSON.parse(errText);
                    showToast("Erro: " + (errJson.message || errJson.error || JSON.stringify(errJson.messages)), "error");
                } catch(e) {
                    showToast(`Erro ${res.status}: ` + errText.substring(0, 200), "error");
                }
                return;
            }

            const data = await res.json();
            if (data.success) {
                showToast(id ? "Produto atualizado com sucesso!" : "Produto cadastrado com sucesso!", "success");
                closeProductModal();
                loadProducts();
            } else {
                showToast("Erro: " + data.message, "error");
            }
        } catch (e) {
            console.error(e);
            showToast("Erro de conexão ao salvar produto.", "error");
        }
    }

    async function toggleProductStatus(id, currentStatus) {
        const newStatus = currentStatus ? 0 : 1;
        const actionLabel = currentStatus ? "inativar" : "ativar";
        const confirmed = await appConfirmModal(`${actionLabel.toUpperCase()} PRODUTO`, `Deseja realmente ${actionLabel} este produto do catálogo?`, actionLabel.toUpperCase(), currentStatus ? true : false);
        if (!confirmed) return;

        try {
            const res = await fetch(`${API_URL}/produtos-admin/${id}`, {
                method: "PATCH",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },
                body: JSON.stringify({ ativo: newStatus === 1 })
            });

            const data = await res.json();
            if (data.success) {
                showToast(`Produto ${newStatus ? 'ativado' : 'inativado'} com sucesso!`, "success");
                loadProducts();
            } else {
                showToast("Erro: " + (data.message || data.error), "error");
            }
        } catch (e) {
            console.error(e);
            showToast("Erro de conexão ao alterar status do produto.", "error");
        }
    }
</script>
@endsection
