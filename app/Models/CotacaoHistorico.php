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

    protected static function booted()
    {
        static::creating(function (CotacaoHistorico $hist) {
            if (!empty($hist->usuario_id) && !in_array($hist->papel, ['sistema', 'cliente'], true)) {
                $user = User::find($hist->usuario_id);
                if ($user && !empty($user->papel)) {
                    $hist->papel = $user->papel;
                }
            }
        });
    }

    public function getPapelAttribute($value)
    {
        if (!in_array($value, ['sistema', 'cliente'], true) && $this->relationLoaded('usuario') && $this->usuario && !empty($this->usuario->papel)) {
            return $this->usuario->papel;
        }
        return $value;
    }

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
