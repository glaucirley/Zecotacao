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
        self::runCleanup();
    }

    /**
     * Execute idempotent cleanup of test openings (COT-033 to COT-036)
     * and fix historical audit trail roles.
     */
    public static function runCleanup(): void
    {
        if (Schema::hasTable('cotacoes') && Schema::hasTable('cotacao_envios')) {
            $quotes = DB::table('cotacoes')
                ->where(function ($q) {
                    $q->where('numero', 'like', '%000033')
                      ->orWhere('numero', 'like', '%000034')
                      ->orWhere('numero', 'like', '%000035')
                      ->orWhere('numero', 'like', '%000036')
                      ->orWhere('numero', 'like', '%-033')
                      ->orWhere('numero', 'like', '%-034')
                      ->orWhere('numero', 'like', '%-035')
                      ->orWhere('numero', 'like', '%-036');
                })
                ->get(['id', 'parceiro_id']);

            if ($quotes->isNotEmpty()) {
                $quoteIds = $quotes->pluck('id')->all();
                $parceiroIds = $quotes->pluck('parceiro_id')->filter()->unique()->all();

                $envioContatoIds = DB::table('cotacao_envios')
                    ->whereIn('cotacao_id', $quoteIds)
                    ->whereNotNull('contato_id')
                    ->pluck('contato_id')
                    ->all();

                DB::table('cotacao_envios')
                    ->whereIn('cotacao_id', $quoteIds)
                    ->update([
                        'aberto_em'            => null,
                        'ip_abertura'          => null,
                        'user_agent_abertura'  => null,
                    ]);

                if (Schema::hasTable('cotacao_historico')) {
                    DB::table('cotacao_historico')
                        ->whereIn('cotacao_id', $quoteIds)
                        ->where('evento', 'PROPOSTA_VISUALIZADA_CLIENTE')
                        ->delete();
                }

                if (Schema::hasTable('cliente_contatos')) {
                    $contactQuery = DB::table('cliente_contatos');
                    if (!empty($envioContatoIds) && !empty($parceiroIds)) {
                        $contactQuery->whereIn('id', $envioContatoIds)
                                     ->orWhereIn('parceiro_id', $parceiroIds);
                    } elseif (!empty($envioContatoIds)) {
                        $contactQuery->whereIn('id', $envioContatoIds);
                    } elseif (!empty($parceiroIds)) {
                        $contactQuery->whereIn('parceiro_id', $parceiroIds);
                    } else {
                        $contactQuery = null;
                    }

                    if ($contactQuery) {
                        $contacts = $contactQuery->pluck('id')->all();
                        foreach ($contacts as $contactId) {
                            $stats = DB::table('cotacao_envios')
                                ->where('contato_id', $contactId)
                                ->whereNotNull('aberto_em')
                                ->selectRaw('COUNT(*) as total_aberturas, MAX(aberto_em) as ultima_abertura')
                                ->first();

                            $totalAberturas = (int) ($stats->total_aberturas ?? 0);
                            $ultimaAbertura = $stats->ultima_abertura ?? null;

                            DB::table('cliente_contatos')
                                ->where('id', $contactId)
                                ->update([
                                    'qtd_aberturas'      => $totalAberturas,
                                    'ultima_abertura_em' => $ultimaAbertura,
                                    'confirmado'         => $totalAberturas > 0,
                                ]);
                        }
                    }
                }
            }
        }

        if (Schema::hasTable('cotacao_historico') && Schema::hasTable('usuarios')) {
            // Ensure system events don't carry a user ID that overrides 'sistema'
            DB::table('cotacao_historico')
                ->where('papel', 'sistema')
                ->whereNotNull('usuario_id')
                ->update(['usuario_id' => null]);

            // Fix audit records where a logged-in user (e.g. administrador, gestor, diretor) was recorded as 'representante'
            $users = DB::table('usuarios')->get(['id', 'nome', 'papel']);
            foreach ($users as $user) {
                if (!$user->papel) {
                    continue;
                }

                $rows = DB::table('cotacao_historico')
                    ->where('usuario_id', $user->id)
                    ->whereNotIn('papel', ['sistema', 'cliente'])
                    ->where('papel', '!=', $user->papel)
                    ->get(['id', 'condicao']);

                foreach ($rows as $row) {
                    $newCondicao = $row->condicao;
                    if (is_string($newCondicao) && str_contains($newCondicao, 'pelo representante comercial') && $user->papel !== 'representante') {
                        $newCondicao = str_replace('pelo representante comercial', 'por ' . $user->nome, $newCondicao);
                    }

                    DB::table('cotacao_historico')
                        ->where('id', $row->id)
                        ->update([
                            'papel'    => $user->papel,
                            'condicao' => $newCondicao,
                        ]);
                }
            }
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
