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
        if (Schema::hasTable('cotacao_itens')) {
            Schema::table('cotacao_itens', function (Blueprint $table) {
                // Expand percent columns from decimal(5, 2) [-999.99 to 999.99] to decimal(8, 2) [-999999.99 to 999999.99]
                // This prevents out of range exceptions on heavy discount / extreme negative margin simulations.
                $table->decimal('margem_calculada', 8, 2)->nullable()->change();
                $table->decimal('ajuste_percentual', 8, 2)->default(0.00)->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('cotacao_itens')) {
            Schema::table('cotacao_itens', function (Blueprint $table) {
                $table->decimal('margem_calculada', 5, 2)->nullable()->change();
                $table->decimal('ajuste_percentual', 5, 2)->default(0.00)->change();
            });
        }
    }
};
