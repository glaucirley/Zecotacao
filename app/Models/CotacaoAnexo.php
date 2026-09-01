<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CotacaoAnexo extends Model
{
    protected $table = 'cotacao_anexos';

    protected $fillable = [
        'cotacao_id',
        'justificativa_id',
        'tipo',
        'arquivo_url',
    ];

    public function cotacao()
    {
        return $this->belongsTo(Cotacao::class, 'cotacao_id');
    }

    public function justificativa()
    {
        return $this->belongsTo(CotacaoJustificativa::class, 'justificativa_id');
    }
}
