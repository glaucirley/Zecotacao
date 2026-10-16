<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CotacaoEnvio extends Model
{
    protected $table = 'cotacao_envios';

    protected $fillable = [
        'cotacao_id',
        'contato_id',
        'token_acesso',
        'canal',
        'telefone_destino',
        'nome_contato_snapshot',
        'mensagem_enviada',
        'enviado_por',
        'enviado_em',
        'aberto_em',
        'ip_abertura',
        'user_agent_abertura',
        'expiracao_em',
    ];

    protected $casts = [
        'enviado_em' => 'datetime',
        'aberto_em' => 'datetime',
        'expiracao_em' => 'datetime',
    ];

    protected $hidden = [
        'token_acesso',
    ];

    public function cotacao()
    {
        return $this->belongsTo(Cotacao::class, 'cotacao_id');
    }

    public function contato()
    {
        return $this->belongsTo(ClienteContato::class, 'contato_id');
    }

    public function remetente()
    {
        return $this->belongsTo(User::class, 'enviado_por');
    }

    public function isAberto(): bool
    {
        return !is_null($this->aberto_em);
    }
}
