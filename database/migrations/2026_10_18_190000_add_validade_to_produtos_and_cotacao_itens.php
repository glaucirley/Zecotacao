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
        if (Schema::hasTable('produtos') && !Schema::hasColumn('produtos', 'validade_proxima')) {
            Schema::table('produtos', function (Blueprint $table) {
                $table->date('validade_proxima')->nullable()->after('unidade');
            });
        }

        if (Schema::hasTable('cotacao_itens') && !Schema::hasColumn('cotacao_itens', 'validade')) {
            Schema::table('cotacao_itens', function (Blueprint $table) {
                $table->date('validade')->nullable()->after('observacao_ia');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('produtos') && Schema::hasColumn('produtos', 'validade_proxima')) {
            Schema::table('produtos', function (Blueprint $table) {
                $table->dropColumn('validade_proxima');
            });
        }

        if (Schema::hasTable('cotacao_itens') && Schema::hasColumn('cotacao_itens', 'validade')) {
            Schema::table('cotacao_itens', function (Blueprint $table) {
                $table->dropColumn('validade');
            });
        }
    }
};
