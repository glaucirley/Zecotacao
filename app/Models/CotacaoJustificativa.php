<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CotacaoJustificativa extends Model
{
    protected $table = 'cotacao_justificativas';

    protected $fillable = [
        'cotacao_id',
        'cotacao_item_id',
        'texto',
        'audio_url',
        'criado_por',
    ];

    public function cotacao()
    {
        return $this->belongsTo(Cotacao::class, 'cotacao_id');
    }

    public function item()
    {
        return $this->belongsTo(CotacaoItem::class, 'cotacao_item_id');
    }

    public function autor()
    {
        return $this->belongsTo(User::class, 'criado_por');
    }

    public function anexos()
    {
        return $this->hasMany(CotacaoAnexo::class, 'justificativa_id');
    }
}
