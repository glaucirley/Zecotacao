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

        if (!$token) {
            return response()->json(['error' => 'Token parameter is missing.'], 400);
        }

        $quote = Cotacao::where('token_representante', $token)->first();

        if (!$quote) {
            return response()->json(['error' => 'Invalid or expired access token.'], 404);
        }

        // Lock modifications if status is finalized
        $lockedStatuses = ['FINALIZADA_COM_PEDIDO', 'FATURADA', 'PERDIDA'];
        if (in_array($quote->status, $lockedStatuses) && !$request->isMethod('GET')) {
            return response()->json([
                'error' => 'Locked quote',
                'message' => 'This quotation has been finalized or closed and cannot be modified.'
            ], 403);
        }

        // If it's a GET request, update the token access timestamp
        if ($request->isMethod('GET')) {
            $quote->update(['token_acesso_em' => now()]);
        }

        // Attach quote to request
        $request->merge(['cotacao' => $quote]);

        return $next($request);
    }
}
