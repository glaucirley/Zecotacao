<?php

namespace App\Services;

use App\Models\Parceiro;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PartnerImportService
{
    /**
     * Map alternative header/key variations from Sankhya or CSV to standard model fields.
     */
    protected array $fieldMap = [
        // Código Sankhya
        'codparc'         => 'codigo_sankhya',
        'cod_parc'        => 'codigo_sankhya',
        'codparceiro'     => 'codigo_sankhya',
        'codigo'          => 'codigo_sankhya',
        'codigo_sankhya'  => 'codigo_sankhya',
        'cod_sankhya'     => 'codigo_sankhya',
        'id_sankhya'      => 'codigo_sankhya',

        // Razão Social
        'razaosocial'     => 'razao_social',
        'razao_social'    => 'razao_social',
        'razao'           => 'razao_social',
        'nome'            => 'razao_social',
        'nomeparc'        => 'nome_fantasia',
        'nome_parc'       => 'nome_fantasia',
        'nome_fantasia'   => 'nome_fantasia',
        'fantasia'        => 'nome_fantasia',

        // Documento
        'cgc_cpf'         => 'cnpj',
        'cgccpf'          => 'cnpj',
        'cnpj_cpf'        => 'cnpj',
        'cnpj'            => 'cnpj',
        'cpf'             => 'cnpj',

        // Contato
        'telefone'        => 'telefone',
        'tel'             => 'telefone',
        'celular'         => 'telefone',
        'fone'            => 'telefone',
        'email'           => 'email',
        'e_mail'          => 'email',

        // Endereço
        'cidade'          => 'cidade',
        'nomecid'         => 'cidade',
        'municipio'       => 'cidade',
        'uf'              => 'uf',
        'estado'          => 'uf',
        'bairro'          => 'bairro',
        'nomebai'         => 'bairro',
        'logradouro'      => 'logradouro',
        'nomeend'         => 'logradouro',
        'rua'             => 'logradouro',
        'endereco'        => 'endereco',
        'numero'          => 'numero',
        'num'             => 'numero',
        'cep'             => 'cep',

        // Vendedor / Representante
        'codvend'         => 'vendedor_1_codigo',
        'vendedor'        => 'vendedor_1_codigo',
        'vendedor_1'      => 'vendedor_1_codigo',
        'vendedor_1_codigo' => 'vendedor_1_codigo',
        'vendedor_codigo' => 'vendedor_1_codigo',

        // Status
        'ativo'           => 'ativo',
    ];

    /**
     * Import partners from an array of associative items (used by N8N or JSON payloads).
     */
    public function importFromRows(array $rows): array
    {
        $created = 0;
        $updated = 0;
        $errors = [];

        foreach (array_chunk($rows, 250) as $chunk) {
            DB::beginTransaction();
            try {
                foreach ($chunk as $index => $row) {
                    $normalized = $this->normalizeRow($row);

                    if (empty($normalized['codigo_sankhya'])) {
                        $errors[] = "Linha " . ($index + 1) . " ignorada: Código Sankhya vazio.";
                        continue;
                    }

                    if (empty($normalized['razao_social'])) {
                        $normalized['razao_social'] = $normalized['nome_fantasia'] ?? ('Cliente ' . $normalized['codigo_sankhya']);
                    }

                    $exists = Parceiro::where('codigo_sankhya', $normalized['codigo_sankhya'])->first();
                    if ($exists) {
                        $exists->update($normalized);
                        $updated++;
                    } else {
                        Parceiro::create($normalized);
                        $created++;
                    }
                }
                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                Log::error("PartnerImportService chunk error: " . $e->getMessage());
                $errors[] = "Erro no lote: " . $e->getMessage();
            }
        }

        return [
            'success'   => true,
            'total'     => count($rows),
            'criados'   => $created,
            'atualizados' => $updated,
            'erros'     => $errors,
        ];
    }

    /**
     * Import partners from a CSV or TXT file on disk.
     */
    public function importFromCsv(string $filePath): array
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw new \Exception("Arquivo não encontrado ou sem permissão de leitura: {$filePath}");
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new \Exception("Não foi possível abrir o arquivo {$filePath}");
        }

        // Read first line to detect delimiter and encoding
        $firstLine = fgets($handle);
        if ($firstLine === false) {
            fclose($handle);
            throw new \Exception("O arquivo enviado está vazio.");
        }

        // Remove UTF-8 BOM if present
        $bom = pack('H*','EFBBBF');
        $firstLine = preg_replace("/^{$bom}/", '', $firstLine);

        // Detect delimiter: semicolon (;), comma (,), tab (\t) or pipe (|)
        $delimiters = [';' => 0, ',' => 0, "\t" => 0, '|' => 0];
        foreach ($delimiters as $delim => &$count) {
            $count = substr_count($firstLine, $delim);
        }
        arsort($delimiters);
        $delimiter = key($delimiters) ?: ';';

        // Parse header
        $rawHeaders = str_getcsv(trim($firstLine), $delimiter);
        $headers = [];
        foreach ($rawHeaders as $idx => $h) {
            $cleanKey = strtolower(trim(preg_replace('/[^a-zA-Z0-9_]/', '', str_replace([' ', '-'], '_', $h))));
            $headers[$idx] = $cleanKey;
        }

        $batch = [];
        $totalProcessed = 0;
        $totalCreated = 0;
        $totalUpdated = 0;
        $errors = [];

        while (($data = fgetcsv($handle, 4096, $delimiter)) !== false) {
            if (empty(array_filter($data))) {
                continue; // Skip empty rows
            }

            $row = [];
            foreach ($headers as $colIdx => $colKey) {
                if (isset($data[$colIdx])) {
                    $row[$colKey] = trim($data[$colIdx]);
                }
            }

            $batch[] = $row;
            $totalProcessed++;

            if (count($batch) >= 250) {
                $res = $this->importFromRows($batch);
                $totalCreated += $res['criados'];
                $totalUpdated += $res['atualizados'];
                if (!empty($res['erros'])) {
                    $errors = array_merge($errors, $res['erros']);
                }
                $batch = [];
            }
        }

        if (!empty($batch)) {
            $res = $this->importFromRows($batch);
            $totalCreated += $res['criados'];
            $totalUpdated += $res['atualizados'];
            if (!empty($res['erros'])) {
                $errors = array_merge($errors, $res['erros']);
            }
        }

        fclose($handle);

        return [
            'success'     => true,
            'total'       => $totalProcessed,
            'criados'     => $totalCreated,
            'atualizados' => $totalUpdated,
            'erros'       => array_slice($errors, 0, 10), // Limit error list
        ];
    }

    /**
     * Normalize raw row fields to Parceiro model schema.
     */
    protected function normalizeRow(array $raw): array
    {
        $normalized = [
            'ativo' => true,
        ];

        foreach ($raw as $key => $val) {
            $cleanKey = strtolower(trim(preg_replace('/[^a-zA-Z0-9_]/', '', str_replace([' ', '-'], '_', $key))));
            $field = $this->fieldMap[$cleanKey] ?? null;

            if ($field && $val !== null && $val !== '') {
                $normalized[$field] = is_string($val) ? trim($val) : $val;
            }
        }

        // Clean CNPJ / CPF
        if (isset($normalized['cnpj'])) {
            $cleanCnpj = preg_replace('/[^0-9]/', '', (string)$normalized['cnpj']);
            $normalized['cnpj'] = $cleanCnpj !== '' ? $cleanCnpj : null;
        }

        // Clean CEP
        if (isset($normalized['cep'])) {
            $cleanCep = preg_replace('/[^0-9]/', '', (string)$normalized['cep']);
            $normalized['cep'] = $cleanCep !== '' ? $cleanCep : null;
        }

        // Clean UF (normalized 2-letter uppercase acronym)
        if (isset($normalized['uf'])) {
            $normalized['uf'] = Parceiro::normalizeUf($normalized['uf'], $normalized['cidade'] ?? null);
        }

        // Normalize ativo status
        if (isset($normalized['ativo'])) {
            $val = strtolower((string)$normalized['ativo']);
            $normalized['ativo'] = in_array($val, ['1', 's', 'sim', 'true', 'ativo', 't']);
        }

        // Format concatenated address if not provided
        if (empty($normalized['endereco'])) {
            $parts = array_filter([
                ($normalized['logradouro'] ?? '') . (!empty($normalized['numero']) ? ', ' . $normalized['numero'] : ''),
                $normalized['bairro'] ?? null,
                ($normalized['cidade'] ?? '') . (!empty($normalized['uf']) ? '/' . $normalized['uf'] : ''),
            ]);
            if (!empty($parts)) {
                $normalized['endereco'] = implode(' - ', $parts);
            }
        }

        return $normalized;
    }
}
