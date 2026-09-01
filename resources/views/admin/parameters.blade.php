@extends('layouts.app')

@section('page_title', 'Parâmetros Dinâmicos do Sistema')

@section('content')
<!-- Business Rules Card -->
<div class="card">
    <div class="card-header">
        <h3>Configurações Globais (Alçada, Justificativa e Validade)</h3>
        <span class="user-role-label" style="background-color: var(--color-primary-hover);">Administrador Geral</span>
    </div>

    <!-- Table Parameters -->
    <div class="table-responsive">
        <table class="table-premium">
            <thead>
                <tr>
                    <th style="width: 25%;">Chave</th>
                    <th style="width: 35%;">Descrição da Regra de Negócio</th>
                    <th style="width: 15%;">Tipo Dado</th>
                    <th style="width: 15%;">Configuração / Valor</th>
                    <th style="width: 10%; text-align: center;">Ações</th>
                </tr>
            </thead>
            <tbody id="params-table-body">
                <!-- Dynamic rows -->
            </tbody>
        </table>
    </div>

    <!-- Loading spinner -->
    <div id="loading-spinner" style="text-align: center; padding: 40px 20px;">
        <div style="border: 3px solid rgba(15,81,50,0.1); border-top: 3px solid var(--color-primary); border-radius: 50%; width: 30px; height: 30px; animation: spin 1s linear infinite; margin: 0 auto;"></div>
    </div>
</div>

<!-- Sankhya Connection Card -->
<div class="card" style="margin-top: 24px;">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
        <div>
            <h3>Conexão Direta com ERP Sankhya (Banco Oracle)</h3>
            <span class="user-role-label" style="background-color: var(--color-primary-hover);">Integração Ativa</span>
        </div>
        <div style="display: flex; gap: 8px;">
            <button class="btn btn-secondary" onclick="testConnection()" id="btn-test-conn" style="font-size: 13px; font-weight: 600;">
                Testar Conexão
            </button>
            <button class="btn btn-primary" onclick="syncCatalog()" id="btn-sync-catalog" style="font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>
                Sincronizar Catálogo Agora
            </button>
        </div>
    </div>
    
    <div style="padding: 24px;">
        <form id="sankhya-config-form" onsubmit="saveSankhyaConfig(event)">
            <!-- Connection Row 1 -->
            <div class="grid-3" style="gap: 20px; margin-bottom: 20px;">
                <div>
                    <label class="form-label" style="font-weight: 600; margin-bottom: 8px; display: block;">Tipo de Conexão</label>
                    <select id="sankhya-tipo" class="form-control" onchange="toggleSshFields()" required>
                        <option value="DIRETO">Conexão Direta (TCP)</option>
                        <option value="SSH_TUNNEL">Túnel SSH (Redirecionamento Localhost)</option>
                    </select>
                </div>
                <div>
                    <label class="form-label" style="font-weight: 600; margin-bottom: 8px; display: block;">Host do Banco Oracle</label>
                    <input type="text" id="sankhya-host" class="form-control" placeholder="ex: 192.168.1.100" required>
                    <span style="font-size: 11px; color: var(--color-text-muted);" id="sankhya-host-note">Utilize o endereço IP ou Host real do servidor Oracle.</span>
                </div>
                <div>
                    <label class="form-label" style="font-weight: 600; margin-bottom: 8px; display: block;">Porta do Banco Oracle</label>
                    <input type="number" id="sankhya-port" class="form-control" value="1521" required>
                </div>
            </div>

            <!-- Connection Row 2 -->
            <div class="grid-3" style="gap: 20px; margin-bottom: 20px;">
                <div>
                    <label class="form-label" style="font-weight: 600; margin-bottom: 8px; display: block;">Nome do Serviço / SID Oracle</label>
                    <input type="text" id="sankhya-name" class="form-control" placeholder="ex: XE" required>
                </div>
                <div>
                    <label class="form-label" style="font-weight: 600; margin-bottom: 8px; display: block;">Usuário do Banco</label>
                    <input type="text" id="sankhya-user" class="form-control" required>
                </div>
                <div>
                    <label class="form-label" style="font-weight: 600; margin-bottom: 8px; display: block;">Senha do Banco</label>
                    <div style="position: relative; display: flex; align-items: center;">
                        <input type="password" id="sankhya-pass" class="form-control" placeholder="Preencha apenas para alterar" style="padding-right: 40px;">
                        <button type="button" onclick="togglePassVisibility()" style="position: absolute; right: 10px; background: none; border: none; cursor: pointer; color: var(--color-text-muted); display: flex; align-items: center; justify-content: center; height: 100%;">
                            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Automatic Sync Scheduling Row (New) -->
            <div class="grid-2" style="gap: 20px; margin-bottom: 20px; border-top: 1px solid var(--color-border); padding-top: 20px;">
                <div>
                    <label class="form-label" style="font-weight: 600; margin-bottom: 8px; display: block;">Sincronização Automática (Background)</label>
                    <select id="sankhya-auto" class="form-control" onchange="toggleAutoSyncInterval()" required>
                        <option value="false">Desativada (Apenas manual)</option>
                        <option value="true">Ativada (Fundo/Agendada)</option>
                    </select>
                    <span style="font-size: 11px; color: var(--color-text-muted);">Requer a ativação do Scheduler/Cron no servidor Linux da Hetzner.</span>
                </div>
                <div id="sync-interval-container" style="display: none;">
                    <label class="form-label" style="font-weight: 600; margin-bottom: 8px; display: block;">Intervalo da Sincronização</label>
                    <select id="sankhya-intervalo" class="form-control" required>
                        <option value="DIARIO">Diariamente (às 02:00 AM)</option>
                        <option value="CADA_12_HORAS">A cada 12 horas</option>
                        <option value="CADA_6_HORAS">A cada 6 horas</option>
                        <option value="HORARIO">A cada hora</option>
                    </select>
                </div>
            </div>

            <!-- SSH Tunnel Info Section (Conditional) -->
            <div id="ssh-fields-container" style="display: none; border-top: 1px solid var(--color-border); padding-top: 20px; margin-bottom: 20px;">
                <h4 style="font-size: 14px; font-weight: 600; margin-bottom: 15px; color: var(--color-text-main);">Informações do Servidor SSH (Apenas Informativo)</h4>
                <div class="grid-3" style="gap: 20px;">
                    <div>
                        <label class="form-label" style="font-weight: 600; margin-bottom: 8px; display: block;">Host SSH (Jump Server)</label>
                        <input type="text" id="sankhya-ssh-host" class="form-control" placeholder="ex: jump.meuprovedor.com">
                    </div>
                    <div>
                        <label class="form-label" style="font-weight: 600; margin-bottom: 8px; display: block;">Porta SSH</label>
                        <input type="number" id="sankhya-ssh-port" class="form-control" value="22">
                    </div>
                    <div>
                        <label class="form-label" style="font-weight: 600; margin-bottom: 8px; display: block;">Usuário SSH</label>
                        <input type="text" id="sankhya-ssh-user" class="form-control">
                    </div>
                </div>
                <div style="background-color: rgba(59, 130, 246, 0.05); padding: 12px 16px; border-radius: 8px; margin-top: 15px; font-size: 12px; color: var(--color-text-muted); line-height: 1.5; border: 1px solid rgba(59,130,246,0.15);">
                    <strong>💡 Como funciona o Túnel SSH persistente no servidor:</strong><br>
                    Para que a plataforma converse com a porta 1521 localmente, você deve rodar no terminal da sua máquina Hetzner o seguinte comando em background:<br>
                    <code>ssh -N -L 1521:IP_REMOTO_DO_ORACLE:1521 usuario_ssh@IP_JUMP_SERVER -p PORTA_SSH</code>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; margin-top: 20px; border-top: 1px solid var(--color-border); padding-top: 20px;">
                <button type="submit" class="btn btn-primary" id="btn-save-sankhya" style="font-weight: 600; padding: 10px 24px;">
                    Salvar Conectividade
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener("DOMContentLoaded", () => {
        loadParameters();
    });

    async function loadParameters() {
        try {
            const res = await fetch("{{ url('/api/v1/parametros') }}");
            
            if (res.status === 401 || res.status === 403) {
                window.location.href = "{{ url('/login') }}";
                return;
            }

            const data = await res.json();
            document.getElementById("loading-spinner").style.display = "none";

            if (data.success) {
                const list = data.data;
                const body = document.getElementById("params-table-body");
                body.innerHTML = "";

                // List of keys to route to the Oracle connection panel instead of table
                const connectionKeys = [
                    'SANKHYA_CONN_TIPO', 'SANKHYA_DB_HOST', 'SANKHYA_DB_PORT', 
                    'SANKHYA_DB_NAME', 'SANKHYA_DB_USER', 'SANKHYA_DB_PASS',
                    'SANKHYA_SSH_HOST', 'SANKHYA_SSH_PORT', 'SANKHYA_SSH_USER',
                    'SANKHYA_SYNC_AUTO', 'SANKHYA_SYNC_INTERVALO'
                ];

                list.forEach(p => {
                    // Populate technical Oracle fields dynamically
                    if (connectionKeys.includes(p.chave)) {
                        if (p.chave === 'SANKHYA_CONN_TIPO') {
                            document.getElementById('sankhya-tipo').value = p.valor;
                            toggleSshFields();
                        }
                        if (p.chave === 'SANKHYA_DB_HOST') document.getElementById('sankhya-host').value = p.valor;
                        if (p.chave === 'SANKHYA_DB_PORT') document.getElementById('sankhya-port').value = p.valor;
                        if (p.chave === 'SANKHYA_DB_NAME') document.getElementById('sankhya-name').value = p.valor;
                        if (p.chave === 'SANKHYA_DB_USER') document.getElementById('sankhya-user').value = p.valor;
                        if (p.chave === 'SANKHYA_SSH_HOST') document.getElementById('sankhya-ssh-host').value = p.valor;
                        if (p.chave === 'SANKHYA_SSH_PORT') document.getElementById('sankhya-ssh-port').value = p.valor;
                        if (p.chave === 'SANKHYA_SSH_USER') document.getElementById('sankhya-ssh-user').value = p.valor;
                        
                        if (p.chave === 'SANKHYA_SYNC_AUTO') {
                            document.getElementById('sankhya-auto').value = p.valor;
                            toggleAutoSyncInterval();
                        }
                        if (p.chave === 'SANKHYA_SYNC_INTERVALO') {
                            document.getElementById('sankhya-intervalo').value = p.valor;
                        }
                        return; // Skip drawing row in parameters table
                    }

                    let inputMarkup = "";

                    // Tailored edit inputs based on configuration key or data type
                    if (p.chave === 'DESCONTO_AVALIACAO_MODO') {
                        inputMarkup = `
                            <select id="val-${p.chave}" class="form-control" style="font-size:13px; padding:6px 10px;">
                                <option value="ITEM_A_ITEM" ${p.valor === 'ITEM_A_ITEM' ? 'selected' : ''}>Item a Item</option>
                                <option value="MEDIA_TOTAL" ${p.valor === 'MEDIA_TOTAL' ? 'selected' : ''}>Média Total</option>
                            </select>
                        `;
                    } else if (p.chave === 'REENVIO_PARCIAL_MODO') {
                        inputMarkup = `
                            <select id="val-${p.chave}" class="form-control" style="font-size:13px; padding:6px 10px;">
                                <option value="RECALCULA_TUDO" ${p.valor === 'RECALCULA_TUDO' ? 'selected' : ''}>Recalcula Tudo</option>
                                <option value="SO_ITENS_ALTERADOS" ${p.valor === 'SO_ITENS_ALTERADOS' ? 'selected' : ''}>Só Itens Alterados</option>
                            </select>
                        `;
                    } else if (p.tipo === 'booleano') {
                        inputMarkup = `
                            <select id="val-${p.chave}" class="form-control" style="font-size:13px; padding:6px 10px;">
                                <option value="true" ${p.valor === 'true' || p.valor === '1' ? 'selected' : ''}>Ativo (Sim)</option>
                                <option value="false" ${p.valor === 'false' || p.valor === '0' ? 'selected' : ''}>Inativo (Não)</option>
                            </select>
                        `;
                    } else if (p.tipo === 'numero') {
                        inputMarkup = `
                            <input type="number" id="val-${p.chave}" class="form-control" value="${p.valor}" style="font-size:13px; padding:6px 10px;">
                        `;
                    } else {
                        inputMarkup = `
                            <input type="text" id="val-${p.chave}" class="form-control" value="${p.valor}" style="font-size:13px; padding:6px 10px;">
                        `;
                    }

                    body.innerHTML += `
                        <tr>
                            <td><strong>${p.chave}</strong></td>
                            <td style="font-size: 13px; color: var(--color-text-muted);">${p.descricao}</td>
                            <td><span style="font-size:11px; text-transform:uppercase; font-weight:600; color:#555;">${p.tipo}</span></td>
                            <td>${inputMarkup}</td>
                            <td class="text-center">
                                <button class="btn btn-primary" onclick="saveParameter('${p.chave}')" style="padding: 6px 12px; font-size: 12px; font-weight: 600;">
                                    Salvar
                                </button>
                            </td>
                        </tr>
                    `;
                });
            } else {
                alert("Erro ao carregar parâmetros: " + data.error);
            }
        } catch (e) {
            console.error(e);
            alert("Erro de conexão.");
        }
    }

    async function saveParameter(chave) {
        const inputEl = document.getElementById(`val-${chave}`);
        if (!inputEl) return;

        const val = inputEl.value;

        try {
            const res = await fetch(`{{ url('/api/v1/parametros') }}/${chave}`, {
                method: "PATCH",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ valor: val })
            });

            const data = await res.json();
            if (data.success) {
                alert(`Parâmetro '${chave}' updated successfully to '${val}'!`);
                loadParameters();
            } else {
                alert("Erro ao salvar parâmetro: " + data.message);
            }
        } catch (e) {
            alert("Erro de conexão.");
        }
    }

    function toggleSshFields() {
        const tipo = document.getElementById("sankhya-tipo").value;
        const container = document.getElementById("ssh-fields-container");
        const note = document.getElementById("sankhya-host-note");
        
        if (tipo === 'SSH_TUNNEL') {
            container.style.display = "block";
            note.innerText = "Utilize '127.0.0.1' pois a conexão será tunelada localmente.";
        } else {
            container.style.display = "none";
            note.innerText = "Utilize o endereço IP ou Host real do servidor Oracle.";
        }
    }

    function toggleAutoSyncInterval() {
        const auto = document.getElementById("sankhya-auto").value;
        const container = document.getElementById("sync-interval-container");
        if (auto === 'true') {
            container.style.display = "block";
        } else {
            container.style.display = "none";
        }
    }

    function togglePassVisibility() {
        const input = document.getElementById("sankhya-pass");
        if (input.type === "password") {
            input.type = "text";
        } else {
            input.type = "password";
        }
    }

    async function saveSankhyaConfig(e) {
        e.preventDefault();
        const btn = document.getElementById("btn-save-sankhya");
        const originalText = btn.innerText;
        btn.innerText = "Salvando...";
        btn.disabled = true;

        const payload = {
            tipo: document.getElementById("sankhya-tipo").value,
            host: document.getElementById("sankhya-host").value,
            port: document.getElementById("sankhya-port").value,
            name: document.getElementById("sankhya-name").value,
            user: document.getElementById("sankhya-user").value,
            pass: document.getElementById("sankhya-pass").value || null,
            ssh_host: document.getElementById("sankhya-ssh-host").value || null,
            ssh_port: document.getElementById("sankhya-ssh-port").value || null,
            ssh_user: document.getElementById("sankhya-ssh-user").value || null,
            auto_sync: document.getElementById("sankhya-auto").value === 'true' ? 1 : 0,
            intervalo: document.getElementById("sankhya-intervalo").value,
        };

        try {
            const res = await fetch("{{ url('/api/v1/parametros/sankhya/conexao') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(payload)
            });

            const data = await res.json();
            if (data.success) {
                alert("Configurações do Sankhya salvas com sucesso!");
                document.getElementById("sankhya-pass").value = ""; // clear password field
                loadParameters();
            } else {
                alert("Erro ao salvar: " + data.message);
            }
        } catch(err) {
            alert("Erro de rede ao salvar configurações.");
        } finally {
            btn.innerText = originalText;
            btn.disabled = false;
        }
    }

    async function testConnection() {
        const btn = document.getElementById("btn-test-conn");
        const originalText = btn.innerText;
        btn.innerText = "Testando...";
        btn.disabled = true;

        try {
            const res = await fetch("{{ url('/api/v1/parametros/sankhya/testar') }}", {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });
            const data = await res.json();
            if (data.success) {
                alert("Sucesso! " + data.message);
            } else {
                alert("Falha na conexão: " + data.message);
            }
        } catch(err) {
            alert("Erro de rede ao tentar testar conexão.");
        } finally {
            btn.innerText = originalText;
            btn.disabled = false;
        }
    }

    async function syncCatalog() {
        if (!confirm("Isso irá importar todos os Clientes, Produtos e Vendedores do Oracle do Sankhya. Deseja continuar?")) {
            return;
        }

        const btn = document.getElementById("btn-sync-catalog");
        const originalHtml = btn.innerHTML;
        btn.innerHTML = "Sincronizando...";
        btn.disabled = true;

        try {
            const res = await fetch("{{ url('/api/v1/parametros/sankhya/sincronizar') }}", {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });
            const data = await res.json();
            if (data.success) {
                alert(data.message);
            } else {
                alert("Falha na sincronização: " + data.message);
            }
        } catch(err) {
            alert("Erro de rede ao tentar sincronizar catálogo.");
        } finally {
            btn.innerHTML = originalHtml;
            btn.disabled = false;
        }
    }
</script>
@endsection
