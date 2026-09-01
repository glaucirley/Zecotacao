<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CotacaoHistorico extends Model
{
    protected $table = 'cotacao_historico';

    protected $fillable = [
        'cotacao_id',
        'cotacao_item_id',
        'evento',
        'usuario_id',
        'papel',
        'condicao',
    ];

    public function cotacao()
    {
        return $this->belongsTo(Cotacao::class, 'cotacao_id');
    }

    public function item()
    {
        return $this->belongsTo(CotacaoItem::class, 'cotacao_item_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
