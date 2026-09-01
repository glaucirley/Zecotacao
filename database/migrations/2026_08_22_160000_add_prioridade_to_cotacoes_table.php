<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('cotacoes', function (Blueprint $table) {
            $table->boolean('prioridade')->default(false)->after('status');
        });
    }

    public function down()
    {
        Schema::table('cotacoes', function (Blueprint $table) {
            $table->dropColumn('prioridade');
        });
    }
};
