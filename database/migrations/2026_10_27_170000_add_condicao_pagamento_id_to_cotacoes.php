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
        if (Schema::hasTable('cotacoes') && !Schema::hasColumn('cotacoes', 'condicao_pagamento_id')) {
            Schema::table('cotacoes', function (Blueprint $table) {
                $table->unsignedBigInteger('condicao_pagamento_id')->nullable()->after('forma_pagamento');
                
                if (Schema::hasTable('condicoes_pagamento')) {
                    $table->foreign('condicao_pagamento_id')
                        ->references('id')
                        ->on('condicoes_pagamento')
                        ->nullOnDelete();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('cotacoes') && Schema::hasColumn('cotacoes', 'condicao_pagamento_id')) {
            Schema::table('cotacoes', function (Blueprint $table) {
                try {
                    $table->dropForeign(['condicao_pagamento_id']);
                } catch (\Throwable $e) {}
                $table->dropColumn('condicao_pagamento_id');
            });
        }
    }
};
