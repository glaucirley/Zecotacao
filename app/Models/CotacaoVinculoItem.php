<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CotacaoVinculoItem extends Model
{
    protected $table = 'cotacao_vinculo_itens';

    protected $fillable = [
        'cotacao_id',
        'cotacao_item_id',
        'grupo_vinculo',
    ];

    public function cotacao()
    {
        return $this->belongsTo(Cotacao::class, 'cotacao_id');
    }

    public function item()
    {
        return $this->belongsTo(CotacaoItem::class, 'cotacao_item_id');
    }
}
