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
     * Exibe a proposta comercial responsiva pública para o cliente final
     * e registra a visualização / confirmação de contato.
     */
    public function show(Request $request, string $token)
    {
        $envio = CotacaoEnvio::where('token_acesso', $token)
            ->with([
                'cotacao.parceiro',
                'cotacao.representante.equipe',
                'cotacao.itens.produto',
                'contato',
            ])
            ->first();

        if (!$envio || !$envio->cotacao) {
            abort(404, 'Proposta comercial não encontrada ou link inválido.');
        }

        $quote = $envio->cotacao;

        // Se for a primeira visualização, registrar abertura e confirmar contato
        if (is_null($envio->aberto_em)) {
            $envio->aberto_em = now();
            $envio->ip_abertura = $request->ip();
            $envio->user_agent_abertura = Str::limit($request->userAgent() ?? '', 500);
            $envio->save();

            if ($envio->contato) {
                $envio->contato->confirmado = true;
                $envio->contato->increment('qtd_aberturas');
                $envio->contato->ultima_abertura_em = now();
                $envio->contato->save();
            }

            CotacaoHistorico::create([
                'cotacao_id' => $quote->id,
                'evento' => 'PROPOSTA_VISUALIZADA_CLIENTE',
                'usuario_id' => null,
                'papel' => 'cliente',
                'condicao' => 'Proposta comercial aberta pelo cliente (' . ($envio->nome_contato_snapshot ?: 'Cliente') . ') a partir do link do WhatsApp.',
            ]);
        }

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

        return view('public.proposal', [
            'envio' => $envio,
            'quote' => $quote,
            'items' => $items,
            'repWhatsappUrl' => $repWhatsappUrl,
            'token' => $token,
        ]);
    }

    /**
     * Download do PDF oficial pelo cliente através do token.
     */
    public function downloadPdf(string $token)
    {
        $envio = CotacaoEnvio::where('token_acesso', $token)
            ->with(['cotacao'])
            ->first();

        if (!$envio || !$envio->cotacao) {
            abort(404, 'Proposta comercial não encontrada.');
        }

        $pdf = $this->pdfService->generateQuotePdf($envio->cotacao);
        return $pdf->download("proposta_{$envio->cotacao->numero}.pdf");
    }
}
