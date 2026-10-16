<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Limpa os registros de envio fantasma e auditoria da COT-035 gerados durante a falha técnica.
     */
    public function up(): void
    {
        if (Schema::hasTable('cotacoes')) {
            $quote = DB::table('cotacoes')
                ->where('numero', 'like', '%000035%')
                ->orWhere('numero', 'like', '%COT-035%')
                ->first();

            if ($quote) {
                // 1. Remover envios fantasmas (não abertos) desta cotação
                if (Schema::hasTable('cotacao_envios')) {
                    DB::table('cotacao_envios')
                        ->where('cotacao_id', $quote->id)
                        ->whereNull('aberto_em')
                        ->delete();
                }

                // 2. Remover evento de envio fantasma do histórico de auditoria
                if (Schema::hasTable('cotacao_historico')) {
                    DB::table('cotacao_historico')
                        ->where('cotacao_id', $quote->id)
                        ->whereIn('evento', ['PROPOSTA_ENVIADA_WHATSAPP', 'PROPOSTA_COMPARTILHADA_PDF'])
                        ->delete();
                }

                // 3. Reverter status para APROVADA para permitir o teste completo do fluxo pelo usuário
                DB::table('cotacoes')
                    ->where('id', $quote->id)
                    ->update([
                        'status' => 'APROVADA',
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
        // Operação de limpeza irreversível de registros com erro
    }
};
