<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cotacao;
use App\Models\PedidoExterno;
use App\Models\CotacaoHistorico;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BillingController extends Controller
{
    /**
     * List all quotations in faturamento flow (PDF_GERADO, AGUARDANDO_PEDIDO, FINALIZADA_COM_PEDIDO, FATURADA).
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        if (!$user->isFaturamento() && !$user->isDiretor() && !$user->isAdministrador()) {
            return response()->json(['error' => 'Forbidden. Only billing staff, directors, and administrators can view the billing queue.'], 403);
        }

        $quotes = Cotacao::whereIn('status', ['PDF_GERADO', 'AGUARDANDO_PEDIDO', 'FINALIZADA_COM_PEDIDO', 'FATURADA'])
            ->with(['parceiro', 'representante', 'pedidoExterno'])
            ->orderBy('updated_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $quotes
        ]);
    }

    /**
     * Show quotation detail for billing verification.
     */
    public function show($cotacao_id)
    {
        $user = Auth::user();

        if (!$user->isFaturamento() && !$user->isDiretor() && !$user->isAdministrador()) {
            return response()->json(['error' => 'Forbidden.'], 403);
        }

        $quote = Cotacao::with([
            'parceiro',
            'representante.equipe',
            'itens.produto',
            'justificativas.anexos',
            'anexos',
            'historico.usuario',
            'pedidoExterno'
        ])->findOrFail($cotacao_id);

        return response()->json([
            'success' => true,
            'data' => $quote
        ]);
    }

    /**
     * Register a received external order mapping to this quotation.
     * Transitions quote status to FINALIZADA_COM_PEDIDO.
     */
    public function createExternalOrder(Request $request, $cotacao_id)
    {
        $user = Auth::user();

        if (!$user->isFaturamento() && !$user->isDiretor() && !$user->isAdministrador()) {
            return response()->json(['error' => 'Forbidden.'], 403);
        }

        $quote = Cotacao::findOrFail($cotacao_id);

        // Block modifications if locked
        if ($quote->status === 'FATURADA') {
            return response()->json(['error' => 'Locked quote', 'message' => 'This quotation has already been billed.'], 422);
        }

        $request->validate([
            'numero_pedido_externo' => 'required|string|max:100',
            'valor_pedido' => 'required|numeric|min:0.01'
        ]);

        try {
            DB::transaction(function () use ($request, $quote, $user) {
                // Upsert PedidoExterno
                $pedido = PedidoExterno::updateOrCreate(
                    ['cotacao_id' => $quote->id],
                    [
                        'numero_pedido_externo' => $request->input('numero_pedido_externo'),
                        'valor_pedido' => $request->input('valor_pedido'),
                        'status_conferencia' => 'pendente'
                    ]
                );

                // Update quote status to FINALIZADA_COM_PEDIDO
                $quote->update(['status' => 'FINALIZADA_COM_PEDIDO']);

                // Log audit
                CotacaoHistorico::create([
                    'cotacao_id' => $quote->id,
                    'evento' => 'PEDIDO_EXTERNO_REGISTRADO',
                    'usuario_id' => $user->id,
                    'papel' => $user->papel,
                    'condicao' => sprintf(
                        'Pedido externo Nº %s registrado no valor de R$ %s. Status alterado para FINALIZADA_COM_PEDIDO.',
                        $pedido->numero_pedido_externo,
                        number_format($pedido->valor_pedido, 2, ',', '.')
                    )
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'External order registered successfully. Quotation moved to FINALIZADA_COM_PEDIDO.',
                'data' => $quote->fresh(['pedidoExterno'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Database error',
                'message' => 'Failed to register external order. ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update verification/conference status of the external order.
     */
    public function updateConference(Request $request, $cotacao_id)
    {
        $user = Auth::user();

        if (!$user->isFaturamento() && !$user->isDiretor() && !$user->isAdministrador()) {
            return response()->json(['error' => 'Forbidden.'], 403);
        }

        $quote = Cotacao::findOrFail($cotacao_id);
        $pedido = $quote->pedidoExterno;

        if (!$pedido) {
            return response()->json(['error' => 'Not found', 'message' => 'No external order registered for this quotation.'], 404);
        }

        $request->validate([
            'status_conferencia' => 'required|in:conforme,divergente'
        ]);

        try {
            DB::transaction(function () use ($request, $quote, $pedido, $user) {
                $statusConf = $request->input('status_conferencia');
                $pedido->update(['status_conferencia' => $statusConf]);

                CotacaoHistorico::create([
                    'cotacao_id' => $quote->id,
                    'evento' => 'CONFERENCIA_ATUALIZADA',
                    'usuario_id' => $user->id,
                    'papel' => $user->papel,
                    'condicao' => "Conferencia do pedido externo alterada para: '{$statusConf}'."
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Conference status updated successfully.',
                'data' => $pedido->fresh()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Database error',
                'message' => 'Failed to update conference status. ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Log a detailed divergence (conflict) between the quotation and the external order.
     */
    public function logDivergence(Request $request, $cotacao_id)
    {
        $user = Auth::user();

        if (!$user->isFaturamento() && !$user->isDiretor() && !$user->isAdministrador()) {
            return response()->json(['error' => 'Forbidden.'], 403);
        }

        $quote = Cotacao::findOrFail($cotacao_id);
        $pedido = $quote->pedidoExterno;

        $request->validate([
            'motivo' => 'required|string|min:5'
        ]);

        try {
            DB::transaction(function () use ($request, $quote, $pedido, $user) {
                if ($pedido) {
                    $pedido->update(['status_conferencia' => 'divergente']);
                }

                CotacaoHistorico::create([
                    'cotacao_id' => $quote->id,
                    'evento' => 'DIVERGENCIA_APONTADA',
                    'usuario_id' => $user->id,
                    'papel' => $user->papel,
                    'condicao' => 'Divergencia apontada pelo faturamento: ' . $request->input('motivo')
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Divergence logged successfully. Status set to divergente.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Database error',
                'message' => 'Failed to log divergence. ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bill the quotation (Mark status as FATURADA).
     */
    public function bill(Request $request, $cotacao_id)
    {
        $user = Auth::user();

        if (!$user->isFaturamento() && !$user->isDiretor() && !$user->isAdministrador()) {
            return response()->json(['error' => 'Forbidden.'], 403);
        }

        $quote = Cotacao::findOrFail($cotacao_id);
        $pedido = $quote->pedidoExterno;

        if ($quote->status === 'FATURADA') {
            return response()->json(['error' => 'Conflict', 'message' => 'This quotation is already marked as billed (FATURADA).'], 422);
        }

        try {
            DB::transaction(function () use ($quote, $pedido, $user) {
                // Release billing
                $quote->update(['status' => 'FATURADA']);

                // If order exists, force it to conforme since it's billed
                if ($pedido) {
                    $pedido->update(['status_conferencia' => 'conforme']);
                }

                CotacaoHistorico::create([
                    'cotacao_id' => $quote->id,
                    'evento' => 'FATURADA',
                    'usuario_id' => $user->id,
                    'papel' => $user->papel,
                    'condicao' => 'Faturamento liberado pelo faturamento. Processo finalizado.'
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Quotation marked as FATURADA successfully.',
                'status' => 'FATURADA'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Database error',
                'message' => 'Failed to bill quotation. ' . $e->getMessage()
            ], 500);
        }
    }
}
