<?php

namespace App\Http\Controllers;

use App\Models\Cotacao;
use App\Models\CotacaoHistorico;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;

class RepresentativeLinkController extends Controller
{
    /**
     * Resolve and validate representative access link /r/{token}.
     */
    public function show(Request $request, string $token)
    {
        $userAgent = (string) $request->userAgent();

        // 1. Crawler / Bot detection: return generic Open Graph preview (WhatsApp, Facebook, etc.)
        if ($this->isCrawler($userAgent)) {
            return response()->view('public.link_preview');
        }

        // 2. Authentication check: must be logged in
        if (!Auth::check()) {
            return redirect()->guest(route('login', [
                'redirect' => $request->getRequestUri()
            ]));
        }

        // 3. Resolve quotation by representative token (SHA-256 hash or legacy plain text)
        $quote = Cotacao::findQuoteByRepresentativeToken($token);

        if (!$quote) {
            return response()->view('errors.quote_error', [
                'title' => 'Cotação Não Encontrada',
                'message' => 'O link informado não corresponde a nenhuma cotação ativa ou o acesso foi cancelado.'
            ], 404);
        }

        // 4. Expiration check
        if ($quote->isExpired()) {
            $formattedDate = $quote->data_validade ? $quote->data_validade->format('d/m/Y H:i') : null;
            return response()->view('errors.quote_error', [
                'title' => 'Cotação Expirada',
                'message' => $formattedDate 
                    ? "Esta cotação ({$quote->numero}) expirou sua validade em {$formattedDate}."
                    : "Esta cotação ({$quote->numero}) não está mais ativa."
            ], 410);
        }

        $user = Auth::user();
        $ip = $request->ip();
        $device = preg_match('/Mobile|Android|iPhone|iPad/i', $userAgent) ? 'celular' : 'computador';

        // 5. Authorization check: seller owner, team manager, or admin/director
        if (!$user->canAccessQuote($quote)) {
            // Log unauthorized attempt in audit trail
            CotacaoHistorico::create([
                'cotacao_id' => $quote->id,
                'evento' => 'TENTATIVA_ACESSO_NAO_AUTORIZADO',
                'usuario_id' => $user->id,
                'papel' => $user->papel,
                'condicao' => "Tentativa bloqueada: Usuário {$user->nome} ({$user->papel}) tentou acessar cotação vinculada ao representante ID {$quote->representante_id} no {$device}. IP: {$ip}.",
            ]);

            return response()->view('errors.unauthorized_quote', [
                'quote' => $quote
            ], 403);
        }

        // 6. Authorized access: record in quotation audit trail if not logged within the last 15 minutes
        $recentAudit = CotacaoHistorico::where('cotacao_id', $quote->id)
            ->where('evento', 'ACESSO_LINK_INTERNO')
            ->where('usuario_id', $user->id)
            ->where('created_at', '>=', now()->subMinutes(15))
            ->exists();

        if (!$recentAudit) {
            CotacaoHistorico::create([
                'cotacao_id' => $quote->id,
                'evento' => 'ACESSO_LINK_INTERNO',
                'usuario_id' => $user->id,
                'papel' => $user->papel,
                'condicao' => "Aberta por {$user->nome} no {$device}, " . now()->format('d/m H:i'),
            ]);
        }

        // Update token access timestamp
        $quote->updateQuietly([
            'token_acesso_em' => now(),
        ]);

        // Maintain internal user session and persistent cookie
        if ($request->hasSession()) {
            $request->session()->put('zecotacao_internal_user', true);
        }
        Cookie::queue('zecotacao_internal', '1', 60 * 24 * 30);

        // Redirect directly to the internal quotation editor
        return redirect("/cotacoes/id/{$quote->id}");
    }

    /**
     * Check if the request is from a social preview crawler or web bot.
     */
    protected function isCrawler(string $userAgent): bool
    {
        if (empty($userAgent)) {
            return false;
        }

        $crawlers = [
            'WhatsApp',
            'facebookexternalhit',
            'Facebot',
            'Twitterbot',
            'TelegramBot',
            'Slackbot',
            'LinkedInBot',
            'Googlebot',
            'bingbot',
            'Applebot',
        ];

        foreach ($crawlers as $crawler) {
            if (stripos($userAgent, $crawler) !== false) {
                return true;
            }
        }

        return false;
    }
}
