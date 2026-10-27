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
        if (Schema::hasTable('produtos') && !Schema::hasColumn('produtos', 'estoque')) {
            Schema::table('produtos', function (Blueprint $table) {
                $table->decimal('estoque', 12, 2)->default(0.00)->nullable()->after('validade_proxima');
            });
        }

        if (Schema::hasTable('cotacao_itens') && !Schema::hasColumn('cotacao_itens', 'estoque')) {
            Schema::table('cotacao_itens', function (Blueprint $table) {
                $table->decimal('estoque', 12, 2)->nullable()->after('validade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('produtos') && Schema::hasColumn('produtos', 'estoque')) {
            Schema::table('produtos', function (Blueprint $table) {
                $table->dropColumn('estoque');
            });
        }

        if (Schema::hasTable('cotacao_itens') && Schema::hasColumn('cotacao_itens', 'estoque')) {
            Schema::table('cotacao_itens', function (Blueprint $table) {
                $table->dropColumn('estoque');
            });
        }
    }
};
