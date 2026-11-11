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
        if (!Schema::hasColumn('parceiros', 'carteira_livre')) {
            Schema::table('parceiros', function (Blueprint $table) {
                $table->boolean('carteira_livre')->default(false)->index()->after('codigo_tabela_preco');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('parceiros', 'carteira_livre')) {
            Schema::table('parceiros', function (Blueprint $table) {
                $table->dropColumn('carteira_livre');
            });
        }
    }
};
