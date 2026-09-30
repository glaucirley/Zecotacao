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
        Schema::create('condicoes_pagamento', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_sankhya', 50)->unique();
            $table->string('descricao', 255);
            $table->decimal('venda_minima', 12, 4)->default(0);
            $table->decimal('venda_maxima', 12, 4)->default(0);
            $table->boolean('ativa')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('condicoes_pagamento');
    }
};
