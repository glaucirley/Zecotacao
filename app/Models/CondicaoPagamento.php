<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CondicaoPagamento extends Model
{
    protected $table = 'condicoes_pagamento';

    protected $fillable = [
        'codigo_sankhya',
        'descricao',
        'venda_minima',
        'venda_maxima',
        'ativa',
    ];

    protected $casts = [
        'venda_minima' => 'float',
        'venda_maxima' => 'float',
        'ativa' => 'boolean',
    ];
}
