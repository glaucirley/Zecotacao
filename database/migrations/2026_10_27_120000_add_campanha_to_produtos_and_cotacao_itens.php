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
        if (Schema::hasTable('produtos') && !Schema::hasColumn('produtos', 'campanha')) {
            Schema::table('produtos', function (Blueprint $table) {
                $table->text('campanha')->nullable()->after('estoque');
            });
        }

        if (Schema::hasTable('cotacao_itens') && !Schema::hasColumn('cotacao_itens', 'campanha')) {
            Schema::table('cotacao_itens', function (Blueprint $table) {
                $table->text('campanha')->nullable()->after('estoque');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('produtos') && Schema::hasColumn('produtos', 'campanha')) {
            Schema::table('produtos', function (Blueprint $table) {
                $table->dropColumn('campanha');
            });
        }

        if (Schema::hasTable('cotacao_itens') && Schema::hasColumn('cotacao_itens', 'campanha')) {
            Schema::table('cotacao_itens', function (Blueprint $table) {
                $table->dropColumn('campanha');
            });
        }
    }
};
