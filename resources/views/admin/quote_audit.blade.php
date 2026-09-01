@extends('layouts.app')

@section('page_title', 'Auditoria de Cotação')

@section('content')
<div id="loading-spinner" style="text-align: center; padding: 100px 20px;">
    <div style="border: 4px solid rgba(15,81,50,0.1); border-top: 4px solid var(--color-primary); border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 0 auto 15px auto;"></div>
    <span style="color: var(--color-text-muted); font-weight: 500;">Carregando histórico de auditoria...</span>
</div>

<div id="audit-content" style="display: none; flex-direction: column; gap: 20px;">
    <!-- Top Header Navigation Card -->
    <div class="card" style="padding: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
            <div>
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 4px;">
                    <h2 style="font-size: 22px; margin: 0;" id="quote-title">Cotação #...</h2>
                    <span id="quote-status-badge" class="badge-status" style="font-size: 11px; padding: 4px 8px; border-radius: 12px; font-weight: 600;">...</span>
                </div>
                <p style="color: var(--color-text-muted); font-size: 13px; margin: 0;" id="quote-subtitle">
                    Cliente: ... | Representante: ...
                </p>
            </div>
            <div>
                <button class="btn btn-outline" onclick="goBack()" style="display: flex; align-items: center; gap: 8px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                    Voltar para Cotação
                </button>
            </div>
        </div>
    </div>

    <!-- Timeline / Table of Events -->
    <div class="card" style="padding: 24px;">
        <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 20px; color: var(--color-text);">Registro Cronológico de Eventos</h3>
        
        <div class="table-responsive" style="margin: 0;">
            <table class="table-premium" style="font-size: 13px;">
                <thead>
                    <tr>
                        <th style="width: 15%;">Data / Hora</th>
                        <th style="width: 20%;">Operador</th>
                        <th style="width: 12%;">Perfil / Papel</th>
                        <th style="width: 18%;">Evento</th>
                        <th style="width: 35%;">Observações / Alterações</th>
                    </tr>
                </thead>
                <tbody id="audit-table-body">
                    <!-- Dynamic logs -->
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<style>
    .event-badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    
    .event-criacao { background-color: rgba(59, 130, 246, 0.08); color: #2563eb; border: 1px solid rgba(59, 130, 246, 0.2); }
    .event-pdf { background-color: rgba(16, 185, 129, 0.08); color: #059669; border: 1px solid rgba(16, 185, 129, 0.2); }
    .event-aprovacao { background-color: rgba(16, 185, 129, 0.1); color: #0f5132; border: 1px solid rgba(16, 185, 129, 0.3); }
    .event-devolucao { background-color: rgba(245, 158, 11, 0.08); color: #d97706; border: 1px solid rgba(245, 158, 11, 0.2); }
    .event-escalacao { background-color: rgba(139, 92, 246, 0.08); color: #7c3aed; border: 1px solid rgba(139, 92, 246, 0.2); }
    .event-faturamento { background-color: rgba(13, 148, 136, 0.08); color: #0d9488; border: 1px solid rgba(13, 148, 136, 0.2); }
    .event-default { background-color: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
</style>

<script>
    const QUOTE_ID = "{{ $id }}";
    const API_URL = "{{ url('/api/v1') }}";
    let referrer = document.referrer;

    document.addEventListener("DOMContentLoaded", () => {
        loadAuditLogs();
    });

    async function loadAuditLogs() {
        try {
            const res = await fetch(`${API_URL}/aprovacoes/${QUOTE_ID}`);
            if (res.status === 401 || res.status === 403) {
                window.location.href = "{{ url('/login') }}";
                return;
            }
            const json = await res.json();
            document.getElementById("loading-spinner").style.display = "none";

            if (json.success) {
                renderAudit(json.data);
            } else {
                alert("Erro ao buscar histórico: " + json.error);
            }
        } catch(e) {
            console.error(e);
            document.getElementById("loading-spinner").style.display = "none";
            alert("Erro de conexão ao buscar histórico de auditoria.");
        }
    }

    function renderAudit(quote) {
        document.getElementById("audit-content").style.display = "flex";

        // Update headers
        document.getElementById("quote-title").innerText = `Cotação #${quote.numero}`;
        
        // Status Badge
        const badge = document.getElementById("quote-status-badge");
        badge.innerText = quote.status.replace(/_/g, ' ');
        badge.className = `badge-status badge-${quote.status.toLowerCase().replace(/_/g, '-')}`;

        // Subtitle
        document.getElementById("quote-subtitle").innerText = `Cliente: ${quote.parceiro.razao_social} | Representante: ${quote.representante.nome}`;

        // Render table
        const tbody = document.getElementById("audit-table-body");
        tbody.innerHTML = "";

        if (quote.historico.length === 0) {
            tbody.innerHTML = `<tr><td colspan="5" style="text-align:center; color:var(--color-text-muted); padding:30px;">Nenhum evento registrado no histórico desta cotação.</td></tr>`;
            return;
        }

        quote.historico.forEach(h => {
            const date = new Date(h.created_at).toLocaleString('pt-BR');
            const userStr = h.usuario ? `${h.usuario.nome} (${h.usuario.email})` : 'Sistema n8n / WhatsApp';
            const roleStr = h.papel ? capitalize(h.papel) : 'Integração';
            
            // Map event to a nice badge
            let badgeClass = 'event-default';
            const evt = h.evento.toLowerCase();
            if (evt.includes('criada') || evt.includes('criaçao') || evt.includes('item_adicionado')) {
                badgeClass = 'event-criacao';
            } else if (evt.includes('pdf')) {
                badgeClass = 'event-pdf';
            } else if (evt.includes('aprovado') || evt.includes('liberado') || evt.includes('finalizado')) {
                badgeClass = 'event-aprovacao';
            } else if (evt.includes('devolvido')) {
                badgeClass = 'event-devolucao';
            } else if (evt.includes('escalado')) {
                badgeClass = 'event-escalacao';
            } else if (evt.includes('faturado') || evt.includes('pedido')) {
                badgeClass = 'event-faturamento';
            }

            const cleanEvent = h.evento.replace(/_/g, ' ');

            tbody.innerHTML += `
                <tr>
                    <td style="white-space: nowrap;"><strong>${date}</strong></td>
                    <td>${userStr}</td>
                    <td><span style="font-weight:600; color:#555;">${roleStr}</span></td>
                    <td><span class="event-badge ${badgeClass}">${cleanEvent}</span></td>
                    <td style="color:var(--color-text); line-height:1.45;">${h.condicao || '-'}</td>
                </tr>
            `;
        });
    }

    function capitalize(str) {
        if (!str) return '';
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    function goBack() {
        if (referrer && referrer.includes('/aprovacoes/') || referrer.includes('/faturamento/')) {
            window.location.href = referrer;
        } else {
            // Default back to approvals or details
            window.location.href = `{{ url('/aprovacoes') }}/${QUOTE_ID}`;
        }
    }
</script>
@endsection
