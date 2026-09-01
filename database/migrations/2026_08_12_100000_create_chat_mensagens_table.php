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
        Schema::create('chat_mensagens', function (Blueprint $table) {
            $table->id();
            $table->string('telefone_cliente')->index();
            $table->string('nome_cliente')->nullable();
            $table->enum('direcao', ['received', 'sent']);
            $table->text('mensagem');
            $table->string('tipo')->default('texto');
            $table->unsignedBigInteger('cotacao_id')->nullable();
            $table->timestamps();

            $table->foreign('cotacao_id')->references('id')->on('cotacoes')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_mensagens');
    }
};
