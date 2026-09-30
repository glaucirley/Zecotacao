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
     * Concatenates address fields into a single human-readable string.
     */
    public function getEnderecoCompletoAttribute(): string
    {
        $logradouroNum = trim(($this->logradouro ?? $this->endereco ?? '') . ($this->numero ? ', ' . $this->numero : ''));
        $cidadeUf = trim(($this->cidade ?? '') . ($this->uf ? '/' . $this->uf : ''));
        
        $parts = array_filter([
            $logradouroNum !== '' ? $logradouroNum : null,
            $this->bairro ?: null,
            $cidadeUf !== '' ? $cidadeUf : null,
            $this->cep ? 'CEP ' . $this->cep : null,
        ]);

        return implode(' - ', $parts);
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
