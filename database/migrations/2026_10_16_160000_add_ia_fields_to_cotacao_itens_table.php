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
        Schema::table('cotacao_itens', function (Blueprint $table) {
            if (!Schema::hasColumn('cotacao_itens', 'nao_encontrado')) {
                $table->boolean('nao_encontrado')->default(false)->after('status_item');
            }
            if (!Schema::hasColumn('cotacao_itens', 'descricao_original')) {
                $table->string('descricao_original', 500)->nullable()->after('nao_encontrado');
            }
            if (!Schema::hasColumn('cotacao_itens', 'observacao_ia')) {
                $table->text('observacao_ia')->nullable()->after('descricao_original');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cotacao_itens', function (Blueprint $table) {
            $cols = [];
            if (Schema::hasColumn('cotacao_itens', 'nao_encontrado')) {
                $cols[] = 'nao_encontrado';
            }
            if (Schema::hasColumn('cotacao_itens', 'descricao_original')) {
                $cols[] = 'descricao_original';
            }
            if (Schema::hasColumn('cotacao_itens', 'observacao_ia')) {
                $cols[] = 'observacao_ia';
            }
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
