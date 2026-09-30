@extends('layouts.app')

@section('page_title', 'Gestão de Usuários e Acessos')

@section('content')
<!-- Filter Bar for Sellers / Users -->
<div class="filters-bar-card" style="background:#ffffff; border-radius:14px; border:1px solid #e2e8f0; padding:14px 18px; margin-bottom:20px; box-shadow:0 1px 3px rgba(0,0,0,0.02);">
    <div style="display:flex; flex-wrap:wrap; gap:12px; align-items:center;">
        
        <!-- Search Name/Email -->
        <div style="position:relative; flex:1 1 220px; min-width:200px;">
            <span style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:14px;">🔍</span>
            <input type="text" id="search-input" placeholder="Buscar por Nome ou E-mail..." oninput="filterUsers()" style="width:100%; padding:9px 12px 9px 36px; border-radius:10px; border:1px solid #e2e8f0; font-size:13px; color:#1e293b; background:#f8fafc; outline:none;">
        </div>

        <!-- Filter Code Sankhya -->
        <div style="position:relative; flex:0 1 170px; min-width:140px;">
            <span style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:13px;">🔢</span>
            <input type="text" id="code-filter" placeholder="Cód. Sankhya..." oninput="filterUsers()" style="width:100%; padding:9px 12px 9px 34px; border-radius:10px; border:1px solid #e2e8f0; font-size:13px; color:#1e293b; background:#ffffff; outline:none;">
        </div>

        <!-- Filter Role (Papel) -->
        <select id="role-filter" onchange="filterUsers()" style="padding:9px 12px; border-radius:10px; border:1px solid #e2e8f0; font-size:13px; color:#334155; background:#ffffff; outline:none; cursor:pointer; min-width:160px;">
            <option value="">Todos os Papéis</option>
            <option value="representante">Representante Comercial</option>
            <option value="gestor">Gestor de Equipe</option>
            <option value="faturamento">Faturamento / Conf.</option>
            <option value="diretor">Diretor Comercial</option>
            <option value="administrador">Administrador Geral</option>
        </select>

        <!-- Filter Team -->
        <select id="team-filter" onchange="filterUsers()" style="padding:9px 12px; border-radius:10px; border:1px solid #e2e8f0; font-size:13px; color:#334155; background:#ffffff; outline:none; cursor:pointer; min-width:160px;">
            <option value="">Todas as Equipes</option>
            <!-- Dynamic options -->
        </select>

        <!-- Filter Status -->
        <select id="status-filter" onchange="filterUsers()" style="padding:9px 12px; border-radius:10px; border:1px solid #e2e8f0; font-size:13px; color:#334155; background:#ffffff; outline:none; cursor:pointer; min-width:130px;">
            <option value="">Todos os Status</option>
            <option value="1">Ativos</option>
            <option value="0">Bloqueados / Inativos</option>
        </select>

        <!-- Clear Button -->
        <button type="button" onclick="clearAllFilters()" style="color:#2563eb; font-size:13px; font-weight:600; background:none; border:none; cursor:pointer; padding:6px 10px; margin-left:auto;">
            Limpar Filtros
        </button>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div>
            <h3 style="margin:0;">Colaboradores Cadastrados</h3>
            <span id="users-count-info" style="font-size: 12px; color: #64748b; font-weight: 500;">Mostrando 0 de 0 colaboradores</span>
        </div>
        <button class="btn btn-primary" onclick="openCreateModal()" style="font-size:13px; padding: 8px 16px;">
            + Novo Usuário
        </button>
    </div>

    <!-- Table Users -->
    <div class="table-responsive">
        <table class="table-premium">
            <thead>
                <tr>
                    <th style="width: 25%;">Nome / E-mail</th>
                    <th style="width: 15%;">Papel</th>
                    <th style="width: 15%;">Código Sankhya</th>
                    <th style="width: 15%;">Equipe Vinculada</th>
                    <th style="width: 12%;">Alçada Desc.</th>
                    <th style="width: 10%; text-align: center;">Status</th>
                    <th style="width: 8%; text-align: center;">Ações</th>
                </tr>
            </thead>
            <tbody id="users-table-body">
                <!-- Dynamic rows -->
            </tbody>
        </table>
    </div>

    <!-- Empty state -->
    <div id="empty-state" style="display: none; text-align: center; padding: 40px 20px; color: #64748b;">
        Nenhum colaborador encontrado com os filtros aplicados.
    </div>

    <!-- Loading spinner -->
    <div id="loading-spinner" style="text-align: center; padding: 40px 20px;">
        <div style="border: 3px solid rgba(15,81,50,0.1); border-top: 3px solid var(--color-primary); border-radius: 50%; width: 30px; height: 30px; animation: spin 1s linear infinite; margin: 0 auto;"></div>
    </div>
</div>

<!-- Create / Edit User Sheet Drawer -->
<div id="user-overlay" class="sheet-overlay" onclick="closeUserModal()"></div>
<div id="user-modal" class="sheet-drawer">
    <div class="sheet-header">
        <h3 id="modal-title" style="margin: 0; color: var(--color-primary);">Cadastrar Novo Usuário</h3>
        <button type="button" class="sheet-close-btn" onclick="closeUserModal()">&times;</button>
    </div>
    <div class="sheet-body">
        
        <form id="user-form" onsubmit="saveUser(event)">
            <input type="hidden" id="user-id">
            
            <div class="grid-2" style="gap: 12px; margin-bottom: 12px;">
                <div class="form-group" style="margin-bottom:0;">
                    <label for="user-nome" class="form-label">Nome Completo</label>
                    <input type="text" id="user-nome" class="form-control" required placeholder="Ex: Roberto Silva">
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label for="user-email" class="form-label">E-mail (Acesso)</label>
                    <input type="email" id="user-email" class="form-control" required placeholder="Ex: roberto@zecotacao.com.br">
                </div>
            </div>

            <div class="grid-2" style="gap: 12px; margin-bottom: 12px;">
                <div class="form-group" style="margin-bottom:0;">
                    <label for="user-password" class="form-label">Senha <span id="pwd-help" style="font-size:10px; color:var(--color-text-muted);"></span></label>
                    <input type="password" id="user-password" class="form-control" placeholder="Mínimo 6 caracteres">
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label for="user-papel" class="form-label">Papel / Perfil</label>
                    <select id="user-papel" class="form-control" onchange="onPapelChange(this.value)" required>
                        <option value="representante">Representante Comercial</option>
                        <option value="gestor">Gestor de Equipe</option>
                        <option value="faturamento">Faturamento / Conf.</option>
                        <option value="diretor">Diretor Comercial</option>
                        <option value="administrador">Administrador Geral</option>
                    </select>
                </div>
            </div>

            <div class="grid-2" style="gap: 12px; margin-bottom: 12px;">
                <div class="form-group" style="margin-bottom:0;">
                    <label for="user-sankhya" class="form-label">Código Sankhya</label>
                    <input type="text" id="user-sankhya" class="form-control" placeholder="Ex: REP031">
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label for="user-telefone" class="form-label">Telefone / WhatsApp</label>
                    <input type="text" id="user-telefone" class="form-control" placeholder="Ex: (11) 99999-8888">
                </div>
            </div>

            <div class="grid-2" style="gap: 12px; margin-bottom: 12px;">
                <div class="form-group" style="margin-bottom:0;" id="limit-container">
                    <label for="user-limite" class="form-label">Limite Desconto Alçada (%)</label>
                    <input type="number" id="user-limite" class="form-control" min="0" max="100" step="0.01" value="0.00">
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label for="user-equipe" class="form-label">Equipe Relacionada</label>
                    <select id="user-equipe" class="form-control">
                        <option value="">Nenhuma equipe</option>
                        <!-- Dynamic teams -->
                    </select>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 16px;">
                <label for="user-ativo" class="form-label">Status do Acesso</label>
                <select id="user-ativo" class="form-control">
                    <option value="1">Ativo (Permitir login / operações)</option>
                    <option value="0">Bloqueado / Inativo</option>
                </select>
            </div>

            <div style="margin-bottom: 16px; padding: 15px; border: 1px solid var(--color-border); border-radius: 12px; background-color: #f8fafc;">
                <label style="display:flex; align-items:center; gap:8px; font-size:13px; font-weight:600; cursor:pointer; color: var(--color-text);">
                    <input type="checkbox" id="user-acesso-chat" value="1">
                    Permitir Acesso ao Histórico de Conversas (WhatsApp Audit)
                </label>
            </div>

            <div style="margin-bottom: 24px; padding: 15px; border: 1px solid var(--color-border); border-radius: 12px; background-color: #f8fafc;">
                <h5 style="margin-bottom: 12px; color: var(--color-primary); font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Permissões de Visualização do Dashboard</h5>
                
                <div style="display:flex; flex-direction:column; gap:10px;">
                    <label style="display:flex; align-items:center; gap:8px; font-size:13px; font-weight:500; cursor:pointer;">
                        <input type="checkbox" id="user-perm-kpis" value="1">
                        Ver KPIs Gerais (Valores Totais / Taxa Conversão)
                    </label>
                    <label style="display:flex; align-items:center; gap:8px; font-size:13px; font-weight:500; cursor:pointer;">
                        <input type="checkbox" id="user-perm-timeline" value="1">
                        Ver Gráfico de Evolução Temporal (Cotações vs Faturado)
                    </label>
                    <label style="display:flex; align-items:center; gap:8px; font-size:13px; font-weight:500; cursor:pointer;">
                        <input type="checkbox" id="user-perm-status" value="1">
                        Ver Gráfico de Distribuição por Status (Rosca)
                    </label>
                    <label style="display:flex; align-items:center; gap:8px; font-size:13px; font-weight:500; cursor:pointer;">
                        <input type="checkbox" id="user-perm-ranking" value="1">
                        Ver Ranking de Representantes (Melhores Vendedores)
                    </label>
                    <label style="display:flex; align-items:center; gap:8px; font-size:13px; font-weight:500; cursor:pointer;">
                        <input type="checkbox" id="user-perm-clients" value="1">
                        Ver Top Clientes (Maiores Compradores)
                    </label>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px;">
                <button type="button" class="btn btn-outline" onclick="closeUserModal()">Cancelar</button>
                <button type="submit" class="btn btn-primary">Salvar Usuário</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const API_URL = "{{ url('/api/v1') }}";
    let usersList = [];
    let teamsList = [];

    document.addEventListener("DOMContentLoaded", () => {
        loadData();
    });

    async function loadData() {
        try {
            const res = await fetch(`${API_URL}/usuarios`);
            
            if (res.status === 401 || res.status === 403) {
                window.location.href = "{{ url('/login') }}";
                return;
            }

            const data = await res.json();
            document.getElementById("loading-spinner").style.display = "none";

            if (data.success) {
                usersList = data.data.users;
                teamsList = data.data.teams;

                renderUsers();
                populateTeamsDropdown();
            } else {
                alert("Erro ao carregar dados: " + data.error);
            }
        } catch (e) {
            console.error(e);
            alert("Erro de conexão ao buscar usuários.");
        }
    }

    function renderUsers(list = usersList) {
        const body = document.getElementById("users-table-body");
        const countInfo = document.getElementById("users-count-info");
        const emptyState = document.getElementById("empty-state");
        body.innerHTML = "";

        if (countInfo) {
            countInfo.innerText = `Mostrando ${list.length} de ${usersList.length} colaboradores`;
        }

        if (list.length === 0) {
            if (emptyState) emptyState.style.display = "block";
            return;
        } else {
            if (emptyState) emptyState.style.display = "none";
        }

        list.forEach(u => {
            const activeClass = u.ativo ? "background-color:#d1fae5; color:#065f46;" : "background-color:#fee2e2; color:#991b1b;";
            const activeText = u.ativo ? "Ativo" : "Inativo";
            
            // Format Role
            let roleLabel = u.papel.toUpperCase();
            if (u.papel === 'representante') roleLabel = "REPRESENTANTE";
            else if (u.papel === 'gestor') roleLabel = "GESTOR DE EQUIPE";
            else if (u.papel === 'diretor') roleLabel = "DIRETOR";
            else if (u.papel === 'faturamento') roleLabel = "FATURAMENTO";

            const limitText = u.papel === 'gestor' ? `${parseFloat(u.limite_desconto_percentual)}%` : '-';
            const teamName = u.equipe ? u.equipe.nome : '-';

            body.innerHTML += `
                <tr>
                    <td>
                        <strong>${u.nome}</strong><br>
                        <span style="font-size:12px; color:var(--color-text-muted);">${u.email}</span>
                    </td>
                    <td><span class="user-role-label" style="background-color: var(--color-primary-hover);">${roleLabel}</span></td>
                    <td><strong>${u.codigo_sankhya || '-'}</strong></td>
                    <td>${teamName}</td>
                    <td><strong>${limitText}</strong></td>
                    <td class="text-center">
                        <span style="display:inline-block; font-size:11px; font-weight:700; padding:2px 8px; border-radius:50px; ${activeClass}">
                            ${activeText}
                        </span>
                    </td>
                    <td class="text-center" style="white-space: nowrap;">
                        <button class="btn btn-outline" style="padding: 6px 12px; font-size:11px;" onclick="openEditModal(${u.id})">
                            Editar
                        </button>
                        <button class="btn btn-outline" style="padding: 6px 12px; font-size:11px; color:var(--status-recusada); border-color:var(--status-recusada); margin-left:4px;" onclick="deleteUser(${u.id})">
                            Excluir
                        </button>
                    </td>
                </tr>
            `;
        });
    }

    function filterUsers() {
        const search = (document.getElementById("search-input").value || "").toLowerCase();
        const codeSearch = (document.getElementById("code-filter").value || "").toLowerCase();
        const role = document.getElementById("role-filter").value;
        const teamId = document.getElementById("team-filter").value;
        const status = document.getElementById("status-filter").value;

        const filtered = usersList.filter(u => {
            // Text search (Nome or Email)
            const nome = (u.nome || "").toLowerCase();
            const email = (u.email || "").toLowerCase();
            const matchesSearch = search === "" || nome.includes(search) || email.includes(search);

            // Code Sankhya search
            const sankhyaCode = (u.codigo_sankhya || "").toLowerCase();
            const matchesCode = codeSearch === "" || sankhyaCode.includes(codeSearch);

            // Role search
            const matchesRole = role === "" || u.papel === role;

            // Team search
            const matchesTeam = teamId === "" || (u.equipe_id && u.equipe_id == teamId);

            // Status search
            const matchesStatus = status === "" || (status === "1" ? u.ativo : !u.ativo);

            return matchesSearch && matchesCode && matchesRole && matchesTeam && matchesStatus;
        });

        renderUsers(filtered);
    }

    function clearAllFilters() {
        document.getElementById("search-input").value = "";
        document.getElementById("code-filter").value = "";
        document.getElementById("role-filter").value = "";
        document.getElementById("team-filter").value = "";
        document.getElementById("status-filter").value = "";
        renderUsers(usersList);
    }

    function populateTeamsDropdown() {
        const modalSelect = document.getElementById("user-equipe");
        const filterSelect = document.getElementById("team-filter");
        
        modalSelect.innerHTML = '<option value="">Nenhuma equipe</option>';
        if (filterSelect) {
            filterSelect.innerHTML = '<option value="">Todas as Equipes</option>';
        }

        teamsList.forEach(t => {
            modalSelect.innerHTML += `<option value="${t.id}">${t.nome}</option>`;
            if (filterSelect) {
                filterSelect.innerHTML += `<option value="${t.id}">${t.nome}</option>`;
            }
        });
    }

    function getDefaultPermissions(papel) {
        if (papel === 'administrador' || papel === 'diretor') {
            return { ver_kpis: true, ver_evolucao_temporal: true, ver_status_dist: true, ver_ranking_vendedores: true, ver_top_clientes: true };
        } else if (papel === 'gestor') {
            return { ver_kpis: true, ver_evolucao_temporal: true, ver_status_dist: true, ver_ranking_vendedores: true, ver_top_clientes: true };
        } else if (papel === 'faturamento') {
            return { ver_kpis: true, ver_evolucao_temporal: false, ver_status_dist: true, ver_ranking_vendedores: false, ver_top_clientes: false };
        } else {
            // representante
            return { ver_kpis: false, ver_evolucao_temporal: true, ver_status_dist: false, ver_ranking_vendedores: false, ver_top_clientes: false };
        }
    }

    function onPapelChange(role) {
        const limitInput = document.getElementById("user-limite");
        // Limit discount percentage is only valid for Gestor
        if (role === 'gestor') {
            limitInput.disabled = false;
            document.getElementById("limit-container").style.opacity = "1";
        } else {
            limitInput.disabled = true;
            limitInput.value = "0.00";
            document.getElementById("limit-container").style.opacity = "0.5";
        }

        // Apply defaults only in Create Mode
        if (document.getElementById("user-id").value === "") {
            const defaults = getDefaultPermissions(role);
            document.getElementById("user-perm-kpis").checked = defaults.ver_kpis;
            document.getElementById("user-perm-timeline").checked = defaults.ver_evolucao_temporal;
            document.getElementById("user-perm-status").checked = defaults.ver_status_dist;
            document.getElementById("user-perm-ranking").checked = defaults.ver_ranking_vendedores;
            document.getElementById("user-perm-clients").checked = defaults.ver_top_clientes;
        }
    }

    // Modal Control
    function openCreateModal() {
        document.getElementById("user-id").value = "";
        document.getElementById("modal-title").innerText = "Cadastrar Novo Usuário";
        document.getElementById("pwd-help").innerText = "(Obrigatório)";
        document.getElementById("user-form").reset();
        
        document.getElementById("user-email").disabled = false;
        document.getElementById("user-password").required = true;
        
        document.getElementById("user-acesso-chat").checked = false;
        
        onPapelChange('representante');
        document.getElementById("user-overlay").classList.add("open");
        document.getElementById("user-modal").classList.add("open");
    }

    function openEditModal(userId) {
        const user = usersList.find(u => u.id === userId);
        if (!user) return;

        document.getElementById("user-id").value = user.id;
        document.getElementById("modal-title").innerText = "Editar Colaborador";
        document.getElementById("pwd-help").innerText = "(Preencher apenas se desejar redefinir)";
        
        document.getElementById("user-nome").value = user.nome;
        document.getElementById("user-email").value = user.email;
        document.getElementById("user-email").disabled = true; // email login keys are locked
        
        document.getElementById("user-password").required = false;
        document.getElementById("user-password").value = "";
        
        document.getElementById("user-papel").value = user.papel;
        document.getElementById("user-sankhya").value = user.codigo_sankhya || "";
        document.getElementById("user-telefone").value = user.telefone || "";
        document.getElementById("user-limite").value = parseFloat(user.limite_desconto_percentual || 0);
        document.getElementById("user-equipe").value = user.equipe_id || "";
        document.getElementById("user-ativo").value = user.ativo ? "1" : "0";
        document.getElementById("user-acesso-chat").checked = !!user.acesso_chat;

        const perms = user.permissoes_dashboard || getDefaultPermissions(user.papel);
        document.getElementById("user-perm-kpis").checked = !!perms.ver_kpis;
        document.getElementById("user-perm-timeline").checked = !!perms.ver_evolucao_temporal;
        document.getElementById("user-perm-status").checked = !!perms.ver_status_dist;
        document.getElementById("user-perm-ranking").checked = !!perms.ver_ranking_vendedores;
        document.getElementById("user-perm-clients").checked = !!perms.ver_top_clientes;

        onPapelChange(user.papel);
        document.getElementById("user-overlay").classList.add("open");
        document.getElementById("user-modal").classList.add("open");
    }

    function closeUserModal() {
        document.getElementById("user-overlay").classList.remove("open");
        document.getElementById("user-modal").classList.remove("open");
    }

    // Submit actions
    async function saveUser(e) {
        e.preventDefault();

        const id = document.getElementById("user-id").value;
        const isEdit = id !== "";

        const payload = {
            nome: document.getElementById("user-nome").value,
            email: document.getElementById("user-email").value,
            papel: document.getElementById("user-papel").value,
            codigo_sankhya: document.getElementById("user-sankhya").value || null,
            telefone: document.getElementById("user-telefone").value || null,
            limite_desconto_percentual: parseFloat(document.getElementById("user-limite").value) || 0.00,
            equipe_id: document.getElementById("user-equipe").value ? parseInt(document.getElementById("user-equipe").value) : null,
            ativo: document.getElementById("user-ativo").value === "1",
            acesso_chat: document.getElementById("user-acesso-chat").checked,
            permissoes_dashboard: {
                ver_kpis: document.getElementById("user-perm-kpis").checked,
                ver_evolucao_temporal: document.getElementById("user-perm-timeline").checked,
                ver_status_dist: document.getElementById("user-perm-status").checked,
                ver_ranking_vendedores: document.getElementById("user-perm-ranking").checked,
                ver_top_clientes: document.getElementById("user-perm-clients").checked
            }
        };

        const password = document.getElementById("user-password").value;
        if (password) {
            payload.password = password;
        }

        const url = isEdit ? `${API_URL}/usuarios/${id}` : `${API_URL}/usuarios`;
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
                alert(isEdit ? "Dados do usuário atualizados!" : "Novo usuário cadastrado!");
                closeUserModal();
                loadData();
            } else {
                alert("Erro ao salvar: " + (data.message || JSON.stringify(data.messages)));
            }
        } catch (e) {
            alert("Erro de conexão.");
        }
    }

    async function deleteUser(id) {
        if (id === CURRENT_USER.id) {
            alert("Você não pode excluir a sua própria conta.");
            return;
        }

        if (!confirm("Deseja realmente excluir este usuário permanentemente?")) return;

        try {
            const res = await fetch(`${API_URL}/usuarios/${id}`, {
                method: "DELETE",
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            const data = await res.json();
            if (data.success) {
                alert("Usuário excluído com sucesso!");
                loadData();
            } else {
                alert("Erro ao excluir: " + data.message);
            }
        } catch (e) {
            alert("Erro de conexão.");
        }
    }
</script>
@endsection
