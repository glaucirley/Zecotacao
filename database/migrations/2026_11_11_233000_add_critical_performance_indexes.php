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
        // 1. Índices para a tabela 'produtos'
        if (Schema::hasTable('produtos')) {
            $existingIndexes = Schema::getIndexListing('produtos');
            Schema::table('produtos', function (Blueprint $table) use ($existingIndexes) {
                if (!in_array('produtos_ativo_index', $existingIndexes)) {
                    $table->index('ativo', 'produtos_ativo_index');
                }
                if (!in_array('produtos_descricao_index', $existingIndexes)) {
                    $table->index('descricao', 'produtos_descricao_index');
                }
                if (!in_array('produtos_marca_index', $existingIndexes) && Schema::hasColumn('produtos', 'marca')) {
                    $table->index('marca', 'produtos_marca_index');
                }
                if (!in_array('produtos_ativo_descricao_index', $existingIndexes)) {
                    $table->index(['ativo', 'descricao'], 'produtos_ativo_descricao_index');
                }
            });
        }

        // 2. Índices compostos para a tabela 'tabela_preco_itens'
        if (Schema::hasTable('tabela_preco_itens')) {
            $existingIndexes = Schema::getIndexListing('tabela_preco_itens');
            Schema::table('tabela_preco_itens', function (Blueprint $table) use ($existingIndexes) {
                if (!in_array('idx_tpi_tabela_produto_id', $existingIndexes)) {
                    $table->index(['codigo_sankhya_tabela', 'produto_id'], 'idx_tpi_tabela_produto_id');
                }
                if (!in_array('idx_tpi_tabela_codprod', $existingIndexes)) {
                    $table->index(['codigo_sankhya_tabela', 'codigo_sankhya_produto'], 'idx_tpi_tabela_codprod');
                }
            });
        }

        // 3. Índice composto para a tabela 'notificacoes' (polling constante do painel)
        if (Schema::hasTable('notificacoes')) {
            $existingIndexes = Schema::getIndexListing('notificacoes');
            Schema::table('notificacoes', function (Blueprint $table) use ($existingIndexes) {
                if (!in_array('idx_notif_usuario_lida_created', $existingIndexes)) {
                    $table->index(['usuario_id', 'lida', 'created_at'], 'idx_notif_usuario_lida_created');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('produtos')) {
            $existingIndexes = Schema::getIndexListing('produtos');
            Schema::table('produtos', function (Blueprint $table) use ($existingIndexes) {
                if (in_array('produtos_ativo_descricao_index', $existingIndexes)) {
                    $table->dropIndex('produtos_ativo_descricao_index');
                }
                if (in_array('produtos_marca_index', $existingIndexes)) {
                    $table->dropIndex('produtos_marca_index');
                }
                if (in_array('produtos_descricao_index', $existingIndexes)) {
                    $table->dropIndex('produtos_descricao_index');
                }
                if (in_array('produtos_ativo_index', $existingIndexes)) {
                    $table->dropIndex('produtos_ativo_index');
                }
            });
        }

        if (Schema::hasTable('tabela_preco_itens')) {
            $existingIndexes = Schema::getIndexListing('tabela_preco_itens');
            Schema::table('tabela_preco_itens', function (Blueprint $table) use ($existingIndexes) {
                if (in_array('idx_tpi_tabela_produto_id', $existingIndexes)) {
                    $table->dropIndex('idx_tpi_tabela_produto_id');
                }
                if (in_array('idx_tpi_tabela_codprod', $existingIndexes)) {
                    $table->dropIndex('idx_tpi_tabela_codprod');
                }
            });
        }

        if (Schema::hasTable('notificacoes')) {
            $existingIndexes = Schema::getIndexListing('notificacoes');
            Schema::table('notificacoes', function (Blueprint $table) use ($existingIndexes) {
                if (in_array('idx_notif_usuario_lida_created', $existingIndexes)) {
                    $table->dropIndex('idx_notif_usuario_lida_created');
                }
            });
        }
    }
};
