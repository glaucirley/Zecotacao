<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('cotacao_itens') || !Schema::hasTable('produtos')) {
            return;
        }

        // Atualiza itens onde descricao_original está nula ou vazia, preenchendo a partir da descrição do produto
        $unfoundItems = DB::table('cotacao_itens')
            ->join('produtos', 'cotacao_itens.produto_id', '=', 'produtos.id')
            ->where(function ($q) {
                $q->whereNull('cotacao_itens.descricao_original')
                  ->orWhere('cotacao_itens.descricao_original', '');
            })
            ->where(function ($q) {
                $q->where('cotacao_itens.nao_encontrado', true)
                  ->orWhere('produtos.codigo_sankhya', 'like', 'SEM_CODIGO%');
            })
            ->select('cotacao_itens.id', 'produtos.descricao as prod_desc', 'cotacao_itens.observacao_ia')
            ->get();

        foreach ($unfoundItems as $item) {
            $desc = !empty($item->prod_desc) && !str_starts_with($item->prod_desc, 'Produto SEM_CODIGO')
                ? $item->prod_desc
                : ($item->observacao_ia ?: 'Item solicitado pelo cliente');

            DB::table('cotacao_itens')
                ->where('id', $item->id)
                ->update(['descricao_original' => $desc]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Operação de backfill idempotente
    }
};
