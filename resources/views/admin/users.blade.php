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

<!-- View Mode Switcher (Point 7) -->
<div style="display:flex; gap:8px; margin-bottom:16px;">
    <button type="button" class="btn" id="view-tab-table" onclick="switchUsersView('table')" style="background:#2563eb; color:#ffffff; font-size:13px; font-weight:700; padding:8px 16px; border-radius:8px; border:none; cursor:pointer;">
        📋 Lista de Colaboradores
    </button>
    <button type="button" class="btn" id="view-tab-teams" onclick="switchUsersView('teams')" style="background:#ffffff; color:#475569; border:1px solid #e2e8f0; font-size:13px; font-weight:700; padding:8px 16px; border-radius:8px; cursor:pointer;">
        👥 Visão por Equipes (Gestores &amp; Representantes)
    </button>
</div>

<div class="card" id="users-table-container">
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

<!-- Teams Board Container (Point 7) -->
<div id="users-teams-board" style="display: none; margin-bottom: 24px;">
    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px 16px; margin-bottom: 16px; font-size: 13px; color: #475569; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;">
        <div style="display:flex; align-items:center; gap:8px;">
            <span>💡</span>
            <span><strong>Hierarquia de Vendas:</strong> Cada equipe tem seu Gestor no topo e seus Representantes vinculados. Arraste e solte representantes entre as colunas para reatribuir equipes em tempo real.</span>
        </div>
        <span style="font-size:11px; background:#e0f2fe; color:#0369a1; padding:3px 8px; border-radius:6px; font-weight:700;">Drag &amp; Drop Ativo</span>
    </div>

    <div id="teams-grid-container" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 16px; align-items: flex-start;">
        <!-- Teams rendered dynamically -->
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
                renderTeamsBoard();
                populateTeamsDropdown();
            } else {
                showToast("Erro ao carregar dados: " + data.error, "error");
            }
        } catch (e) {
            console.error(e);
            showToast("Erro de conexão ao buscar usuários.", "error");
        }
    }

    let currentUsersView = 'table';

    function switchUsersView(mode) {
        currentUsersView = mode;
        const btnTable = document.getElementById("view-tab-table");
        const btnTeams = document.getElementById("view-tab-teams");
        const tableContainer = document.getElementById("users-table-container");
        const teamsBoard = document.getElementById("users-teams-board");

        if (mode === 'table') {
            btnTable.style.background = "#2563eb";
            btnTable.style.color = "#ffffff";
            btnTable.style.border = "none";
            btnTeams.style.background = "#ffffff";
            btnTeams.style.color = "#475569";
            btnTeams.style.border = "1px solid #e2e8f0";
            if (tableContainer) tableContainer.style.display = "block";
            if (teamsBoard) teamsBoard.style.display = "none";
        } else {
            btnTeams.style.background = "#2563eb";
            btnTeams.style.color = "#ffffff";
            btnTeams.style.border = "none";
            btnTable.style.background = "#ffffff";
            btnTable.style.color = "#475569";
            btnTable.style.border = "1px solid #e2e8f0";
            if (tableContainer) tableContainer.style.display = "none";
            if (teamsBoard) teamsBoard.style.display = "block";
            renderTeamsBoard();
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
            const toggleColor = u.ativo ? '#ef4444' : '#16a34a';
            const toggleText = u.ativo ? 'Inativar' : 'Ativar';

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
                        <button class="btn btn-outline" style="padding: 5px 10px; font-size:11px; font-weight:600;" onclick="openEditModal(${u.id})">
                            Editar
                        </button>
                        <button class="btn btn-outline" style="padding: 5px 10px; font-size:11px; font-weight:600; color:${toggleColor}; border-color:${toggleColor}; margin-left:4px;" onclick="toggleUserStatus(${u.id}, ${u.ativo ? 1 : 0})">
                            ${toggleText}
                        </button>
                    </td>
                </tr>
            `;
        });
    }

    function renderTeamsBoard() {
        const grid = document.getElementById("teams-grid-container");
        if (!grid) return;
        grid.innerHTML = "";

        teamsList.forEach(t => {
            const teamUsers = usersList.filter(u => u.equipe_id == t.id);
            const manager = usersList.find(u => u.id == t.gestor_id) || teamUsers.find(u => u.papel === 'gestor');
            const reps = teamUsers.filter(u => u.papel === 'representante');

            const card = document.createElement("div");
            card.className = "team-board-card";
            card.style.cssText = "background:#ffffff; border:1px solid #e2e8f0; border-radius:14px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,0.04); display:flex; flex-direction:column;";
            card.dataset.teamId = t.id;

            card.innerHTML = `
                <div style="background:#f8fafc; border-bottom:1px solid #e2e8f0; padding:12px 16px; display:flex; justify-content:space-between; align-items:center;">
                    <div style="font-weight:700; font-size:14px; color:#0f172a;">${t.nome}</div>
                    <span style="font-size:11px; background:#e2e8f0; color:#475569; padding:2px 8px; border-radius:999px; font-weight:700;">${reps.length} rep(s)</span>
                </div>

                <!-- Gestor da Equipe (No Topo) -->
                <div style="padding:12px 16px; background:#f0fdf4; border-bottom:1px solid #bbf7d0; display:flex; align-items:center; gap:10px;">
                    <div style="width:32px; height:32px; border-radius:50%; background:#16a34a; color:#ffffff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:13px; flex-shrink:0;">
                        ${manager ? manager.nome.charAt(0).toUpperCase() : '?'}
                    </div>
                    <div style="min-width:0; flex-grow:1;">
                        <div style="font-size:11px; font-weight:700; color:#15803d; text-transform:uppercase; letter-spacing:0.3px;">Gestor da Equipe</div>
                        <div style="font-size:13px; font-weight:700; color:#166534; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                            ${manager ? manager.nome : 'Sem gestor vinculado'}
                        </div>
                    </div>
                </div>

                <!-- Lista de Representantes (Dropzone) -->
                <div class="team-reps-dropzone" 
                     ondragover="allowDropUser(event)" 
                     ondragleave="leaveDropUser(event)"
                     ondrop="dropUser(event, ${t.id})" 
                     style="padding:12px 16px; min-height:140px; display:flex; flex-direction:column; gap:8px; flex-grow:1; background:#ffffff; transition:background 0.2s;">
                    ${reps.length === 0 ? '<div style="font-size:12px; color:#94a3b8; text-align:center; padding:24px 8px; border:1px dashed #cbd5e1; border-radius:8px;">Arraste representantes para cá</div>' : ''}
                    ${reps.map(r => `
                        <div class="rep-card-draggable" 
                             draggable="true" 
                             ondragstart="dragUser(event, ${r.id})"
                             style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:8px 12px; display:flex; align-items:center; justify-content:space-between; cursor:grab; transition:all 0.15s;">
                            <div style="display:flex; align-items:center; gap:8px; min-width:0;">
                                <span style="color:#94a3b8; font-size:13px;">⠿</span>
                                <div style="min-width:0;">
                                    <div style="font-size:12.5px; font-weight:600; color:#1e293b; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">${r.nome}</div>
                                    <div style="font-size:10.5px; color:#64748b;">${r.codigo_sankhya ? 'Cód: ' + r.codigo_sankhya : r.email}</div>
                                </div>
                            </div>
                            <span style="font-size:10px; font-weight:700; padding:2px 6px; border-radius:4px; ${r.ativo ? 'background:#dcfce7; color:#15803d;' : 'background:#fee2e2; color:#dc2626;'}">
                                ${r.ativo ? 'Ativo' : 'Inativo'}
                            </span>
                        </div>
                    `).join('')}
                </div>
            `;
            grid.appendChild(card);
        });

        // Card para representantes sem equipe
        const orphanReps = usersList.filter(u => u.papel === 'representante' && !u.equipe_id);
        if (orphanReps.length > 0) {
            const orphanCard = document.createElement("div");
            orphanCard.style.cssText = "background:#ffffff; border:1px dashed #fca5a5; border-radius:14px; overflow:hidden; display:flex; flex-direction:column;";
            orphanCard.innerHTML = `
                <div style="background:#fef2f2; border-bottom:1px solid #fecaca; padding:12px 16px; display:flex; justify-content:space-between; align-items:center;">
                    <div style="font-weight:700; font-size:14px; color:#991b1b;">Sem Equipe Vinculada</div>
                    <span style="font-size:11px; background:#fee2e2; color:#991b1b; padding:2px 8px; border-radius:999px; font-weight:700;">${orphanReps.length}</span>
                </div>
                <div style="padding:12px 16px; display:flex; flex-direction:column; gap:8px;">
                    ${orphanReps.map(r => `
                        <div class="rep-card-draggable" 
                             draggable="true" 
                             ondragstart="dragUser(event, ${r.id})"
                             style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:8px 12px; display:flex; align-items:center; justify-content:space-between; cursor:grab;">
                            <div style="display:flex; align-items:center; gap:8px;">
                                <span style="color:#94a3b8; font-size:13px;">⠿</span>
                                <div>
                                    <div style="font-size:12.5px; font-weight:600; color:#1e293b;">${r.nome}</div>
                                    <div style="font-size:10.5px; color:#64748b;">${r.email}</div>
                                </div>
                            </div>
                            <span style="font-size:10.5px; color:#ea580c; font-weight:600;">Arraste p/ equipe</span>
                        </div>
                    `).join('')}
                </div>
            `;
            grid.appendChild(orphanCard);
        }
    }

    let draggedUserId = null;

    function dragUser(e, userId) {
        draggedUserId = userId;
        e.dataTransfer.setData("text/plain", userId);
        e.dataTransfer.effectAllowed = "move";
    }

    function allowDropUser(e) {
        e.preventDefault();
        e.currentTarget.style.background = "#eff6ff";
    }

    function leaveDropUser(e) {
        e.currentTarget.style.background = "#ffffff";
    }

    async function dropUser(e, targetTeamId) {
        e.preventDefault();
        e.currentTarget.style.background = "#ffffff";
        const userId = draggedUserId || e.dataTransfer.getData("text/plain");
        if (!userId) return;

        const user = usersList.find(u => u.id == userId);
        if (!user) return;
        if (user.equipe_id == targetTeamId) return;

        const team = teamsList.find(t => t.id == targetTeamId);
        const teamName = team ? team.nome : `Equipe #${targetTeamId}`;

        await updateUserTeam(userId, targetTeamId, teamName);
    }

    async function updateUserTeam(userId, targetTeamId, teamName) {
        try {
            const res = await fetch(`${API_URL}/usuarios/${userId}`, {
                method: "PATCH",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ equipe_id: targetTeamId })
            });

            const data = await res.json();
            if (data.success) {
                showToast(`Representante transferido para ${teamName}!`, "success");
                loadData();
            } else {
                showToast("Erro ao transferir: " + (data.message || data.error), "error");
            }
        } catch(err) {
            console.error(err);
            showToast("Erro de comunicação ao reatribuir equipe.", "error");
        }
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

        if (payload.papel === 'representante' && payload.ativo && !payload.equipe_id) {
            showToast("Não é permitido salvar um Representante Comercial ativo sem equipe vinculada. Selecione uma equipe antes de salvar.", "warning");
            return;
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
                showToast(isEdit ? "Dados do usuário atualizados com sucesso!" : "Novo usuário cadastrado com sucesso!", "success");
                closeUserModal();
                loadData();
            } else {
                let msg = data.message || "Erro ao salvar usuário.";
                if (data.messages) {
                    if (typeof data.messages === 'object') {
                        msg = Object.values(data.messages).flat().join(". ");
                    } else {
                        msg = JSON.stringify(data.messages);
                    }
                }
                showToast("Erro ao salvar: " + msg, "error");
            }
        } catch (e) {
            showToast("Erro de conexão ao salvar usuário.", "error");
        }
    }

    function deleteUser(id) {
        if (id === CURRENT_USER.id) {
            showToast("Você não pode excluir a sua própria conta.", "warning");
            return;
        }

        appConfirmModal({
            title: "Excluir Usuário",
            message: "Deseja realmente excluir este usuário permanentemente? Esta ação é irreversível.",
            confirmText: "Sim, Excluir",
            cancelText: "Cancelar",
            isDanger: true,
            onConfirm: async () => {
                try {
                    const res = await fetch(`${API_URL}/usuarios/${id}`, {
                        method: "DELETE",
                        headers: {
                            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });

                    const data = await res.json();
                    if (data.success) {
                        showToast("Usuário excluído com sucesso!", "success");
                        loadData();
                    } else {
                        showToast("Erro ao excluir: " + (data.message || data.error), "error");
                    }
                } catch (e) {
                    showToast("Erro de conexão ao excluir usuário.", "error");
                }
            }
        });
    }
</script>
@endsection
