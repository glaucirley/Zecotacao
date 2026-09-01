<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Parceiro extends Model
{
    protected $table = 'parceiros';

    protected $fillable = [
        'codigo_sankhya',
        'razao_social',
        'nome_fantasia',
        'cnpj',
        'telefone',
        'email',
        'endereco',
        'cidade',
        'uf',
        'cep',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    public function cotacoes()
    {
        return $this->hasMany(Cotacao::class, 'parceiro_id');
    }
}
