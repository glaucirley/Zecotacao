<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cotacao extends Model
{
    protected $table = 'cotacoes';

    protected $fillable = [
        'numero',
        'parceiro_id',
        'representante_id',
        'status',
        'prioridade',
        'origem',
        'data_emissao',
        'validade_horas',
        'data_validade',
        'subtotal',
        'desconto',
        'total',
        'forma_pagamento',
        'prazo_entrega',
        'frete_tipo',
        'transportadora',
        'observacao_cliente',
        'observacao_interna',
        'token_representante',
        'token_acesso_em',
    ];

    protected $casts = [
        'data_emissao' => 'datetime',
        'data_validade' => 'datetime',
        'token_acesso_em' => 'datetime',
        'subtotal' => 'decimal:2',
        'desconto' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function parceiro()
    {
        return $this->belongsTo(Parceiro::class, 'parceiro_id');
    }

    public function representante()
    {
        return $this->belongsTo(User::class, 'representante_id');
    }

    public function itens()
    {
        return $this->hasMany(CotacaoItem::class, 'cotacao_id');
    }

    public function vinculos()
    {
        return $this->hasMany(CotacaoVinculoItem::class, 'cotacao_id');
    }

    public function justificativas()
    {
        return $this->hasMany(CotacaoJustificativa::class, 'cotacao_id');
    }

    public function anexos()
    {
        return $this->hasMany(CotacaoAnexo::class, 'cotacao_id');
    }

    public function historico()
    {
        return $this->hasMany(CotacaoHistorico::class, 'cotacao_id');
    }

    public function pedidoExterno()
    {
        return $this->hasOne(PedidoExterno::class, 'cotacao_id');
    }
}
