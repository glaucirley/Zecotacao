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
        'nao_encontrado',
        'descricao_original',
        'observacao_ia',
        'validade',
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
        'validade' => 'date',
        'mostrar_selo_campanha' => 'boolean',
        'inconsistente' => 'boolean',
        'nao_encontrado' => 'boolean',
    ];

    protected $appends = [
        'inconsistente',
        'preco_minimo_efetivo',
        'validade_formatada',
        'tem_margem_negativa',
    ];

    public function getTemMargemNegativaAttribute(): bool
    {
        if ($this->margem_calculada !== null && (float)$this->margem_calculada < 0) {
            return true;
        }
        $custo = (float)($this->custo ?? 0);
        $prop = (float)($this->preco_unit_proposto ?? 0);
        $imposto = (float)($this->imposto ?? 0);
        if ($custo > 0 && $prop > 0) {
            $net = $prop * (1 - ($imposto / 100));
            return ($net < $custo);
        }
        return false;
    }

    public function toArray()
    {
        $array = parent::toArray();
        if (!User::canUserViewCosts()) {
            unset($array['custo'], $array['margem_calculada'], $array['imposto']);
        }
        return $array;
    }

    public function getValidadeFormatadaAttribute(): string
    {
        $val = static::hasValidadeColumn()
            ? ($this->validade ?? $this->produto?->validade_proxima)
            : ($this->produto?->validade_proxima ?? null);

        if (!$val) {
            return '--';
        }
        try {
            return \Carbon\Carbon::parse($val)->format('m/Y');
        } catch (\Throwable $e) {
            return '--';
        }
    }

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

    protected static ?bool $hasValidadeColumn = null;

    public static function hasValidadeColumn(): bool
    {
        if (static::$hasValidadeColumn === null) {
            try {
                static::$hasValidadeColumn = \Illuminate\Support\Facades\Schema::hasColumn('cotacao_itens', 'validade');
            } catch (\Throwable $e) {
                static::$hasValidadeColumn = false;
            }
        }
        return static::$hasValidadeColumn;
    }

    protected static function booted()
    {
        static::saving(function ($model) {
            if (!static::hasInconsistenteColumn()) {
                unset($model->attributes['inconsistente']);
            }
            if (!static::hasValidadeColumn()) {
                unset($model->attributes['validade']);
            } elseif (empty($model->validade) && $model->produto_id) {
                $prod = $model->produto ?? Produto::find($model->produto_id);
                if ($prod && !empty($prod->validade_proxima)) {
                    $model->validade = $prod->validade_proxima;
                }
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
