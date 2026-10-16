<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Zerar confirmações indevidas causadas por robôs/scrapers do WhatsApp
        if (Schema::hasTable('cotacao_envios')) {
            DB::table('cotacao_envios')
                ->where(function ($q) {
                    $q->where('user_agent_abertura', 'like', '%WhatsApp%')
                      ->orWhere('user_agent_abertura', 'like', '%facebookexternalhit%')
                      ->orWhere('user_agent_abertura', 'like', '%bot%')
                      ->orWhere('user_agent_abertura', 'like', '%crawl%');
                })
                ->update([
                    'aberto_em' => null,
                    'ip_abertura' => null,
                    'user_agent_abertura' => null,
                ]);
        }

        // 2. Zerar confirmação do contato da CENTRAL RURAL (ou qualquer contato cuja única abertura foi falsa)
        if (Schema::hasTable('cliente_contatos')) {
            $centralRuralParcIds = DB::table('parceiros')
                ->where('razao_social', 'like', '%CENTRAL%RURAL%')
                ->orWhere('nome_fantasia', 'like', '%CENTRAL%RURAL%')
                ->orWhere('codigo_sankhya', '108390')
                ->pluck('id');

            if ($centralRuralParcIds->isNotEmpty()) {
                DB::table('cliente_contatos')
                    ->whereIn('parceiro_id', $centralRuralParcIds)
                    ->update([
                        'confirmado' => false,
                        'qtd_aberturas' => 0,
                        'ultima_abertura_em' => null,
                    ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Operação de limpeza irreversível de aberturas de robôs
    }
};
