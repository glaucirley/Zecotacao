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
            ['chave' => 'DESCONTO_AVALIACAO_MODO', 'valor' => 'ITEM_A_ITEM', 'descricao' => 'Modo de avaliacao de desconto para alcada de aprovacao (ITEM_A_ITEM ou MEDIA_TOTAL)', 'tipo' => 'texto', 'editavel_por' => 'diretor'],
            ['chave' => 'ALCADA_GRANDE_CONTA_VALOR', 'valor' => '10000.00', 'descricao' => 'Valor total limite (igual ou maior) para classificar uma cotação como Grande Conta / Alta Prioridade', 'tipo' => 'numero', 'editavel_por' => 'diretor'],
            ['chave' => 'ALCADA_GRANDE_CONTA_QTD', 'valor' => '100', 'descricao' => 'Quantidade total de itens (igual ou maior) para classificar uma cotação como Grande Conta / Alta Prioridade', 'tipo' => 'numero', 'editavel_por' => 'diretor'],
            ['chave' => 'ALCADA_GRANDE_CONTA_MARGEM', 'valor' => '15.00', 'descricao' => 'Margem geral calculada (igual ou menor) para classificar uma cotação como Grande Conta / Alta Prioridade', 'tipo' => 'numero', 'editavel_por' => 'diretor'],
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
                'message' => 'DESCONTO_AVALIACAO_MODO must be either ITEM_A_ITEM or MEDIA_TOTAL.'
            ], 422);
        }

        if ($chave === 'REENVIO_PARCIAL_MODO' && !in_array($valor, ['RECALCULA_TUDO', 'SO_ITENS_ALTERADOS'])) {
            return response()->json([
                'error' => 'Validation error',
                'message' => 'REENVIO_PARCIAL_MODO must be either RECALCULA_TUDO or SO_ITENS_ALTERADOS.'
            ], 422);
        }

        if ($param->tipo === 'booleano' && !in_array(strtolower($valor), ['true', 'false', '1', '0'])) {
            return response()->json([
                'error' => 'Validation error',
                'message' => 'Value must be a boolean representation (true or false).'
            ], 422);
        }

        if ($param->tipo === 'numero' && !is_numeric($valor)) {
            return response()->json([
                'error' => 'Validation error',
                'message' => 'Value must be a valid number.'
            ], 422);
        }

        $param->update(['valor' => $valor]);

        return response()->json([
            'success' => true,
            'message' => "Parameter '{$chave}' updated successfully to '{$valor}'.",
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

        try {
            $db = resolve(\App\Services\SankhyaDatabaseService::class);
            
            $products = $db->fetchProducts();
            $partners = $db->fetchPartners();
            $reps = $db->fetchRepresentatives();

            $prodCount = 0;
            foreach ($products as $p) {
                Produto::updateOrCreate(
                    ['codigo_sankhya' => (string)$p['CODPROD']],
                    [
                        'descricao' => $p['DESCRPROD'],
                        'unidade' => 'UN',
                        'ativo' => $p['ATIVO'] === 'S'
                    ]
                );
                $prodCount++;
            }

            $partnerCount = 0;
            foreach ($partners as $pa) {
                Parceiro::updateOrCreate(
                    ['codigo_sankhya' => (string)$pa['CODPARC']],
                    [
                        'razao_social' => $pa['NOMEPARC'],
                        'nome_fantasia' => $pa['NOMEPARC'],
                        'cnpj' => preg_replace('/[^0-9]/', '', $pa['CGC_CPF'] ?? ''),
                        'telefone' => $pa['TELEFONE'] ?? '',
                        'email' => $pa['EMAIL'] ?? '',
                        'cep' => preg_replace('/[^0-9]/', '', $pa['CEP'] ?? ''),
                        'ativo' => true,
                    ]
                );
                $partnerCount++;
            }

            $repCount = 0;
            foreach ($reps as $r) {
                $user = User::where('codigo_sankhya', (string)$r['CODVEND'])->first();
                if ($user) {
                    $user->update([
                        'nome' => $r['APELIDO'],
                        'email' => $r['EMAIL'] ?? $user->email,
                    ]);
                } else {
                    User::create([
                        'nome' => $r['APELIDO'],
                        'papel' => 'representante',
                        'email' => $r['EMAIL'] ?? ('vendedor' . $r['CODVEND'] . '@zecotacao.com.br'),
                        'senha_hash' => bcrypt(\Illuminate\Support\Str::random(16)),
                        'codigo_sankhya' => (string)$r['CODVEND'],
                        'ativo' => true,
                    ]);
                }
                $repCount++;
            }

            return response()->json([
                'success' => true,
                'message' => "Sincronização efetuada com sucesso: {$prodCount} produtos, {$partnerCount} clientes e {$repCount} representantes sincronizados."
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro durante a sincronização: ' . $e->getMessage()
            ], 500);
        }
    }
}
