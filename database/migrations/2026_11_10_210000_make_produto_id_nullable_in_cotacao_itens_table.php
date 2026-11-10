<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Tornar a coluna produto_id anulável em cotacao_itens
        try {
            Schema::table('cotacao_itens', function (Blueprint $table) {
                try {
                    $table->dropForeign(['produto_id']);
                } catch (\Throwable $e) {
                    // Chave pode ter outro nome ou não existir
                }
            });
        } catch (\Throwable $e) {}

        try {
            DB::statement("ALTER TABLE cotacao_itens MODIFY COLUMN produto_id BIGINT UNSIGNED NULL");
        } catch (\Throwable $e) {
            try {
                Schema::table('cotacao_itens', function (Blueprint $table) {
                    $table->unsignedBigInteger('produto_id')->nullable()->change();
                });
            } catch (\Throwable $e2) {}
        }

        try {
            Schema::table('cotacao_itens', function (Blueprint $table) {
                $table->foreign('produto_id')
                    ->references('id')
                    ->on('produtos')
                    ->onDelete('set null');
            });
        } catch (\Throwable $e) {}

        // 2. Limpar produtos dummy criados para itens não encontrados (ex: SEM_CODIGO_% ou cadastrados como inativos sem preço)
        try {
            // A. Identificar produtos SEM_CODIGO_%
            $dummyProducts = DB::table('produtos')
                ->where('codigo_sankhya', 'like', 'SEM_CODIGO%')
                ->get(['id', 'codigo_sankhya', 'descricao']);

            foreach ($dummyProducts as $dp) {
                // Preservar a descrição na cotação
                DB::table('cotacao_itens')
                    ->where('produto_id', $dp->id)
                    ->where(function ($q) {
                        $q->whereNull('descricao_original')->orWhere('descricao_original', '');
                    })
                    ->update(['descricao_original' => $dp->descricao]);

                // Desvincular o produto do item da cotação
                DB::table('cotacao_itens')
                    ->where('produto_id', $dp->id)
                    ->update([
                        'produto_id' => null,
                        'nao_encontrado' => true,
                    ]);

                // Remover produto falso da tabela de produtos
                DB::table('produtos')->where('id', $dp->id)->delete();
            }

            // B. Identificar produtos inativos criados como fallback a partir de ProdutoNaoEncontrado sem preço na tabela
            $missingInativos = DB::table('produtos')
                ->where('ativo', false)
                ->whereIn('codigo_sankhya', function ($sub) {
                    $sub->select('codigo_sankhya')->from('produtos_nao_encontrados');
                })
                ->whereNotExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('tabela_precos_itens')
                        ->whereColumn('tabela_precos_itens.produto_id', 'produtos.id');
                })
                ->get(['id', 'codigo_sankhya', 'descricao']);

            foreach ($missingInativos as $dp) {
                DB::table('cotacao_itens')
                    ->where('produto_id', $dp->id)
                    ->where(function ($q) {
                        $q->whereNull('descricao_original')->orWhere('descricao_original', '');
                    })
                    ->update(['descricao_original' => $dp->descricao]);

                DB::table('cotacao_itens')
                    ->where('produto_id', $dp->id)
                    ->update([
                        'produto_id' => null,
                        'nao_encontrado' => true,
                    ]);

                DB::table('produtos')->where('id', $dp->id)->delete();
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Migração: aviso ao limpar produtos dummy: " . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            DB::statement("ALTER TABLE cotacao_itens MODIFY COLUMN produto_id BIGINT UNSIGNED NOT NULL");
        } catch (\Throwable $e) {}
    }
};
