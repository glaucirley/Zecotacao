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
        // 1. Add 'inconsistente' column to cotacao_itens if not present
        if (!Schema::hasColumn('cotacao_itens', 'inconsistente')) {
            Schema::table('cotacao_itens', function (Blueprint $table) {
                $table->boolean('inconsistente')->default(false)->after('status_item');
            });
        }

        // 2. Round existing values in cotacao_itens and flag inconsistent items (min > suggested)
        DB::statement("
            UPDATE cotacao_itens 
            SET 
                preco_unit_sugerido = ROUND(preco_unit_sugerido, 2),
                preco_minimo = ROUND(preco_minimo, 2),
                preco_unit_proposto = ROUND(preco_unit_proposto, 2),
                subtotal = ROUND(subtotal, 2),
                inconsistente = (preco_unit_sugerido > 0 AND preco_minimo > preco_unit_sugerido)
        ");

        // 3. Round existing values in tabela_preco_itens
        if (Schema::hasTable('tabela_preco_itens')) {
            DB::statement("
                UPDATE tabela_preco_itens 
                SET 
                    preco_venda = ROUND(preco_venda, 2),
                    preco_padrao = ROUND(preco_padrao, 2),
                    preco_minimo = ROUND(preco_minimo, 2),
                    custo_variavel = ROUND(custo_variavel, 2)
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('cotacao_itens', 'inconsistente')) {
            Schema::table('cotacao_itens', function (Blueprint $table) {
                $table->dropColumn('inconsistente');
            });
        }
    }
};
