@extends('layouts.app')

@section('page_title', 'Fila de Faturamento e Conferência')

@section('content')
<div class="card">
    <div class="card-header">
        <h3>Cotações Prontas para Faturamento</h3>
        <span id="billing-count" class="user-role-label" style="background-color: var(--color-primary-hover);">0 Itens</span>
    </div>

    <!-- Table Fila -->
    <div class="table-responsive">
        <table class="table-premium">
            <thead>
                <tr>
                    <th style="width: 12%;">Cotação Nº</th>
                    <th style="width: 22%;">Cliente</th>
                    <th style="width: 18%;">Vendedor</th>
                    <th style="width: 15%;">Situação Cotação</th>
                    <th style="width: 20%;">Pedido Externo (Sankhya)</th>
                    <th style="width: 13%; text-align: center;">Ações</th>
                </tr>
            </thead>
            <tbody id="billing-queue-body">
                <!-- Dynamic rows -->
            </tbody>
        </table>
    </div>

    <!-- Empty State -->
    <div id="empty-state" style="display: none; text-align: center; padding: 40px 20px;">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--color-text-muted)" stroke-width="1.5" style="margin: 0 auto 15px auto;"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
        <p style="font-weight: 500; color: var(--color-text-muted);">Nenhuma cotação na fila de faturamento no momento.</p>
    </div>

    <!-- Loading spinner -->
    <div id="loading-spinner" style="text-align: center; padding: 40px 20px;">
        <div style="border: 3px solid rgba(15,81,50,0.1); border-top: 3px solid var(--color-primary); border-radius: 50%; width: 30px; height: 30px; animation: spin 1s linear infinite; margin: 0 auto;"></div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener("DOMContentLoaded", () => {
        loadBillingQueue();
    });

    async function loadBillingQueue() {
        try {
            const res = await fetch("{{ url('/api/v1/faturamento/fila') }}");
            
            if (res.status === 401 || res.status === 403) {
                window.location.href = "{{ url('/login') }}";
                return;
            }

            const data = await res.json();
            document.getElementById("loading-spinner").style.display = "none";

            if (data.success) {
                const list = data.data;
                document.getElementById("billing-count").innerText = `${list.length} Cotações`;
                
                if (list.length === 0) {
                    document.getElementById("empty-state").style.display = "block";
                    return;
                }

                const body = document.getElementById("billing-queue-body");
                body.innerHTML = "";

                list.forEach(q => {
                    const statusClass = q.status.toLowerCase().replace(/_/g, '-');
                    const statusText = q.status === 'PDF_GERADO' ? 'Liberada (Pendente PDF)' : q.status.replace(/_/g, ' ');

                    // Check if external order is registered
                    let orderMarkup = `<span style="color:var(--color-text-muted); font-size:12px;">Não registrado</span>`;
                    if (q.pedido_externo) {
                        const confClass = q.pedido_externo.status_conferencia === 'conforme' ? 'background-color:#d1fae5; color:#065f46;' : 'background-color:#fee2e2; color:#991b1b;';
                        orderMarkup = `
                            <strong>Nº: ${q.pedido_externo.numero_pedido_externo}</strong><br>
                            <span style="font-size:12px; color:var(--color-text-muted);">Valor: R$ ${parseFloat(q.pedido_externo.valor_pedido).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</span><br>
                            <span style="display:inline-block; margin-top:4px; font-size:10px; font-weight:700; text-transform:uppercase; padding:2px 6px; border-radius:4px; ${confClass}">
                                ${q.pedido_externo.status_conferencia}
                            </span>
                        `;
                    }

                    body.innerHTML += `
                        <tr>
                            <td><strong>${q.numero}</strong><br><span style="font-size:11px; color:var(--color-text-muted);">R$ ${parseFloat(q.total).toLocaleString('pt-BR', {minimumFractionDigits:2})}</span></td>
                            <td>${q.parceiro.razao_social}</td>
                            <td>${q.representante.nome}</td>
                            <td>
                                <span class="badge-status ${statusClass}">${statusText}</span>
                            </td>
                            <td>${orderMarkup}</td>
                            <td class="text-center">
                                <a href="{{ url('/faturamento') }}/${q.id}" class="btn btn-secondary" style="padding: 6px 12px; font-size: 12px; font-weight: 600;">
                                    Conferir
                                </a>
                            </td>
                        </tr>
                    `;
                });
            } else {
                alert("Erro ao carregar faturamento: " + data.error);
            }
        } catch (e) {
            console.error(e);
            alert("Erro de conexão ao buscar fila de faturamento.");
        }
    }
</script>
@endsection
