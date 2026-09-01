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
        // 1. Create equipes table first (without gestor_id foreign key constraint yet)
        Schema::create('equipes', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->unsignedBigInteger('gestor_id')->nullable();
            $table->timestamps();
        });

        // 2. Create usuarios table (with equipe_id foreign key constraint)
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->enum('papel', ['representante', 'gestor', 'diretor', 'faturamento']);
            $table->unsignedBigInteger('equipe_id')->nullable();
            $table->string('email')->unique();
            $table->string('senha_hash');
            $table->string('telefone')->nullable();
            $table->string('codigo_sankhya')->nullable()->index();
            $table->decimal('limite_desconto_percentual', 5, 2)->nullable();
            $table->boolean('ativo')->default(true);
            $table->rememberToken();
            $table->timestamps();

            $table->foreign('equipe_id')->references('id')->on('equipes')->onDelete('set null');
        });

        // 3. Add the gestor_id foreign key constraint to equipes table
        Schema::table('equipes', function (Blueprint $table) {
            $table->foreign('gestor_id')->references('id')->on('usuarios')->onDelete('set null');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('equipes', function (Blueprint $table) {
            $table->dropForeign(['gestor_id']);
        });
        Schema::dropIfExists('usuarios');
        Schema::dropIfExists('equipes');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
