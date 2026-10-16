<?php

namespace App\Http\Controllers;

use App\Models\CotacaoEnvio;
use App\Models\CotacaoHistorico;
use App\Services\PdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PublicProposalController extends Controller
{
    protected PdfService $pdfService;

    public function __construct(PdfService $pdfService)
    {
        $this->pdfService = $pdfService;
    }

    /**
     * Localiza o registro de envio suportando tanto o hash seguro SHA-256
     * quanto o token legado direto.
     */
    protected function findEnvio(string $token, array $relations = [])
    {
        $hash = hash('sha256', $token);
        return CotacaoEnvio::where('token_acesso', $hash)
            ->orWhere('token_acesso', $token)
            ->with($relations)
            ->first();
    }

    /**
     * Exibe a proposta comercial responsiva pública para o cliente final.
     * Não registra abertura síncrona aqui para evitar falsos positivos de robôs
     * de prévia de links (ex: WhatsApp, Facebook, Telegram).
     */
    public function show(Request $request, string $token)
    {
        $envio = $this->findEnvio($token, [
            'cotacao.parceiro',
            'cotacao.representante.equipe',
            'cotacao.itens.produto',
            'contato',
        ]);

        if (!$envio || !$envio->cotacao) {
            abort(404, 'Proposta comercial não encontrada ou link inválido.');
        }

        $quote = $envio->cotacao;

        // Itens aprovados para a exibição oficial ao cliente
        $items = $quote->itens->where('status_item', 'aprovado');
        if ($items->isEmpty()) {
            $items = $quote->itens->where('status_item', '!=', 'recusado');
        }

        // WhatsApp do representante para retorno rápido do cliente
        $repWhatsappUrl = null;
        if ($quote->representante && $quote->representante->telefone) {
            $digits = preg_replace('/\D/', '', $quote->representante->telefone);
            $digits = ltrim($digits, '0');
            if (!str_starts_with($digits, '55')) {
                $digits = '55' . $digits;
            }
            $msgRetorno = "Olá {$quote->representante->nome}, estou visualizando a proposta {$quote->numero} e gostaria de falar com você.";
            $repWhatsappUrl = "https://wa.me/{$digits}?text=" . rawurlencode($msgRetorno);
        }

        return response()
            ->view('public.proposal', [
                'envio' => $envio,
                'quote' => $quote,
                'items' => $items,
                'repWhatsappUrl' => $repWhatsappUrl,
                'token' => $token,
            ])
            ->header('X-Robots-Tag', 'noindex, nofollow, noarchive, nosnippet')
            ->header('Referrer-Policy', 'no-referrer');
    }

    /**
     * Registra a abertura real da proposta quando executada via JavaScript
     * no navegador do cliente, filtrando estritamente bots de mensageiros.
     */
    public function track(Request $request, string $token)
    {
        $userAgent = $request->userAgent() ?? '';

        // Ignorar crawlers e geradores de preview de mensagens
        $botPatterns = '/bot|crawl|spider|slurp|whatsapp|facebookexternalhit|facebot|twitterbot|telegrambot|slackbot|discordbot|preview|google|bing|yahoo|duckduck/i';
        if (preg_match($botPatterns, $userAgent)) {
            return response()->json(['tracked' => false, 'reason' => 'bot_ignored']);
        }

        $envio = $this->findEnvio($token, ['cotacao', 'contato']);
        if (!$envio || !$envio->cotacao) {
            return response()->json(['error' => 'Not found'], 404);
        }

        // Se for a primeira visualização real
        if (is_null($envio->aberto_em)) {
            $envio->aberto_em = now();
            $envio->ip_abertura = $request->ip();
            $envio->user_agent_abertura = Str::limit($userAgent, 500);
            $envio->save();

            if ($envio->contato) {
                $envio->contato->confirmado = true;
                $envio->contato->increment('qtd_aberturas');
                $envio->contato->ultima_abertura_em = now();
                $envio->contato->save();
            }

            $contactDisplayName = $envio->nome_contato_snapshot
                ?: ($envio->contato?->nome_contato ?: ($envio->cotacao->parceiro?->nome_fantasia ?: $envio->cotacao->parceiro?->razao_social ?: 'Cliente'));

            CotacaoHistorico::create([
                'cotacao_id' => $envio->cotacao->id,
                'evento' => 'PROPOSTA_VISUALIZADA_CLIENTE',
                'usuario_id' => null,
                'papel' => 'cliente',
                'condicao' => 'Proposta comercial aberta pelo cliente (' . $contactDisplayName . ') no navegador.',
            ]);
        }

        return response()->json([
            'tracked' => true,
            'aberto_em' => $envio->aberto_em?->format('d/m/Y H:i')
        ]);
    }

    /**
     * Download do PDF oficial pelo cliente através do token.
     */
    public function downloadPdf(string $token)
    {
        $envio = $this->findEnvio($token, ['cotacao']);

        if (!$envio || !$envio->cotacao) {
            abort(404, 'Proposta comercial não encontrada.');
        }

        $pdf = $this->pdfService->generateQuotePdf($envio->cotacao);
        return $pdf->download("proposta_{$envio->cotacao->numero}.pdf");
    }
}
