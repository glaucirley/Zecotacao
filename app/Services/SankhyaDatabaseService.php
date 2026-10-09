<?php

namespace App\Services;

use App\Models\ParametroSistema;
use App\Models\Produto;
use App\Models\Parceiro;
use App\Models\User;
use App\Models\TabelaPreco;
use App\Models\TabelaPrecoItem;
use App\Models\CondicaoPagamento;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class SankhyaDatabaseService
{
    protected ?\PDO $pdo = null;

    /**
     * Establish direct PDO connection to Sankhya's Oracle database.
     *
     * @return \PDO
     * @throws \Exception
     */
    public function connect(): \PDO
    {
        if ($this->pdo) {
            return $this->pdo;
        }

        $tipo = ParametroSistema::getVal('SANKHYA_CONN_TIPO', 'DIRETO');
        $port = ParametroSistema::getVal('SANKHYA_DB_PORT', '1521');
        $serviceName = ParametroSistema::getVal('SANKHYA_DB_NAME', 'XE');
        $user = ParametroSistema::getVal('SANKHYA_DB_USER', 'sankhya');
        $encryptedPass = ParametroSistema::getVal('SANKHYA_DB_PASS');

        // Resolve Host: if SSH tunnel mode, connect to localhost (127.0.0.1)
        if ($tipo === 'SSH_TUNNEL') {
            $host = '127.0.0.1';
        } else {
            $host = ParametroSistema::getVal('SANKHYA_DB_HOST', '127.0.0.1');
        }

        // Decrypt password
        $password = '';
        if ($encryptedPass) {
            try {
                $password = Crypt::decryptString($encryptedPass);
            } catch (\Exception $e) {
                Log::error("Sankhya Service - Failed to decrypt Oracle password: " . $e->getMessage());
                throw new \Exception("Erro de segurança: Não foi possível descriptografar a senha do banco do Sankhya.");
            }
        }

        if (empty($host) || empty($user) || empty($serviceName)) {
            throw new \Exception("Configurações do banco Sankhya incompletas no painel de Parâmetros.");
        }

        // Oracle PDO DSN format: oci:dbname=//host:port/service_name;charset=AL32UTF8
        $dsn = "oci:dbname=//{$host}:{$port}/{$serviceName};charset=UTF8";

        try {
            $this->pdo = new \PDO($dsn, $user, $password, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_TIMEOUT => 5, // Timeout fast
            ]);
        } catch (\PDOException $e) {
            Log::error("Sankhya Service - Connection failed: " . $e->getMessage());
            throw new \Exception("Falha na conexão com o banco Oracle do Sankhya: " . $e->getMessage());
        }

        return $this->pdo;
    }

    /**
     * Test Oracle connectivity.
     *
     * @return array
     */
    public function testConnection(): array
    {
        try {
            $conn = $this->connect();
            $stmt = $conn->query("SELECT 1 FROM DUAL");
            $result = $stmt->fetch();
            if ($result) {
                return ['success' => true, 'message' => 'Conexão efetuada com sucesso ao Oracle (Sankhya).'];
            }
            return ['success' => false, 'message' => 'Retorno inválido da consulta de teste do Oracle.'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * SQL string for fetching Products with technical attributes, NCM, weight, and variable cost.
     */
    /**
     * SQL string for fetching Products with technical attributes, NCM, weight, and variable cost.
     */
    protected function getProductBaseSql(bool $withPrefix = true): string
    {
        $prefix = $withPrefix ? 'SANKHYA.' : '';
        return "SELECT 
            PRO.CODPROD,
            PRO.DESCRPROD,
            PRO.MARCA,
            PRO.AD_BASE              AS BASE,
            PRO.AD_BASECV            AS BASE_LOJAVIRTUAL,
            PRO.AD_DESCLONGALV       AS DESC_LONGA,
            PRO.AD_DESCTECNICALV     AS DESC_TECNICA,
            PRO.AD_DESCRICAOINT      AS DESC_INTERNA,
            PRO.NOMEPRDLV            AS DES_LOJAVIRTUAL,
            PRO.AD_INDICACAO         AS INDICADO,
            PRO.AD_PRATIVO           AS PRICP_ATIVO,
            PRO.AD_APLICACAO || ' ' || PRO.AD_TIPOMED AS APLICACAO,
            PRO.PESOBRUTO, 
            PRO.PESOLIQ              AS PESOLIQ,
            PRO.MARGLUCRO, 
            PRO.NCM,
            PRO.ATIVO,
            (SELECT CUSVARIAVEL 
               FROM {$prefix}TGFCUS CUS 
              WHERE CUS.CODPROD = PRO.CODPROD 
                AND ROWNUM = 1 
              ORDER BY CUS.DTATUAL DESC) AS CUSTO_VARIAVEL
        FROM {$prefix}TGFPRO PRO";
    }

    /**
     * Fetch all active products from Sankhya Oracle with fallback queries.
     */
    public function fetchProducts(): array
    {
        $conn = $this->connect();

        // 1. Full query with SANKHYA. prefix
        try {
            $sql = $this->getProductBaseSql(true) . " WHERE PRO.ATIVO = 'S' AND PRO.USOPROD <> 'I'";
            return $conn->query($sql)->fetchAll();
        } catch (\PDOException $e1) {
            Log::warning("Sankhya Service - fetchProducts Attempt 1 (SANKHYA. prefix) failed: " . $e1->getMessage());
        }

        // 2. Full query without SANKHYA. prefix
        try {
            $sql = $this->getProductBaseSql(false) . " WHERE PRO.ATIVO = 'S' AND PRO.USOPROD <> 'I'";
            return $conn->query($sql)->fetchAll();
        } catch (\PDOException $e2) {
            Log::warning("Sankhya Service - fetchProducts Attempt 2 (no prefix) failed: " . $e2->getMessage());
        }

        // 3. Basic standard columns with SANKHYA. prefix
        try {
            $sql = "SELECT CODPROD, DESCRPROD, MARCA, PESOBRUTO, PESOLIQ, MARGLUCRO, NCM, ATIVO FROM SANKHYA.TGFPRO WHERE ATIVO = 'S' AND USOPROD <> 'I'";
            return $conn->query($sql)->fetchAll();
        } catch (\PDOException $e3) {
            Log::warning("Sankhya Service - fetchProducts Attempt 3 (basic SANKHYA.) failed: " . $e3->getMessage());
        }

        // 4. Basic standard columns without prefix
        $sql = "SELECT CODPROD, DESCRPROD, MARCA, PESOBRUTO, PESOLIQ, MARGLUCRO, NCM, ATIVO FROM TGFPRO WHERE ATIVO = 'S' AND USOPROD <> 'I'";
        return $conn->query($sql)->fetchAll();
    }

    /**
     * Save/update a local Produto model record from an Oracle result row.
     */
    /**
     * Clean and ensure UTF-8 encoding for database string fields.
     */
    protected function cleanStr($val): ?string
    {
        if ($val === null) return null;
        $str = (string)$val;
        if (!mb_check_encoding($str, 'UTF-8')) {
            $str = mb_convert_encoding($str, 'UTF-8', ['Windows-1252', 'ISO-8859-1']);
        }
        return trim($str);
    }

    public function saveProductFromRow(array $row): Produto
    {
        $descricao = $this->cleanStr($row['DESCRPROD'] ?? $row['descrprod'] ?? $row['DESC_INTERNA'] ?? $row['desc_interna'] ?? '');

        return Produto::updateOrCreate(
            ['codigo_sankhya' => (string)($row['CODPROD'] ?? $row['codprod'])],
            [
                'descricao'         => $descricao,
                'marca'             => $this->cleanStr($row['MARCA'] ?? $row['marca'] ?? null),
                'base'              => $this->cleanStr($row['BASE'] ?? $row['base'] ?? null),
                'base_loja_virtual' => $this->cleanStr($row['BASE_LOJAVIRTUAL'] ?? $row['base_lojavirtual'] ?? null),
                'descricao_longa'   => $this->cleanStr($row['DESC_LONGA'] ?? $row['desc_longa'] ?? null),
                'descricao_tecnica' => $this->cleanStr($row['DESC_TECNICA'] ?? $row['desc_tecnica'] ?? null),
                'descricao_interna' => $this->cleanStr($row['DESC_INTERNA'] ?? $row['desc_interna'] ?? null),
                'nome_loja_virtual' => $this->cleanStr($row['DES_LOJAVIRTUAL'] ?? $row['des_lojavirtual'] ?? null),
                'indicacao'         => $this->cleanStr($row['INDICADO'] ?? $row['indicado'] ?? null),
                'principio_ativo'   => $this->cleanStr($row['PRICP_ATIVO'] ?? $row['pricp_ativo'] ?? null),
                'aplicacao'         => $this->cleanStr($row['APLICACAO'] ?? $row['aplicacao'] ?? null),
                'peso_bruto'        => isset($row['PESOBRUTO']) ? (float)$row['PESOBRUTO'] : null,
                'peso_liquido'      => isset($row['PESOLIQ']) ? (float)$row['PESOLIQ'] : null,
                'margem_lucro'      => isset($row['MARGLUCRO']) ? (float)$row['MARGLUCRO'] : null,
                'custo_variavel'    => isset($row['CUSTO_VARIAVEL']) ? (float)$row['CUSTO_VARIAVEL'] : null,
                'ncm'               => $this->cleanStr($row['NCM'] ?? $row['ncm'] ?? null),
                'unidade'           => 'UN',
                'ativo'             => ($row['ATIVO'] ?? 'S') === 'S',
            ]
        );
    }

    /**
     * Sync single Product on demand.
     */
    public function syncProductByCode(string $code): ?Produto
    {
        try {
            $conn = $this->connect();
            try {
                $sql = $this->getProductBaseSql(true) . " WHERE PRO.CODPROD = :code";
                $stmt = $conn->prepare($sql);
                $stmt->execute(['code' => $code]);
                $row = $stmt->fetch();
            } catch (\PDOException $e1) {
                $sql = $this->getProductBaseSql(false) . " WHERE PRO.CODPROD = :code";
                $stmt = $conn->prepare($sql);
                $stmt->execute(['code' => $code]);
                $row = $stmt->fetch();
            }

            if (!$row) {
                return null;
            }

            return $this->saveProductFromRow($row);
        } catch (\Exception $e) {
            Log::error("Sankhya Service - Failed to sync product {$code}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * SQL string for fetching Partners joined with TSIEND, TSIBAI, TSICID and multi-sellers.
     */
    protected function getPartnerBaseSql(bool $withPrefix = true): string
    {
        $prefix = $withPrefix ? 'SANKHYA.' : '';
        return "SELECT 
            PAR.CODPARC,
            PAR.CODVEND               AS VENDEDOR_1,
            PAR.AD_AGLUTINA           AS VENDEDOR_2,
            PAR.AD_AGLUTINA2          AS VENDEDOR_3,
            PAR.AD_AGLUTINA3          AS VENDEDOR_4,
            PAR.AD_AGLUTINA4          AS VENDEDOR_5,
            PAR.NOMEPARC              AS NOME_FANTASIA,
            PAR.RAZAOSOCIAL,
            PAR.CGC_CPF,
            PAR.IDENTINSCESTAD        AS INSCRICAO_ESTADUAL,
            PAR.TIPPESSOA,
            PAR.CODREG                AS REGIAO_ID,
            PAR.NUMEND                AS NUMERO,
            PAR.CEP,
            PAR.TELEFONE,
            PAR.EMAIL,
            PAR.OBSERVACOES,
            END.NOMEEND               AS LOGRADOURO,
            BAI.NOMEBAI               AS BAIRRO,
            CID.NOMECID               AS CIDADE,
            COALESCE(UFS.UF, TO_CHAR(CID.UF)) AS UF
        FROM {$prefix}TGFPAR PAR
        LEFT JOIN {$prefix}TSIEND END ON PAR.CODEND = END.CODEND
        LEFT JOIN {$prefix}TSIBAI BAI ON PAR.CODBAI = BAI.CODBAI
        LEFT JOIN {$prefix}TSICID CID ON PAR.CODCID = CID.CODCID
        LEFT JOIN {$prefix}TSIUFS UFS ON CID.UF = UFS.CODUF";
    }

    /**
     * Fetch all active clients (partners) from Sankhya Oracle with fallback queries.
     */
    public function fetchPartners(): array
    {
        $conn = $this->connect();

        // 1. Full query with SANKHYA. prefix
        try {
            $sql = $this->getPartnerBaseSql(true) . " WHERE PAR.CLIENTE = 'S' AND PAR.ATIVO = 'S'";
            return $conn->query($sql)->fetchAll();
        } catch (\PDOException $e1) {
            Log::warning("Sankhya Service - fetchPartners Attempt 1 failed: " . $e1->getMessage());
        }

        // 2. Full query without prefix
        try {
            $sql = $this->getPartnerBaseSql(false) . " WHERE PAR.CLIENTE = 'S' AND PAR.ATIVO = 'S'";
            return $conn->query($sql)->fetchAll();
        } catch (\PDOException $e2) {
            Log::warning("Sankhya Service - fetchPartners Attempt 2 failed: " . $e2->getMessage());
        }

        // 3. Basic query with prefix
        try {
            $sql = "SELECT CODPARC, CODVEND AS VENDEDOR_1, NOMEPARC AS NOME_FANTASIA, RAZAOSOCIAL, CGC_CPF, TELEFONE, EMAIL, ATIVO FROM SANKHYA.TGFPAR WHERE CLIENTE = 'S' AND ATIVO = 'S'";
            return $conn->query($sql)->fetchAll();
        } catch (\PDOException $e3) {
            Log::warning("Sankhya Service - fetchPartners Attempt 3 failed: " . $e3->getMessage());
        }

        // 4. Basic query without prefix
        $sql = "SELECT CODPARC, CODVEND AS VENDEDOR_1, NOMEPARC AS NOME_FANTASIA, RAZAOSOCIAL, CGC_CPF, TELEFONE, EMAIL, ATIVO FROM TGFPAR WHERE CLIENTE = 'S' AND ATIVO = 'S'";
        return $conn->query($sql)->fetchAll();
    }

    /**
     * Fetch all active representatives (sellers) from Sankhya Oracle with fallbacks.
     */
    public function fetchRepresentatives(): array
    {
        $conn = $this->connect();

        try {
            return $conn->query("SELECT CODVEND, APELIDO, EMAIL, AD_TELEFONE AS TELEFONE FROM SANKHYA.TGFVEN WHERE ATIVO = 'S' AND TIPVEND = 'R'")->fetchAll();
        } catch (\PDOException $e1) {
            try {
                return $conn->query("SELECT CODVEND, APELIDO, EMAIL, AD_TELEFONE AS TELEFONE FROM TGFVEN WHERE ATIVO = 'S' AND TIPVEND = 'R'")->fetchAll();
            } catch (\PDOException $e2) {
                try {
                    return $conn->query("SELECT CODVEND, APELIDO, EMAIL FROM SANKHYA.TGFVEN WHERE ATIVO = 'S' AND TIPVEND = 'R'")->fetchAll();
                } catch (\PDOException $e3) {
                    return $conn->query("SELECT CODVEND, APELIDO, EMAIL FROM TGFVEN WHERE ATIVO = 'S' AND TIPVEND = 'R'")->fetchAll();
                }
            }
        }
    }

    /**
     * Save/update a local Parceiro model record from an Oracle result row.
     */
    public function savePartnerFromRow(array $row): Parceiro
    {
        $cleanCnpj = preg_replace('/[^0-9]/', '', $row['CGC_CPF'] ?? $row['cgc_cpf'] ?? '');
        $cleanCep = preg_replace('/[^0-9]/', '', $row['CEP'] ?? $row['cep'] ?? '');

        $logradouro = $this->cleanStr($row['LOGRADOURO'] ?? $row['logradouro'] ?? null);
        $numero = $this->cleanStr($row['NUMERO'] ?? $row['numero'] ?? null);
        $bairro = $this->cleanStr($row['BAIRRO'] ?? $row['bairro'] ?? null);
        $cidade = $this->cleanStr($row['CIDADE'] ?? $row['cidade'] ?? null);
        $uf = \App\Models\Parceiro::normalizeUf($row['UF'] ?? $row['uf'] ?? null, $cidade);

        $razaoSocial = $this->cleanStr($row['RAZAOSOCIAL'] ?? $row['razaosocial'] ?? $row['NOME_FANTASIA'] ?? $row['NOMEPARC'] ?? '');
        $nomeFantasia = $this->cleanStr($row['NOME_FANTASIA'] ?? $row['nome_fantasia'] ?? $row['NOMEPARC'] ?? '');

        if (!\App\Services\PartnerImportService::isValidPartnerName($razaoSocial)) {
            if (\App\Services\PartnerImportService::isValidPartnerName($nomeFantasia)) {
                $razaoSocial = $nomeFantasia;
            } else {
                throw new \InvalidArgumentException("Partner name is invalid: '{$razaoSocial}'");
            }
        }

        $enderecoConcatenado = implode(' - ', array_filter([
            trim(($logradouro ?: '') . ($numero ? ', ' . $numero : '')),
            $bairro,
            trim(($cidade ?: '') . ($uf ? '/' . $uf : '')),
        ]));

        return Parceiro::updateOrCreate(
            ['codigo_sankhya' => (string)($row['CODPARC'] ?? $row['codparc'])],
            [
                'razao_social'        => $razaoSocial,
                'nome_fantasia'       => $nomeFantasia ?: $razaoSocial,
                'cnpj'                => $cleanCnpj ?: null,
                'inscricao_estadual'  => $this->cleanStr($row['INSCRICAO_ESTADUAL'] ?? $row['inscricao_estadual'] ?? null),
                'tipo_pessoa'         => $this->cleanStr($row['TIPPESSOA'] ?? $row['tippessoa'] ?? null),
                'codigo_regiao'       => isset($row['REGIAO_ID']) ? (string)$row['REGIAO_ID'] : (isset($row['regiao_id']) ? (string)$row['regiao_id'] : null),
                'vendedor_1_codigo'   => isset($row['VENDEDOR_1']) ? (string)$row['VENDEDOR_1'] : (isset($row['vendedor_1']) ? (string)$row['vendedor_1'] : null),
                'vendedor_2_codigo'   => isset($row['VENDEDOR_2']) ? (string)$row['VENDEDOR_2'] : (isset($row['vendedor_2']) ? (string)$row['vendedor_2'] : null),
                'vendedor_3_codigo'   => isset($row['VENDEDOR_3']) ? (string)$row['VENDEDOR_3'] : (isset($row['vendedor_3']) ? (string)$row['vendedor_3'] : null),
                'vendedor_4_codigo'   => isset($row['VENDEDOR_4']) ? (string)$row['VENDEDOR_4'] : (isset($row['vendedor_4']) ? (string)$row['vendedor_4'] : null),
                'vendedor_5_codigo'   => isset($row['VENDEDOR_5']) ? (string)$row['VENDEDOR_5'] : (isset($row['vendedor_5']) ? (string)$row['vendedor_5'] : null),
                'telefone'            => $this->cleanStr($row['TELEFONE'] ?? $row['telefone'] ?? null),
                'email'               => $this->cleanStr($row['EMAIL'] ?? $row['email'] ?? null),
                'endereco'            => $enderecoConcatenado ?: null,
                'logradouro'          => $logradouro,
                'numero'              => $numero,
                'bairro'              => $bairro,
                'cidade'              => $cidade,
                'uf'                  => $uf,
                'cep'                 => $cleanCep ?: null,
                'observacoes'         => $this->cleanStr($row['OBSERVACOES'] ?? $row['observacoes'] ?? null),
                'ativo'               => true,
            ]
        );
    }

    /**
     * Reprocess existing local partners to normalize numeric UFs and concatenated addresses.
     */
    public function reprocessExistingPartnersUf(): int
    {
        $updated = 0;
        $partners = Parceiro::all();
        foreach ($partners as $partner) {
            $normalizedUf = Parceiro::normalizeUf($partner->uf, $partner->cidade);
            $needsUpdate = false;

            if ($partner->uf !== $normalizedUf) {
                $partner->uf = $normalizedUf;
                $needsUpdate = true;
            }

            if ($partner->cidade && strtoupper(trim($partner->cidade)) === 'UBERLANDIA' && $partner->uf !== 'MG') {
                $partner->uf = 'MG';
                $needsUpdate = true;
            }

            // Fix addresses with '/2' or '- 2'
            if (!empty($partner->endereco) && (strpos($partner->endereco, '/2') !== false || strpos($partner->endereco, '- 2') !== false)) {
                $partner->endereco = preg_replace('/(\b[A-Za-zÀ-ÿ\s]+)[\/\-]\s*2\b/i', '$1/MG', $partner->endereco);
                $needsUpdate = true;
            }

            if ($needsUpdate) {
                $partner->save();
                $updated++;
            }
        }
        return $updated;
    }

    /**
     * Sync single Partner (Client) on demand.
     */
    public function syncPartnerByCode(string $code): ?Parceiro
    {
        try {
            $conn = $this->connect();
            $sql = $this->getPartnerBaseSql() . " WHERE PAR.CODPARC = :code";
            $stmt = $conn->prepare($sql);
            $stmt->execute(['code' => $code]);
            $row = $stmt->fetch();

            if (!$row) {
                return null;
            }

            return $this->savePartnerFromRow($row);
        } catch (\Exception $e) {
            Log::error("Sankhya Service - Failed to sync partner {$code}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Sync all active partners from Sankhya in bulk.
     */
    public function syncAllPartners(): int
    {
        try {
            $rows = $this->fetchPartners();
            $count = 0;
            foreach ($rows as $row) {
                $this->savePartnerFromRow($row);
                $count++;
            }
            return $count;
        } catch (\Exception $e) {
            Log::error("Sankhya Service - Failed bulk partner sync: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Fetch all price table records from Sankhya's VGF_PRECOAPP View.
     */
    public function fetchPriceTablesFromView(): array
    {
        $conn = $this->connect();
        try {
            $stmt = $conn->query("SELECT 
                CODPROD,
                CODTAB,
                NOMETAB,
                MARGLUCRO,
                MARGMIN,
                VLRMIN,
                CUSVAR,
                VLRPAD,
                VLRVENDA_TAB
              FROM SANKHYA.VGF_PRECOAPP");
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            $stmt = $conn->query("SELECT 
                CODPROD,
                CODTAB,
                NOMETAB,
                MARGLUCRO,
                MARGMIN,
                VLRMIN,
                CUSVAR,
                VLRPAD,
                VLRVENDA_TAB
              FROM VGF_PRECOAPP");
            return $stmt->fetchAll();
        }
    }

    /**
     * Bulk save/sync price tables and items from Oracle VGF_PRECOAPP view rows.
     */
    public function savePriceTablesFromViewRows(array $rows): int
    {
        $productsMap = Produto::pluck('id', 'codigo_sankhya')->toArray();
        $tablesMap = [];
        $count = 0;

        foreach ($rows as $row) {
            $codTab = (string)($row['CODTAB'] ?? $row['codtab']);
            $nomeTab = $row['NOMETAB'] ?? $row['nometab'] ?? ('Tabela ' . $codTab);
            $codProd = (string)($row['CODPROD'] ?? $row['codprod']);

            if (!isset($tablesMap[$codTab])) {
                $tabela = TabelaPreco::updateOrCreate(
                    ['codigo_sankhya' => $codTab],
                    [
                        'nome_tabela' => $nomeTab,
                        'ativa'       => true,
                    ]
                );
                $tablesMap[$codTab] = $tabela->id;
            }

            $tabelaId = $tablesMap[$codTab];
            $produtoId = $productsMap[$codProd] ?? null;

            $precoVenda = round((float)($row['VLRVENDA_TAB'] ?? $row['vlrvenda_tab'] ?? 0), 2);
            $precoPadrao = round((float)($row['VLRPAD'] ?? $row['vlrpad'] ?? $precoVenda), 2);
            $precoMinimo = round((float)($row['VLRMIN'] ?? $row['vlrmin'] ?? 0), 2);
            $margemLucro = round((float)($row['MARGLUCRO'] ?? $row['marglucro'] ?? 0), 2);
            $margemMinima = round((float)($row['MARGMIN'] ?? $row['margmin'] ?? 0), 2);
            $custoVariavel = round((float)($row['CUSVAR'] ?? $row['cusvar'] ?? 0), 2);

            TabelaPrecoItem::updateOrCreate(
                [
                    'tabela_preco_id'        => $tabelaId,
                    'codigo_sankhya_produto' => $codProd,
                ],
                [
                    'codigo_sankhya_tabela' => $codTab,
                    'produto_id'            => $produtoId,
                    'preco_venda'           => $precoVenda,
                    'preco_padrao'          => $precoPadrao,
                    'preco_minimo'          => $precoMinimo,
                    'margem_lucro'          => $margemLucro,
                    'margem_minima'         => $margemMinima,
                    'custo_variavel'        => $custoVariavel,
                ]
            );

            // Optionally update product's custo_variavel if present
            if ($produtoId && $custoVariavel > 0 && \Illuminate\Support\Facades\Schema::hasColumn('produtos', 'custo_variavel')) {
                Produto::where('id', $produtoId)->update(['custo_variavel' => $custoVariavel]);
            }

            $count++;
        }

        return $count;
    }

    /**
     * Sync all price tables from Sankhya VGF_PRECOAPP view.
     */
    public function syncAllPriceTables(): int
    {
        try {
            $rows = $this->fetchPriceTablesFromView();
            return $this->savePriceTablesFromViewRows($rows);
        } catch (\Exception $e) {
            Log::error("Sankhya Service - Failed price tables sync: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Fetch all payment conditions / negotiation types from Sankhya's TIPONEG_APP view.
     */
    public function fetchPaymentConditionsFromView(): array
    {
        $conn = $this->connect();
        try {
            $stmt = $conn->query("SELECT 
                CODTIPVENDA,
                DESCRTIPVENDA,
                VENDAMIN,
                VENDAMAX
              FROM SANKHYA.TIPONEG_APP");
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            $stmt = $conn->query("SELECT 
                CODTIPVENDA,
                DESCRTIPVENDA,
                VENDAMIN,
                VENDAMAX
              FROM TIPONEG_APP");
            return $stmt->fetchAll();
        }
    }

    /**
     * Bulk save/sync payment conditions from Oracle TIPONEG_APP view rows.
     */
    public function savePaymentConditionsFromViewRows(array $rows): int
    {
        $count = 0;
        foreach ($rows as $row) {
            $codTipVenda = (string)($row['CODTIPVENDA'] ?? $row['codtipvenda']);
            $descricao = $row['DESCRTIPVENDA'] ?? $row['descrtipvenda'] ?? ('Condição ' . $codTipVenda);

            CondicaoPagamento::updateOrCreate(
                ['codigo_sankhya' => $codTipVenda],
                [
                    'descricao'    => $descricao,
                    'venda_minima' => (float)($row['VENDAMIN'] ?? $row['vendamin'] ?? 0),
                    'venda_maxima' => (float)($row['VENDAMAX'] ?? $row['vendamax'] ?? 0),
                    'ativa'        => true,
                ]
            );
            $count++;
        }
        return $count;
    }

    /**
     * Sync all payment conditions from Sankhya TIPONEG_APP view.
     */
    public function syncAllPaymentConditions(): int
    {
        try {
            $rows = $this->fetchPaymentConditionsFromView();
            return $this->savePaymentConditionsFromViewRows($rows);
        } catch (\Exception $e) {
            Log::error("Sankhya Service - Failed payment conditions sync: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Save/update a local User representative record from an Oracle TGFVEN row.
     */
    public function saveRepresentativeFromRow(array $row): User
    {
        $codVend = (string)($row['CODVEND'] ?? $row['codvend']);
        $apelido = $row['APELIDO'] ?? $row['apelido'] ?? ('Vendedor ' . $codVend);
        $email = $row['EMAIL'] ?? $row['email'] ?? ('vendedor' . $codVend . '@zecotacao.com.br');
        $telefone = $row['TELEFONE'] ?? $row['telefone'] ?? $row['AD_TELEFONE'] ?? $row['ad_telefone'] ?? null;

        $user = User::where('codigo_sankhya', $codVend)->first();

        if ($user) {
            $user->update([
                'nome' => $apelido,
                'email' => $email,
                'telefone' => $telefone ?: $user->telefone,
                'ativo' => true,
            ]);
            return $user;
        }

        return User::create([
            'nome' => $apelido,
            'papel' => 'representante',
            'email' => $email,
            'telefone' => $telefone,
            'senha_hash' => bcrypt(\Illuminate\Support\Str::random(16)),
            'codigo_sankhya' => $codVend,
            'ativo' => true,
        ]);
    }

    /**
     * Sync single Representative on demand.
     */
    public function syncRepresentativeByCode(string $code): ?User
    {
        try {
            $conn = $this->connect();
            $stmt = $conn->prepare("SELECT CODVEND, APELIDO, EMAIL, AD_TELEFONE AS TELEFONE FROM SANKHYA.TGFVEN WHERE CODVEND = :code");
            $stmt->execute(['code' => $code]);
            $row = $stmt->fetch();

            if (!$row) {
                return null;
            }

            return $this->saveRepresentativeFromRow($row);
        } catch (\Exception $e) {
            Log::error("Sankhya Service - Failed to sync representative {$code}: " . $e->getMessage());
            return null;
        }
    }
}
