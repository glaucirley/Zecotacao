<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cotacao;
use App\Models\ClienteContato;
use App\Models\ParametroSistema;
use App\Services\QuoteDeliveryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class QuoteDeliveryController extends Controller
{
    protected QuoteDeliveryService $deliveryService;

    public function __construct(QuoteDeliveryService $deliveryService)
    {
        $this->deliveryService = $deliveryService;
    }

    /**
     * Tenta auto-executar a migration pendente caso as tabelas de envio ainda não existam no ambiente.
     */
    protected function ensureDeliveryTablesExist(): void
    {
        if (!Schema::hasTable('cotacao_envios') || !Schema::hasTable('cliente_contatos')) {
            try {
                Artisan::call('migrate', ['--force' => true]);
            } catch (\Throwable $e) {
                Log::warning('Falha ao auto-executar migrate de tabelas de envio: ' . $e->getMessage());
            }
        }
    }

    protected function findQuote($idOrToken): Cotacao
    {
        $this->ensureDeliveryTablesExist();

        $relations = ['parceiro', 'representante'];
        if (Schema::hasTable('cotacao_envios')) {
            $relations[] = 'ultimoEnvio.contato';
            $relations[] = 'ultimoEnvioAberto.contato';
            $relations[] = 'ultimoEnvioWhatsApp.contato';
            $relations[] = 'envios.contato';
        }

        return Cotacao::with($relations)
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

        if (!Schema::hasTable('cotacao_envios') || !Schema::hasTable('cliente_contatos')) {
            return response()->json([
                'error' => 'As tabelas do recurso de envio pelo WhatsApp não foram encontradas no banco de dados. Por favor, execute "php artisan migrate" no terminal do servidor.',
            ], 500);
        }

        $rankedContacts = $this->deliveryService->getRankedContacts($quote->parceiro_id);

        // Informações sobre o último envio realizado
        $ultimoEnvio = null;
        if (Schema::hasTable('cotacao_envios') && $quote->ultimoEnvio) {
            $env = $quote->ultimoEnvio;
            $ultimoEnvio = [
                'id' => $env->id,
                'canal' => $env->canal,
                'nome_contato' => $env->nome_contato_snapshot ?: $env->contato?->nome_contato ?: 'Cliente',
                'telefone' => $env->telefone_destino,
                'telefone_formatado' => $env->contato?->telefone_formatado ?: \App\Services\QuoteDeliveryService::formatPhoneNumber($env->telefone_destino),
                'enviado_em' => $env->enviado_em?->format('d/m/Y H:i'),
                'aberto_em' => $env->aberto_em?->format('d/m/Y H:i'),
                'is_aberto' => $env->isAberto(),
            ];
        }

        $ultimoEnvioAberto = null;
        if (Schema::hasTable('cotacao_envios') && $quote->ultimoEnvioAberto) {
            $envAb = $quote->ultimoEnvioAberto;
            $ultimoEnvioAberto = [
                'id' => $envAb->id,
                'canal' => $envAb->canal,
                'nome_contato' => $envAb->nome_contato_snapshot ?: $envAb->contato?->nome_contato ?: 'Cliente',
                'telefone' => $envAb->telefone_destino,
                'telefone_formatado' => $envAb->contato?->telefone_formatado ?: \App\Services\QuoteDeliveryService::formatPhoneNumber($envAb->telefone_destino),
                'enviado_em' => $envAb->enviado_em?->format('d/m/Y H:i'),
                'aberto_em' => $envAb->aberto_em?->format('d/m/Y H:i'),
                'is_aberto' => true,
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
                '🔗 link da proposta',
            ],
            $template
        );
        $previewMsg = QuoteDeliveryService::normalizeCurrencyText($previewMsg);

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
                    'nome_fantasia' => $quote->parceiro?->nome_fantasia,
                    'razao_social' => $quote->parceiro?->razao_social ?? 'Cliente',
                    'codigo_sankhya' => $quote->parceiro?->codigo_sankhya,
                ],
            ],
            'contatos' => $rankedContacts,
            'ultimo_envio' => $ultimoEnvio,
            'ultimo_envio_aberto' => $ultimoEnvioAberto,
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

        if (!Schema::hasTable('cotacao_envios') || !Schema::hasTable('cliente_contatos')) {
            return response()->json([
                'success' => false,
                'error' => 'As tabelas do recurso de envio pelo WhatsApp não foram encontradas no banco de dados. Por favor, execute "php artisan migrate" no terminal do servidor.',
            ], 500);
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
            Log::error('Erro ao enviar proposta via WhatsApp: ' . $e->getMessage(), [
                'exception' => $e,
                'quote_id' => $quote->id,
                'user_id' => $user->id,
            ]);

            $msg = $e->getMessage();
            $isTechnicalDbError = ($e instanceof \Illuminate\Database\QueryException || str_contains($msg, 'SQLSTATE'));

            return response()->json([
                'success' => false,
                'error' => $isTechnicalDbError
                    ? 'Ocorreu um erro no banco de dados ao registrar o envio. Por favor, tente novamente.'
                    : $msg,
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
