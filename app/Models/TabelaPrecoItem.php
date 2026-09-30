<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TabelaPrecoItem extends Model
{
    protected $table = 'tabela_preco_itens';

    protected $fillable = [
        'tabela_preco_id',
        'codigo_sankhya_tabela',
        'produto_id',
        'codigo_sankhya_produto',
        'preco_venda',
        'preco_padrao',
        'preco_minimo',
        'margem_lucro',
        'margem_minima',
        'custo_variavel',
    ];

    protected $casts = [
        'preco_venda' => 'float',
        'preco_padrao' => 'float',
        'preco_minimo' => 'float',
        'margem_lucro' => 'float',
        'margem_minima' => 'float',
        'custo_variavel' => 'float',
    ];

    public function tabelaPreco()
    {
        return $this->belongsTo(TabelaPreco::class, 'tabela_preco_id');
    }

    public function produto()
    {
        return $this->belongsTo(Produto::class, 'produto_id');
    }
}
