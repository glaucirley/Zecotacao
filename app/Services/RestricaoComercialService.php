<?php

namespace App\Services;

use App\Models\Cotacao;
use App\Models\CotacaoItem;
use App\Models\CotacaoHistorico;
use App\Models\Parceiro;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RestricaoComercialService
{
    protected SankhyaDatabaseService $sankhyaDb;

    public function __construct(SankhyaDatabaseService $sankhyaDb)
    {
        $this->sankhyaDb = $sankhyaDb;
    }

    /**
     * Chave de cache para produtos restritos por par vendedor + cliente.
     */
    public function getCacheKey(string $codVend, string $codParc): string
    {
        return "restricoes:{$codVend}:{$codParc}";
    }

    /**
     * Retorna a lista de produtos restritos para um determinado vendedor e cliente.
     * Resultado é mantido em cache por 10 minutos (600s).
     *
     * @param string $codVend Código Sankhya do Vendedor
     * @param string $codParc Código Sankhya do Parceiro (Cliente)
     * @param string $canal Canal de consulta (default 'ZC')
     * @return array Array de itens com [ 'CODPROD' => ..., 'FONTE' => ..., 'MSG_VENDEDOR' => ... ]
     */
    public function produtosRestritos(string $codVend, string $codParc, string $canal = 'ZC'): array
    {
        if (empty($codVend) || empty($codParc)) {
            return [];
        }

        $cacheKey = $this->getCacheKey($codVend, $codParc);

        return Cache::remember($cacheKey, 600, function () use ($codVend, $codParc, $canal) {
            try {
                $conn = $this->sankhyaDb->connect();
                $sql = "SELECT CODPROD, FONTE, MSG_VENDEDOR FROM TABLE(AD_PKG_ZC_REGRAS.PRODUTOS_RESTRITOS(:v, :p, :canal))";
                try {
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([
                        ':v' => (string)$codVend,
                        ':p' => (string)$codParc,
                        ':canal' => (string)$canal,
                    ]);
                    return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
                } catch (\PDOException $e1) {
                    $sqlPrefix = "SELECT CODPROD, FONTE, MSG_VENDEDOR FROM TABLE(SANKHYA.AD_PKG_ZC_REGRAS.PRODUTOS_RESTRITOS(:v, :p, :canal))";
                    $stmt = $conn->prepare($sqlPrefix);
                    $stmt->execute([
                        ':v' => (string)$codVend,
                        ':p' => (string)$codParc,
                        ':canal' => (string)$canal,
                    ]);
                    return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
                }
            } catch (\Throwable $e) {
                Log::warning("RestricaoComercialService::produtosRestritos falhou para VEND={$codVend}, PARC={$codParc}: " . $e->getMessage());
                return [];
            }
        });
    }

    /**
     * Retorna apenas os códigos dos produtos restritos (array simples de strings)
     * para filtragem no catálogo e na busca de produtos.
     */
    public function codigosProdutosRestritos(string $codVend, string $codParc): array
    {
        $list = $this->produtosRestritos($codVend, $codParc);
        $codes = [];
        foreach ($list as $item) {
            $cod = (string)($item['CODPROD'] ?? $item['codprod'] ?? '');
            if ($cod !== '') {
                $codes[] = $cod;
            }
        }
        return array_values(array_unique($codes));
    }

    /**
     * Valida um produto individual para um vendedor e parceiro.
     *
     * @param string $codVend Código Sankhya do vendedor
     * @param string $codParc Código Sankhya do cliente
     * @param string $codProd Código Sankhya do produto
     * @param string $canal 'ZC' | 'N8N'
     * @return array ['efeito' => 'L'|'B', 'fonte' => string|null, 'msg_vendedor' => string|null, 'msg_cliente' => string|null, 'sucesso_consulta' => bool]
     */
    public function validarItem(string $codVend, string $codParc, string $codProd, string $canal = 'ZC'): array
    {
        $defaultLiberado = [
            'efeito' => 'L',
            'fonte' => null,
            'msg_vendedor' => null,
            'msg_cliente' => null,
            'sucesso_consulta' => true,
        ];

        if (empty($codVend) || empty($codParc) || empty($codProd)) {
            return $defaultLiberado;
        }

        try {
            $conn = $this->sankhyaDb->connect();

            // 1. Tenta procedure com parâmetros de saída
            $sqlProc = "BEGIN AD_PKG_ZC_REGRAS.VALIDA_ITEM(:v, :p, :prod, :canal, :efeito, :fonte, :msgv, :msgc); END;";
            try {
                $stmt = $conn->prepare($sqlProc);
                $efeito = 'L';
                $fonte = '';
                $msgVendedor = '';
                $msgCliente = '';

                $stmt->bindParam(':v', $codVend);
                $stmt->bindParam(':p', $codParc);
                $stmt->bindParam(':prod', $codProd);
                $stmt->bindParam(':canal', $canal);
                $stmt->bindParam(':efeito', $efeito, \PDO::PARAM_STR | \PDO::PARAM_INPUT_OUTPUT, 2);
                $stmt->bindParam(':fonte', $fonte, \PDO::PARAM_STR | \PDO::PARAM_INPUT_OUTPUT, 30);
                $stmt->bindParam(':msgv', $msgVendedor, \PDO::PARAM_STR | \PDO::PARAM_INPUT_OUTPUT, 400);
                $stmt->bindParam(':msgc', $msgCliente, \PDO::PARAM_STR | \PDO::PARAM_INPUT_OUTPUT, 400);

                $stmt->execute();

                $efeito = trim((string)$efeito) ?: 'L';
                return [
                    'efeito' => $efeito,
                    'fonte' => trim((string)$fonte) ?: null,
                    'msg_vendedor' => trim((string)$msgVendedor) ?: null,
                    'msg_cliente' => trim((string)$msgCliente) ?: ($efeito === 'B' ? 'Produto indisponível para o seu cadastro.' : null),
                    'sucesso_consulta' => true,
                ];
            } catch (\Throwable $eProc) {
                // 2. Fallback via function SELECT AD_PKG_ZC_REGRAS.VALIDA(...)
                $sqlFunc = "SELECT AD_PKG_ZC_REGRAS.PARTE(RET, 1) AS EFEITO,
                                   AD_PKG_ZC_REGRAS.PARTE(RET, 2) AS FONTE,
                                   AD_PKG_ZC_REGRAS.PARTE(RET, 4) AS MSG_VENDEDOR,
                                   AD_PKG_ZC_REGRAS.PARTE(RET, 5) AS MSG_CLIENTE
                            FROM (SELECT AD_PKG_ZC_REGRAS.VALIDA(:v, :p, :prod, :canal) AS RET FROM DUAL)";
                $stmtFunc = $conn->prepare($sqlFunc);
                $stmtFunc->execute([
                    ':v' => (string)$codVend,
                    ':p' => (string)$codParc,
                    ':prod' => (string)$codProd,
                    ':canal' => (string)$canal,
                ]);
                $row = $stmtFunc->fetch(\PDO::FETCH_ASSOC);
                if ($row) {
                    $efeito = trim((string)($row['EFEITO'] ?? 'L')) ?: 'L';
                    return [
                        'efeito' => $efeito,
                        'fonte' => trim((string)($row['FONTE'] ?? '')) ?: null,
                        'msg_vendedor' => trim((string)($row['MSG_VENDEDOR'] ?? '')) ?: null,
                        'msg_cliente' => trim((string)($row['MSG_CLIENTE'] ?? '')) ?: ($efeito === 'B' ? 'Produto indisponível para o seu cadastro.' : null),
                        'sucesso_consulta' => true,
                    ];
                }
                throw $eProc;
            }
        } catch (\Throwable $e) {
            Log::error("RestricaoComercialService::validarItem falhou: " . $e->getMessage());
            return [
                'efeito' => null,
                'fonte' => null,
                'msg_vendedor' => 'Erro ao consultar restrições no Oracle: ' . $e->getMessage(),
                'msg_cliente' => null,
                'sucesso_consulta' => false,
            ];
        }
    }

    /**
     * Valida todos os itens de uma cotação e atualiza os campos de restrição no banco.
     *
     * @param Cotacao $quote
     * @param string $canal
     * @return array [
     *   'sucesso' => bool,
     *   'erro_conexao' => bool,
     *   'bloqueados' => array,
     *   'total_bloqueados' => int,
     *   'mensagem' => string|null
     * ]
     */
    public function validarCotacao(Cotacao $quote, string $canal = 'ZC'): array
    {
        $quote->loadMissing(['parceiro', 'representante', 'itens.produto']);

        $codVend = $quote->representante?->codigo_sankhya;
        $codParc = $quote->parceiro?->codigo_sankhya;

        if (empty($codVend)) {
            return [
                'sucesso' => false,
                'erro_conexao' => false,
                'bloqueados' => [],
                'total_bloqueados' => 0,
                'mensagem' => 'Cadastro do vendedor sem código Sankhya. As regras por vendedor não teriam como ser conferidas.',
            ];
        }

        if (empty($codParc)) {
            return [
                'sucesso' => false,
                'erro_conexao' => false,
                'bloqueados' => [],
                'total_bloqueados' => 0,
                'mensagem' => 'Cliente sem código Sankhya cadastrado.',
            ];
        }

        $bloqueados = [];
        $houveFalhaConexao = false;

        // Otimização de Performance: Consulta lote de restrições em 1 única query (ou direto do cache de 10 min)
        $batchMap = null;
        try {
            $batchList = $this->produtosRestritos($codVend, $codParc, $canal);
            $batchMap = [];
            foreach ($batchList as $r) {
                $cProd = (string)($r['CODPROD'] ?? $r['codprod'] ?? '');
                if ($cProd !== '') {
                    $batchMap[$cProd] = [
                        'efeito' => 'B',
                        'fonte' => $r['FONTE'] ?? $r['fonte'] ?? 'RESTRICAO_COMERCIAL',
                        'msg_vendedor' => $r['MSG_VENDEDOR'] ?? $r['msg_vendedor'] ?? 'Produto restrito comercialmente para este cliente.',
                        'msg_cliente' => $r['MSG_CLIENTE'] ?? $r['msg_cliente'] ?? 'Produto indisponível para o seu cadastro.',
                        'sucesso_consulta' => true,
                    ];
                }
            }
        } catch (\Throwable $eBatch) {
            Log::warning("RestricaoComercialService::validarCotacao - Falha ao carregar lote de restrições, usando fallback: " . $eBatch->getMessage());
            $batchMap = null;
        }

        foreach ($quote->itens as $item) {
            // Ignora item que ainda não possui produto vinculado (não encontrado)
            if ($item->nao_encontrado && empty($item->produto_id)) {
                continue;
            }

            $codProd = (string)($item->produto?->codigo_sankhya ?? '');
            if (empty($codProd)) {
                continue;
            }

            if ($batchMap !== null) {
                // Resolução instantânea em memória a partir do lote de restrições
                $res = $batchMap[$codProd] ?? [
                    'efeito' => 'L',
                    'fonte' => null,
                    'msg_vendedor' => null,
                    'msg_cliente' => null,
                    'sucesso_consulta' => true,
                ];
            } else {
                // Fallback item a item caso a procedure em lote falhe
                $res = $this->validarItem($codVend, $codParc, $codProd, $canal);
            }

            if (!$res['sucesso_consulta']) {
                $houveFalhaConexao = true;
                continue;
            }

            $efeitoAnterior = $item->restricao_efeito;

            $item->restricao_efeito = $res['efeito']; // 'L' ou 'B'
            $item->restricao_fonte = $res['fonte'];
            $item->restricao_msg_vendedor = $res['msg_vendedor'];
            $item->restricao_validada_em = now();
            if (empty($item->restricao_origem)) {
                $item->restricao_origem = ($canal === 'N8N') ? 'N8N' : 'PAINEL';
            }
            if ($item->isDirty()) {
                $item->save();
            }

            if ($res['efeito'] === 'B') {
                $itemInfo = [
                    'item_id' => $item->id,
                    'codprod' => $codProd,
                    'descricao' => $item->produto?->descricao ?? $item->descricao_original,
                    'fonte' => $res['fonte'],
                    'msg_vendedor' => $res['msg_vendedor'] ?? 'Produto restrito comercialmente para este cliente.',
                ];
                $bloqueados[] = $itemInfo;

                // Se acabou de ser identificado como bloqueado, registrar na trilha de auditoria
                if ($efeitoAnterior !== 'B') {
                    CotacaoHistorico::create([
                        'cotacao_id' => $quote->id,
                        'cotacao_item_id' => $item->id,
                        'evento' => 'ITEM_RESTRITO_IDENTIFICADO',
                        'usuario_id' => auth()->id() ?? $quote->representante_id,
                        'papel' => auth()->user()?->papel ?? 'sistema',
                        'condicao' => "Item '{$itemInfo['descricao']}' (Cód: {$codProd}) marcado como bloqueado por {$res['fonte']}: {$itemInfo['msg_vendedor']}",
                    ]);
                }
            }
        }

        if ($houveFalhaConexao) {
            $quote->restricoes_validadas = false;
            $quote->save();

            return [
                'sucesso' => false,
                'erro_conexao' => true,
                'bloqueados' => $bloqueados,
                'total_bloqueados' => count($bloqueados),
                'mensagem' => 'Não foi possível validar as restrições comerciais, tente de novo.',
            ];
        }

        $quote->restricoes_validadas = true;
        $quote->restricoes_validadas_em = now();
        $quote->save();

        return [
            'sucesso' => empty($bloqueados),
            'erro_conexao' => false,
            'bloqueados' => $bloqueados,
            'total_bloqueados' => count($bloqueados),
            'mensagem' => empty($bloqueados) ? null : 'A cotação possui ' . count($bloqueados) . ' item(ns) bloqueado(s) por restrições comerciais.',
        ];
    }

    /**
     * Registra o bloqueio no log do Oracle via AD_PKG_ZC_REGRAS.GRAVA_LOG
     */
    public function registrarBloqueio($codVend, $codParc, $codProd, $idCotacao, $fonte, $msg, string $canal = 'ZC'): void
    {
        try {
            $conn = $this->sankhyaDb->connect();
            $sql = "BEGIN AD_PKG_ZC_REGRAS.GRAVA_LOG(:canal, :idcot, :v, :p, :prod, :fonte, :msg); END;";
            try {
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    ':canal' => (string)$canal,
                    ':idcot' => (string)$idCotacao,
                    ':v' => (string)$codVend,
                    ':p' => (string)$codParc,
                    ':prod' => (string)$codProd,
                    ':fonte' => (string)$fonte,
                    ':msg' => (string)$msg,
                ]);
            } catch (\PDOException $e1) {
                $sqlPrefix = "BEGIN SANKHYA.AD_PKG_ZC_REGRAS.GRAVA_LOG(:canal, :idcot, :v, :p, :prod, :fonte, :msg); END;";
                $stmt = $conn->prepare($sqlPrefix);
                $stmt->execute([
                    ':canal' => (string)$canal,
                    ':idcot' => (string)$idCotacao,
                    ':v' => (string)$codVend,
                    ':p' => (string)$codParc,
                    ':prod' => (string)$codProd,
                    ':fonte' => (string)$fonte,
                    ':msg' => (string)$msg,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning("RestricaoComercialService::registrarBloqueio falhou ao gravar no Oracle: " . $e->getMessage());
        }
    }
}
