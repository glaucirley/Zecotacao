<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Produto extends Model
{
    protected $table = 'produtos';

    protected $fillable = [
        'codigo_sankhya',
        'descricao',
        'marca',
        'base',
        'base_loja_virtual',
        'descricao_longa',
        'descricao_tecnica',
        'descricao_interna',
        'nome_loja_virtual',
        'indicacao',
        'principio_ativo',
        'aplicacao',
        'peso_bruto',
        'peso_liquido',
        'margem_lucro',
        'custo_variavel',
        'ncm',
        'unidade',
        'metadados_sankhya',
        'ativo',
    ];

    protected $casts = [
        'peso_bruto' => 'float',
        'peso_liquido' => 'float',
        'margem_lucro' => 'float',
        'custo_variavel' => 'float',
        'ativo' => 'boolean',
        'metadados_sankhya' => 'array',
    ];

    public function itensCotacao()
    {
        return $this->hasMany(CotacaoItem::class, 'produto_id');
    }

    public function precos()
    {
        return $this->hasMany(TabelaPrecoItem::class, 'produto_id');
    }

    public function tabelasPreco()
    {
        return $this->hasMany(TabelaPrecoItem::class, 'produto_id');
    }
}
