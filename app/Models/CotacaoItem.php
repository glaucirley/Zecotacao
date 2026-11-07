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
        'estoque',
        'campanha',
        'margem_calculada',
        'custo',
        'imposto',
        'campanha_id',
        'mostrar_selo_campanha',
        'ordem_exibicao',
        'restricao_efeito',
        'restricao_fonte',
        'restricao_msg_vendedor',
        'restricao_validada_em',
        'restricao_origem',
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
        'estoque' => 'float',
        'mostrar_selo_campanha' => 'boolean',
        'inconsistente' => 'boolean',
        'nao_encontrado' => 'boolean',
        'restricao_validada_em' => 'datetime',
    ];

    protected $appends = [
        'inconsistente',
        'preco_minimo_efetivo',
        'validade_formatada',
        'estoque_formatado',
        'campanha',
        'tem_campanha',
        'tem_margem_negativa',
    ];

    public function getTemMargemNegativaAttribute(): bool
    {
        $custo = (float)($this->custo ?? 0);
        $prop = (float)($this->preco_unit_proposto ?? 0);
        $min = (float)($this->preco_minimo ?? 0);

        // Se o preço proposto está no preço mínimo (ou acima) da política comercial, nunca é margem negativa
        if ($min > 0 && $prop >= ($min - 0.005)) {
            return false;
        }

        // Se a margem calculada oficial foi informada
        if ($this->margem_calculada !== null) {
            return (float)$this->margem_calculada < 0;
        }

        // Venda abaixo do custo cadastrado (margem comercial direta, sem desconto arbitrário de imposto)
        if ($custo > 0 && $prop > 0) {
            return $prop < ($custo - 0.005);
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

    public function getEstoqueFormatadoAttribute(): string
    {
        $val = ($this->produto && $this->produto->estoque !== null)
            ? (float) $this->produto->estoque
            : (static::hasEstoqueColumn() && $this->estoque !== null ? (float) $this->estoque : null);

        if ($val === null) {
            return '0';
        }
        return (floor($val) == $val)
            ? number_format($val, 0, ',', '.')
            : number_format($val, 2, ',', '.');
    }

    public function getCampanhaAttribute(): ?string
    {
        $val = static::hasCampanhaColumn() && !empty($this->attributes['campanha'])
            ? $this->attributes['campanha']
            : ($this->produto?->campanha ?? null);

        return !empty($val) ? trim($val) : null;
    }

    public function getTemCampanhaAttribute(): bool
    {
        return !empty($this->campanha);
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

    public static function hasInconsistenteColumn(): bool
    {
        return true;
    }

    public static function hasValidadeColumn(): bool
    {
        return true;
    }

    public static function hasEstoqueColumn(): bool
    {
        return true;
    }

    public static function hasCampanhaColumn(): bool
    {
        return true;
    }

    protected static function booted()
    {
        static::saving(function ($model) {
            // Garante que percentuais nunca estourem o limite das colunas decimais
            if (isset($model->attributes['margem_calculada']) && $model->attributes['margem_calculada'] !== null) {
                $model->attributes['margem_calculada'] = round(max(-999999.99, min(999999.99, (float)$model->attributes['margem_calculada'])), 2);
            }
            if (isset($model->attributes['ajuste_percentual']) && $model->attributes['ajuste_percentual'] !== null) {
                $model->attributes['ajuste_percentual'] = round(max(-999999.99, min(999999.99, (float)$model->attributes['ajuste_percentual'])), 2);
            }
            if (isset($model->attributes['imposto']) && $model->attributes['imposto'] !== null) {
                $model->attributes['imposto'] = round(max(-999.99, min(999.99, (float)$model->attributes['imposto'])), 2);
            }

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
            if (!static::hasEstoqueColumn()) {
                unset($model->attributes['estoque']);
            } elseif ($model->estoque === null && $model->produto_id) {
                $prod = $model->produto ?? Produto::find($model->produto_id);
                if ($prod && $prod->estoque !== null) {
                    $model->estoque = $prod->estoque;
                }
            }
            if (!static::hasCampanhaColumn()) {
                unset($model->attributes['campanha']);
            } elseif (empty($model->campanha) && $model->produto_id) {
                $prod = $model->produto ?? Produto::find($model->produto_id);
                if ($prod && !empty($prod->campanha)) {
                    $model->campanha = $prod->campanha;
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
        $this->attributes['subtotal'] = ceil(round(round((float)$value, 4) * 100, 2)) / 100;
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

    public function isBloqueado(): bool
    {
        return $this->restricao_efeito === 'B';
    }
}
