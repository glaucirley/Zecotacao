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
        'inconsistente',
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
        'inconsistente' => 'boolean',
    ];

    protected $appends = [
        'inconsistente',
        'preco_minimo_efetivo',
    ];

    public function getInconsistenteAttribute(): bool
    {
        $sug = (float)($this->attributes['preco_unit_sugerido'] ?? 0);
        $min = (float)($this->attributes['preco_minimo'] ?? 0);
        return ($sug > 0 && $min > $sug);
    }

    public function getPrecoMinimoEfetivoAttribute(): float
    {
        $sug = (float)($this->attributes['preco_unit_sugerido'] ?? 0);
        $min = (float)($this->attributes['preco_minimo'] ?? 0);
        if ($sug > 0 && $min > $sug) {
            return round($sug, 2);
        }
        return round($min, 2);
    }

    protected static ?bool $hasInconsistenteColumn = null;

    public static function hasInconsistenteColumn(): bool
    {
        if (static::$hasInconsistenteColumn === null) {
            try {
                static::$hasInconsistenteColumn = \Illuminate\Support\Facades\Schema::hasColumn('cotacao_itens', 'inconsistente');
            } catch (\Throwable $e) {
                static::$hasInconsistenteColumn = false;
            }
        }
        return static::$hasInconsistenteColumn;
    }

    protected static function booted()
    {
        static::saving(function ($model) {
            if (!static::hasInconsistenteColumn()) {
                unset($model->attributes['inconsistente']);
            }
        });
    }

    public function setPrecoUnitSugeridoAttribute($value): void
    {
        $this->attributes['preco_unit_sugerido'] = round((float)$value, 2);
        $this->updateInconsistenteFlag();
    }

    public function setPrecoMinimoAttribute($value): void
    {
        $this->attributes['preco_minimo'] = round((float)$value, 2);
        $this->updateInconsistenteFlag();
    }

    public function setPrecoUnitPropostoAttribute($value): void
    {
        $this->attributes['preco_unit_proposto'] = round((float)$value, 2);
    }

    public function setSubtotalAttribute($value): void
    {
        $this->attributes['subtotal'] = round((float)$value, 2);
    }

    protected function updateInconsistenteFlag(): void
    {
        $sug = (float)($this->attributes['preco_unit_sugerido'] ?? 0);
        $min = (float)($this->attributes['preco_minimo'] ?? 0);
        if (static::hasInconsistenteColumn()) {
            $this->attributes['inconsistente'] = ($sug > 0 && $min > $sug);
        }
    }

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
