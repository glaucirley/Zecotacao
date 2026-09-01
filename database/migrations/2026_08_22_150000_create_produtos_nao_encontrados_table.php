<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('produtos_nao_encontrados', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_sankhya')->unique();
            $table->string('descricao');
            $table->integer('requisicoes')->default(1);
            $table->string('ultimo_solicitante')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('produtos_nao_encontrados');
    }
};
