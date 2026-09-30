<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TabelaPreco extends Model
{
    protected $table = 'tabelas_preco';

    protected $fillable = [
        'codigo_sankhya',
        'nome_tabela',
        'ativa',
    ];

    protected $casts = [
        'ativa' => 'boolean',
    ];

    public function itens()
    {
        return $this->hasMany(TabelaPrecoItem::class, 'tabela_preco_id');
    }
}
