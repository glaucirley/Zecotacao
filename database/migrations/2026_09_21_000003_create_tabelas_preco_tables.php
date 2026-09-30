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
        // 1. Cabeçalho das Tabelas de Preço
        Schema::create('tabelas_preco', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_sankhya', 50)->unique();
            $table->string('nome_tabela', 255);
            $table->boolean('ativa')->default(true);
            $table->timestamps();
        });

        // 2. Itens e Preços das Tabelas (TGFEXC / VGF_PRECOAPP)
        Schema::create('tabela_preco_itens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tabela_preco_id');
            $table->string('codigo_sankhya_tabela', 50)->index();
            $table->unsignedBigInteger('produto_id')->nullable();
            $table->string('codigo_sankhya_produto', 50)->index();

            $table->decimal('preco_venda', 12, 4)->default(0);
            $table->decimal('preco_padrao', 12, 4)->default(0);
            $table->decimal('preco_minimo', 12, 4)->default(0);
            $table->decimal('margem_lucro', 8, 2)->default(0);
            $table->decimal('margem_minima', 8, 2)->default(0);
            $table->decimal('custo_variavel', 12, 4)->default(0);

            $table->timestamps();

            $table->foreign('tabela_preco_id')
                ->references('id')
                ->on('tabelas_preco')
                ->onDelete('cascade');

            $table->foreign('produto_id')
                ->references('id')
                ->on('produtos')
                ->onDelete('cascade');

            $table->unique(['tabela_preco_id', 'codigo_sankhya_produto'], 'uk_tabela_produto');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tabela_preco_itens');
        Schema::dropIfExists('tabelas_preco');
    }
};
