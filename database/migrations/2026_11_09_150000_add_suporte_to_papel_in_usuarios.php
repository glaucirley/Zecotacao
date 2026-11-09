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
        DB::statement("ALTER TABLE usuarios MODIFY COLUMN papel ENUM('representante', 'gestor', 'diretor', 'faturamento', 'administrador', 'suporte') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE usuarios MODIFY COLUMN papel ENUM('representante', 'gestor', 'diretor', 'faturamento', 'administrador') NOT NULL");
    }
};
