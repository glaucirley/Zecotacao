<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ParametroSistema;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ParameterController extends Controller
{
    /**
     * List all system parameters. Only accessible to Directors.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        if (!$user->isAdministrador() && !$user->isDiretor()) {
            return response()->json(['error' => 'Forbidden. Only administrators and directors can view or manage system parameters.'], 403);
        }

        $defaults = [
            ['chave' => 'REENVIO_PARCIAL_MODO', 'valor' => 'RECALCULA_TUDO', 'descricao' => 'Modo de processamento de cotacoes devolvidas ao reabrir para edicao (RECALCULA_TUDO ou SO_ITENS_ALTERADOS)', 'tipo' => 'texto', 'editavel_por' => 'diretor'],
            ['chave' => 'VALIDADE_PADRAO_HORAS', 'valor' => '24', 'descricao' => 'Validade padrao em horas de uma nova cotacao', 'tipo' => 'numero', 'editavel_por' => 'diretor'],
            ['chave' => 'EXIGE_ANEXO_JUSTIFICATIVA', 'valor' => 'true', 'descricao' => 'Exige anexo de comprovante/documento ao enviar justificativa de desconto abaixo do preco minimo', 'tipo' => 'booleano', 'editavel_por' => 'diretor'],
            ['chave' => 'REGRA_LIBERACAO_DESCONTO', 'valor' => 'ALCADA_REPRESENTANTE', 'descricao' => 'Regra para liberação automática de cotação: ALCADA_REPRESENTANTE (desconto acima da alçada do vendedor exige aprovação) ou PRECO_MINIMO (libera se estiver acima do mínimo)', 'tipo' => 'texto', 'editavel_por' => 'diretor'],
            ['chave' => 'SEM_GESTOR_ACAO', 'valor' => 'BLOQUEAR', 'descricao' => 'Ação quando representante não possui gestor ativo configurado: BLOQUEAR envio ou encaminhar para DIRETORIA', 'tipo' => 'texto', 'editavel_por' => 'diretor'],
            ['chave' => 'DESCONTO_AVALIACAO_MODO', 'valor' => 'ITEM_A_ITEM', 'descricao' => 'Modo de avaliacao de desconto para alcada de aprovacao (ITEM_A_ITEM ou MEDIA_TOTAL)', 'tipo' => 'texto', 'editavel_por' => 'diretor'],
            ['chave' => 'ALCADA_GRANDE_CONTA_VALOR', 'valor' => '10000.00', 'descricao' => 'Valor total limite (igual ou maior) para classificar uma cotação como Grande Conta / Alta Prioridade', 'tipo' => 'numero', 'editavel_por' => 'diretor'],
            ['chave' => 'ALCADA_GRANDE_CONTA_QTD', 'valor' => '100', 'descricao' => 'Quantidade total de itens (igual ou maior) para classificar uma cotação como Grande Conta / Alta Prioridade', 'tipo' => 'numero', 'editavel_por' => 'diretor'],
            ['chave' => 'ALCADA_GRANDE_CONTA_MARGEM', 'valor' => '15.00', 'descricao' => 'Margem geral calculada (igual ou menor) para disparar alerta comercial de margem baixa', 'tipo' => 'numero', 'editavel_por' => 'diretor'],
            ['chave' => 'SANKHYA_CONN_TIPO', 'valor' => 'DIRETO', 'descricao' => 'Tipo de conexao com o banco de dados do Sankhya (DIRETO ou SSH_TUNNEL)', 'tipo' => 'texto', 'editavel_por' => 'administrador'],
            ['chave' => 'SANKHYA_DB_HOST', 'valor' => '127.0.0.1', 'descricao' => 'Endereço IP ou hostname do banco de dados Oracle do Sankhya', 'tipo' => 'texto', 'editavel_por' => 'administrador'],
            ['chave' => 'SANKHYA_DB_PORT', 'valor' => '1521', 'descricao' => 'Porta do banco de dados Oracle do Sankhya', 'tipo' => 'numero', 'editavel_por' => 'administrador'],
            ['chave' => 'SANKHYA_DB_NAME', 'valor' => 'XE', 'descricao' => 'Nome do Serviço ou SID do Oracle do Sankhya', 'tipo' => 'texto', 'editavel_por' => 'administrador'],
            ['chave' => 'SANKHYA_DB_USER', 'valor' => 'sankhya', 'descricao' => 'Usuário do banco de dados Oracle do Sankhya', 'tipo' => 'texto', 'editavel_por' => 'administrador'],
            ['chave' => 'SANKHYA_DB_PASS', 'valor' => '', 'descricao' => 'Senha criptografada do banco de dados Oracle do Sankhya', 'tipo' => 'texto', 'editavel_por' => 'administrador'],
            ['chave' => 'SANKHYA_SSH_HOST', 'valor' => '', 'descricao' => 'Host/IP do servidor SSH intermediário (informativo)', 'tipo' => 'texto', 'editavel_por' => 'administrador'],
            ['chave' => 'SANKHYA_SSH_PORT', 'valor' => '22', 'descricao' => 'Porta do servidor SSH intermediário (informativo)', 'tipo' => 'numero', 'editavel_por' => 'administrador'],
            ['chave' => 'SANKHYA_SSH_USER', 'valor' => '', 'descricao' => 'Usuário do servidor SSH intermediário (informativo)', 'tipo' => 'texto', 'editavel_por' => 'administrador'],
            ['chave' => 'SANKHYA_SYNC_AUTO', 'valor' => 'false', 'descricao' => 'Sincronização automática ativa', 'tipo' => 'booleano', 'editavel_por' => 'administrador'],
            ['chave' => 'SANKHYA_SYNC_INTERVALO', 'valor' => 'DIARIO', 'descricao' => 'Intervalo de sincronização automática', 'tipo' => 'texto', 'editavel_por' => 'administrador'],
        ];

        foreach ($defaults as $d) {
            ParametroSistema::firstOrCreate(['chave' => $d['chave']], $d);
        }

        $params = ParametroSistema::all();

        return response()->json([
            'success' => true,
            'data' => $params
        ]);
    }

    /**
     * Update a specific system parameter. Only accessible to Directors.
     */
    public function update(Request $request, $chave)
    {
        $user = Auth::user();

        if (!$user->isAdministrador() && !$user->isDiretor()) {
            return response()->json(['error' => 'Forbidden. Only administrators and directors can edit system parameters.'], 403);
        }

        $param = ParametroSistema::where('chave', $chave)->firstOrFail();

        $request->validate([
            'valor' => 'required|string'
        ]);

        $valor = $request->input('valor');

        // Parameter-specific business validation
        if ($chave === 'DESCONTO_AVALIACAO_MODO' && !in_array($valor, ['ITEM_A_ITEM', 'MEDIA_TOTAL'])) {
            return response()->json([
                'error' => 'Validation error',
                'message' => 'DESCONTO_AVALIACAO_MODO deve ser ITEM_A_ITEM ou MEDIA_TOTAL.'
            ], 422);
        }

        if ($chave === 'REENVIO_PARCIAL_MODO' && !in_array($valor, ['RECALCULA_TUDO', 'SO_ITENS_ALTERADOS'])) {
            return response()->json([
                'error' => 'Validation error',
                'message' => 'REENVIO_PARCIAL_MODO deve ser RECALCULA_TUDO ou SO_ITENS_ALTERADOS.'
            ], 422);
        }

        if ($param->tipo === 'booleano' && !in_array(strtolower($valor), ['true', 'false', '1', '0'])) {
            return response()->json([
                'error' => 'Validation error',
                'message' => 'O valor deve ser booleano (true ou false).'
            ], 422);
        }

        if ($param->tipo === 'numero' && !is_numeric($valor)) {
            return response()->json([
                'error' => 'Validation error',
                'message' => 'O valor deve ser um número válido.'
            ], 422);
        }

        $param->update(['valor' => $valor]);

        return response()->json([
            'success' => true,
            'message' => "Parâmetro '{$chave}' atualizado com sucesso.",
            'data' => $param
        ]);
    }

    /**
     * Save Sankhya Oracle connection parameters.
     */
    public function saveSankhyaSettings(Request $request)
    {
        $user = Auth::user();
        if (!$user->isAdministrador()) {
            return response()->json(['error' => 'Forbidden. Only administrators can configure Sankhya.'], 403);
        }

        $request->validate([
            'tipo' => 'required|in:DIRETO,SSH_TUNNEL',
            'host' => 'required|string',
            'port' => 'required|numeric',
            'name' => 'required|string',
            'user' => 'required|string',
            'pass' => 'nullable|string',
            'ssh_host' => 'nullable|string',
            'ssh_port' => 'nullable|numeric',
            'ssh_user' => 'nullable|string',
            'auto_sync' => 'required|boolean',
            'intervalo' => 'required|in:DIARIO,CADA_12_HORAS,CADA_6_HORAS,HORARIO',
        ]);

        ParametroSistema::updateOrCreate(['chave' => 'SANKHYA_CONN_TIPO'], ['valor' => $request->tipo, 'descricao' => 'Tipo de conexao com o banco de dados do Sankhya (DIRETO ou SSH_TUNNEL)', 'tipo' => 'texto', 'editavel_por' => 'administrador']);
        ParametroSistema::updateOrCreate(['chave' => 'SANKHYA_DB_HOST'], ['valor' => $request->host, 'descricao' => 'Endereço IP ou hostname do banco de dados Oracle do Sankhya', 'tipo' => 'texto', 'editavel_por' => 'administrador']);
        ParametroSistema::updateOrCreate(['chave' => 'SANKHYA_DB_PORT'], ['valor' => (string)$request->port, 'descricao' => 'Porta do banco de dados Oracle do Sankhya', 'tipo' => 'numero', 'editavel_por' => 'administrador']);
        ParametroSistema::updateOrCreate(['chave' => 'SANKHYA_DB_NAME'], ['valor' => $request->name, 'descricao' => 'Nome do Serviço ou SID do Oracle do Sankhya', 'tipo' => 'texto', 'editavel_por' => 'administrador']);
        ParametroSistema::updateOrCreate(['chave' => 'SANKHYA_DB_USER'], ['valor' => $request->user, 'descricao' => 'Usuário do banco de dados Oracle do Sankhya', 'tipo' => 'texto', 'editavel_por' => 'administrador']);

        if ($request->filled('pass')) {
            $encrypted = \Illuminate\Support\Facades\Crypt::encryptString($request->pass);
            ParametroSistema::updateOrCreate(['chave' => 'SANKHYA_DB_PASS'], ['valor' => $encrypted, 'descricao' => 'Senha criptografada do banco de dados Oracle do Sankhya', 'tipo' => 'texto', 'editavel_por' => 'administrador']);
        }

        ParametroSistema::updateOrCreate(['chave' => 'SANKHYA_SSH_HOST'], ['valor' => $request->ssh_host ?? '', 'descricao' => 'Host/IP do servidor SSH intermediário (informativo)', 'tipo' => 'texto', 'editavel_por' => 'administrador']);
        ParametroSistema::updateOrCreate(['chave' => 'SANKHYA_SSH_PORT'], ['valor' => (string)($request->ssh_port ?? '22'), 'descricao' => 'Porta do servidor SSH intermediário (informativo)', 'tipo' => 'numero', 'editavel_por' => 'administrador']);
        ParametroSistema::updateOrCreate(['chave' => 'SANKHYA_SSH_USER'], ['valor' => $request->ssh_user ?? '', 'descricao' => 'Usuário do servidor SSH intermediário (informativo)', 'tipo' => 'texto', 'editavel_por' => 'administrador']);
        
        ParametroSistema::updateOrCreate(['chave' => 'SANKHYA_SYNC_AUTO'], ['valor' => $request->auto_sync ? 'true' : 'false', 'descricao' => 'Sincronização automática ativa', 'tipo' => 'booleano', 'editavel_por' => 'administrador']);
        ParametroSistema::updateOrCreate(['chave' => 'SANKHYA_SYNC_INTERVALO'], ['valor' => $request->intervalo, 'descricao' => 'Intervalo de sincronização automática', 'tipo' => 'texto', 'editavel_por' => 'administrador']);

        return response()->json([
            'success' => true,
            'message' => 'Configurações de conexão do Sankhya salvas com sucesso.'
        ]);
    }

    /**
     * Test connection to Sankhya Oracle.
     */
    public function testSankhyaConnection()
    {
        $user = Auth::user();
        if (!$user->isAdministrador()) {
            return response()->json(['error' => 'Forbidden.'], 403);
        }

        $sankhyaDb = resolve(\App\Services\SankhyaDatabaseService::class);
        $res = $sankhyaDb->testConnection();

        return response()->json($res);
    }

    /**
     * Sincronizar catálogo do Sankhya com o banco local.
     */
    public function syncSankhyaCatalog()
    {
        $user = Auth::user();
        if (!$user->isAdministrador()) {
            return response()->json(['error' => 'Forbidden.'], 403);
        }

        // If running under FastCGI / PHP-FPM (Nginx), send HTTP 200 immediately to prevent 504 Gateway Time-out
        if (function_exists('fastcgi_finish_request')) {
            response()->json([
                'success' => true,
                'message' => 'Sincronização do catálogo iniciada com sucesso em segundo plano! O servidor continuará importando Produtos, Clientes, Vendedores, Tabelas de Preço e Condições de Pagamento do Sankhya.'
            ])->send();

            fastcgi_finish_request();

            @set_time_limit(600);
            @ini_set('memory_limit', '512M');

            $db = resolve(\App\Services\SankhyaDatabaseService::class);

            // 1. Sync Products
            try {
                $products = $db->fetchProducts();
                foreach ($products as $p) { $db->saveProductFromRow($p); }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error("Async Sync Products Error: " . $e->getMessage());
            }

            // 2. Sync Partners (Clients)
            try {
                $partners = $db->fetchPartners();
                foreach ($partners as $pa) { $db->savePartnerFromRow($pa); }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error("Async Sync Partners Error: " . $e->getMessage());
            }

            // 3. Sync Representatives (Sellers)
            try {
                $reps = $db->fetchRepresentatives();
                foreach ($reps as $r) { $db->saveRepresentativeFromRow($r); }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error("Async Sync Reps Error: " . $e->getMessage());
            }

            // 4. Sync Price Tables
            try {
                $priceRows = $db->fetchPriceTablesFromView();
                $db->savePriceTablesFromViewRows($priceRows);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error("Async Sync Prices Error: " . $e->getMessage());
            }

            // 5. Sync Payment Conditions
            try {
                $condRows = $db->fetchPaymentConditionsFromView();
                $db->savePaymentConditionsFromViewRows($condRows);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error("Async Sync Conditions Error: " . $e->getMessage());
            }

            \Illuminate\Support\Facades\Log::info("Async Sankhya Catalog Sync Completed Successfully!");
            exit;
        }

        // Synchronous fallback
        @set_time_limit(300);
        @ini_set('memory_limit', '512M');

        $db = resolve(\App\Services\SankhyaDatabaseService::class);

        $prodCount = 0;
        $partnerCount = 0;
        $repCount = 0;
        $priceItemCount = 0;
        $condCount = 0;
        $warnings = [];

        // 1. Sync Products
        try {
            $products = $db->fetchProducts();
            foreach ($products as $p) {
                $db->saveProductFromRow($p);
                $prodCount++;
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Sync Error (Produtos): " . $e->getMessage());
            $warnings[] = "Produtos (Tabela TGFPRO do Sankhya não acessível: " . $e->getMessage() . ")";
        }

        // 2. Sync Partners (Clients)
        try {
            $partners = $db->fetchPartners();
            foreach ($partners as $pa) {
                $db->savePartnerFromRow($pa);
                $partnerCount++;
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Sync Error (Clientes): " . $e->getMessage());
            $warnings[] = "Clientes (Tabela TGFPAR do Sankhya não acessível: " . $e->getMessage() . ")";
        }

        // 3. Sync Representatives (Sellers)
        try {
            $reps = $db->fetchRepresentatives();
            foreach ($reps as $r) {
                $db->saveRepresentativeFromRow($r);
                $repCount++;
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Sync Error (Vendedores): " . $e->getMessage());
            $warnings[] = "Vendedores (Tabela TGFVEN do Sankhya não acessível: " . $e->getMessage() . ")";
        }

        // 4. Sync Price Tables View
        try {
            $priceRows = $db->fetchPriceTablesFromView();
            $priceItemCount = $db->savePriceTablesFromViewRows($priceRows);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Sync Error (Tabelas de Preço): " . $e->getMessage());
            $warnings[] = "Tabelas de Preço (View VGF_PRECOAPP não encontrada no Oracle)";
        }

        // 5. Sync Payment Conditions View
        try {
            $condRows = $db->fetchPaymentConditionsFromView();
            $condCount = $db->savePaymentConditionsFromViewRows($condRows);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Sync Error (Condições de Pagamento): " . $e->getMessage());
            $warnings[] = "Condições de Pagamento (View TIPONEG_APP não encontrada no Oracle)";
        }

        if ($prodCount == 0 && $partnerCount == 0 && $repCount == 0 && $priceItemCount == 0 && $condCount == 0) {
            return response()->json([
                'success' => false,
                'message' => "Falha na sincronização. Detalhes: " . implode(" | ", $warnings)
            ], 500);
        }

        $msg = "Sincronização efetuada com sucesso: {$prodCount} produtos, {$partnerCount} clientes, {$repCount} representantes, {$priceItemCount} itens de preço e {$condCount} condições de pagamento.";
        if (count($warnings) > 0) {
            $msg .= " Avisos: " . implode(" | ", $warnings);
        }

        return response()->json([
            'success' => true,
            'message' => $msg
        ]);
    }
}
