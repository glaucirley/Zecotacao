<?php

namespace App\Services;

use App\Models\ParametroSistema;
use App\Models\Produto;
use App\Models\Parceiro;
use App\Models\User;
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
     * Fetch all active products from Sankhya Oracle.
     */
    public function fetchProducts(): array
    {
        $conn = $this->connect();
        $stmt = $conn->query("SELECT CODPROD, DESCRPROD, ATIVO FROM SANKHYA.TGFPRO WHERE ATIVO = 'S'");
        return $stmt->fetchAll();
    }

    /**
     * Fetch all active clients (partners) from Sankhya Oracle.
     */
    public function fetchPartners(): array
    {
        $conn = $this->connect();
        // TGFPAR represents partners. CLIENTE = 'S' means client.
        $stmt = $conn->query("SELECT CODPARC, NOMEPARC, CGC_CPF, TELEFONE, EMAIL, CEP FROM SANKHYA.TGFPAR WHERE CLIENTE = 'S' AND ATIVO = 'S'");
        return $stmt->fetchAll();
    }

    /**
     * Fetch all active representatives (sellers) from Sankhya Oracle.
     */
    public function fetchRepresentatives(): array
    {
        $conn = $this->connect();
        $stmt = $conn->query("SELECT CODVEND, APELIDO, EMAIL FROM SANKHYA.TGFVEN WHERE ATIVO = 'S'");
        return $stmt->fetchAll();
    }

    /**
     * Sync single Product on demand.
     */
    public function syncProductByCode(string $code): ?Produto
    {
        try {
            $conn = $this->connect();
            $stmt = $conn->prepare("SELECT CODPROD, DESCRPROD, ATIVO FROM SANKHYA.TGFPRO WHERE CODPROD = :code");
            $stmt->execute(['code' => $code]);
            $row = $stmt->fetch();

            if (!$row) {
                return null;
            }

            return Produto::updateOrCreate(
                ['codigo_sankhya' => (string)$row['CODPROD']],
                [
                    'descricao' => $row['DESCRPROD'],
                    'unidade' => 'UN',
                    'ativo' => $row['ATIVO'] === 'S',
                ]
            );
        } catch (\Exception $e) {
            Log::error("Sankhya Service - Failed to sync product {$code}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Sync single Partner (Client) on demand.
     */
    public function syncPartnerByCode(string $code): ?Parceiro
    {
        try {
            $conn = $this->connect();
            $stmt = $conn->prepare("SELECT CODPARC, NOMEPARC, CGC_CPF, TELEFONE, EMAIL, CEP FROM SANKHYA.TGFPAR WHERE CODPARC = :code");
            $stmt->execute(['code' => $code]);
            $row = $stmt->fetch();

            if (!$row) {
                return null;
            }

            return Parceiro::updateOrCreate(
                ['codigo_sankhya' => (string)$row['CODPARC']],
                [
                    'razao_social' => $row['NOMEPARC'],
                    'nome_fantasia' => $row['NOMEPARC'],
                    'cnpj' => preg_replace('/[^0-9]/', '', $row['CGC_CPF'] ?? ''),
                    'telefone' => $row['TELEFONE'] ?? '',
                    'email' => $row['EMAIL'] ?? '',
                    'cep' => preg_replace('/[^0-9]/', '', $row['CEP'] ?? ''),
                    'ativo' => true,
                ]
            );
        } catch (\Exception $e) {
            Log::error("Sankhya Service - Failed to sync partner {$code}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Sync single Representative on demand.
     */
    public function syncRepresentativeByCode(string $code): ?User
    {
        try {
            $conn = $this->connect();
            $stmt = $conn->prepare("SELECT CODVEND, APELIDO, EMAIL FROM SANKHYA.TGFVEN WHERE CODVEND = :code");
            $stmt->execute(['code' => $code]);
            $row = $stmt->fetch();

            if (!$row) {
                return null;
            }

            $user = User::where('codigo_sankhya', (string)$row['CODVEND'])->first();

            if ($user) {
                $user->update([
                    'nome' => $row['APELIDO'],
                    'email' => $row['EMAIL'] ?? $user->email,
                ]);
                return $user;
            }

            // Create representative with a safe randomized password
            return User::create([
                'nome' => $row['APELIDO'],
                'papel' => 'representante',
                'email' => $row['EMAIL'] ?? ('vendedor' . $row['CODVEND'] . '@zecotacao.com.br'),
                'senha_hash' => bcrypt(str_random(16)),
                'codigo_sankhya' => (string)$row['CODVEND'],
                'ativo' => true,
            ]);
        } catch (\Exception $e) {
            Log::error("Sankhya Service - Failed to sync representative {$code}: " . $e->getMessage());
            return null;
        }
    }
}
