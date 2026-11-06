<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cotacoes', function (Blueprint $table) {
            $table->index('status', 'cotacoes_status_index');
            $table->index('created_at', 'cotacoes_created_at_index');
            $table->index('data_validade', 'cotacoes_data_validade_index');
            $table->index(['representante_id', 'status'], 'cotacoes_rep_status_index');
        });

        Schema::table('cotacao_itens', function (Blueprint $table) {
            $table->index('status_item', 'cotacao_itens_status_item_index');
            if (Schema::hasColumn('cotacao_itens', 'nao_encontrado')) {
                $table->index('nao_encontrado', 'cotacao_itens_nao_encontrado_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cotacoes', function (Blueprint $table) {
            $table->dropIndex('cotacoes_status_index');
            $table->dropIndex('cotacoes_created_at_index');
            $table->dropIndex('cotacoes_data_validade_index');
            $table->dropIndex('cotacoes_rep_status_index');
        });

        Schema::table('cotacao_itens', function (Blueprint $table) {
            $table->dropIndex('cotacao_itens_status_item_index');
            if (Schema::hasColumn('cotacao_itens', 'nao_encontrado')) {
                $table->dropIndex('cotacao_itens_nao_encontrado_index');
            }
        });
    }
};
