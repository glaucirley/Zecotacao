<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatMensagem;
use App\Models\Cotacao;
use App\Models\Parceiro;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class ChatLogController extends Controller
{
    /**
     * Store a new chat message log (Webhook endpoint for n8n).
     * Protected by ApiKeyMiddleware.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'telefone_cliente' => 'required|string',
            'nome_cliente' => 'nullable|string',
            'direcao' => 'required|in:received,sent',
            'mensagem' => 'required|string',
            'tipo' => 'nullable|string',
            'cotacao_id' => 'nullable|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Validation error',
                'messages' => $validator->errors()
            ], 422);
        }

        // Clean phone number (leave digits only)
        $phoneDigits = preg_replace('/\D/', '', $request->input('telefone_cliente'));

        // Resolve cotacao_id and customer name intelligently
        $resolution = $this->resolveQuoteAndNameForChat(
            $phoneDigits,
            $request->input('mensagem'),
            $request->input('cotacao_id'),
            $request->input('nome_cliente')
        );

        $cotacaoId = $resolution['cotacao_id'];
        $nomeCliente = (!empty($resolution['nome_cliente']) && $resolution['nome_cliente'] !== 'Cliente WhatsApp') 
            ? $resolution['nome_cliente'] 
            : ($request->input('nome_cliente') ?: null);

        try {
            $log = ChatMensagem::create([
                'telefone_cliente' => $phoneDigits,
                'nome_cliente' => $nomeCliente,
                'direcao' => $request->input('direcao'),
                'mensagem' => $request->input('mensagem'),
                'tipo' => $request->input('tipo', 'texto'),
                'cotacao_id' => $cotacaoId
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Mensagem de chat registrada com sucesso.',
                'data' => $log
            ], 201);

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Falha ao registrar logs de chat: " . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'error' => 'Erro interno do servidor',
                'code' => 'CHAT_LOG_ERROR',
                'message' => config('app.debug') ? ('Falha ao registrar logs de chat: ' . $e->getMessage()) : 'Ocorreu um erro interno ao processar os registros de conversa.'
            ], 500);
        }
    }

    /**
     * List unique client contacts who have chat messages.
     * Protected by auth and checks user's chat permission.
     */
    public function listContacts(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasChatAccess()) {
            return response()->json(['error' => 'Forbidden.'], 403);
        }

        try {
            // We select the latest message for each telefone_cliente.
            $subQuery = ChatMensagem::select('telefone_cliente', DB::raw('MAX(created_at) as last_msg_time'))
                ->groupBy('telefone_cliente');

        $query = ChatMensagem::joinSub($subQuery, 'latest', function ($join) {
                $join->on('chat_mensagens.telefone_cliente', '=', 'latest.telefone_cliente')
                     ->on('chat_mensagens.created_at', '=', 'latest.last_msg_time');
            })
            ->select('chat_mensagens.*')
            ->orderBy('chat_mensagens.created_at', 'desc');

        if ($user->isRepresentante()) {
            // Representative: only show contacts they own or who have quotes from them
            $query->where(function($q) use ($user) {
                $q->whereHas('cotacao', function($cq) use ($user) {
                    $cq->where('representante_id', $user->id);
                })->orWhereExists(function($eq) use ($user) {
                    $eq->select(DB::raw(1))
                       ->from('cotacoes')
                       ->join('parceiros', 'cotacoes.parceiro_id', '=', 'parceiros.id')
                       ->whereRaw("REGEXP_REPLACE(parceiros.telefone, '[^0-9]', '') LIKE CONCAT('%', RIGHT(chat_mensagens.telefone_cliente, 8))")
                       ->where('cotacoes.representante_id', $user->id);
                });
            });
        } elseif ($user->isGestor()) {
            // Gestor: only show contacts belonging to their team members
            $teamIds = $user->equipesGerenciadas->pluck('id');
            $query->where(function($q) use ($teamIds) {
                $q->whereHas('cotacao.representante', function($uq) use ($teamIds) {
                    $uq->whereIn('equipe_id', $teamIds);
                });
            });
        }

        $contacts = $query->with(['cotacao' => function($q) {
            $q->select('id', 'numero', 'parceiro_id');
        }, 'cotacao.parceiro'])->get();

        foreach ($contacts as $c) {
            $resolution = $this->resolveQuoteAndNameForChat(
                $c->telefone_cliente,
                $c->mensagem,
                $c->cotacao_id,
                $c->nome_cliente
            );

            if ($resolution['cotacao']) {
                $c->cotacao_id = $resolution['cotacao']->id;
                $c->setRelation('cotacao', $resolution['cotacao']);
            }
            if (!empty($resolution['nome_cliente']) && $resolution['nome_cliente'] !== 'Cliente WhatsApp') {
                $c->nome_cliente = $resolution['nome_cliente'];
            }

            // Atualizar mensagens com dados enriquecidos
            $updatePayload = [];
            if ($c->cotacao_id) {
                $updatePayload['cotacao_id'] = $c->cotacao_id;
            }
            if (!empty($c->nome_cliente) && !in_array($c->nome_cliente, ['Cliente', 'Cliente WhatsApp'])) {
                $updatePayload['nome_cliente'] = $c->nome_cliente;
            }
            if (!empty($updatePayload)) {
                ChatMensagem::where('telefone_cliente', $c->telefone_cliente)
                    ->update($updatePayload);
            }
        }

        return response()->json([
            'success' => true,
            'data' => $contacts
        ]);
        } catch (\Throwable $e) {
            Log::error('Erro ao listar contatos de chat: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'error' => 'Falha ao carregar contatos de chat.',
                'message' => config('app.debug') ? $e->getMessage() : 'Ocorreu um erro interno ao carregar a lista de contatos.'
            ], 500);
        }
    }

    /**
     * Get chat history for a specific phone number.
     * Protected by auth and checks user's chat permission.
     */
    public function getHistory($telefone)
    {
        $user = Auth::user();
        if (!$user->hasChatAccess()) {
            return response()->json(['error' => 'Forbidden.'], 403);
        }

        try {
            $phoneDigits = preg_replace('/\D/', '', $telefone);
            $last8 = strlen($phoneDigits) >= 8 ? substr($phoneDigits, -8) : $phoneDigits;

        // Security check for Representative
        if ($user->isRepresentante()) {
            $hasAccess = Cotacao::where('representante_id', $user->id)
                ->where(function($q) use ($last8) {
                    $q->whereHas('parceiro', function($pq) use ($last8) {
                        $pq->whereRaw("REGEXP_REPLACE(telefone, '[^0-9]', '') LIKE ?", ['%' . $last8]);
                    });
                })->exists();

            if (!$hasAccess) {
                $hasAccess = ChatMensagem::where('telefone_cliente', $phoneDigits)
                    ->whereHas('cotacao', function($q) use ($user) {
                        $q->where('representante_id', $user->id);
                    })->exists();
            }

            if (!$hasAccess) {
                return response()->json(['error' => 'Forbidden. You do not have access to this contact.'], 403);
            }
        } elseif ($user->isGestor()) {
            $teamIds = $user->equipesGerenciadas->pluck('id');
            $hasAccess = Cotacao::whereHas('representante', function($uq) use ($teamIds) {
                $uq->whereIn('equipe_id', $teamIds);
            })->where(function($q) use ($last8) {
                $q->whereHas('parceiro', function($pq) use ($last8) {
                    $pq->whereRaw("REGEXP_REPLACE(telefone, '[^0-9]', '') LIKE ?", ['%' . $last8]);
                });
            })->exists();

            if (!$hasAccess) {
                $hasAccess = ChatMensagem::where('telefone_cliente', $phoneDigits)
                    ->whereHas('cotacao.representante', function($q) use ($teamIds) {
                        $q->whereIn('equipe_id', $teamIds);
                    })->exists();
            }

            if (!$hasAccess) {
                return response()->json(['error' => 'Forbidden. You do not have access to this team contact.'], 403);
            }
        }

        $history = ChatMensagem::where('telefone_cliente', $phoneDigits)
            ->with(['cotacao' => function($q) {
                $q->select('id', 'numero', 'parceiro_id');
            }, 'cotacao.parceiro'])
            ->orderBy('created_at', 'asc')
            ->get();

        // Auto-resolver cotação e nome para o histórico do contato
        $firstMsg = $history->first();
        $sampleText = $history->pluck('mensagem')->filter()->implode(' ');
        $resolution = $this->resolveQuoteAndNameForChat(
            $phoneDigits,
            $sampleText,
            $firstMsg?->cotacao_id,
            $firstMsg?->nome_cliente
        );

        if ($resolution['cotacao'] || !empty($resolution['nome_cliente'])) {
            foreach ($history as $m) {
                if (!$m->cotacao_id && $resolution['cotacao']) {
                    $m->cotacao_id = $resolution['cotacao']->id;
                    $m->setRelation('cotacao', $resolution['cotacao']);
                }
                if (empty($m->nome_cliente) || in_array($m->nome_cliente, ['Cliente', 'Cliente WhatsApp'])) {
                    $m->nome_cliente = $resolution['nome_cliente'];
                }
            }

            $updatePayload = [];
            if ($resolution['cotacao']) {
                $updatePayload['cotacao_id'] = $resolution['cotacao']->id;
            }
            if (!empty($resolution['nome_cliente']) && !in_array($resolution['nome_cliente'], ['Cliente', 'Cliente WhatsApp'])) {
                $updatePayload['nome_cliente'] = $resolution['nome_cliente'];
            }
            if (!empty($updatePayload)) {
                ChatMensagem::where('telefone_cliente', $phoneDigits)->update($updatePayload);
            }
        }

        return response()->json([
            'success' => true,
            'data' => $history
        ]);
        } catch (\Throwable $e) {
            Log::error('Erro ao carregar histórico de chat: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'error' => 'Falha ao carregar mensagens do chat.',
                'message' => config('app.debug') ? $e->getMessage() : 'Ocorreu um erro interno ao carregar as mensagens.'
            ], 500);
        }
    }

    /**
     * Resolve de forma inteligente a cotação vinculada e o nome do cliente para mensagens de chat.
     */
    protected function resolveQuoteAndNameForChat(string $telefoneCliente, ?string $mensagem = null, ?int $existingCotacaoId = null, ?string $existingNome = null): array
    {
        $digits = preg_replace('/\D/', '', $telefoneCliente);
        $cleanPhone = (str_starts_with($digits, '55') && strlen($digits) >= 12) ? substr($digits, 2) : $digits;
        $last8 = strlen($digits) >= 8 ? substr($digits, -8) : $digits;
        $last9 = strlen($digits) >= 9 ? substr($digits, -9) : null;

        $resolvedQuote = null;
        $resolvedName = (!empty($existingNome) && !in_array($existingNome, ['Cliente', 'Cliente WhatsApp'])) ? $existingNome : null;

        // 0. Se já possui cotacao_id existente
        if ($existingCotacaoId) {
            $resolvedQuote = Cotacao::with('parceiro')->find($existingCotacaoId);
            if ($resolvedQuote && empty($resolvedName)) {
                $resolvedName = $resolvedQuote->parceiro?->nome_fantasia ?: $resolvedQuote->parceiro?->razao_social;
            }
        }

        // 1. Tentar localizar via número da cotação mencionado no texto da mensagem
        if (!$resolvedQuote && !empty($mensagem)) {
            if (preg_match('/(COT[A-Za-z0-9\-_]+)/i', $mensagem, $mQuote)) {
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

        // 4. Tentar localizar via telefone do parceiro (limpando caracteres não-numéricos)
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
                ->where(function($q) use ($last8, $digits) {
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

        // 6. Se ainda não achou cotação, mas o nome do cliente foi localizado, buscar cotação desse cliente
        if (!$resolvedQuote && !empty($resolvedName)) {
            $resolvedQuote = Cotacao::with('parceiro')
                ->whereHas('parceiro', function($pq) use ($resolvedName) {
                    $pq->where('nome_fantasia', 'like', "%{$resolvedName}%")
                       ->orWhere('razao_social', 'like', "%{$resolvedName}%");
                })->orderByDesc('id')->first();
        }

        // Fallback final: se houver cotação, garantir que parceiro está carregado
        if ($resolvedQuote) {
            if (!$resolvedQuote->relationLoaded('parceiro')) {
                $resolvedQuote->load('parceiro');
            }
            if (empty($resolvedName)) {
                $resolvedName = $resolvedQuote->parceiro?->nome_fantasia ?: $resolvedQuote->parceiro?->razao_social;
            }
        }

        return [
            'cotacao' => $resolvedQuote,
            'cotacao_id' => $resolvedQuote?->id ?? $existingCotacaoId,
            'nome_cliente' => $resolvedName ?: ($resolvedQuote ? ('Cotação #' . $resolvedQuote->numero) : 'Cliente WhatsApp')
        ];
    }
}
