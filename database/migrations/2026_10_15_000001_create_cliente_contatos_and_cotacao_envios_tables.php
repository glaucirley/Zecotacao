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
        // 1. Tabela cliente_contatos (histórico cumulativo de contatos com ranqueamento)
        Schema::create('cliente_contatos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parceiro_id');
            $table->string('codigo_sankhya_parc')->nullable();
            $table->string('nome_contato')->nullable();
            $table->string('cargo')->nullable();
            $table->string('telefone')->index();
            $table->enum('origem', ['REPRESENTANTE', 'SANKHYA'])->default('REPRESENTANTE');
            $table->boolean('confirmado')->default(false)->index();
            $table->integer('qtd_envios')->default(0);
            $table->integer('qtd_aberturas')->default(0);
            $table->timestamp('ultimo_envio_em')->nullable();
            $table->timestamp('ultima_abertura_em')->nullable();
            $table->enum('status', ['ATIVO', 'INATIVO'])->default('ATIVO')->index();
            $table->string('motivo_inativacao')->nullable();
            $table->unsignedBigInteger('criado_por')->nullable();
            $table->timestamps();

            $table->foreign('parceiro_id')->references('id')->on('parceiros')->onDelete('cascade');
            $table->foreign('criado_por')->references('id')->on('usuarios')->onDelete('set null');
            $table->index(['parceiro_id', 'status', 'confirmado']);
        });

        // 2. Tabela cotacao_envios (registro auditável de cada disparo e rastreio de abertura)
        Schema::create('cotacao_envios', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cotacao_id');
            $table->unsignedBigInteger('contato_id')->nullable();
            $table->string('token_acesso', 64)->unique();
            $table->enum('canal', ['WHATSAPP', 'WEB_SHARE'])->default('WHATSAPP');
            $table->string('telefone_destino')->nullable();
            $table->string('nome_contato_snapshot')->nullable();
            $table->text('mensagem_enviada')->nullable();
            $table->unsignedBigInteger('enviado_por');
            $table->timestamp('enviado_em')->nullable();
            $table->timestamp('aberto_em')->nullable()->index();
            $table->string('ip_abertura', 45)->nullable();
            $table->text('user_agent_abertura')->nullable();
            $table->timestamp('expiracao_em')->nullable();
            $table->timestamps();

            $table->foreign('cotacao_id')->references('id')->on('cotacoes')->onDelete('cascade');
            $table->foreign('contato_id')->references('id')->on('cliente_contatos')->onDelete('set null');
            $table->foreign('enviado_por')->references('id')->on('usuarios')->onDelete('cascade');
            $table->index(['cotacao_id', 'enviado_em']);
        });

        // 3. Cadastrar parâmetro padrão WA_MSG_PROPOSTA se não existir
        if (Schema::hasTable('parametros_sistema')) {
            $exists = DB::table('parametros_sistema')->where('chave', 'WA_MSG_PROPOSTA')->exists();
            if (!$exists) {
                DB::table('parametros_sistema')->insert([
                    'chave' => 'WA_MSG_PROPOSTA',
                    'valor' => "Olá {nome_contato}, aqui é {nome_representante} da Central Veterinária.\nSegue sua proposta {numero_cotacao}, no valor de R$ {total}, válida até {validade}:\n{link}",
                    'descricao' => 'Template padrão de mensagem do WhatsApp ao enviar proposta ao cliente',
                    'tipo' => 'texto',
                    'editavel_por' => 'administrador',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cotacao_envios');
        Schema::dropIfExists('cliente_contatos');

        if (Schema::hasTable('parametros_sistema')) {
            DB::table('parametros_sistema')->where('chave', 'WA_MSG_PROPOSTA')->delete();
        }
    }
};
