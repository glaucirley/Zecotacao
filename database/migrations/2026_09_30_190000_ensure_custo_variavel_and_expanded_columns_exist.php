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
        if (Schema::hasTable('produtos')) {
            Schema::table('produtos', function (Blueprint $table) {
                if (!Schema::hasColumn('produtos', 'marca')) {
                    $table->string('marca', 100)->nullable();
                }
                if (!Schema::hasColumn('produtos', 'base')) {
                    $table->string('base', 100)->nullable();
                }
                if (!Schema::hasColumn('produtos', 'base_loja_virtual')) {
                    $table->string('base_loja_virtual', 100)->nullable();
                }
                if (!Schema::hasColumn('produtos', 'descricao_longa')) {
                    $table->text('descricao_longa')->nullable();
                }
                if (!Schema::hasColumn('produtos', 'descricao_tecnica')) {
                    $table->text('descricao_tecnica')->nullable();
                }
                if (!Schema::hasColumn('produtos', 'descricao_interna')) {
                    $table->text('descricao_interna')->nullable();
                }
                if (!Schema::hasColumn('produtos', 'nome_loja_virtual')) {
                    $table->string('nome_loja_virtual', 255)->nullable();
                }
                if (!Schema::hasColumn('produtos', 'indicacao')) {
                    $table->string('indicacao', 255)->nullable();
                }
                if (!Schema::hasColumn('produtos', 'principio_ativo')) {
                    $table->string('principio_ativo', 255)->nullable();
                }
                if (!Schema::hasColumn('produtos', 'aplicacao')) {
                    $table->text('aplicacao')->nullable();
                }
                if (!Schema::hasColumn('produtos', 'peso_bruto')) {
                    $table->decimal('peso_bruto', 12, 4)->nullable();
                }
                if (!Schema::hasColumn('produtos', 'peso_liquido')) {
                    $table->decimal('peso_liquido', 12, 4)->nullable();
                }
                if (!Schema::hasColumn('produtos', 'margem_lucro')) {
                    $table->decimal('margem_lucro', 8, 2)->nullable();
                }
                if (!Schema::hasColumn('produtos', 'custo_variavel')) {
                    $table->decimal('custo_variavel', 12, 4)->nullable();
                }
                if (!Schema::hasColumn('produtos', 'ncm')) {
                    $table->string('ncm', 20)->nullable();
                }
                if (!Schema::hasColumn('produtos', 'metadados_sankhya')) {
                    $table->json('metadados_sankhya')->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
