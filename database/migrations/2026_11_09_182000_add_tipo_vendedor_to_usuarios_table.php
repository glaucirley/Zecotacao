<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('usuarios', 'tipo_vendedor')) {
            Schema::table('usuarios', function (Blueprint $table) {
                $table->enum('tipo_vendedor', ['interno', 'externo'])->default('externo')->after('papel');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('usuarios', 'tipo_vendedor')) {
            Schema::table('usuarios', function (Blueprint $table) {
                $table->dropColumn('tipo_vendedor');
            });
        }
    }
};
