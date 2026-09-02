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
                'message' => 'Chat log recorded successfully.',
                'data' => $log
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Database error',
                'message' => $e->getMessage()
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

        $contacts = $query->get();

        return response()->json([
            'success' => true,
            'data' => $contacts
        ]);
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

        return response()->json([
            'success' => true,
            'data' => $history
        ]);
    }
}
