<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('parametros_sistema')) {
            DB::table('parametros_sistema')->updateOrInsert(
                ['chave' => 'REGRA_LIBERACAO_DESCONTO'],
                [
                    'valor' => 'PRECO_MINIMO',
                    'descricao' => 'Regra para liberação automática de cotação: ALCADA_REPRESENTANTE (desconto acima da alçada do vendedor exige aprovação) ou PRECO_MINIMO (libera se estiver acima do mínimo)',
                    'tipo' => 'texto',
                    'editavel_por' => 'diretor',
                    'updated_at' => now(),
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('parametros_sistema')) {
            DB::table('parametros_sistema')
                ->where('chave', 'REGRA_LIBERACAO_DESCONTO')
                ->update(['valor' => 'ALCADA_REPRESENTANTE', 'updated_at' => now()]);
        }
    }
};
