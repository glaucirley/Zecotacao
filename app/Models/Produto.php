<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Produto extends Model
{
    protected $table = 'produtos';

    protected $fillable = [
        'codigo_sankhya',
        'descricao',
        'marca',
        'base',
        'base_loja_virtual',
        'descricao_longa',
        'descricao_tecnica',
        'descricao_interna',
        'nome_loja_virtual',
        'indicacao',
        'principio_ativo',
        'aplicacao',
        'peso_bruto',
        'peso_liquido',
        'margem_lucro',
        'custo_variavel',
        'ncm',
        'unidade',
        'validade_proxima',
        'estoque',
        'metadados_sankhya',
        'ativo',
    ];

    protected $casts = [
        'peso_bruto' => 'float',
        'peso_liquido' => 'float',
        'margem_lucro' => 'float',
        'custo_variavel' => 'float',
        'ativo' => 'boolean',
        'validade_proxima' => 'date',
        'estoque' => 'float',
        'metadados_sankhya' => 'array',
    ];

    protected $appends = [
        'validade_formatada',
        'estoque_formatado',
    ];

    protected static ?bool $hasValidadeProximaColumn = null;
    protected static ?bool $hasEstoqueColumn = null;

    public static function hasValidadeProximaColumn(): bool
    {
        if (static::$hasValidadeProximaColumn === null) {
            try {
                static::$hasValidadeProximaColumn = \Illuminate\Support\Facades\Schema::hasColumn('produtos', 'validade_proxima');
            } catch (\Throwable $e) {
                static::$hasValidadeProximaColumn = false;
            }
        }
        return static::$hasValidadeProximaColumn;
    }

    public static function hasEstoqueColumn(): bool
    {
        if (static::$hasEstoqueColumn === null) {
            try {
                static::$hasEstoqueColumn = \Illuminate\Support\Facades\Schema::hasColumn('produtos', 'estoque');
            } catch (\Throwable $e) {
                static::$hasEstoqueColumn = false;
            }
        }
        return static::$hasEstoqueColumn;
    }

    protected static function booted()
    {
        static::saving(function ($model) {
            if (!static::hasValidadeProximaColumn()) {
                unset($model->attributes['validade_proxima']);
            }
            if (!static::hasEstoqueColumn()) {
                unset($model->attributes['estoque']);
            }
        });
    }

    public function toArray()
    {
        $array = parent::toArray();
        if (!User::canUserViewCosts()) {
            unset($array['custo_variavel'], $array['margem_lucro'], $array['custo']);
        }
        return $array;
    }

    public function getValidadeFormatadaAttribute(): string
    {
        if (!static::hasValidadeProximaColumn() || !$this->validade_proxima) {
            return '--';
        }
        try {
            return \Carbon\Carbon::parse($this->validade_proxima)->format('m/Y');
        } catch (\Throwable $e) {
            return '--';
        }
    }

    public function getEstoqueFormatadoAttribute(): string
    {
        if (!static::hasEstoqueColumn() || $this->estoque === null) {
            return '0';
        }
        $val = (float) $this->estoque;
        return (floor($val) == $val)
            ? number_format($val, 0, ',', '.')
            : number_format($val, 2, ',', '.');
    }

    public function itensCotacao()
    {
        return $this->hasMany(CotacaoItem::class, 'produto_id');
    }

    public function precos()
    {
        return $this->hasMany(TabelaPrecoItem::class, 'produto_id');
    }

    public function tabelasPreco()
    {
        return $this->hasMany(TabelaPrecoItem::class, 'produto_id');
    }
}
