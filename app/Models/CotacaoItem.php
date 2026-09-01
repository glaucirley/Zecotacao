<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CotacaoItem extends Model
{
    protected $table = 'cotacao_itens';

    protected $fillable = [
        'cotacao_id',
        'produto_id',
        'qtd',
        'preco_unit_sugerido',
        'preco_minimo',
        'preco_unit_proposto',
        'ajuste_percentual',
        'subtotal',
        'status_item',
        'margem_calculada',
        'custo',
        'imposto',
        'campanha_id',
        'mostrar_selo_campanha',
        'ordem_exibicao',
    ];

    protected $casts = [
        'preco_unit_sugerido' => 'decimal:2',
        'preco_minimo' => 'decimal:2',
        'preco_unit_proposto' => 'decimal:2',
        'ajuste_percentual' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'margem_calculada' => 'decimal:2',
        'custo' => 'decimal:2',
        'imposto' => 'decimal:2',
        'mostrar_selo_campanha' => 'boolean',
    ];

    public function cotacao()
    {
        return $this->belongsTo(Cotacao::class, 'cotacao_id');
    }

    public function produto()
    {
        return $this->belongsTo(Produto::class, 'produto_id');
    }

    public function vinculo()
    {
        return $this->hasOne(CotacaoVinculoItem::class, 'cotacao_item_id');
    }

    public function justificativa()
    {
        return $this->hasOne(CotacaoJustificativa::class, 'cotacao_item_id');
    }

    public function historico()
    {
        return $this->hasMany(CotacaoHistorico::class, 'cotacao_item_id');
    }
}
