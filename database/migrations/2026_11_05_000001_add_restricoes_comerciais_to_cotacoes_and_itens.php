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
        // 1. Campos de restrição comercial em cotacao_itens
        Schema::table('cotacao_itens', function (Blueprint $table) {
            if (!Schema::hasColumn('cotacao_itens', 'restricao_efeito')) {
                $table->char('restricao_efeito', 1)->nullable()->after('status_item'); // 'L' ou 'B'
            }
            if (!Schema::hasColumn('cotacao_itens', 'restricao_fonte')) {
                $table->string('restricao_fonte', 30)->nullable()->after('restricao_efeito'); // 'AD_EXCPRODUTO' ou 'AD_EXCMARCA'
            }
            if (!Schema::hasColumn('cotacao_itens', 'restricao_msg_vendedor')) {
                $table->string('restricao_msg_vendedor', 400)->nullable()->after('restricao_fonte');
            }
            if (!Schema::hasColumn('cotacao_itens', 'restricao_validada_em')) {
                $table->dateTime('restricao_validada_em')->nullable()->after('restricao_msg_vendedor');
            }
            if (!Schema::hasColumn('cotacao_itens', 'restricao_origem')) {
                $table->enum('restricao_origem', ['PAINEL', 'APP', 'N8N'])->nullable()->after('restricao_validada_em');
            }

            $table->index(['cotacao_id', 'restricao_efeito'], 'idx_cotacao_itens_restricao');
        });

        // 2. Status de validação de restrições em cotacoes
        Schema::table('cotacoes', function (Blueprint $table) {
            if (!Schema::hasColumn('cotacoes', 'restricoes_validadas')) {
                $table->tinyInteger('restricoes_validadas')->default(1)->after('status');
            }
            if (!Schema::hasColumn('cotacoes', 'restricoes_validadas_em')) {
                $table->dateTime('restricoes_validadas_em')->nullable()->after('restricoes_validadas');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cotacao_itens', function (Blueprint $table) {
            $table->dropIndex('idx_cotacao_itens_restricao');
            $table->dropColumn([
                'restricao_efeito',
                'restricao_fonte',
                'restricao_msg_vendedor',
                'restricao_validada_em',
                'restricao_origem'
            ]);
        });

        Schema::table('cotacoes', function (Blueprint $table) {
            $table->dropColumn([
                'restricoes_validadas',
                'restricoes_validadas_em'
            ]);
        });
    }
};
