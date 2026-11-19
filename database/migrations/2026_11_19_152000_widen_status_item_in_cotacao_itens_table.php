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
        if (Schema::hasTable('cotacao_itens')) {
            try {
                // Modifica a coluna para VARCHAR(30) para evitar qualquer erro de truncamento futuro com ENUMs no MySQL
                DB::statement("ALTER TABLE `cotacao_itens` MODIFY COLUMN `status_item` VARCHAR(30) NOT NULL DEFAULT 'pendente'");
            } catch (\Throwable $e) {
                try {
                    Schema::table('cotacao_itens', function (Blueprint $table) {
                        $table->string('status_item', 30)->default('pendente')->change();
                    });
                } catch (\Throwable $th) {}
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('cotacao_itens')) {
            try {
                DB::statement("ALTER TABLE `cotacao_itens` MODIFY COLUMN `status_item` ENUM('pendente', 'aprovado', 'recusado') NOT NULL DEFAULT 'pendente'");
            } catch (\Throwable $e) {}
        }
    }
};
