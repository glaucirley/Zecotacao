@extends('layouts.app')

@section('page_title', 'Parâmetros e Regras do Sistema')

@section('content')
<style>
    /* Tabs Navigation */
    .param-nav-tabs {
        display: flex;
        gap: 8px;
        margin-bottom: 24px;
        border-bottom: 2px solid #e2e8f0;
        overflow-x: auto;
    }
    .param-nav-tab {
        background: transparent;
        border: none;
        padding: 12px 20px;
        font-size: 14px;
        font-weight: 700;
        color: #64748b;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        border-radius: 8px 8px 0 0;
        border-bottom: 3px solid transparent;
        margin-bottom: -2px;
        transition: all 0.2s ease;
        white-space: nowrap;
    }
    .param-nav-tab:hover {
        color: #1e293b;
    }
    .param-nav-tab.active {
        color: var(--color-primary);
        border-bottom-color: var(--color-primary);
        background: rgba(37, 99, 235, 0.05);
    }

    /* Parameter Setting Cards */
    .param-card {
        background: #ffffff;
        border-radius: 14px;
        border: 1px solid #e2e8f0;
        padding: 22px;
        margin-bottom: 18px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        transition: box-shadow 0.2s ease, border-color 0.2s ease;
    }
    .param-card:hover {
        border-color: #cbd5e1;
        box-shadow: 0 4px 12px rgba(0,0,0,0.04);
    }
    .param-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 10px;
    }
    .param-title {
        font-size: 15.5px;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .param-key-badge {
        font-family: monospace;
        font-size: 11px;
        background: #f1f5f9;
        color: #475569;
        padding: 2px 7px;
        border-radius: 5px;
        border: 1px solid #e2e8f0;
        font-weight: 600;
    }
    .param-description {
        font-size: 13.5px;
        color: #475569;
        line-height: 1.5;
        margin-bottom: 12px;
    }
    .param-example-box {
        background: #f8fafc;
        border-left: 3px solid #3b82f6;
        padding: 10px 14px;
        border-radius: 0 8px 8px 0;
        font-size: 12.5px;
        color: #334155;
        margin-bottom: 16px;
        line-height: 1.5;
    }
    .param-action-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
        padding-top: 14px;
        border-top: 1px solid #f1f5f9;
    }
    .param-input-group {
        display: flex;
        align-items: center;
        gap: 12px;
        flex: 1 1 300px;
        max-width: 520px;
    }

    /* Status Banner for ERP */
    .erp-status-banner {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 20px 24px;
        margin-bottom: 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 18px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    }
    .erp-status-left {
        display: flex;
        align-items: center;
        gap: 14px;
    }
    .erp-indicator-dot {
        width: 14px;
        height: 14px;
        border-radius: 50%;
        background-color: #10b981;
        box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.2);
        animation: pulseDot 2s infinite ease-in-out;
    }
    @keyframes pulseDot {
        0%, 100% { transform: scale(1); opacity: 1; }
        50% { transform: scale(1.15); opacity: 0.8; }
    }
</style>

<!-- Tabs Navigation (Item 8) -->
<div class="param-nav-tabs">
    <button type="button" class="param-nav-tab active" id="tab-btn-alcada" onclick="switchParamTab('alcada')">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
        </svg>
        Aprovação &amp; Alçada
    </button>
    <button type="button" class="param-nav-tab" id="tab-btn-validade" onclick="switchParamTab('validade')">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
        </svg>
        Prazos &amp; Validade
    </button>
    <button type="button" class="param-nav-tab" id="tab-btn-grande-conta" onclick="switchParamTab('grande-conta')">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
        </svg>
        Grande Conta &amp; Prioridade
    </button>
    <button type="button" class="param-nav-tab" id="tab-btn-sankhya" onclick="switchParamTab('sankhya')">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="2" y="2" width="20" height="8" rx="2" ry="2"/><rect x="2" y="14" width="20" height="8" rx="2" ry="2"/><line x1="6" y1="6" x2="6.01" y2="6"/><line x1="6" y1="18" x2="6.01" y2="18"/>
        </svg>
        Integração Sankhya ERP
    </button>
</div>

<!-- Loading Spinner -->
<div id="loading-spinner" style="text-align: center; padding: 40px 20px;">
    <div style="border: 3px solid rgba(15,81,50,0.1); border-top: 3px solid var(--color-primary); border-radius: 50%; width: 32px; height: 32px; animation: spin 1s linear infinite; margin: 0 auto;"></div>
    <div style="margin-top: 10px; font-size: 13px; color: #64748b;">Carregando parâmetros...</div>
</div>

<!-- ========================================== -->
<!-- ABA 1: APROVAÇÃO & ALÇADA                  -->
<!-- ========================================== -->
<div id="tab-content-alcada" class="tab-content-panel">

    <!-- REGRA_LIBERACAO_DESCONTO -->
    <div class="param-card">
        <div class="param-header">
            <div>
                <h4 class="param-title">
                    Critério de Liberação Comercial de Cotações
                    <span class="param-key-badge">REGRA_LIBERACAO_DESCONTO</span>
                </h4>
            </div>
            <span class="user-role-label" style="background:#0284c7;">Diretoria</span>
        </div>
        <div class="param-description">
            Define quando uma cotação precisa de aprovação de um superior hierárquico antes de ser enviada ao cliente ou convertida em pedido.
        </div>
        <div class="param-example-box">
            <strong>Exemplo prático:</strong> Quando o desconto concedido passar da alçada configurada no representante → enviar cotação ao gestor. Ex.: Representante possui 5% de alçada e concede 7% de desconto ➔ o sistema encaminha a cotação imediatamente para a fila do gestor.
        </div>
        <div class="param-action-row">
            <div class="param-input-group">
                <select id="val-REGRA_LIBERACAO_DESCONTO" class="form-control" style="font-size: 13.5px; padding: 8px 12px;">
                    <option value="ALCADA_REPRESENTANTE">Alçada do Representante (Recomendado - Exige Gestor)</option>
                    <option value="PRECO_MINIMO">Apenas Preço Mínimo (Libera se acima do mínimo)</option>
                </select>
            </div>
            <button type="button" class="btn btn-primary" onclick="saveParameter('REGRA_LIBERACAO_DESCONTO', this)" style="padding: 8px 20px; font-weight: 600; font-size: 13px;">
                Salvar Alteração
            </button>
        </div>
    </div>

    <!-- DESCONTO_AVALIACAO_MODO -->
    <div class="param-card">
        <div class="param-header">
            <div>
                <h4 class="param-title">
                    Modo de Avaliação do Desconto
                    <span class="param-key-badge">DESCONTO_AVALIACAO_MODO</span>
                </h4>
            </div>
            <span class="user-role-label" style="background:#0284c7;">Diretoria</span>
        </div>
        <div class="param-description">
            Determina se a validação da alçada de desconto é checada item por item ou pela média ponderada de toda a proposta.
        </div>
        <div class="param-example-box">
            <strong>Exemplo prático:</strong> No modo <em>Item a Item</em> (mais seguro), se 3 produtos estiverem com desconto de 2% e 1 produto estiver com 8%, a cotação exige aprovação pelo item excedente. Na <em>Média Total</em>, o desconto médio da proposta inteira é usado.
        </div>
        <div class="param-action-row">
            <div class="param-input-group">
                <select id="val-DESCONTO_AVALIACAO_MODO" class="form-control" style="font-size: 13.5px; padding: 8px 12px;">
                    <option value="ITEM_A_ITEM">Item a Item (Mais Rigoroso / Recomendado)</option>
                    <option value="MEDIA_TOTAL">Média Total Ponderada da Proposta</option>
                </select>
            </div>
            <button type="button" class="btn btn-primary" onclick="saveParameter('DESCONTO_AVALIACAO_MODO', this)" style="padding: 8px 20px; font-weight: 600; font-size: 13px;">
                Salvar Alteração
            </button>
        </div>
    </div>

    <!-- EXIGE_ANEXO_JUSTIFICATIVA -->
    <div class="param-card">
        <div class="param-header">
            <div>
                <h4 class="param-title">
                    Obrigatoriedade de Comprovante em Justificativa
                    <span class="param-key-badge">EXIGE_ANEXO_JUSTIFICATIVA</span>
                </h4>
            </div>
            <span class="user-role-label" style="background:#0284c7;">Diretoria</span>
        </div>
        <div class="param-description">
            Obriga o representante a anexar um documento ou foto (ex: orçamento de concorrente ou nota fiscal recente) quando solicitar desconto abaixo do preço mínimo de tabela.
        </div>
        <div class="param-example-box">
            <strong>Exemplo prático:</strong> Se ativado, o representante não consegue enviar a cotação para aprovação apenas digitando texto; ele deve obrigatoriamente fazer upload do comprovante para a diretoria analisar.
        </div>
        <div class="param-action-row">
            <div class="param-input-group">
                <select id="val-EXIGE_ANEXO_JUSTIFICATIVA" class="form-control" style="font-size: 13.5px; padding: 8px 12px;">
                    <option value="true">Ativo (Exigir anexo de documento/comprovante)</option>
                    <option value="false">Inativo (Permitir justificativa apenas por texto)</option>
                </select>
            </div>
            <button type="button" class="btn btn-primary" onclick="saveParameter('EXIGE_ANEXO_JUSTIFICATIVA', this)" style="padding: 8px 20px; font-weight: 600; font-size: 13px;">
                Salvar Alteração
            </button>
        </div>
    </div>

    <!-- SEM_GESTOR_ACAO -->
    <div class="param-card">
        <div class="param-header">
            <div>
                <h4 class="param-title">
                    Ação Quando Representante Não Possui Gestor Ativo
                    <span class="param-key-badge">SEM_GESTOR_ACAO</span>
                </h4>
            </div>
            <span class="user-role-label" style="background:#0284c7;">Diretoria</span>
        </div>
        <div class="param-description">
            Define o comportamento do fluxo comercial quando um representante comercial solicita aprovação de desconto, mas sua equipe não tem gestor vinculado ou ativo.
        </div>
        <div class="param-example-box">
            <strong>Exemplo prático:</strong> Se configurado como <em>Encaminhar para Diretoria</em>, a cotação vai direto para a fila dos diretores. Se for <em>Bloquear Envio</em>, o representante é alertado para entrar em contato com o suporte comercial.
        </div>
        <div class="param-action-row">
            <div class="param-input-group">
                <select id="val-SEM_GESTOR_ACAO" class="form-control" style="font-size: 13.5px; padding: 8px 12px;">
                    <option value="BLOQUEAR">Bloquear Envio (Exigir Gestor Ativo Cadastrado)</option>
                    <option value="DIRETORIA">Encaminhar Diretamente para a Diretoria</option>
                </select>
            </div>
            <button type="button" class="btn btn-primary" onclick="saveParameter('SEM_GESTOR_ACAO', this)" style="padding: 8px 20px; font-weight: 600; font-size: 13px;">
                Salvar Alteração
            </button>
        </div>
    </div>

    <!-- REENVIO_PARCIAL_MODO -->
    <div class="param-card">
        <div class="param-header">
            <div>
                <h4 class="param-title">
                    Recálculo em Reabertura de Cotação Devolvida
                    <span class="param-key-badge">REENVIO_PARCIAL_MODO</span>
                </h4>
            </div>
            <span class="user-role-label" style="background:#0284c7;">Diretoria</span>
        </div>
        <div class="param-description">
            Determina como o sistema reavalia as alçadas quando o gestor devolve a cotação para ajuste e o representante realiza alterações e reenvia.
        </div>
        <div class="param-example-box">
            <strong>Exemplo prático:</strong> <em>Recalcula Tudo</em> reavalia a conformidade e os preços de todos os produtos do pedido. <em>Só Itens Alterados</em> mantém a aprovação dos itens que o representante não mexeu.
        </div>
        <div class="param-action-row">
            <div class="param-input-group">
                <select id="val-REENVIO_PARCIAL_MODO" class="form-control" style="font-size: 13.5px; padding: 8px 12px;">
                    <option value="RECALCULA_TUDO">Recalcula Tudo (Reavalia Todos os Itens)</option>
                    <option value="SO_ITENS_ALTERADOS">Só Itens Alterados (Preserva Itens Inalterados)</option>
                </select>
            </div>
            <button type="button" class="btn btn-primary" onclick="saveParameter('REENVIO_PARCIAL_MODO', this)" style="padding: 8px 20px; font-weight: 600; font-size: 13px;">
                Salvar Alteração
            </button>
        </div>
    </div>

</div>

<!-- ========================================== -->
<!-- ABA 2: PRAZOS & VALIDADE                   -->
<!-- ========================================== -->
<div id="tab-content-validade" class="tab-content-panel" style="display: none;">

    <!-- VALIDADE_PADRAO_HORAS -->
    <div class="param-card">
        <div class="param-header">
            <div>
                <h4 class="param-title">
                    Prazo Padrão de Validade da Cotação (Horas)
                    <span class="param-key-badge">VALIDADE_PADRAO_HORAS</span>
                </h4>
            </div>
            <span class="user-role-label" style="background:#0284c7;">Diretoria</span>
        </div>
        <div class="param-description">
            Período de tempo (em horas) durante o qual as condições comerciais e preços acordados permanecem válidos. Após o término deste prazo, a cotação expira e não pode mais ser fechada sem revalidação de estoque e preços.
        </div>
        <div class="param-example-box">
            <strong>Exemplo prático:</strong> Valor configurado: <code>24</code> horas. Se uma cotação for criada na terça-feira às 14:00, ela expira na quarta-feira às 14:00 ("Vence em 24h"). Cotações expiradas saem da lista de ativas e bloqueiam faturamento direto.
        </div>
        <div class="param-action-row">
            <div class="param-input-group">
                <div style="position: relative; width: 100%; max-width: 200px;">
                    <input type="number" id="val-VALIDADE_PADRAO_HORAS" class="form-control" min="1" max="720" style="font-size: 14px; padding: 8px 36px 8px 12px; font-weight: 600;">
                    <span style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); font-size: 12px; color: #64748b; font-weight: 600;">horas</span>
                </div>
                <span style="font-size: 12px; color: #64748b;">(Ex: 24h = 1 dia, 48h = 2 dias, 72h = 3 dias)</span>
            </div>
            <button type="button" class="btn btn-primary" onclick="saveParameter('VALIDADE_PADRAO_HORAS', this)" style="padding: 8px 20px; font-weight: 600; font-size: 13px;">
                Salvar Alteração
            </button>
        </div>
    </div>

</div>

<!-- ========================================== -->
<!-- ABA 3: GRANDE CONTA & PRIORIDADE          -->
<!-- ========================================== -->
<div id="tab-content-grande-conta" class="tab-content-panel" style="display: none;">

    <!-- ALCADA_GRANDE_CONTA_VALOR -->
    <div class="param-card">
        <div class="param-header">
            <div>
                <h4 class="param-title">
                    Gatilho de Valor Financeiro Total (R$)
                    <span class="param-key-badge">ALCADA_GRANDE_CONTA_VALOR</span>
                </h4>
            </div>
            <span class="user-role-label" style="background:#0284c7;">Diretoria</span>
        </div>
        <div class="param-description">
            Cotações com valor bruto ou total igual ou superior a este montante recebem automaticamente a etiqueta de <strong>Grande Conta</strong> e prioridade máxima na fila de análise da gerência.
        </div>
        <div class="param-example-box">
            <strong>Exemplo prático:</strong> Valor configurado: <code>R$ 10.000,00</code>. Um pedido de R$ 12.500,00 entra no topo da fila dos gestores com destaque dourado de alta relevância comercial.
        </div>
        <div class="param-action-row">
            <div class="param-input-group">
                <div style="position: relative; width: 100%; max-width: 220px;">
                    <span style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); font-size: 12px; color: #64748b; font-weight: 600;">R$</span>
                    <input type="number" step="0.01" id="val-ALCADA_GRANDE_CONTA_VALOR" class="form-control" style="font-size: 14px; padding: 8px 12px 8px 36px; font-weight: 600;">
                </div>
            </div>
            <button type="button" class="btn btn-primary" onclick="saveParameter('ALCADA_GRANDE_CONTA_VALOR', this)" style="padding: 8px 20px; font-weight: 600; font-size: 13px;">
                Salvar Alteração
            </button>
        </div>
    </div>

    <!-- ALCADA_GRANDE_CONTA_QTD -->
    <div class="param-card">
        <div class="param-header">
            <div>
                <h4 class="param-title">
                    Gatilho de Volume de Peças / Itens
                    <span class="param-key-badge">ALCADA_GRANDE_CONTA_QTD</span>
                </h4>
            </div>
            <span class="user-role-label" style="background:#0284c7;">Diretoria</span>
        </div>
        <div class="param-description">
            Quantidade total somada de itens/unidades na cotação para acionar a classificação de Grande Conta devido ao volume de entrega e impacto na expedição.
        </div>
        <div class="param-example-box">
            <strong>Exemplo prático:</strong> Valor configurado: <code>100</code> unidades. Um pedido de 150 unidades dispara prioridade alta para verificação de capacidade de fornecimento e conferência rápida.
        </div>
        <div class="param-action-row">
            <div class="param-input-group">
                <div style="position: relative; width: 100%; max-width: 220px;">
                    <input type="number" id="val-ALCADA_GRANDE_CONTA_QTD" class="form-control" style="font-size: 14px; padding: 8px 38px 8px 12px; font-weight: 600;">
                    <span style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); font-size: 12px; color: #64748b; font-weight: 600;">unid.</span>
                </div>
            </div>
            <button type="button" class="btn btn-primary" onclick="saveParameter('ALCADA_GRANDE_CONTA_QTD', this)" style="padding: 8px 20px; font-weight: 600; font-size: 13px;">
                Salvar Alteração
            </button>
        </div>
    </div>

    <!-- ALCADA_GRANDE_CONTA_MARGEM -->
    <div class="param-card">
        <div class="param-header">
            <div>
                <h4 class="param-title">
                    Margem Crítica de Alerta (%)
                    <span class="param-key-badge">ALCADA_GRANDE_CONTA_MARGEM</span>
                </h4>
            </div>
            <span class="user-role-label" style="background:#0284c7;">Diretoria</span>
        </div>
        <div class="param-description">
            Percentual mínimo de margem operacional. Se a margem calculada da cotação for igual ou menor que este percentual, a cotação é marcada como margem de risco.
        </div>
        <div class="param-example-box">
            <strong>Exemplo prático:</strong> Valor configurado: <code>15.0%</code>. Se o vendedor conceder descontos que resultem em uma margem de 11.5%, a cotação dispara alerta visual para a diretoria avaliar a viabilidade antes da aprovação.
        </div>
        <div class="param-action-row">
            <div class="param-input-group">
                <div style="position: relative; width: 100%; max-width: 180px;">
                    <input type="number" step="0.1" id="val-ALCADA_GRANDE_CONTA_MARGEM" class="form-control" style="font-size: 14px; padding: 8px 30px 8px 12px; font-weight: 600;">
                    <span style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); font-size: 13px; color: #64748b; font-weight: 600;">%</span>
                </div>
            </div>
            <button type="button" class="btn btn-primary" onclick="saveParameter('ALCADA_GRANDE_CONTA_MARGEM', this)" style="padding: 8px 20px; font-weight: 600; font-size: 13px;">
                Salvar Alteração
            </button>
        </div>
    </div>

</div>

<!-- ========================================== -->
<!-- ABA 4: INTEGRAÇÃO SANKHYA ERP (Item 8)     -->
<!-- ========================================== -->
<div id="tab-content-sankhya" class="tab-content-panel" style="display: none;">

    <!-- Live Connection Banner (Item 8: "Conectado · última sincronização há 2h") -->
    <div class="erp-status-banner">
        <div class="erp-status-left">
            <div class="erp-indicator-dot" id="erp-status-dot"></div>
            <div>
                <div style="font-size: 15px; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                    <span id="erp-status-title">Conectado ao ERP Sankhya (Banco Oracle)</span>
                    <span class="user-role-label" style="background:#059669;">Sincronização Ativa</span>
                </div>
                <div style="font-size: 12.5px; color: #64748b; margin-top: 3px;" id="erp-last-sync-text">
                    Conexão pronta · Última sincronização de dados: recente (Hoje)
                </div>
            </div>
        </div>
        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <button type="button" class="btn btn-secondary" onclick="testConnection()" id="btn-test-conn" style="font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                Testar Conexão
            </button>
            <button type="button" class="btn btn-primary" onclick="syncCatalog()" id="btn-sync-catalog" style="font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>
                Sincronizar Catálogo Agora
            </button>
        </div>
    </div>

    <!-- Technical Connection Form -->
    <div class="card" style="box-shadow: 0 1px 3px rgba(0,0,0,0.02); border-radius: 14px;">
        <div class="card-header" style="background:#ffffff; border-bottom: 1px solid #e2e8f0; padding: 18px 24px;">
            <h3 style="font-size: 15px; font-weight: 700; color: #0f172a; margin: 0;">Parâmetros Técnicos de Conectividade Oracle</h3>
        </div>
        <div style="padding: 24px;">
            <form id="sankhya-config-form" onsubmit="saveSankhyaConfig(event)">
                <!-- Row 1 -->
                <div class="grid-3" style="gap: 20px; margin-bottom: 20px;">
                    <div>
                        <label class="form-label" style="font-weight: 600; margin-bottom: 8px; display: block; font-size: 13px;">Tipo de Conexão</label>
                        <select id="sankhya-tipo" class="form-control" onchange="toggleSshFields()" required>
                            <option value="DIRETO">Conexão Direta (TCP)</option>
                            <option value="SSH_TUNNEL">Túnel SSH (Redirecionamento Localhost)</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label" style="font-weight: 600; margin-bottom: 8px; display: block; font-size: 13px;">Host do Banco Oracle</label>
                        <input type="text" id="sankhya-host" class="form-control" placeholder="ex: 192.168.1.100" required>
                        <span style="font-size: 11px; color: var(--color-text-muted);" id="sankhya-host-note">Utilize o endereço IP ou Host real do servidor Oracle.</span>
                    </div>
                    <div>
                        <label class="form-label" style="font-weight: 600; margin-bottom: 8px; display: block; font-size: 13px;">Porta do Banco Oracle</label>
                        <input type="number" id="sankhya-port" class="form-control" value="1521" required>
                    </div>
                </div>

                <!-- Row 2 -->
                <div class="grid-3" style="gap: 20px; margin-bottom: 20px;">
                    <div>
                        <label class="form-label" style="font-weight: 600; margin-bottom: 8px; display: block; font-size: 13px;">Nome do Serviço / SID Oracle</label>
                        <input type="text" id="sankhya-name" class="form-control" placeholder="ex: XE" required>
                    </div>
                    <div>
                        <label class="form-label" style="font-weight: 600; margin-bottom: 8px; display: block; font-size: 13px;">Usuário do Banco</label>
                        <input type="text" id="sankhya-user" class="form-control" required>
                    </div>
                    <div>
                        <label class="form-label" style="font-weight: 600; margin-bottom: 8px; display: block; font-size: 13px;">Senha do Banco</label>
                        <div style="position: relative; display: flex; align-items: center;">
                            <input type="password" id="sankhya-pass" class="form-control" placeholder="Preencha apenas para alterar" style="padding-right: 40px;">
                            <button type="button" onclick="togglePassVisibility()" style="position: absolute; right: 10px; background: none; border: none; cursor: pointer; color: var(--color-text-muted); display: flex; align-items: center; justify-content: center; height: 100%;">
                                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Automatic Sync Scheduling Row -->
                <div class="grid-2" style="gap: 20px; margin-bottom: 20px; border-top: 1px solid var(--color-border); padding-top: 20px;">
                    <div>
                        <label class="form-label" style="font-weight: 600; margin-bottom: 8px; display: block; font-size: 13px;">Sincronização Automática (Background)</label>
                        <select id="sankhya-auto" class="form-control" onchange="toggleAutoSyncInterval()" required>
                            <option value="false">Desativada (Apenas manual)</option>
                            <option value="true">Ativada (Fundo / Agendada)</option>
                        </select>
                        <span style="font-size: 11px; color: var(--color-text-muted);">Requer a ativação do Scheduler / Cron no servidor da hospedagem.</span>
                    </div>
                    <div id="sync-interval-container" style="display: none;">
                        <label class="form-label" style="font-weight: 600; margin-bottom: 8px; display: block; font-size: 13px;">Intervalo da Sincronização</label>
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
                            <label class="form-label" style="font-weight: 600; margin-bottom: 8px; display: block; font-size: 13px;">Host SSH (Jump Server)</label>
                            <input type="text" id="sankhya-ssh-host" class="form-control" placeholder="ex: jump.meuprovedor.com">
                        </div>
                        <div>
                            <label class="form-label" style="font-weight: 600; margin-bottom: 8px; display: block; font-size: 13px;">Porta SSH</label>
                            <input type="number" id="sankhya-ssh-port" class="form-control" value="22">
                        </div>
                        <div>
                            <label class="form-label" style="font-weight: 600; margin-bottom: 8px; display: block; font-size: 13px;">Usuário SSH</label>
                            <input type="text" id="sankhya-ssh-user" class="form-control">
                        </div>
                    </div>
                    <div style="background-color: rgba(59, 130, 246, 0.05); padding: 12px 16px; border-radius: 8px; margin-top: 15px; font-size: 12px; color: var(--color-text-muted); line-height: 1.5; border: 1px solid rgba(59,130,246,0.15);">
                        <strong>💡 Como funciona o Túnel SSH persistente no servidor:</strong><br>
                        Para que a plataforma converse com a porta 1521 localmente, você deve rodar no terminal do servidor o seguinte comando:<br>
                        <code>ssh -N -L 1521:IP_REMOTO_DO_ORACLE:1521 usuario_ssh@IP_JUMP_SERVER -p PORTA_SSH</code>
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; margin-top: 20px; border-top: 1px solid var(--color-border); padding-top: 20px;">
                    <button type="submit" class="btn btn-primary" id="btn-save-sankhya" style="font-weight: 600; padding: 10px 24px; font-size: 13.5px;">
                        Salvar Conectividade
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<!-- Container for any custom/dynamic unknown parameters -->
<div id="tab-content-outros" class="tab-content-panel" style="display: none;">
    <div id="dynamic-other-params"></div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener("DOMContentLoaded", () => {
        loadParameters();
    });

    // Switch between the 4 thematic sections
    function switchParamTab(tabKey) {
        document.querySelectorAll('.param-nav-tab').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-content-panel').forEach(p => p.style.display = 'none');

        const tabBtn = document.getElementById(`tab-btn-${tabKey}`);
        const tabContent = document.getElementById(`tab-content-${tabKey}`);

        if (tabBtn) tabBtn.classList.add('active');
        if (tabContent) tabContent.style.display = 'block';
    }

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

                // Technical Sankhya keys
                const connectionKeys = [
                    'SANKHYA_CONN_TIPO', 'SANKHYA_DB_HOST', 'SANKHYA_DB_PORT', 
                    'SANKHYA_DB_NAME', 'SANKHYA_DB_USER', 'SANKHYA_DB_PASS',
                    'SANKHYA_SSH_HOST', 'SANKHYA_SSH_PORT', 'SANKHYA_SSH_USER',
                    'SANKHYA_SYNC_AUTO', 'SANKHYA_SYNC_INTERVALO'
                ];

                list.forEach(p => {
                    // Populate technical Oracle fields
                    if (connectionKeys.includes(p.chave)) {
                        if (p.chave === 'SANKHYA_CONN_TIPO') {
                            const el = document.getElementById('sankhya-tipo');
                            if (el) el.value = (p.valor || 'DIRETO').toUpperCase();
                            toggleSshFields();
                        }
                        if (p.chave === 'SANKHYA_DB_HOST') {
                            const el = document.getElementById('sankhya-host');
                            if (el) el.value = p.valor || '';
                        }
                        if (p.chave === 'SANKHYA_DB_PORT') {
                            const el = document.getElementById('sankhya-port');
                            if (el) el.value = p.valor || '1521';
                        }
                        if (p.chave === 'SANKHYA_DB_NAME') {
                            const el = document.getElementById('sankhya-name');
                            if (el) el.value = p.valor || '';
                        }
                        if (p.chave === 'SANKHYA_DB_USER') {
                            const el = document.getElementById('sankhya-user');
                            if (el) el.value = p.valor || '';
                        }
                        if (p.chave === 'SANKHYA_SSH_HOST') {
                            const el = document.getElementById('sankhya-ssh-host');
                            if (el) el.value = p.valor || '';
                        }
                        if (p.chave === 'SANKHYA_SSH_PORT') {
                            const el = document.getElementById('sankhya-ssh-port');
                            if (el) el.value = p.valor || '22';
                        }
                        if (p.chave === 'SANKHYA_SSH_USER') {
                            const el = document.getElementById('sankhya-ssh-user');
                            if (el) el.value = p.valor || '';
                        }
                        if (p.chave === 'SANKHYA_SYNC_AUTO') {
                            const el = document.getElementById('sankhya-auto');
                            const isAuto = p.valor === 'true' || p.valor === '1' || p.valor === 1;
                            if (el) el.value = isAuto ? 'true' : 'false';
                            toggleAutoSyncInterval();
                        }
                        if (p.chave === 'SANKHYA_SYNC_INTERVALO') {
                            const el = document.getElementById('sankhya-intervalo');
                            if (el) el.value = p.valor || 'DIARIO';
                        }
                        return;
                    }

                    // Populate pre-rendered thematic inputs
                    const inputEl = document.getElementById(`val-${p.chave}`);
                    if (inputEl) {
                        const safeVal = p.valor !== null && p.valor !== undefined ? p.valor : '';
                        inputEl.value = safeVal;
                    }
                });
            } else {
                showToast("Erro ao carregar parâmetros: " + (data.error || 'Erro desconhecido'), "error");
            }
        } catch (e) {
            console.error(e);
            showToast("Erro de conexão ao carregar parâmetros.", "error");
        }
    }

    async function saveParameter(chave, btnEl) {
        const inputEl = document.getElementById(`val-${chave}`);
        if (!inputEl) return;

        const val = inputEl.value;
        const originalText = btnEl ? btnEl.innerText : 'Salvar';
        if (btnEl) {
            btnEl.innerText = "Salvando...";
            btnEl.disabled = true;
        }

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
                showToast(`Parâmetro atualizado com sucesso!`, "success");
            } else {
                showToast("Erro ao salvar: " + (data.message || data.error), "error");
            }
        } catch (e) {
            showToast("Erro de comunicação ao salvar parâmetro.", "error");
        } finally {
            if (btnEl) {
                btnEl.innerText = originalText;
                btnEl.disabled = false;
            }
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
                showToast("Conectividade do Sankhya salva com sucesso!", "success");
                document.getElementById("sankhya-pass").value = "";
                loadParameters();
            } else {
                showToast("Erro ao salvar: " + (data.message || data.error), "error");
            }
        } catch(err) {
            showToast("Erro de rede ao salvar configurações do Sankhya.", "error");
        } finally {
            btn.innerText = originalText;
            btn.disabled = false;
        }
    }

    async function testConnection() {
        const btn = document.getElementById("btn-test-conn");
        const originalText = btn.innerHTML;
        btn.innerHTML = "<span>Testando...</span>";
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
                showToast("Conexão bem-sucedida! " + data.message, "success");
                document.getElementById("erp-status-dot").style.backgroundColor = "#10b981";
                document.getElementById("erp-status-title").innerText = "Conectado ao ERP Sankhya (Banco Oracle)";
            } else {
                showToast("Falha na conexão: " + data.message, "error");
                document.getElementById("erp-status-dot").style.backgroundColor = "#ef4444";
                document.getElementById("erp-status-title").innerText = "Falha ao conectar com o ERP Sankhya";
            }
        } catch(err) {
            showToast("Erro de rede ao testar conexão.", "error");
        } finally {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    }

    function syncCatalog() {
        appConfirmModal({
            title: "Sincronizar Catálogo com Sankhya",
            message: "Esta ação importará e atualizará Clientes, Produtos, Vendedores e Tabelas de Preço do banco Oracle do Sankhya. Deseja iniciar agora?",
            confirmText: "Sincronizar Agora",
            cancelText: "Cancelar",
            isDanger: false,
            onConfirm: async () => {
                const btn = document.getElementById("btn-sync-catalog");
                const originalHtml = btn.innerHTML;
                btn.innerHTML = "<span>Sincronizando...</span>";
                btn.disabled = true;

                try {
                    const res = await fetch("{{ url('/api/v1/parametros/sankhya/sincronizar') }}", {
                        method: "POST",
                        headers: {
                            "Accept": "application/json",
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });

                    const rawText = await res.text();
                    let data;
                    try {
                        data = JSON.parse(rawText);
                    } catch (e) {
                        throw new Error("Resposta inesperada do servidor: " + rawText.substring(0, 150));
                    }

                    if (data.success) {
                        showToast(data.message, "success");
                        const lastSyncEl = document.getElementById("erp-last-sync-text");
                        if (lastSyncEl) {
                            lastSyncEl.innerText = "Conectado · Sincronizado agora mesmo com sucesso!";
                        }
                    } else {
                        showToast(data.message || data.error || "Falha na sincronização.", "error");
                    }
                } catch(err) {
                    console.error("Sync error:", err);
                    showToast("Erro durante a sincronização: " + err.message, "error");
                } finally {
                    btn.innerHTML = originalHtml;
                    btn.disabled = false;
                }
            }
        });
    }
</script>
@endsection
