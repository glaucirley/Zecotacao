<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TabelaPrecoItem extends Model
{
    protected $table = 'tabela_preco_itens';

    protected $fillable = [
        'tabela_preco_id',
        'codigo_sankhya_tabela',
        'produto_id',
        'codigo_sankhya_produto',
        'preco_venda',
        'preco_padrao',
        'preco_minimo',
        'margem_lucro',
        'margem_minima',
        'custo_variavel',
    ];

    protected $casts = [
        'preco_venda' => 'decimal:2',
        'preco_padrao' => 'decimal:2',
        'preco_minimo' => 'decimal:2',
        'margem_lucro' => 'decimal:2',
        'margem_minima' => 'decimal:2',
        'custo_variavel' => 'decimal:2',
    ];

    protected $appends = [
        'inconsistente',
    ];

    public function getInconsistenteAttribute(): bool
    {
        $venda = (float)($this->attributes['preco_venda'] ?? $this->attributes['preco_padrao'] ?? 0);
        $min = (float)($this->attributes['preco_minimo'] ?? 0);
        return ($venda > 0 && $min > $venda);
    }

    public function setPrecoVendaAttribute($value): void
    {
        $this->attributes['preco_venda'] = round((float)$value, 2);
    }

    public function setPrecoPadraoAttribute($value): void
    {
        $this->attributes['preco_padrao'] = round((float)$value, 2);
    }

    public function setPrecoMinimoAttribute($value): void
    {
        $this->attributes['preco_minimo'] = round((float)$value, 2);
    }

    public function setCustoVariavelAttribute($value): void
    {
        $this->attributes['custo_variavel'] = round((float)$value, 2);
    }

    public function tabelaPreco()
    {
        return $this->belongsTo(TabelaPreco::class, 'tabela_preco_id');
    }

    public function produto()
    {
        return $this->belongsTo(Produto::class, 'produto_id');
    }
}
