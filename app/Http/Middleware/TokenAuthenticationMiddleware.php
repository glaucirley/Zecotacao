<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Cotacao;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TokenAuthenticationMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->route('token');
        $id = $request->route('id');

        if (!$token && !$id) {
            return response()->json(['error' => 'Identificador da cotação não fornecido.'], 400);
        }

        if ($id) {
            $quote = Cotacao::find($id);
        } else {
            $quote = Cotacao::where('token_representante', $token)->first();
        }

        if (!$quote) {
            return response()->json(['error' => 'Cotação não encontrada ou token inválido.'], 404);
        }

        // Quando houver sessão ativa, conferir se o usuário autenticado tem permissão para acessar a cotação
        if (auth()->check()) {
            $user = auth()->user();
            if (!$user->canAccessQuote($quote)) {
                return response()->json([
                    'error' => 'Acesso não autorizado.',
                    'message' => 'Esta cotação pertence a outro representante.'
                ], 403);
            }
        } elseif ($id) {
            // Acesso direto por ID exige sessão autenticada
            return response()->json([
                'error' => 'Unauthenticated.',
                'message' => 'Acesso por ID exige autenticação.'
            ], 401);
        }

        // Lock modifications if status is finalized or expired
        $lockedStatuses = ['FINALIZADA_COM_PEDIDO', 'FATURADA', 'PERDIDA', 'EXPIRADA'];
        if (in_array($quote->status, $lockedStatuses) && !$request->isMethod('GET')) {
            return response()->json([
                'error' => 'Locked quote',
                'message' => 'Esta cotação já foi finalizada ou expirada e não pode ser modificada.'
            ], 403);
        }

        // If it's a GET request via token, update the token access timestamp
        if ($token && $request->isMethod('GET')) {
            $quote->update(['token_acesso_em' => now()]);
        }

        // Attach quote to request
        $request->merge(['cotacao' => $quote]);

        return $next($request);
    }
}
