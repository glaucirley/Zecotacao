@extends('layouts.app')

@section('page_title', 'Central de Auditoria de Conversas')

@section('content')
<div style="display: flex; height: calc(100vh - 120px); min-height: 500px; border: 1px solid var(--color-border); border-radius: 16px; overflow: hidden; background-color: white; box-shadow: var(--shadow-sm);">
    
    <!-- Left Pane: Contact list -->
    <div style="width: 320px; border-right: 1px solid var(--color-border); display: flex; flex-direction: column; background-color: #f8fafc;">
        <!-- Search bar -->
        <div style="padding: 15px; border-bottom: 1px solid var(--color-border); background-color: white;">
            <div style="position: relative;">
                <input type="text" id="contact-search" class="form-control" placeholder="Buscar contato ou Nº..." oninput="filterContacts()" style="margin: 0; padding-left: 36px; font-size: 13px; height: 38px; border-radius: 8px;">
                <svg style="position: absolute; left: 12px; top: 10px; color: var(--color-text-muted);" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            </div>
        </div>
        
        <!-- Contact list container -->
        <div id="contacts-list" style="flex-grow: 1; overflow-y: auto; display: flex; flex-direction: column;">
            <!-- Dynamic contact cards -->
            <div style="text-align: center; color: var(--color-text-muted); padding: 40px 10px; font-size: 13px;">
                Carregando contatos...
            </div>
        </div>
    </div>
    
    <!-- Right Pane: Active conversation -->
    <div style="flex-grow: 1; display: flex; flex-direction: column; background-color: #f1f5f9; position: relative;">
        
        <!-- No chat selected state -->
        <div id="no-chat-state" style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; padding: 40px; text-align: center; background-color: #f8fafc; z-grow: 1;">
            <div style="background-color: rgba(15,81,50,0.06); padding: 20px; border-radius: 50%; color: var(--color-primary); margin-bottom: 15px;">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            </div>
            <h3 style="font-size: 16px; font-weight: 600; color: var(--color-text);">Auditor de Conversas WhatsApp</h3>
            <p style="font-size: 13px; color: var(--color-text-muted); max-width: 320px; margin-top: 5px;">Selecione um contato na barra lateral esquerda para auditar o histórico completo da conversa.</p>
        </div>
        
        <!-- Active chat state -->
        <div id="active-chat-state" style="display: none; flex-direction: column; height: 100%;">
            <!-- Chat Header -->
            <div style="padding: 12px 20px; border-bottom: 1px solid var(--color-border); background-color: white; display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <div id="active-chat-name" style="font-size: 15px; font-weight: 700; color: var(--color-text);">Nome Fantasia</div>
                    <div id="active-chat-phone" style="font-size: 12px; color: var(--color-text-muted); margin-top: 2px;">+55 11 99999-8888</div>
                </div>
                
                <div style="display: flex; align-items: center; gap: 10px;">
                    <button class="btn btn-outline" onclick="loadActiveHistory()" style="padding: 8px 12px; display: flex; align-items: center; gap: 6px; font-size:12px;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>
                        Atualizar
                    </button>
                </div>
            </div>
            
            <!-- Link to quotation warning banner -->
            <div id="quote-association-banner" style="display: none; align-items: center; justify-content: space-between; padding: 10px 20px; background-color: #e2f0d9; border-bottom: 1px solid rgba(15,81,50,0.1); color: #2e7d32; font-size: 12px; font-weight: 600;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    <span>Esta conversa gerou a cotação ativa <strong id="associated-quote-num">COT-X-XXXX</strong></span>
                </div>
                <a href="#" id="associated-quote-link" class="btn btn-primary" style="padding: 4px 10px; font-size: 11px; background-color: var(--color-primary); border-color: var(--color-primary);">
                    Analisar Cotação
                </a>
            </div>
            
            <!-- Message thread box -->
            <div id="message-thread" style="flex-grow: 1; overflow-y: auto; padding: 20px; display: flex; flex-direction: column; gap: 14px;">
                <!-- Chat bubbles -->
            </div>
            
            <!-- Bottom Footer: Read Only indicator -->
            <div style="padding: 15px 20px; background-color: #f8fafc; border-top: 1px solid var(--color-border); text-align: center; font-size: 12px; color: var(--color-text-muted); font-weight: 500; display: flex; justify-content: center; align-items: center; gap: 6px;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                Visualização de Histórico Somente Leitura (Log do WhatsApp via n8n)
            </div>
        </div>
        
    </div>
    
</div>
@endsection

@section('scripts')
<style>
    .contact-card {
        padding: 14px 16px;
        border-bottom: 1px solid rgba(226, 232, 240, 0.6);
        cursor: pointer;
        background-color: white;
        transition: all 0.2s ease;
        display: flex;
        flex-direction: column;
        gap: 4px;
        text-decoration: none;
    }
    
    .contact-card:hover {
        background-color: #f1f5f9;
    }
    
    .contact-card.active-contact {
        background-color: rgba(15, 81, 50, 0.05);
        border-left: 4px solid var(--color-primary);
        padding-left: 12px;
    }
    
    .chat-bubble {
        max-width: 65%;
        padding: 10px 14px;
        border-radius: 12px;
        font-size: 13px;
        line-height: 1.45;
        position: relative;
        word-break: break-word;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }
    
    .bubble-received {
        align-self: flex-start;
        background-color: white;
        color: var(--color-text);
        border-top-left-radius: 0;
    }
    
    .bubble-sent {
        align-self: flex-end;
        background-color: #d9fdd3;
        color: #111b21;
        border-top-right-radius: 0;
    }
    
    .bubble-time {
        display: block;
        font-size: 10px;
        color: var(--color-text-muted);
        text-align: right;
        margin-top: 4px;
    }
</style>

<script>
    const API_URL = "{{ url('/api/v1') }}";
    const PUBLIC_URL = "{{ url('/') }}";
    let contacts = [];
    let filteredContactsList = [];
    let selectedPhone = null;
    let selectedName = null;
    let pollerInterval = null;

    document.addEventListener("DOMContentLoaded", () => {
        loadContacts();
        
        // Auto refresh current selected chat every 15 seconds
        setInterval(() => {
            if (selectedPhone) {
                loadActiveHistory(true); // quiet refresh
            }
        }, 15000);
    });

    async function loadContacts() {
        try {
            const res = await fetch(`${API_URL}/chat/contatos`);
            if (res.status === 401 || res.status === 403) {
                window.location.href = `${PUBLIC_URL}/login`;
                return;
            }
            const json = await res.json();
            if (json.success) {
                contacts = json.data;
                filteredContactsList = [...contacts];
                renderContacts();
            }
        } catch (e) {
            console.error("Error loading contacts:", e);
        }
    }

    function renderContacts() {
        const container = document.getElementById("contacts-list");
        container.innerHTML = "";

        if (filteredContactsList.length === 0) {
            container.innerHTML = `
                <div style="text-align: center; color: var(--color-text-muted); padding: 40px 10px; font-size: 13px;">
                    Nenhum contato encontrado.
                </div>
            `;
            return;
        }

        filteredContactsList.forEach(c => {
            const date = new Date(c.created_at);
            const timeStr = date.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
            const dateStr = date.toLocaleDateString('pt-BR');
            const friendlyTime = date.toDateString() === new Date().toDateString() ? timeStr : dateStr;
            
            const activeClass = selectedPhone === c.telefone_cliente ? 'active-contact' : '';
            const dispName = c.nome_cliente || 'Cliente WhatsApp';
            const shortMsg = c.mensagem.length > 30 ? c.mensagem.substring(0, 30) + '...' : c.mensagem;
            const formattedPhone = formatPhone(c.telefone_cliente);

            container.innerHTML += `
                <div class="contact-card ${activeClass}" onclick="selectContact('${c.telefone_cliente}', '${dispName.replace(/'/g, "\\'")}')">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <strong style="font-size: 13px; color: var(--color-text);">${dispName}</strong>
                        <span style="font-size: 10px; color: var(--color-text-muted);">${friendlyTime}</span>
                    </div>
                    <span style="font-size: 11px; color: var(--color-text-muted);">${formattedPhone}</span>
                    <span style="font-size: 12px; color: var(--color-text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: 2px;">
                        ${c.direcao === 'sent' ? '✓ ' : ''}${shortMsg}
                    </span>
                </div>
            `;
        });
    }

    function filterContacts() {
        const q = document.getElementById("contact-search").value.toLowerCase().trim();
        if (q === "") {
            filteredContactsList = [...contacts];
        } else {
            filteredContactsList = contacts.filter(c => 
                (c.nome_cliente && c.nome_cliente.toLowerCase().includes(q)) || 
                c.telefone_cliente.includes(q) ||
                c.mensagem.toLowerCase().includes(q)
            );
        }
        renderContacts();
    }

    function selectContact(phone, name) {
        selectedPhone = phone;
        selectedName = name;
        
        // Highlight in list
        document.querySelectorAll(".contact-card").forEach(el => el.classList.remove("active-contact"));
        renderContacts(); // refresh selections
        
        // Toggle view panels
        document.getElementById("no-chat-state").style.display = "none";
        document.getElementById("active-chat-state").style.display = "flex";
        
        // Update header
        document.getElementById("active-chat-name").innerText = name;
        document.getElementById("active-chat-phone").innerText = formatPhone(phone);
        
        loadActiveHistory();
    }

    async function loadActiveHistory(quiet = false) {
        if (!selectedPhone) return;
        
        if (!quiet) {
            document.getElementById("message-thread").innerHTML = `
                <div style="text-align: center; color: var(--color-text-muted); padding: 40px 10px;">
                    Carregando mensagens...
                </div>
            `;
        }

        try {
            const res = await fetch(`${API_URL}/chat/historico/${selectedPhone}`);
            const json = await res.json();
            if (json.success) {
                renderMessages(json.data);
            }
        } catch (e) {
            console.error("Error loading chat history:", e);
        }
    }

    function renderMessages(messages) {
        const thread = document.getElementById("message-thread");
        thread.innerHTML = "";
        
        let hasQuoteLink = false;
        let quoteNumber = "";
        let quoteToken = "";

        if (messages.length === 0) {
            thread.innerHTML = `
                <div style="text-align: center; color: var(--color-text-muted); padding: 40px 10px;">
                    Nenhuma mensagem registrada.
                </div>
            `;
            return;
        }

        messages.forEach(m => {
            const isSent = m.direcao === 'sent';
            const date = new Date(m.created_at);
            const timeStr = date.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
            
            // Check for associated quote
            if (m.cotacao) {
                hasQuoteLink = true;
                quoteNumber = m.cotacao.numero;
                quoteToken = m.cotacao.id; // Link is via route
            }

            let msgHtml = `<div>${escapeHtml(m.mensagem).replace(/\n/g, '<br>')}</div>`;

            // Handle audio/media messages
            if (m.tipo === 'audio') {
                msgHtml = `
                    <div style="margin-bottom: 5px; font-weight: 500; font-size:11px; color:var(--color-primary);">🎤 Mensagem de Voz</div>
                    <audio controls style="max-width: 100%; height: 36px; margin-top: 5px;">
                        <source src="${m.mensagem}" type="audio/mpeg">
                        Seu navegador não suporta player de áudio.
                    </audio>
                `;
            }

            thread.innerHTML += `
                <div class="chat-bubble ${isSent ? 'bubble-sent' : 'bubble-received'}">
                    ${msgHtml}
                    <span class="bubble-time">${timeStr}</span>
                </div>
            `;
        });

        // Toggle quote header banner
        const banner = document.getElementById("quote-association-banner");
        if (hasQuoteLink) {
            banner.style.display = "flex";
            document.getElementById("associated-quote-num").innerText = quoteNumber;
            // Admin detail redirect URL depending on role, let's redirect to approval detail or token
            document.getElementById("associated-quote-link").href = `${PUBLIC_URL}/aprovacoes/${quoteToken}`;
        } else {
            banner.style.display = "none";
        }

        // Scroll to bottom
        thread.scrollTop = thread.scrollHeight;
    }

    // Helper functions
    function formatPhone(phone) {
        if (!phone) return "";
        let cleaned = phone.replace(/\D/g, "");
        if (cleaned.length === 13) {
            return `+${cleaned.substring(0, 2)} (${cleaned.substring(2, 4)}) ${cleaned.substring(4, 9)}-${cleaned.substring(9)}`;
        }
        return `+${cleaned}`;
    }

    function escapeHtml(text) {
        if (!text) return "";
        return text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
</script>
@endsection
