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
        if (!Schema::hasColumn('parceiros', 'codigo_tabela_preco')) {
            Schema::table('parceiros', function (Blueprint $table) {
                $table->string('codigo_tabela_preco', 50)->nullable()->after('codigo_regiao')->index();
            });
        }

        if (!Schema::hasColumn('cotacoes', 'codigo_tabela_preco')) {
            Schema::table('cotacoes', function (Blueprint $table) {
                $table->string('codigo_tabela_preco', 50)->nullable()->after('condicao_pagamento_id')->index();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('parceiros', 'codigo_tabela_preco')) {
            Schema::table('parceiros', function (Blueprint $table) {
                $table->dropColumn('codigo_tabela_preco');
            });
        }

        if (Schema::hasColumn('cotacoes', 'codigo_tabela_preco')) {
            Schema::table('cotacoes', function (Blueprint $table) {
                $table->dropColumn('codigo_tabela_preco');
            });
        }
    }
};
