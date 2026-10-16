<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Zera a confirmação falsa do número (34) 99668-3987 (GLAUCIRLEY) gerada pelo acesso do admin.
     */
    public function up(): void
    {
        $phoneDigits = '996683987';

        // 1. Resetar contato em cliente_contatos
        if (Schema::hasTable('cliente_contatos')) {
            DB::table('cliente_contatos')
                ->where(function($q) use ($phoneDigits) {
                    $q->where('telefone', 'like', "%{$phoneDigits}%")
                      ->orWhere('nome_contato', 'like', '%GLAUCIRLEY%');
                })
                ->update([
                    'confirmado' => 0,
                    'qtd_aberturas' => 0,
                    'ultima_abertura_em' => null,
                    'updated_at' => now(),
                ]);
        }

        // 2. Resetar abertura em cotacao_envios
        if (Schema::hasTable('cotacao_envios')) {
            DB::table('cotacao_envios')
                ->where('telefone_destino', 'like', "%{$phoneDigits}%")
                ->orWhere('nome_contato_snapshot', 'like', '%GLAUCIRLEY%')
                ->update([
                    'aberto_em' => null,
                    'ip_abertura' => null,
                    'user_agent_abertura' => null,
                    'updated_at' => now(),
                ]);
        }

        // 3. Remover registros de histórico de abertura falsa
        if (Schema::hasTable('cotacao_historico')) {
            DB::table('cotacao_historico')
                ->where('evento', 'PROPOSTA_VISUALIZADA_CLIENTE')
                ->where(function($q) use ($phoneDigits) {
                    $q->where('condicao', 'like', "%{$phoneDigits}%")
                      ->orWhere('condicao', 'like', '%GLAUCIRLEY%');
                })
                ->delete();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Operação de limpeza irreversível
    }
};
