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
        if (!Schema::hasColumn('parceiros', 'vendedor_6_codigo')) {
            Schema::table('parceiros', function (Blueprint $table) {
                $table->string('vendedor_6_codigo', 50)->nullable()->after('vendedor_5_codigo')->index();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('parceiros', 'vendedor_6_codigo')) {
            Schema::table('parceiros', function (Blueprint $table) {
                $table->dropColumn('vendedor_6_codigo');
            });
        }
    }
};
