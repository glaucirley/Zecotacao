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
        Schema::table('parceiros', function (Blueprint $table) {
            // Drop unique constraint on cnpj to allow multiple Sankhya partner records with shared CNPJ/CPF
            try {
                $table->dropUnique('parceiros_cnpj_unique');
            } catch (\Exception $e) {
                // Index might already be dropped or named differently
            }
        });

        Schema::table('parceiros', function (Blueprint $table) {
            // Add standard index on cnpj for fast lookup
            try {
                $table->index('cnpj', 'parceiros_cnpj_index');
            } catch (\Exception $e) {
                // Index might already exist
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parceiros', function (Blueprint $table) {
            try {
                $table->dropIndex('parceiros_cnpj_index');
                $table->unique('cnpj', 'parceiros_cnpj_unique');
            } catch (\Exception $e) {
                // Ignore if reversing fails due to duplicate CNPJs in DB
            }
        });
    }
};
