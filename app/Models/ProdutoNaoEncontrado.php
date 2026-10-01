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
        try {
            $existing = \Illuminate\Support\Facades\DB::table('produtos_nao_encontrados')
                ->where('codigo_sankhya', $codigo)
                ->first();

            if ($existing) {
                \Illuminate\Support\Facades\DB::table('produtos_nao_encontrados')
                    ->where('id', $existing->id)
                    ->update([
                        'requisicoes' => \Illuminate\Support\Facades\DB::raw('requisicoes + 1'),
                        'descricao' => $descricao,
                        'ultimo_solicitante' => $requester,
                        'updated_at' => now(),
                    ]);
            } else {
                \Illuminate\Support\Facades\DB::table('produtos_nao_encontrados')->insert([
                    'codigo_sankhya' => $codigo,
                    'descricao' => $descricao,
                    'requisicoes' => 1,
                    'ultimo_solicitante' => $requester,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("ProdutoNaoEncontrado::registrar error for code {$codigo}: " . $e->getMessage());
        }
    }
}
