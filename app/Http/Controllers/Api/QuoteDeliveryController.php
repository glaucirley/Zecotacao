<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cotacao;
use App\Models\ClienteContato;
use App\Models\ParametroSistema;
use App\Services\QuoteDeliveryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QuoteDeliveryController extends Controller
{
    protected QuoteDeliveryService $deliveryService;

    public function __construct(QuoteDeliveryService $deliveryService)
    {
        $this->deliveryService = $deliveryService;
    }

    protected function findQuote($idOrToken): Cotacao
    {
        return Cotacao::with(['parceiro', 'representante', 'ultimoEnvio.contato'])
            ->where(function ($q) use ($idOrToken) {
                if (is_numeric($idOrToken)) {
                    $q->where('id', $idOrToken);
                } else {
                    $q->where('numero', $idOrToken)
                      ->orWhere('token_representante', $idOrToken);
                }
            })
            ->firstOrFail();
    }

    /**
     * Retorna os contatos sugeridos e ranqueados do cliente, histórico do último envio
     * e o modelo de mensagem para prévia no app.
     */
    public function getDeliveryData(Request $request, $id)
    {
        $quote = $this->findQuote($id);
        $user = Auth::user() ?? $quote->representante;

        if ($user && !$user->canAccessQuote($quote) && !$user->isAdministrador()) {
            return response()->json(['error' => 'Acesso não autorizado para esta cotação.'], 403);
        }

        $rankedContacts = $this->deliveryService->getRankedContacts($quote->parceiro_id);

        // Informações sobre o último envio realizado
        $ultimoEnvio = null;
        if ($quote->ultimoEnvio) {
            $env = $quote->ultimoEnvio;
            $ultimoEnvio = [
                'id' => $env->id,
                'canal' => $env->canal,
                'nome_contato' => $env->nome_contato_snapshot ?: $env->contato?->nome_contato ?: 'Cliente',
                'telefone' => $env->telefone_destino,
                'telefone_formatado' => $env->contato?->telefone_formatado,
                'enviado_em' => $env->enviado_em?->format('d/m/Y H:i'),
                'aberto_em' => $env->aberto_em?->format('d/m/Y H:i'),
                'is_aberto' => $env->isAberto(),
            ];
        }

        // Modelo de mensagem padrão
        $template = ParametroSistema::where('chave', 'WA_MSG_PROPOSTA')->value('valor')
            ?: "Olá {nome_contato}, aqui é {nome_representante} da Central Veterinária.\nSegue sua proposta {numero_cotacao}, no valor de R$ {total}, válida até {validade}:\n{link}";

        $sugContact = collect($rankedContacts)->firstWhere('is_sugerido', true);
        $sugName = $sugContact['nome_contato'] ?? ($quote->parceiro->nome_fantasia ?: $quote->parceiro->razao_social);

        $previewMsg = str_replace(
            ['{nome_contato}', '{nome_representante}', '{numero_cotacao}', '{total}', '{validade}', '{link}'],
            [
                $sugName,
                $quote->representante?->nome ?? $user->nome,
                $quote->numero,
                number_format((float)$quote->total, 2, ',', '.'),
                $quote->data_validade ? $quote->data_validade->format('d/m/Y') : now()->addHours($quote->validade_horas ?: 24)->format('d/m/Y'),
                '[Link da Proposta será gerado ao enviar]',
            ],
            $template
        );

        return response()->json([
            'success' => true,
            'cotacao' => [
                'id' => $quote->id,
                'numero' => $quote->numero,
                'status' => $quote->status,
                'total' => (float)$quote->total,
                'total_formatado' => number_format((float)$quote->total, 2, ',', '.'),
                'validade' => $quote->data_validade?->format('d/m/Y H:i'),
                'parceiro' => [
                    'id' => $quote->parceiro?->id,
                    'razao_social' => $quote->parceiro?->razao_social ?? 'Cliente',
                    'codigo_sankhya' => $quote->parceiro?->codigo_sankhya,
                ],
            ],
            'contatos' => $rankedContacts,
            'ultimo_envio' => $ultimoEnvio,
            'template_mensagem' => $template,
            'previa_mensagem' => $previewMsg,
        ]);
    }

    /**
     * Processa a solicitação de envio, gera o link único com token e devolve o link wa.me.
     */
    public function send(Request $request, $id)
    {
        $quote = $this->findQuote($id);
        $user = Auth::user() ?? $quote->representante;

        if (!$user) {
            return response()->json(['error' => 'Usuário remetente não identificado.'], 401);
        }

        $validated = $request->validate([
            'canal' => 'nullable|in:WHATSAPP,WEB_SHARE',
            'contato_id' => 'nullable|integer',
            'telefone' => 'nullable|string',
            'nome_contato' => 'nullable|string|max:100',
            'mensagem' => 'nullable|string',
        ]);

        try {
            $result = $this->deliveryService->prepareSend($quote, $user, $validated);
            return response()->json($result);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Inativa um contato sinalizado pelo representante como "Número errado" ou "Cliente pediu para não receber".
     */
    public function inactivateContact(Request $request, $contactId)
    {
        $validated = $request->validate([
            'motivo' => 'required|string|max:100',
        ]);

        $contato = ClienteContato::findOrFail($contactId);
        $contato->status = 'INATIVO';
        $contato->motivo_inativacao = $validated['motivo'];
        $contato->save();

        return response()->json([
            'success' => true,
            'message' => 'Contato inativado com sucesso.',
        ]);
    }
}
