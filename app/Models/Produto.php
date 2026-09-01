<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Produto extends Model
{
    protected $table = 'produtos';

    protected $fillable = [
        'codigo_sankhya',
        'descricao',
        'unidade',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    public function itensCotacao()
    {
        return $this->hasMany(CotacaoItem::class, 'produto_id');
    }
}
