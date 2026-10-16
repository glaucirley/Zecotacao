<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Cotacao;
use App\Models\Parceiro;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Enriquecer registros existentes em chat_mensagens associando cotacao_id e nome_cliente
     */
    public function up(): void
    {
        if (!Schema::hasTable('chat_mensagens')) {
            return;
        }

        $telefones = DB::table('chat_mensagens')
            ->select('telefone_cliente')
            ->distinct()
            ->pluck('telefone_cliente');

        foreach ($telefones as $telefone) {
            $digits = preg_replace('/\D/', '', (string)$telefone);
            if (empty($digits)) continue;

            $cleanPhone = (str_starts_with($digits, '55') && strlen($digits) >= 12) ? substr($digits, 2) : $digits;
            $last8 = strlen($digits) >= 8 ? substr($digits, -8) : $digits;
            $last9 = strlen($digits) >= 9 ? substr($digits, -9) : null;

            // Coleta mensagens e dados existentes
            $messages = DB::table('chat_mensagens')
                ->where('telefone_cliente', $telefone)
                ->get();

            $existingCotacaoId = $messages->firstWhere(fn($m) => !empty($m->cotacao_id))?->cotacao_id;
            $existingNome = $messages->firstWhere(fn($m) => !empty($m->nome_cliente) && !in_array($m->nome_cliente, ['Cliente', 'Cliente WhatsApp']))?->nome_cliente;

            $resolvedQuote = null;
            $resolvedName = $existingNome;

            // 0. Se já possui cotacao_id
            if ($existingCotacaoId) {
                $resolvedQuote = Cotacao::with('parceiro')->find($existingCotacaoId);
                if ($resolvedQuote && empty($resolvedName)) {
                    $resolvedName = $resolvedQuote->parceiro?->nome_fantasia ?: $resolvedQuote->parceiro?->razao_social;
                }
            }

            // 1. Tentar localizar via número da cotação mencionado em alguma mensagem
            if (!$resolvedQuote) {
                $allText = $messages->pluck('mensagem')->filter()->implode(' ');
                if (preg_match('/(COT[A-Za-z0-9\-_]+)/i', $allText, $mQuote)) {
                    $resolvedQuote = Cotacao::with('parceiro')
                        ->where('numero', 'like', "%{$mQuote[1]}%")
                        ->first();
                    if ($resolvedQuote && empty($resolvedName)) {
                        $resolvedName = $resolvedQuote->parceiro?->nome_fantasia ?: $resolvedQuote->parceiro?->razao_social;
                    }
                }
            }

            // 2. Tentar localizar via cotacao_envios
            if (Schema::hasTable('cotacao_envios')) {
                $envio = \App\Models\CotacaoEnvio::with('cotacao.parceiro', 'contato.parceiro')
                    ->where(function($q) use ($digits, $cleanPhone, $last8, $last9) {
                        $q->where('telefone_destino', $digits)
                          ->orWhere('telefone_destino', $cleanPhone);
                        if ($last9) $q->orWhere('telefone_destino', 'like', "%{$last9}%");
                        if ($last8) {
                            $q->orWhere('telefone_destino', 'like', "%{$last8}%")
                              ->orWhereRaw("REGEXP_REPLACE(telefone_destino, '[^0-9]', '') LIKE ?", ["%{$last8}%"]);
                        }
                    })->orderByDesc('id')->first();

                if ($envio) {
                    if (!$resolvedQuote && $envio->cotacao) {
                        $resolvedQuote = $envio->cotacao;
                    }
                    if (empty($resolvedName)) {
                        $resolvedName = $envio->nome_contato_snapshot 
                            ?: ($envio->contato?->nome_contato ?: ($envio->cotacao?->parceiro?->nome_fantasia ?: $envio->cotacao?->parceiro?->razao_social));
                    }
                }
            }

            // 3. Tentar localizar via cliente_contatos
            if (Schema::hasTable('cliente_contatos')) {
                $contato = \App\Models\ClienteContato::with('parceiro')
                    ->where(function($q) use ($digits, $cleanPhone, $last8, $last9) {
                        $q->where('telefone', $digits)
                          ->orWhere('telefone', $cleanPhone);
                        if ($last9) $q->orWhere('telefone', 'like', "%{$last9}%");
                        if ($last8) {
                            $q->orWhere('telefone', 'like', "%{$last8}%")
                              ->orWhereRaw("REGEXP_REPLACE(telefone, '[^0-9]', '') LIKE ?", ["%{$last8}%"]);
                        }
                    })->first();

                if ($contato) {
                    if (empty($resolvedName)) {
                        $resolvedName = $contato->nome_contato ?: ($contato->parceiro?->nome_fantasia ?: $contato->parceiro?->razao_social);
                    }
                    if (!$resolvedQuote && $contato->parceiro_id) {
                        $resolvedQuote = Cotacao::with('parceiro')->where('parceiro_id', $contato->parceiro_id)->orderByDesc('id')->first();
                    }
                }
            }

            // 4. Tentar localizar via telefone do parceiro
            if ($last8) {
                $parceiro = Parceiro::where(function($q) use ($cleanPhone, $last8, $last9) {
                    if ($cleanPhone) $q->where('telefone', 'like', "%{$cleanPhone}%");
                    if ($last9) $q->orWhere('telefone', 'like', "%{$last9}%");
                    $q->orWhere('telefone', 'like', "%{$last8}%")
                      ->orWhereRaw("REGEXP_REPLACE(telefone, '[^0-9]', '') LIKE ?", ["%{$last8}%"]);
                })->first();

                if ($parceiro) {
                    if (empty($resolvedName)) {
                        $resolvedName = $parceiro->nome_fantasia ?: $parceiro->razao_social;
                    }
                    if (!$resolvedQuote) {
                        $resolvedQuote = Cotacao::with('parceiro')->where('parceiro_id', $parceiro->id)->orderByDesc('id')->first();
                    }
                }
            }

            // 5. Tentar localizar via observações da cotação
            if (!$resolvedQuote && $last8) {
                $cotacaoObs = Cotacao::with('parceiro')
                    ->where(function($q) use ($last8) {
                        $q->where('observacao_interna', 'like', "%{$last8}%")
                          ->orWhere('observacao_cliente', 'like', "%{$last8}%");
                    })->orderByDesc('id')->first();

                if ($cotacaoObs) {
                    $resolvedQuote = $cotacaoObs;
                    if (empty($resolvedName)) {
                        $resolvedName = $cotacaoObs->parceiro?->nome_fantasia ?: $cotacaoObs->parceiro?->razao_social;
                    }
                }
            }

            // 6. Se achou nome mas não cotação, buscar cotação desse cliente
            if (!$resolvedQuote && !empty($resolvedName)) {
                $resolvedQuote = Cotacao::with('parceiro')
                    ->whereHas('parceiro', function($pq) use ($resolvedName) {
                        $pq->where('nome_fantasia', 'like', "%{$resolvedName}%")
                           ->orWhere('razao_social', 'like', "%{$resolvedName}%");
                    })->orderByDesc('id')->first();
            }

            // Fallback se achou cotação
            if ($resolvedQuote) {
                if (!$resolvedQuote->relationLoaded('parceiro')) {
                    $resolvedQuote->load('parceiro');
                }
                if (empty($resolvedName)) {
                    $resolvedName = $resolvedQuote->parceiro?->nome_fantasia ?: $resolvedQuote->parceiro?->razao_social;
                }
            }

            $updatePayload = [];
            if ($resolvedQuote) {
                $updatePayload['cotacao_id'] = $resolvedQuote->id;
            }
            if (!empty($resolvedName) && !in_array($resolvedName, ['Cliente', 'Cliente WhatsApp'])) {
                $updatePayload['nome_cliente'] = $resolvedName;
            }

            if (!empty($updatePayload)) {
                DB::table('chat_mensagens')
                    ->where('telefone_cliente', $telefone)
                    ->update($updatePayload);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Enriquecimento de dados, irreversível
    }
};
