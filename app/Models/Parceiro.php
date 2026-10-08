<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Parceiro extends Model
{
    protected $table = 'parceiros';

    protected $fillable = [
        'codigo_sankhya',
        'razao_social',
        'nome_fantasia',
        'cnpj',
        'inscricao_estadual',
        'tipo_pessoa',
        'codigo_regiao',
        'vendedor_1_codigo',
        'vendedor_2_codigo',
        'vendedor_3_codigo',
        'vendedor_4_codigo',
        'vendedor_5_codigo',
        'telefone',
        'email',
        'endereco',
        'logradouro',
        'numero',
        'bairro',
        'cidade',
        'uf',
        'cep',
        'observacoes',
        'metadados_sankhya',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'metadados_sankhya' => 'array',
    ];

    protected $appends = [
        'endereco_completo',
    ];

    /**
     * Normalize state to standard 2-letter uppercase UF acronym.
     */
    public static function normalizeUf(?string $uf, ?string $cidade = null): ?string
    {
        if ($cidade && strtoupper(trim($cidade)) === 'UBERLANDIA') {
            return 'MG';
        }

        if ($uf === null || trim((string)$uf) === '') {
            return null;
        }

        $ufStr = strtoupper(trim((string)$uf));

        $validUfs = [
            'AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS',
            'MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC',
            'SP','SE','TO'
        ];

        if (in_array($ufStr, $validUfs)) {
            return $ufStr;
        }

        // Sankhya standard CODUF mapping & IBGE codes
        $codeMap = [
            '1'  => 'SP',
            '2'  => 'MG',
            '3'  => 'RJ',
            '4'  => 'BA',
            '5'  => 'RS',
            '6'  => 'PR',
            '7'  => 'PE',
            '8'  => 'CE',
            '9'  => 'SC',
            '10' => 'GO',
            '11' => 'MA',
            '12' => 'PB',
            '13' => 'ES',
            '14' => 'PA',
            '15' => 'RN',
            '16' => 'AL',
            '17' => 'PI',
            '18' => 'MT',
            '19' => 'DF',
            '20' => 'MS',
            '21' => 'SE',
            '22' => 'AM',
            '23' => 'RO',
            '24' => 'AC',
            '25' => 'AP',
            '26' => 'RR',
            '27' => 'TO',
            '28' => 'SE',
            '29' => 'BA',
            '31' => 'MG',
            '32' => 'ES',
            '33' => 'RJ',
            '35' => 'SP',
            '41' => 'PR',
            '42' => 'SC',
            '43' => 'RS',
            '50' => 'MS',
            '51' => 'MT',
            '52' => 'GO',
            '53' => 'DF',
        ];

        if (isset($codeMap[$ufStr])) {
            return $codeMap[$ufStr];
        }

        return substr($ufStr, 0, 2);
    }

    public function getUfAttribute($value): ?string
    {
        return static::normalizeUf($value, $this->attributes['cidade'] ?? null);
    }

    public function setUfAttribute($value): void
    {
        $this->attributes['uf'] = static::normalizeUf($value ? (string)$value : null, $this->attributes['cidade'] ?? null);
    }

    public function setCidadeAttribute($value): void
    {
        $this->attributes['cidade'] = $value ? trim((string)$value) : null;
        if (!empty($this->attributes['cidade']) && strtoupper($this->attributes['cidade']) === 'UBERLANDIA') {
            if (empty($this->attributes['uf']) || $this->attributes['uf'] === '2' || is_numeric($this->attributes['uf'])) {
                $this->attributes['uf'] = 'MG';
            }
        }
    }

    /**
     * Concatenates address fields into a single human-readable string.
     */
    public function getEnderecoCompletoAttribute(): string
    {
        $uf = static::normalizeUf($this->uf, $this->cidade);
        $logradouroNum = trim(($this->logradouro ?? $this->endereco ?? '') . ($this->numero ? ', ' . $this->numero : ''));
        $cidadeUf = trim(($this->cidade ?? '') . ($uf ? '/' . $uf : ''));
        
        $parts = array_filter([
            $logradouroNum !== '' ? $logradouroNum : null,
            $this->bairro ?: null,
            $cidadeUf !== '' ? $cidadeUf : null,
            $this->cep ? 'CEP ' . $this->cep : null,
        ]);

        $full = implode(' - ', $parts);
        return preg_replace('/\/2\b/', '/MG', $full);
    }

    /**
     * Get list of seller codes assigned to this partner.
     */
    public function getVendedoresCodigosAttribute(): array
    {
        return array_values(array_filter([
            $this->vendedor_1_codigo,
            $this->vendedor_2_codigo,
            $this->vendedor_3_codigo,
            $this->vendedor_4_codigo,
            $this->vendedor_5_codigo,
        ]));
    }

    public function cotacoes()
    {
        return $this->hasMany(Cotacao::class, 'parceiro_id');
    }
}
