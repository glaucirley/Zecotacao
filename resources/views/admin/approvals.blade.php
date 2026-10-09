@extends('layouts.app')

@section('page_title', 'Fila de Aprovações Pendentes')

@section('content')
<div class="card">
    <div class="card-header" style="flex-wrap: wrap; gap: 15px;">
        <div>
            <h3 id="card-title">Cotações Aguardando Minha Avaliação</h3>
            <span id="queue-count" class="user-role-label" style="background-color: var(--color-primary-hover);">0 Pendentes</span>
        </div>
        <div class="filter-tabs-wrapper" style="display: flex; gap: 8px; background: #f1f5f9; padding: 4px; border-radius: 10px;">
            <button id="tab-pendentes" class="filter-tab-btn active" onclick="switchApprovalTab('pendentes')">
                ⏳ Pendentes de Avaliação
            </button>
            <button id="tab-avaliadas" class="filter-tab-btn" onclick="switchApprovalTab('avaliadas')">
                ✅ Já Avaliadas por Mim
            </button>
        </div>
    </div>

    <!-- Table Fila -->
    <div class="table-responsive">
        <table class="table-premium">
            <thead>
                <tr>
                    <th style="width: 15%;">Cotação Nº</th>
                    <th style="width: 25%;">Cliente</th>
                    <th style="width: 20%;">Vendedor / Equipe</th>
                    <th style="width: 15%;">Emissão</th>
                    <th style="width: 12%; text-align: right;">Total Proposto</th>
                    <th style="width: 13%; text-align: center;">Ações</th>
                </tr>
            </thead>
            <tbody id="approvals-queue-body">
                <!-- Dynamic rows -->
            </tbody>
        </table>
    </div>

    <!-- Empty State -->
    <div id="empty-state" style="display: none; text-align: center; padding: 40px 20px;">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--color-text-muted)" stroke-width="1.5" style="margin: 0 auto 15px auto;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        <p id="empty-message" style="font-weight: 500; color: var(--color-text-muted);">Nenhuma cotação pendente de aprovação no momento.</p>
    </div>

    <!-- Loading spinner -->
    <div id="loading-spinner" style="text-align: center; padding: 40px 20px;">
        <div style="border: 3px solid rgba(15,81,50,0.1); border-top: 3px solid var(--color-primary); border-radius: 50%; width: 30px; height: 30px; animation: spin 1s linear infinite; margin: 0 auto;"></div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    let activeApprovalTab = 'pendentes';

    document.addEventListener("DOMContentLoaded", () => {
        loadQueue();
    });

    function switchApprovalTab(tab) {
        activeApprovalTab = tab;
        document.getElementById('tab-pendentes').classList.toggle('active', tab === 'pendentes');
        document.getElementById('tab-avaliadas').classList.toggle('active', tab === 'avaliadas');

        if (tab === 'pendentes') {
            document.getElementById('card-title').innerText = 'Cotações Aguardando Minha Avaliação';
            document.getElementById('empty-message').innerText = 'Nenhuma cotação pendente de aprovação no momento.';
        } else {
            document.getElementById('card-title').innerText = 'Cotações Avaliadas por Mim';
            document.getElementById('empty-message').innerText = 'Você ainda não avaliou nenhuma cotação no histórico.';
        }

        loadQueue();
    }

    async function loadQueue() {
        document.getElementById("loading-spinner").style.display = "block";
        document.getElementById("empty-state").style.display = "none";
        document.getElementById("approvals-queue-body").innerHTML = "";

        const endpoint = activeApprovalTab === 'avaliadas' ? "{{ url('/api/v1/aprovacoes/avaliadas') }}" : "{{ url('/api/v1/aprovacoes') }}";

        try {
            const res = await fetch(endpoint);
            
            if (res.status === 401 || res.status === 403) {
                window.location.href = "{{ url('/login') }}";
                return;
            }

            const data = await res.json();
            document.getElementById("loading-spinner").style.display = "none";

            if (data.success) {
                const list = data.data;
                document.getElementById("queue-count").innerText = activeApprovalTab === 'avaliadas' 
                    ? `${list.length} Avaliadas` 
                    : `${list.length} Pendentes`;
                
                if (list.length === 0) {
                    document.getElementById("empty-state").style.display = "block";
                    return;
                }

                const body = document.getElementById("approvals-queue-body");
                body.innerHTML = "";

                list.forEach(q => {
                    const date = new Date(q.created_at).toLocaleDateString('pt-BR');
                    const statusText = q.status ? q.status.replace(/_/g, ' ') : '';
                    body.innerHTML += `
                        <tr>
                            <td>
                                <strong>${q.numero}</strong>
                                ${q.prioridade ? '<br><span class="badge-status priority" style="background-color:rgba(239,68,68,0.1); color:#ef4444; border:1px solid rgba(239,68,68,0.2); font-size:10px; font-weight:700; margin-top:4px; display:inline-block; border-radius:4px; padding:2px 6px;">⚡ GRANDE CONTA</span>' : ''}
                                ${activeApprovalTab === 'avaliadas' ? `<br><span class="badge-status" style="font-size:10px; margin-top:4px; display:inline-block;">${statusText}</span>` : ''}
                            </td>
                            <td>${q.parceiro ? q.parceiro.razao_social : 'N/A'}</td>
                            <td>
                                <strong>${q.representante ? q.representante.nome : 'N/A'}</strong><br>
                                <span style="font-size: 11px; color: var(--color-text-muted);">Sankhya Code: ${q.representante ? q.representante.codigo_sankhya : 'N/A'}</span>
                            </td>
                            <td>${date}</td>
                            <td class="text-right" style="font-weight: 600; color: var(--color-primary);">R$ ${parseFloat(q.total).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</td>
                            <td class="text-center">
                                <a href="{{ url('/aprovacoes') }}/${q.id}" class="btn btn-secondary" style="padding: 6px 12px; font-size: 12px; font-weight: 600;">
                                    ${activeApprovalTab === 'avaliadas' ? '👁️ Ver Detalhes' : 'Analisar Cotação'}
                                </a>
                            </td>
                        </tr>
                    `;
                });
            } else {
                showToast("Erro ao carregar lista: " + (data.error || 'Erro desconhecido'), "error");
            }
        } catch (e) {
            console.error(e);
            showToast("Erro de conexão ao buscar aprovações.", "error");
        }
    }
</script>
@endsection
