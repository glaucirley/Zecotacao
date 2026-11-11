<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            DB::statement('ALTER TABLE cotacao_itens MODIFY COLUMN produto_id BIGINT UNSIGNED NULL');
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Migração force_produto_id_nullable: " . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            DB::statement('ALTER TABLE cotacao_itens MODIFY COLUMN produto_id BIGINT UNSIGNED NOT NULL');
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        } catch (\Throwable $e) {}
    }
};
