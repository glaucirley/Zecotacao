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
        Schema::table('parceiros', function (Blueprint $table) {
            $table->string('inscricao_estadual', 50)->nullable()->after('cnpj');
            $table->string('tipo_pessoa', 10)->nullable()->after('inscricao_estadual');
            $table->string('codigo_regiao', 50)->nullable()->after('tipo_pessoa');

            // Multi-representantes / Vendedores Sankhya
            $table->string('vendedor_1_codigo', 50)->nullable()->after('codigo_regiao')->index();
            $table->string('vendedor_2_codigo', 50)->nullable()->after('vendedor_1_codigo')->index();
            $table->string('vendedor_3_codigo', 50)->nullable()->after('vendedor_2_codigo')->index();
            $table->string('vendedor_4_codigo', 50)->nullable()->after('vendedor_3_codigo')->index();
            $table->string('vendedor_5_codigo', 50)->nullable()->after('vendedor_4_codigo')->index();

            // Endereço detalhado
            $table->string('logradouro', 255)->nullable()->after('endereco');
            $table->string('numero', 50)->nullable()->after('logradouro');
            $table->string('bairro', 100)->nullable()->after('numero');
            $table->text('observacoes')->nullable()->after('ativo');
            $table->json('metadados_sankhya')->nullable()->after('observacoes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parceiros', function (Blueprint $table) {
            $table->dropColumn([
                'inscricao_estadual',
                'tipo_pessoa',
                'codigo_regiao',
                'vendedor_1_codigo',
                'vendedor_2_codigo',
                'vendedor_3_codigo',
                'vendedor_4_codigo',
                'vendedor_5_codigo',
                'logradouro',
                'numero',
                'bairro',
                'observacoes',
                'metadados_sankhya'
            ]);
        });
    }
};
