<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ChatMensagem extends Model
{
    use HasFactory;

    protected $table = 'chat_mensagens';

    protected $fillable = [
        'telefone_cliente',
        'nome_cliente',
        'direcao',
        'mensagem',
        'tipo',
        'cotacao_id'
    ];

    public function cotacao()
    {
        return $this->belongsTo(Cotacao::class, 'cotacao_id');
    }
}
