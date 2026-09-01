@extends('layouts.app')

@section('page_title', 'Gestão de Clientes e Parceiros')

@section('content')
<div class="card">
    <div class="card-header">
        <h3>Clientes e Parceiros Cadastrados</h3>
        <button class="btn btn-primary" onclick="openCreateModal()" style="font-size:13px; padding: 8px 16px;">
            + Novo Cliente
        </button>
    </div>

    <!-- Table Clientes -->
    <div class="table-responsive">
        <table class="table-premium">
            <thead>
                <tr>
                    <th style="width: 25%;">Razão Social / Nome Fantasia</th>
                    <th style="width: 15%;">CNPJ</th>
                    <th style="width: 12%;">Cód. Sankhya</th>
                    <th style="width: 20%;">Contato</th>
                    <th style="width: 12%;">Localidade</th>
                    <th style="width: 8%; text-align: center;">Status</th>
                    <th style="width: 8%; text-align: center;">Ações</th>
                </tr>
            </thead>
            <tbody id="partners-table-body">
                <!-- Dynamic rows -->
            </tbody>
        </table>
    </div>

    <!-- Loading spinner -->
    <div id="loading-spinner" style="text-align: center; padding: 40px 20px;">
        <div style="border: 3px solid rgba(15,81,50,0.1); border-top: 3px solid var(--color-primary); border-radius: 50%; width: 30px; height: 30px; animation: spin 1s linear infinite; margin: 0 auto;"></div>
    </div>
</div>

<!-- Create / Edit Client Sheet Drawer -->
<div id="partner-overlay" class="sheet-overlay" onclick="closePartnerModal()"></div>
<div id="partner-modal" class="sheet-drawer">
    <div class="sheet-header">
        <h3 id="modal-title" style="margin: 0; color: var(--color-primary);">Cadastrar Novo Cliente</h3>
        <button type="button" class="sheet-close-btn" onclick="closePartnerModal()">&times;</button>
    </div>
    <div class="sheet-body">
        
        <form id="partner-form" onsubmit="savePartner(event)">
            <input type="hidden" id="partner-id">
            
            <div class="grid-2" style="gap: 12px; margin-bottom: 12px;">
                <div class="form-group" style="margin-bottom:0;">
                    <label for="part-razao" class="form-label">Razão Social</label>
                    <input type="text" id="part-razao" class="form-control" required placeholder="Ex: Clínica Vet Pet Feliz Ltda">
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label for="part-fantasia" class="form-label">Nome Fantasia</label>
                    <input type="text" id="part-fantasia" class="form-control" placeholder="Ex: Pet Feliz">
                </div>
            </div>

            <div class="grid-2" style="gap: 12px; margin-bottom: 12px;">
                <div class="form-group" style="margin-bottom:0;">
                    <label for="part-cnpj" class="form-label">CNPJ (Apenas números)</label>
                    <input type="text" id="part-cnpj" class="form-control" placeholder="Ex: 12345678000190">
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label for="part-sankhya" class="form-label">Código Sankhya</label>
                    <input type="text" id="part-sankhya" class="form-control" required placeholder="Ex: PAR001">
                </div>
            </div>

            <div class="grid-2" style="gap: 12px; margin-bottom: 12px;">
                <div class="form-group" style="margin-bottom:0;">
                    <label for="part-telefone" class="form-label">Telefone</label>
                    <input type="text" id="part-telefone" class="form-control" placeholder="Ex: (11) 3222-1111">
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label for="part-email" class="form-label">E-mail</label>
                    <input type="email" id="part-email" class="form-control" placeholder="Ex: contato@petfeliz.com.br">
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 12px;">
                <label for="part-endereco" class="form-label">Endereço Completo</label>
                <input type="text" id="part-endereco" class="form-control" placeholder="Ex: Rua das Flores, 123">
            </div>

            <div class="grid-3" style="gap: 12px; margin-bottom: 12px;">
                <div class="form-group" style="margin-bottom:0;">
                    <label for="part-cidade" class="form-label">Cidade</label>
                    <input type="text" id="part-cidade" class="form-control" placeholder="Ex: São Paulo">
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label for="part-uf" class="form-label">UF</label>
                    <input type="text" id="part-uf" class="form-control" maxlength="2" placeholder="Ex: SP">
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label for="part-cep" class="form-label">CEP</label>
                    <input type="text" id="part-cep" class="form-control" placeholder="Ex: 01234-000">
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 24px;">
                <label for="part-ativo" class="form-label">Status do Cliente</label>
                <select id="part-ativo" class="form-control">
                    <option value="1">Ativo (Permitir cotações)</option>
                    <option value="0">Bloqueado / Inativo</option>
                </select>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px;">
                <button type="button" class="btn btn-outline" onclick="closePartnerModal()">Cancelar</button>
                <button type="submit" class="btn btn-primary">Salvar Cliente</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const API_URL = "{{ url('/api/v1') }}";
    let partnersList = [];

    document.addEventListener("DOMContentLoaded", () => {
        loadPartners();
    });

    async function loadPartners() {
        try {
            const res = await fetch(`${API_URL}/clientes`);
            if (res.status === 401 || res.status === 403) {
                window.location.href = "{{ url('/login') }}";
                return;
            }

            const data = await res.json();
            document.getElementById("loading-spinner").style.display = "none";

            if (data.success) {
                partnersList = data.data;
                renderPartners();
            } else {
                alert("Erro ao carregar clientes: " + data.error);
            }
        } catch (e) {
            console.error(e);
            alert("Erro de conexão ao buscar clientes.");
        }
    }

    function renderPartners() {
        const body = document.getElementById("partners-table-body");
        body.innerHTML = "";

        partnersList.forEach(p => {
            const activeClass = p.ativo ? "background-color:#d1fae5; color:#065f46;" : "background-color:#fee2e2; color:#991b1b;";
            const activeText = p.ativo ? "Ativo" : "Inativo";
            
            const contactInfo = `
                <strong>Tel:</strong> ${p.telefone || '-'}<br>
                <span style="font-size:11px; color:var(--color-text-muted);">${p.email || '-'}</span>
            `;

            const location = p.cidade ? `${p.cidade} / ${p.uf || ''}` : '-';

            body.innerHTML += `
                <tr>
                    <td>
                        <strong>${p.razao_social}</strong><br>
                        <span style="font-size:12px; color:var(--color-text-muted);">${p.nome_fantasia || '-'}</span>
                    </td>
                    <td><strong>${p.cnpj || '-'}</strong></td>
                    <td><strong>${p.codigo_sankhya}</strong></td>
                    <td>${contactInfo}</td>
                    <td>${location}</td>
                    <td class="text-center">
                        <span style="display:inline-block; font-size:11px; font-weight:700; padding:2px 8px; border-radius:50px; ${activeClass}">
                            ${activeText}
                        </span>
                    </td>
                    <td class="text-center" style="white-space: nowrap;">
                        <button class="btn btn-outline" style="padding: 6px 12px; font-size:11px;" onclick="openEditModal(${p.id})">
                            Editar
                        </button>
                        <button class="btn btn-outline" style="padding: 6px 12px; font-size:11px; color:var(--status-recusada); border-color:var(--status-recusada); margin-left:4px;" onclick="deletePartner(${p.id})">
                            Excluir
                        </button>
                    </td>
                </tr>
            `;
        });
    }

    function openCreateModal() {
        document.getElementById("partner-id").value = "";
        document.getElementById("modal-title").innerText = "Cadastrar Novo Cliente";
        document.getElementById("partner-form").reset();
        document.getElementById("partner-overlay").classList.add("open");
        document.getElementById("partner-modal").classList.add("open");
    }

    function openEditModal(partnerId) {
        const partner = partnersList.find(p => p.id === partnerId);
        if (!partner) return;

        document.getElementById("partner-id").value = partner.id;
        document.getElementById("modal-title").innerText = "Editar Cliente";
        
        document.getElementById("part-razao").value = partner.razao_social;
        document.getElementById("part-fantasia").value = partner.nome_fantasia || "";
        document.getElementById("part-cnpj").value = partner.cnpj || "";
        document.getElementById("part-sankhya").value = partner.codigo_sankhya;
        document.getElementById("part-telefone").value = partner.telefone || "";
        document.getElementById("part-email").value = partner.email || "";
        document.getElementById("part-endereco").value = partner.endereco || "";
        document.getElementById("part-cidade").value = partner.cidade || "";
        document.getElementById("part-uf").value = partner.uf || "";
        document.getElementById("part-cep").value = partner.cep || "";
        document.getElementById("part-ativo").value = partner.ativo ? "1" : "0";

        document.getElementById("partner-overlay").classList.add("open");
        document.getElementById("partner-modal").classList.add("open");
    }

    function closePartnerModal() {
        document.getElementById("partner-overlay").classList.remove("open");
        document.getElementById("partner-modal").classList.remove("open");
    }

    async function savePartner(e) {
        e.preventDefault();

        const id = document.getElementById("partner-id").value;
        const isEdit = id !== "";

        const payload = {
            razao_social: document.getElementById("part-razao").value,
            nome_fantasia: document.getElementById("part-fantasia").value || null,
            cnpj: document.getElementById("part-cnpj").value || null,
            codigo_sankhya: document.getElementById("part-sankhya").value,
            telefone: document.getElementById("part-telefone").value || null,
            email: document.getElementById("part-email").value || null,
            endereco: document.getElementById("part-endereco").value || null,
            cidade: document.getElementById("part-cidade").value || null,
            uf: document.getElementById("part-uf").value || null,
            cep: document.getElementById("part-cep").value || null,
            ativo: document.getElementById("part-ativo").value === "1"
        };

        const url = isEdit ? `${API_URL}/clientes/${id}` : `${API_URL}/clientes`;
        const method = isEdit ? "PATCH" : "POST";

        try {
            const res = await fetch(url, {
                method: method,
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(payload)
            });

            const data = await res.json();
            if (data.success) {
                alert(isEdit ? "Dados do cliente atualizados!" : "Novo cliente cadastrado!");
                closePartnerModal();
                loadPartners();
            } else {
                alert("Erro ao salvar: " + (data.message || JSON.stringify(data.messages)));
            }
        } catch (e) {
            alert("Erro de conexão.");
        }
    }

    async function deletePartner(id) {
        if (!confirm("Deseja realmente excluir este cliente permanentemente?")) return;

        try {
            const res = await fetch(`${API_URL}/clientes/${id}`, {
                method: "DELETE",
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            const data = await res.json();
            if (data.success) {
                alert("Cliente excluído com sucesso!");
                loadPartners();
            } else {
                alert("Erro ao excluir: " + data.message);
            }
        } catch (e) {
            alert("Erro de conexão.");
        }
    }
</script>
@endsection
