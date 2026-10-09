@extends('layouts.app')

@section('page_title', 'Gestão de Clientes e Parceiros')

@section('content')
<div class="card">
    <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
        <h3 style="margin:0;">Clientes e Parceiros Cadastrados</h3>
        <div style="display:flex; gap:10px;">
            <button type="button" class="btn" onclick="openImportModal()" style="font-size:13px; padding: 8px 16px; border: 1px solid var(--color-primary); color: var(--color-primary); background: #f0fdf4; border-radius: 6px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px;">
                📥 Importar CSV (Sankhya)
            </button>
            <button class="btn btn-primary" onclick="openCreateModal()" style="font-size:13px; padding: 8px 16px;">
                + Novo Cliente
            </button>
        </div>
    </div>

    <!-- Filter Bar -->
    <div style="padding: 15px 24px; background: #f8fafc; border-bottom: 1px solid var(--color-border); display: flex; gap: 15px; flex-wrap: wrap; align-items: center;">
        <div style="flex-grow: 1; min-width: 250px;">
            <input type="text" id="partner-search-input" class="form-control" placeholder="🔍 Buscar por Razão Social, CNPJ, Cód. Sankhya, Cidade..." onkeyup="filterPartnersDebounced()">
        </div>
        <div style="width: 160px;">
            <select id="partner-status-filter" class="form-control" onchange="loadPartners()">
                <option value="">Status: Todos</option>
                <option value="ativo">Ativos</option>
                <option value="inativo">Inativos</option>
            </select>
        </div>
        <div style="width: 100%; font-size: 12px; color: var(--color-text-muted); margin-top: 2px;">
            💡 Exibindo até 20 resultados por vez. Digite no campo de busca para encontrar qualquer cliente.
        </div>
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
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <label for="part-cnpj" class="form-label" style="margin-bottom:4px;">CNPJ / CPF</label>
                        <span id="part-cnpj-erp-badge" style="display:none; font-size:10px; color:#15803d; background:#dcfce7; padding:1px 6px; border-radius:4px; font-weight:700;">ERP</span>
                    </div>
                    <input type="text" id="part-cnpj" class="form-control" placeholder="Ex: 12345678000190">
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <label for="part-sankhya" class="form-label" style="margin-bottom:4px;">Código Sankhya</label>
                        <span id="part-erp-badge" style="display:none; font-size:10px; color:#15803d; background:#dcfce7; padding:1px 6px; border-radius:4px; font-weight:700;">ERP Sankhya</span>
                    </div>
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

    let searchTimer = null;

    document.addEventListener("DOMContentLoaded", () => {
        loadPartners();
    });

    function filterPartnersDebounced() {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
            loadPartners();
        }, 300);
    }

    async function loadPartners() {
        document.getElementById("loading-spinner").style.display = "block";
        const search = document.getElementById("partner-search-input") ? document.getElementById("partner-search-input").value : '';
        const status = document.getElementById("partner-status-filter") ? document.getElementById("partner-status-filter").value : '';

        const params = new URLSearchParams();
        if (search) params.append('search', search);
        if (status) params.append('status', status);

        try {
            const res = await fetch(`${API_URL}/clientes?${params.toString()}`);
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
                showToast("Erro ao carregar clientes: " + (data.error || 'Erro desconhecido'), "error");
            }
        } catch (e) {
            console.error(e);
            showToast("Erro de conexão ao buscar clientes.", "error");
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

            const pUf = (p.uf === '2' || (p.cidade && p.cidade.trim().toUpperCase() === 'UBERLANDIA')) ? 'MG' : (p.uf || '');
            const location = p.cidade ? `${p.cidade} / ${pUf}` : '-';

            body.innerHTML += `
                <tr>
                    <td>
                        <strong>${p.razao_social}</strong><br>
                        <span style="font-size:12px; color:var(--color-text-muted);">${p.nome_fantasia || '-'}</span>
                    </td>
                    <td><strong>${p.cnpj || '-'}</strong></td>
                    <td>
                        <strong>${p.codigo_sankhya}</strong>
                        <div style="font-size: 10px; color: #16a34a; font-weight:600;">Sankhya ERP</div>
                    </td>
                    <td>${contactInfo}</td>
                    <td>${location}</td>
                    <td class="text-center">
                        <span style="display:inline-block; font-size:11px; font-weight:700; padding:2px 8px; border-radius:50px; ${activeClass}">
                            ${activeText}
                        </span>
                    </td>
                    <td class="text-center" style="white-space: nowrap;">
                        <button class="btn btn-outline" style="padding: 5px 10px; font-size:11px; font-weight:600;" onclick="openEditModal(${p.id})">
                            Editar
                        </button>
                        <button class="btn btn-outline" style="padding: 5px 10px; font-size:11px; font-weight:600; color:${p.ativo ? '#ef4444' : '#16a34a'}; border-color:${p.ativo ? '#ef4444' : '#16a34a'}; margin-left:4px;" onclick="togglePartnerStatus(${p.id}, ${p.ativo ? 1 : 0})">
                            ${p.ativo ? 'Inativar' : 'Ativar'}
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
        document.getElementById("part-sankhya").readOnly = false;
        document.getElementById("part-cnpj").readOnly = false;
        document.getElementById("part-erp-badge").style.display = "none";
        document.getElementById("part-cnpj-erp-badge").style.display = "none";
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
        document.getElementById("part-cnpj").readOnly = true;
        document.getElementById("part-cnpj-erp-badge").style.display = "inline-block";
        document.getElementById("part-sankhya").value = partner.codigo_sankhya;
        document.getElementById("part-sankhya").readOnly = true;
        document.getElementById("part-erp-badge").style.display = "inline-block";
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
                showToast(isEdit ? "Dados do cliente atualizados com sucesso!" : "Novo cliente cadastrado com sucesso!", "success");
                closePartnerModal();
                loadPartners();
            } else {
                showToast("Erro ao salvar: " + (data.message || JSON.stringify(data.messages)), "error");
            }
        } catch (e) {
            console.error(e);
            showToast("Erro de conexão ao salvar cliente.", "error");
        }
    }

    async function togglePartnerStatus(id, currentStatus) {
        const newStatus = currentStatus ? 0 : 1;
        const actionLabel = currentStatus ? "inativar" : "ativar";
        const confirmed = await appConfirmModal(`${actionLabel.toUpperCase()} CLIENTE`, `Deseja realmente ${actionLabel} este cliente no sistema?`, actionLabel.toUpperCase(), currentStatus ? true : false);
        if (!confirmed) return;

        try {
            const res = await fetch(`${API_URL}/clientes/${id}`, {
                method: "PATCH",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ ativo: newStatus === 1 })
            });

            const data = await res.json();
            if (data.success) {
                showToast(`Cliente ${newStatus ? 'ativado' : 'inativado'} com sucesso!`, "success");
                loadPartners();
            } else {
                showToast("Erro ao atualizar status: " + data.message, "error");
            }
        } catch (e) {
            console.error(e);
            showToast("Erro de conexão ao alterar status do cliente.", "error");
        }
    }

    // --- Import Modal Logic ---
    function openImportModal() {
        document.getElementById("import-overlay").style.display = "block";
        document.getElementById("import-modal").style.display = "block";
        document.getElementById("import-file-input").value = "";
        document.getElementById("import-feedback").style.display = "none";
        document.getElementById("btn-submit-import").disabled = false;
        document.getElementById("btn-submit-import").innerText = "Iniciar Importação";
    }

    function closeImportModal() {
        document.getElementById("import-overlay").style.display = "none";
        document.getElementById("import-modal").style.display = "none";
    }

    async function submitPartnerImport(e) {
        e.preventDefault();
        const fileInput = document.getElementById("import-file-input");
        if (!fileInput.files || fileInput.files.length === 0) {
            showToast("Por favor, selecione um arquivo CSV ou TXT.", "warning");
            return;
        }

        const file = fileInput.files[0];
        const formData = new FormData();
        formData.append("arquivo", file);

        const btn = document.getElementById("btn-submit-import");
        const feedback = document.getElementById("import-feedback");
        
        btn.disabled = true;
        btn.innerText = "⏳ Importando clientes... Aguarde";
        feedback.style.display = "block";
        feedback.innerHTML = `<div style="color:var(--color-primary); font-weight:600;">🔄 Processando arquivo "${file.name}"... Por favor aguarde alguns instantes.</div>`;

        try {
            const res = await fetch(`${API_URL}/clientes/importar-arquivo`, {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: formData
            });

            const data = await res.json();

            if (data.success) {
                feedback.innerHTML = `
                    <div style="background:#f0fdf4; border:1px solid #86efac; border-radius:8px; padding:12px; color:#166534;">
                        <strong>✅ Sucesso!</strong> ${data.message}
                        <ul style="margin:6px 0 0 16px; padding:0; font-size:12px;">
                            <li><strong>Total de linhas:</strong> ${data.total}</li>
                            <li><strong>Novos clientes:</strong> ${data.criados}</li>
                            <li><strong>Atualizados:</strong> ${data.atualizados}</li>
                        </ul>
                    </div>
                `;
                btn.innerText = "Concluído!";
                setTimeout(() => {
                    loadPartners();
                }, 1000);
            } else {
                feedback.innerHTML = `
                    <div style="background:#fef2f2; border:1px solid #fca5a5; border-radius:8px; padding:12px; color:#991b1b;">
                        <strong>❌ Erro na importação:</strong> ${data.message || 'Falha ao processar arquivo.'}
                    </div>
                `;
                btn.disabled = false;
                btn.innerText = "Tentar Novamente";
            }
        } catch(err) {
            feedback.innerHTML = `
                <div style="background:#fef2f2; border:1px solid #fca5a5; border-radius:8px; padding:12px; color:#991b1b;">
                    <strong>❌ Erro de comunicação:</strong> ${err.message || 'Falha de rede ao enviar arquivo.'}
                </div>
            `;
            btn.disabled = false;
            btn.innerText = "Tentar Novamente";
        }
    }
</script>

<!-- Import Modal Sheet -->
<div id="import-overlay" class="sheet-overlay" onclick="closeImportModal()" style="display:none;"></div>
<div id="import-modal" class="sheet-drawer" style="display:none; max-width:550px;">
    <div class="sheet-header">
        <h3 style="margin: 0; color: var(--color-primary);">📥 Importar Clientes do Sankhya (CSV)</h3>
        <button type="button" class="sheet-close-btn" onclick="closeImportModal()">&times;</button>
    </div>
    <div class="sheet-body" style="padding: 20px;">
        <p style="font-size:13px; color:var(--color-text-muted); margin-top:0;">
            Exporte a lista de parceiros do Sankhya em formato <strong>CSV</strong> (separado por vírgula <code>,</code> ou ponto-e-vírgula <code>;</code>) e faça o upload abaixo.
        </p>

        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:12px; margin-bottom:16px; font-size:12px;">
            <strong style="color:var(--color-text);">Colunas reconhecidas automaticamente do Sankhya:</strong>
            <ul style="margin:6px 0 0 16px; padding:0; color:#475569; line-height:1.5;">
                <li><code>CODPARC</code> ou <code>CODIGO_SANKHYA</code> (Obrigatório)</li>
                <li><code>RAZAOSOCIAL</code> ou <code>RAZAO_SOCIAL</code></li>
                <li><code>NOMEPARC</code> ou <code>NOME_FANTASIA</code></li>
                <li><code>CGC_CPF</code> ou <code>CNPJ</code></li>
                <li><code>TELEFONE</code>, <code>EMAIL</code></li>
                <li><code>CIDADE</code> (ou <code>NOMECID</code>), <code>UF</code>, <code>BAIRRO</code></li>
            </ul>
        </div>

        <form onsubmit="submitPartnerImport(event)">
            <div class="form-group" style="margin-bottom:16px;">
                <label class="form-label" style="font-weight:600;">Selecione o arquivo CSV (.csv ou .txt):</label>
                <input type="file" id="import-file-input" class="form-control" accept=".csv,.txt" required style="padding:8px;">
            </div>

            <div id="import-feedback" style="display:none; margin-bottom:16px; font-size:13px;"></div>

            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="btn btn-secondary" onclick="closeImportModal()">Cancelar</button>
                <button type="submit" id="btn-submit-import" class="btn btn-primary">Iniciar Importação</button>
            </div>
        </form>
    </div>
</div>
@endsection

