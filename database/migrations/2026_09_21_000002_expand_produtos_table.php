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
        Schema::table('produtos', function (Blueprint $table) {
            $table->string('marca', 100)->nullable()->after('descricao');
            $table->string('base', 100)->nullable()->after('marca');
            $table->string('base_loja_virtual', 100)->nullable()->after('base');
            $table->text('descricao_longa')->nullable()->after('base_loja_virtual');
            $table->text('descricao_tecnica')->nullable()->after('descricao_longa');
            $table->text('descricao_interna')->nullable()->after('descricao_tecnica');
            $table->string('nome_loja_virtual', 255)->nullable()->after('descricao_interna');
            $table->string('indicacao', 255)->nullable()->after('nome_loja_virtual');
            $table->string('principio_ativo', 255)->nullable()->after('indicacao');
            $table->text('aplicacao')->nullable()->after('principio_ativo');
            $table->decimal('peso_bruto', 12, 4)->nullable()->after('aplicacao');
            $table->decimal('peso_liquido', 12, 4)->nullable()->after('peso_bruto');
            $table->decimal('margem_lucro', 8, 2)->nullable()->after('peso_liquido');
            $table->decimal('custo_variavel', 12, 4)->nullable()->after('margem_lucro');
            $table->string('ncm', 20)->nullable()->after('custo_variavel');
            $table->json('metadados_sankhya')->nullable()->after('ativo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('produtos', function (Blueprint $table) {
            $table->dropColumn([
                'marca',
                'base',
                'base_loja_virtual',
                'descricao_longa',
                'descricao_tecnica',
                'descricao_interna',
                'nome_loja_virtual',
                'indicacao',
                'principio_ativo',
                'aplicacao',
                'peso_bruto',
                'peso_liquido',
                'margem_lucro',
                'custo_variavel',
                'ncm',
                'metadados_sankhya'
            ]);
        });
    }
};
