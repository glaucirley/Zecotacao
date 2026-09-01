<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProdutoNaoEncontrado extends Model
{
    protected $table = 'produtos_nao_encontrados';

    protected $fillable = [
        'codigo_sankhya',
        'descricao',
        'requisicoes',
        'ultimo_solicitante',
    ];

    /**
     * Increment requisition count or register a new missing product request.
     */
    public static function registrar($codigo, $descricao, $requester)
    {
        $record = self::where('codigo_sankhya', $codigo)->first();
        if ($record) {
            $record->increment('requisicoes', 1, [
                'descricao' => $descricao,
                'ultimo_solicitante' => $requester,
                'updated_at' => now(),
            ]);
        } else {
            self::create([
                'codigo_sankhya' => $codigo,
                'descricao' => $descricao,
                'requisicoes' => 1,
                'ultimo_solicitante' => $requester
            ]);
        }
    }
}
