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

        // Resolve cotacao_id if not explicitly provided
        $cotacaoId = $request->input('cotacao_id');
        if (!$cotacaoId) {
            // Find partner by phone
            // We search for partners whose phone matches the end of the clean phone number (e.g. last 8 or 9 digits) to handle formatting differences
            $lastDigits = substr($phoneDigits, -8);
            if ($lastDigits) {
                $partner = Parceiro::where('telefone', 'like', '%' . $lastDigits . '%')->first();
                if ($partner) {
                    // Find latest quote in the last 24 hours
                    $latestQuote = Cotacao::where('parceiro_id', $partner->id)
                        ->where('created_at', '>=', now()->subHours(24))
                        ->orderBy('created_at', 'desc')
                        ->first();
                    if ($latestQuote) {
                        $cotacaoId = $latestQuote->id;
                    }
                }
            }
        }

        try {
            $log = ChatMensagem::create([
                'telefone_cliente' => $phoneDigits,
                'nome_cliente' => $request->input('nome_cliente'),
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
                       ->whereColumn('parceiros.telefone', 'like', DB::raw("CONCAT('%', SUBSTRING(chat_mensagens.telefone_cliente, -8))"))
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
            $digits = preg_replace('/\D/', '', $c->telefone_cliente);
            $last8 = substr($digits, -8);
            $latestQuote = null;

            // 1. Tentar localizar cotação via cotacao_envios
            if (Schema::hasTable('cotacao_envios')) {
                $envio = \App\Models\CotacaoEnvio::with('cotacao.parceiro')
                    ->where(function($q) use ($last8, $digits) {
                        $q->where('telefone_destino', 'like', "%{$last8}%")
                          ->orWhere('telefone_destino', $digits);
                    })->orderByDesc('id')->first();

                if ($envio && $envio->cotacao) {
                    $latestQuote = $envio->cotacao;
                    if (empty($c->nome_cliente) || in_array($c->nome_cliente, ['Cliente', 'Cliente WhatsApp'])) {
                        $c->nome_cliente = $envio->nome_contato_snapshot;
                    }
                }
            }

            // 2. Tentar localizar via cliente_contatos
            if (!$latestQuote && Schema::hasTable('cliente_contatos')) {
                $contato = \App\Models\ClienteContato::with('parceiro')
                    ->where(function($q) use ($last8, $digits) {
                        $q->where('telefone', 'like', "%{$last8}%")
                          ->orWhere('telefone', $digits);
                    })->first();

                if ($contato) {
                    if (empty($c->nome_cliente) || in_array($c->nome_cliente, ['Cliente', 'Cliente WhatsApp'])) {
                        $c->nome_cliente = $contato->nome_contato;
                    }
                    if ($contato->parceiro_id) {
                        $latestQuote = Cotacao::where('parceiro_id', $contato->parceiro_id)->orderByDesc('id')->first();
                    }
                }
            }

            // 3. Tentar localizar via telefone do parceiro (limpando caracteres especiais)
            if ($last8) {
                $parceiro = Parceiro::where(function($q) use ($last8) {
                    $q->whereRaw("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(telefone, '(', ''), ')', ''), '-', ''), ' ', ''), '+', '') LIKE ?", ["%{$last8}%"])
                      ->orWhere('telefone', 'like', "%{$last8}%");
                })->first();

                if ($parceiro) {
                    if (empty($c->nome_cliente) || in_array($c->nome_cliente, ['Cliente', 'Cliente WhatsApp'])) {
                        $c->nome_cliente = $parceiro->nome_fantasia ?: $parceiro->razao_social;
                    }
                    if (!$latestQuote) {
                        $latestQuote = Cotacao::where('parceiro_id', $parceiro->id)->orderByDesc('id')->first();
                    }
                }
            }

            // 4. Se encontrou cotação, associar ao contato e persistir
            if ($latestQuote) {
                $c->cotacao_id = $latestQuote->id;
                $c->setRelation('cotacao', $latestQuote);
                if (empty($c->nome_cliente) || in_array($c->nome_cliente, ['Cliente', 'Cliente WhatsApp'])) {
                    $c->nome_cliente = $latestQuote->parceiro?->nome_fantasia ?: $latestQuote->parceiro?->razao_social;
                }
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

        // Security check for Representative
        if ($user->isRepresentante()) {
            $hasAccess = Cotacao::where('representante_id', $user->id)
                ->where(function($q) use ($phoneDigits) {
                    $q->whereHas('parceiro', function($pq) use ($phoneDigits) {
                        $pq->where('telefone', 'like', '%' . substr($phoneDigits, -8) . '%');
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
            })->where(function($q) use ($phoneDigits) {
                $q->whereHas('parceiro', function($pq) use ($phoneDigits) {
                    $pq->where('telefone', 'like', '%' . substr($phoneDigits, -8) . '%');
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
                $q->select('id', 'numero');
            }])
            ->orderBy('created_at', 'asc')
            ->get();

        // Se houver mensagens sem cotação vinculada, auto-resolver pela última cotação
        $needsQuote = $history->contains(fn($m) => is_null($m->cotacao_id));
        if ($needsQuote) {
            $last8 = substr($phoneDigits, -8);
            $latestQuote = null;

            if (Schema::hasTable('cotacao_envios')) {
                $envio = \App\Models\CotacaoEnvio::with('cotacao')
                    ->where(function($q) use ($last8, $phoneDigits) {
                        $q->where('telefone_destino', 'like', "%{$last8}%")
                          ->orWhere('telefone_destino', $phoneDigits);
                    })->orderByDesc('id')->first();
                if ($envio && $envio->cotacao) {
                    $latestQuote = $envio->cotacao;
                }
            }

            if (!$latestQuote && Schema::hasTable('cliente_contatos')) {
                $contato = \App\Models\ClienteContato::where(function($q) use ($last8, $phoneDigits) {
                    $q->where('telefone', 'like', "%{$last8}%")
                      ->orWhere('telefone', $phoneDigits);
                })->first();
                if ($contato && $contato->parceiro_id) {
                    $latestQuote = Cotacao::where('parceiro_id', $contato->parceiro_id)->orderByDesc('id')->first();
                }
            }

            if (!$latestQuote && $last8) {
                $latestQuote = Cotacao::whereHas('parceiro', function($pq) use ($last8) {
                    $pq->whereRaw("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(telefone, '(', ''), ')', ''), '-', ''), ' ', ''), '+', '') LIKE ?", ["%{$last8}%"])
                       ->orWhere('telefone', 'like', "%{$last8}%");
                })->orderByDesc('id')->first();
            }

            if ($latestQuote) {
                foreach ($history as $m) {
                    if (!$m->cotacao_id) {
                        $m->cotacao_id = $latestQuote->id;
                        $m->setRelation('cotacao', $latestQuote);
                    }
                }
                ChatMensagem::where('telefone_cliente', $phoneDigits)
                    ->whereNull('cotacao_id')
                    ->update(['cotacao_id' => $latestQuote->id]);
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
}
