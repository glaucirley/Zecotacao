<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PedidoExterno extends Model
{
    protected $table = 'pedidos_externos';

    protected $fillable = [
        'cotacao_id',
        'numero_pedido_externo',
        'valor_pedido',
        'recebido_em',
        'status_conferencia',
    ];

    protected $casts = [
        'recebido_em' => 'datetime',
        'valor_pedido' => 'decimal:2',
    ];

    public function cotacao()
    {
        return $this->belongsTo(Cotacao::class, 'cotacao_id');
    }
}
