<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Alter enum options for papel
        DB::statement("ALTER TABLE usuarios MODIFY COLUMN papel ENUM('representante', 'gestor', 'diretor', 'faturamento', 'administrador') NOT NULL");

        // Seed a default administrator user
        User::create([
            'nome' => 'Administrador Geral',
            'papel' => 'administrador',
            'email' => 'admin@zecotacao.com.br',
            'senha_hash' => Hash::make('admin123'),
            'ativo' => true
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Delete seeded admin
        User::where('email', 'admin@zecotacao.com.br')->delete();

        // Revert enum options
        DB::statement("ALTER TABLE usuarios MODIFY COLUMN papel ENUM('representante', 'gestor', 'diretor', 'faturamento') NOT NULL");
    }
};
