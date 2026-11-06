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
        Schema::table('parceiros', function (Blueprint $table) {
            // Index nome_fantasia for high-performance client search
            if (Schema::hasColumn('parceiros', 'nome_fantasia')) {
                $table->index('nome_fantasia', 'parceiros_nome_fantasia_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parceiros', function (Blueprint $table) {
            if (Schema::hasColumn('parceiros', 'nome_fantasia')) {
                $table->dropIndex('parceiros_nome_fantasia_index');
            }
        });
    }
};
