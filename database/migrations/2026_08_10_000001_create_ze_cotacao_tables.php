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
        // 1. parceiros (clientes)
        Schema::create('parceiros', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_sankhya')->unique();
            $table->string('razao_social');
            $table->string('nome_fantasia')->nullable();
            $table->string('cnpj')->unique()->nullable();
            $table->string('telefone')->nullable();
            $table->string('email')->nullable();
            $table->string('endereco')->nullable();
            $table->string('cidade')->nullable();
            $table->string('uf', 2)->nullable();
            $table->string('cep')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        // 2. produtos
        Schema::create('produtos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_sankhya')->unique();
            $table->string('descricao');
            $table->string('unidade');
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        // 3. cotacoes
        Schema::create('cotacoes', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->unique();
            $table->unsignedBigInteger('parceiro_id');
            $table->unsignedBigInteger('representante_id');
            $table->string('status')->default('EM_CRIACAO');
            $table->string('origem')->default('whatsapp_ia');
            $table->timestamp('data_emissao')->nullable();
            $table->integer('validade_horas')->default(24);
            $table->timestamp('data_validade')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0.00);
            $table->decimal('desconto', 12, 2)->default(0.00);
            $table->decimal('total', 12, 2)->default(0.00);
            $table->string('forma_pagamento')->nullable();
            $table->string('prazo_entrega')->nullable();
            $table->enum('frete_tipo', ['CIF', 'FOB'])->default('CIF');
            $table->string('transportadora')->nullable();
            $table->text('observacao_cliente')->nullable();
            $table->text('observacao_interna')->nullable();
            $table->string('token_representante')->unique()->nullable();
            $table->timestamp('token_acesso_em')->nullable();
            $table->timestamps();

            $table->foreign('parceiro_id')->references('id')->on('parceiros')->onDelete('restrict');
            $table->foreign('representante_id')->references('id')->on('usuarios')->onDelete('restrict');
        });

        // 4. cotacao_itens
        Schema::create('cotacao_itens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cotacao_id');
            $table->unsignedBigInteger('produto_id');
            $table->integer('qtd');
            $table->decimal('preco_unit_sugerido', 12, 2);
            $table->decimal('preco_minimo', 12, 2);
            $table->decimal('preco_unit_proposto', 12, 2);
            $table->decimal('ajuste_percentual', 5, 2)->default(0.00);
            $table->decimal('subtotal', 12, 2);
            $table->enum('status_item', ['pendente', 'aprovado', 'recusado'])->default('pendente');
            $table->decimal('margem_calculada', 5, 2)->nullable();
            $table->decimal('custo', 12, 2)->nullable();
            $table->decimal('imposto', 5, 2)->nullable();
            $table->string('campanha_id')->nullable();
            $table->boolean('mostrar_selo_campanha')->default(false);
            $table->integer('ordem_exibicao')->default(0);
            $table->timestamps();

            $table->foreign('cotacao_id')->references('id')->on('cotacoes')->onDelete('cascade');
            $table->foreign('produto_id')->references('id')->on('produtos')->onDelete('restrict');
        });

        // 5. cotacao_vinculo_itens
        Schema::create('cotacao_vinculo_itens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cotacao_id');
            $table->unsignedBigInteger('cotacao_item_id');
            $table->string('grupo_vinculo');
            $table->timestamps();

            $table->foreign('cotacao_id')->references('id')->on('cotacoes')->onDelete('cascade');
            $table->foreign('cotacao_item_id')->references('id')->on('cotacao_itens')->onDelete('cascade');
        });

        // 6. cotacao_justificativas
        Schema::create('cotacao_justificativas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cotacao_id')->nullable();
            $table->unsignedBigInteger('cotacao_item_id')->nullable();
            $table->text('texto')->nullable();
            $table->string('audio_url')->nullable();
            $table->unsignedBigInteger('criado_por');
            $table->timestamps();

            $table->foreign('cotacao_id')->references('id')->on('cotacoes')->onDelete('cascade');
            $table->foreign('cotacao_item_id')->references('id')->on('cotacao_itens')->onDelete('cascade');
            $table->foreign('criado_por')->references('id')->on('usuarios')->onDelete('restrict');
        });

        // 7. cotacao_anexos
        Schema::create('cotacao_anexos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cotacao_id')->nullable();
            $table->unsignedBigInteger('justificativa_id')->nullable();
            $table->string('tipo');
            $table->string('arquivo_url');
            $table->timestamps();

            $table->foreign('cotacao_id')->references('id')->on('cotacoes')->onDelete('cascade');
            $table->foreign('justificativa_id')->references('id')->on('cotacao_justificativas')->onDelete('cascade');
        });

        // 8. cotacao_historico
        Schema::create('cotacao_historico', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cotacao_id');
            $table->unsignedBigInteger('cotacao_item_id')->nullable();
            $table->string('evento');
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->string('papel')->nullable();
            $table->text('condicao')->nullable();
            $table->timestamps();

            $table->foreign('cotacao_id')->references('id')->on('cotacoes')->onDelete('cascade');
            $table->foreign('cotacao_item_id')->references('id')->on('cotacao_itens')->onDelete('cascade');
            $table->foreign('usuario_id')->references('id')->on('usuarios')->onDelete('set null');
        });

        // 9. pedidos_externos
        Schema::create('pedidos_externos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cotacao_id');
            $table->string('numero_pedido_externo');
            $table->decimal('valor_pedido', 12, 2);
            $table->timestamp('recebido_em')->useCurrent();
            $table->string('status_conferencia')->default('pendente');
            $table->timestamps();

            $table->foreign('cotacao_id')->references('id')->on('cotacoes')->onDelete('cascade');
        });

        // 10. parametros_sistema
        Schema::create('parametros_sistema', function (Blueprint $table) {
            $table->id();
            $table->string('chave')->unique();
            $table->text('valor')->nullable();
            $table->text('descricao')->nullable();
            $table->string('tipo')->default('texto');
            $table->string('editavel_por')->default('diretor');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parametros_sistema');
        Schema::dropIfExists('pedidos_externos');
        Schema::dropIfExists('cotacao_historico');
        Schema::dropIfExists('cotacao_anexos');
        Schema::dropIfExists('cotacao_justificativas');
        Schema::dropIfExists('cotacao_vinculo_itens');
        Schema::dropIfExists('cotacao_itens');
        Schema::dropIfExists('cotacoes');
        Schema::dropIfExists('produtos');
        Schema::dropIfExists('parceiros');
    }
};
