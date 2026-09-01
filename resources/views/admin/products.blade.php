@extends('layouts.app')

@section('page_title', 'Gestão de Produtos')

@section('content')
<div class="card">
    <div class="card-header">
        <h3>Catálogo de Produtos</h3>
        <button class="btn btn-primary" onclick="openCreateModal()" style="font-size:13px; padding: 8px 16px;">
            + Novo Produto
        </button>
    </div>

    <!-- Filter input -->
    <div style="margin-bottom: 20px; max-width: 300px; padding: 0 15px;">
        <input type="text" id="search-input" class="form-control" placeholder="Buscar por código ou descrição..." oninput="filterProducts()">
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
                <label for="prod-codigo" class="form-label">Código Sankhya</label>
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
                    <option value="0">Inativo / Bloqueado</option>
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

    document.addEventListener("DOMContentLoaded", () => {
        loadProducts();
    });

    async function loadProducts() {
        try {
            const res = await fetch(`${API_URL}/produtos-admin`);
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
                alert("Erro ao carregar produtos: " + data.error);
            }
        } catch (e) {
            console.error(e);
            alert("Erro de conexão ao buscar catálogo de produtos.");
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
            
            body.innerHTML += `
                <tr>
                    <td><strong>${p.codigo_sankhya}</strong></td>
                    <td><strong>${p.descricao}</strong></td>
                    <td>${p.unidade}</td>
                    <td class="text-center">
                        <span class="${statusClass}">${statusText}</span>
                    </td>
                    <td class="text-center" style="white-space: nowrap;">
                        <button class="btn btn-secondary" style="padding: 6px 12px; font-size: 11px; font-weight: 600;" onclick="openEditModal(${p.id})">
                            Editar
                        </button>
                        <button class="btn btn-outline" style="padding: 6px 12px; font-size: 11px; font-weight: 600; color: var(--status-recusada); border-color: var(--status-recusada); margin-left: 5px;" onclick="deleteProduct(${p.id})">
                            Excluir
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
                    alert("Erro: " + (errJson.message || errJson.error || JSON.stringify(errJson.messages)));
                } catch(e) {
                    alert(`Erro ${res.status}: ` + errText.substring(0, 200));
                }
                return;
            }

            const data = await res.json();
            if (data.success) {
                alert(id ? "Produto atualizado com sucesso!" : "Produto cadastrado com sucesso!");
                closeProductModal();
                loadProducts();
            } else {
                alert("Erro: " + data.message);
            }
        } catch (e) {
            console.error(e);
            alert("Erro de conexão ao salvar produto.");
        }
    }

    async function deleteProduct(id) {
        if (!confirm("Tem certeza que deseja excluir permanentemente este produto?")) {
            return;
        }

        try {
            const res = await fetch(`${API_URL}/produtos-admin/${id}`, {
                method: "DELETE",
                headers: {
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                }
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
                alert("Produto excluído com sucesso!");
                loadProducts();
            } else {
                alert("Erro: " + data.message);
            }
        } catch (e) {
            console.error(e);
            alert("Erro de conexão ao excluir produto.");
        }
    }
</script>
@endsection
