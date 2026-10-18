<?php

namespace App\Services;

use App\Models\Cotacao;
use App\Models\CotacaoEnvio;
use App\Models\ClienteContato;
use App\Models\CotacaoHistorico;
use App\Models\ParametroSistema;
use App\Models\Parceiro;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class QuoteDeliveryService
{
    protected ContactValidationService $validator;
    protected PdfService $pdfService;

    public function __construct(ContactValidationService $validator, PdfService $pdfService)
    {
        $this->validator = $validator;
        $this->pdfService = $pdfService;
    }

    /**
     * Retorna os contatos do cliente ranqueados por confiabilidade e uso.
     *
     * Ordem de prioridade:
     * 1. Abertura confirmada mais recente (ultima_abertura_em DESC)
     * 2. Último envio realizado (ultimo_envio_em DESC)
     * 3. Origem SANKHYA
     * 4. Desempate: qtd_aberturas DESC, depois qtd_envios DESC
     */
    public function getRankedContacts(int $parceiroId): array
    {
        $parceiro = Parceiro::find($parceiroId);
        if (!$parceiro) {
            return [];
        }

        if (!Schema::hasTable('cliente_contatos')) {
            return [];
        }

        $contatos = ClienteContato::where('parceiro_id', $parceiroId)
            ->where('status', 'ATIVO')
            ->get();

        // Verificar regra de 3 envios sem abertura nos últimos 7 dias para rebaixar selo
        $seteDiasAtras = now()->subDays(7);
        foreach ($contatos as $c) {
            if ($c->confirmado && $c->ultimo_envio_em && $c->ultimo_envio_em >= $seteDiasAtras) {
                // Contar envios nos últimos 7 dias após a última abertura
                $enviosRecentesSemAbertura = 0;
                if (Schema::hasTable('cotacao_envios')) {
                    $enviosRecentesSemAbertura = CotacaoEnvio::where('contato_id', $c->id)
                        ->where('enviado_em', '>=', $seteDiasAtras)
                        ->whereNull('aberto_em')
                        ->count();
                }

                if ($enviosRecentesSemAbertura >= 3 && (!$c->ultima_abertura_em || $c->ultima_abertura_em < $seteDiasAtras)) {
                    $c->confirmado = false;
                    $c->save();
                }
            }
        }

        // Ordenar os contatos
        $sorted = $contatos->sort(function ($a, $b) {
            // 1. Confirmado com abertura mais recente
            if ($a->confirmado !== $b->confirmado) {
                return $a->confirmado ? -1 : 1;
            }
            if ($a->ultima_abertura_em && $b->ultima_abertura_em) {
                return $b->ultima_abertura_em <=> $a->ultima_abertura_em;
            }
            if ($a->ultima_abertura_em && !$b->ultima_abertura_em) return -1;
            if (!$a->ultima_abertura_em && $b->ultima_abertura_em) return 1;

            // 2. Último envio mais recente
            if ($a->ultimo_envio_em && $b->ultimo_envio_em) {
                return $b->ultimo_envio_em <=> $a->ultimo_envio_em;
            }
            if ($a->ultimo_envio_em && !$b->ultimo_envio_em) return -1;
            if (!$a->ultimo_envio_em && $b->ultimo_envio_em) return 1;

            // 3. Origem SANKHYA
            if ($a->origem !== $b->origem) {
                return $a->origem === 'SANKHYA' ? -1 : 1;
            }

            // 4. Desempate: qtd_aberturas DESC, depois qtd_envios DESC
            if ($a->qtd_aberturas !== $b->qtd_aberturas) {
                return $b->qtd_aberturas <=> $a->qtd_aberturas;
            }
            return $b->qtd_envios <=> $a->qtd_envios;
        })->values();

        $result = [];
        $isFirst = true;

        foreach ($sorted as $item) {
            $result[] = [
                'id' => $item->id,
                'nome_contato' => $item->nome_contato ?: 'Contato Principal',
                'cargo' => $item->cargo,
                'telefone' => $item->telefone,
                'telefone_formatado' => $item->telefone_formatado,
                'confirmado' => (bool)$item->confirmado,
                'origem' => $item->origem,
                'qtd_envios' => $item->qtd_envios,
                'qtd_aberturas' => $item->qtd_aberturas,
                'ultima_abertura_em' => $item->ultima_abertura_em?->format('d/m/Y H:i'),
                'ultimo_envio_em' => $item->ultimo_envio_em?->format('d/m/Y H:i'),
                'is_sugerido' => $isFirst,
            ];
            $isFirst = false;
        }

        // Se ainda não houver nenhum contato na base e o parceiro tiver telefone válido no cadastro
        if (empty($result) && !empty($parceiro->telefone)) {
            $validation = $this->validator->validate($parceiro->telefone, $parceiroId);
            if ($validation['valid']) {
                $novoContato = null;
                try {
                    $novoContato = ClienteContato::create([
                        'parceiro_id' => $parceiroId,
                        'codigo_sankhya_parc' => $parceiro->codigo_sankhya,
                        'nome_contato' => $parceiro->nome_fantasia ?: $parceiro->razao_social,
                        'cargo' => 'Cadastro Geral',
                        'telefone' => $validation['normalized'],
                        'origem' => 'SANKHYA',
                        'confirmado' => false,
                        'status' => 'ATIVO',
                        'qtd_envios' => 0,
                        'qtd_aberturas' => 0,
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('Não foi possível auto-persistir contato inicial Sankhya: ' . $e->getMessage());
                }

                $result[] = [
                    'id' => $novoContato?->id,
                    'nome_contato' => $parceiro->nome_fantasia ?: $parceiro->razao_social,
                    'cargo' => 'Cadastro Geral',
                    'telefone' => $validation['normalized'],
                    'telefone_formatado' => (new ClienteContato(['telefone' => $validation['normalized']]))->telefone_formatado,
                    'confirmado' => false,
                    'origem' => 'SANKHYA',
                    'qtd_envios' => 0,
                    'qtd_aberturas' => 0,
                    'ultima_abertura_em' => null,
                    'ultimo_envio_em' => null,
                    'is_sugerido' => true,
                ];
            }
        }

        return $result;
    }

    /**
     * Prepara e registra o envio da proposta ao cliente.
     */
    public function prepareSend(Cotacao $quote, User $user, array $data): array
    {
        // 1. Validar permissões de acesso
        if (!$user->canAccessQuote($quote) && !$user->isAdministrador()) {
            throw new \Exception('Você não tem permissão para enviar esta cotação.');
        }

        // 2. Validar status permitido
        $allowedStatuses = ['APROVADA', 'PDF_GERADO', 'FINALIZADA_COM_PEDIDO'];
        if (!in_array($quote->status, $allowedStatuses)) {
            throw new \Exception("Envio não permitido para cotações com status {$quote->status}. Apenas propostas aprovadas ou finalizadas podem ser enviadas ao cliente.");
        }

        // 3. Se o PDF ainda não existe (status APROVADA), gerar e consolidar antes
        if ($quote->status === 'APROVADA') {
            try {
                $this->pdfService->generateQuotePdf($quote);
                $quote->update(['status' => 'PDF_GERADO']);

                CotacaoHistorico::create([
                    'cotacao_id' => $quote->id,
                    'evento' => 'PDF_GERADO',
                    'usuario_id' => $user->id,
                    'papel' => $user->papel,
                    'condicao' => 'PDF gerado automaticamente antes do disparo de envio ao cliente via WhatsApp.',
                ]);
            } catch (\Throwable $e) {
                throw new \Exception('Não foi possível gerar o PDF da proposta antes do envio: ' . $e->getMessage());
            }
        }

        $canal = ($data['canal'] ?? 'WHATSAPP') === 'WEB_SHARE' ? 'WEB_SHARE' : 'WHATSAPP';
        $rawPhone = $data['telefone'] ?? '';
        $contactName = trim($data['nome_contato'] ?? '');
        $partnerDefaultName = $quote->parceiro?->nome_fantasia ?: $quote->parceiro?->razao_social;

        // 4. Validação do telefone para envio via WhatsApp
        $normalizedPhone = null;
        $warning = null;
        $contato = null;

        if ($canal === 'WHATSAPP') {
            $validation = $this->validator->validate($rawPhone, $quote->parceiro_id);
            if (!$validation['valid']) {
                throw new \Exception($validation['error']);
            }
            $normalizedPhone = $validation['normalized'];
            $warning = $validation['warning'];
        }

        // 5. Gerar token criptográfico único para rastreamento de abertura (armazenando apenas o hash SHA-256 no banco)
        $plainToken = Str::random(48);
        $linkProposta = url("/p/{$plainToken}");
        $tokenHash = hash('sha256', $plainToken);

        // 6. Montar texto da mensagem
        $template = ParametroSistema::where('chave', 'WA_MSG_PROPOSTA')->value('valor');
        if (!$template) {
            $template = "Olá {nome_contato}, aqui é {nome_representante} da Central Veterinária.\nSegue sua proposta {numero_cotacao}, no valor de R$ {total}, válida até {validade}:\n{link}";
        }

        $repNome = $quote->representante?->nome ?? $user->nome;
        $totalFormatted = number_format((float)$quote->total, 2, ',', '.');
        $validadeDate = $quote->data_validade 
            ? $quote->data_validade->format('d/m/Y') 
            : now()->addHours($quote->validade_horas ?: 24)->format('d/m/Y');

        $replacements = [
            '{nome_contato}' => $contactName ?: 'Cliente',
            '{nome_representante}' => $repNome,
            '{numero_cotacao}' => $quote->numero,
            '{total}' => $totalFormatted,
            '{validade}' => $validadeDate,
            '{link}' => $linkProposta,
        ];

        // Se o representante editou o texto na tela de envio, substituir o placeholder provisório ou anexar o link
        if (!empty($data['mensagem'])) {
            $mensagemFinal = trim($data['mensagem']);
            $placeholders = [
                '🔗 link da proposta',
                '🔗 link da proposta será gerado ao enviar',
                '[Link da Proposta será gerado ao enviar]',
                '{link}',
            ];
            $replaced = false;
            foreach ($placeholders as $ph) {
                if (stripos($mensagemFinal, $ph) !== false) {
                    $mensagemFinal = str_ireplace($ph, $linkProposta, $mensagemFinal);
                    $replaced = true;
                    break;
                }
            }
            if (!$replaced && !str_contains($mensagemFinal, $linkProposta)) {
                $mensagemFinal .= "\n\n" . $linkProposta;
            }
        } else {
            $mensagemFinal = str_replace(array_keys($replacements), array_values($replacements), $template);
        }
        $mensagemFinal = static::normalizeCurrencyText($mensagemFinal);

        // Construir URL wa.me
        $urlWhatsapp = null;
        if ($canal === 'WHATSAPP' && $normalizedPhone) {
            $urlWhatsapp = 'https://wa.me/' . $normalizedPhone . '?text=' . rawurlencode($mensagemFinal);
        }

        // 7. Gravar envio, atualizar contato e registrar histórico dentro de transação atômica
        return DB::transaction(function () use (
            $quote, $user, $data, $canal, $normalizedPhone, $contactName, $partnerDefaultName,
            $tokenHash, $linkProposta, $mensagemFinal, $urlWhatsapp, $warning
        ) {
            $contato = null;

            if ($canal === 'WHATSAPP') {
                if (!empty($data['contato_id'])) {
                    $contato = ClienteContato::where('id', $data['contato_id'])
                        ->where('parceiro_id', $quote->parceiro_id)
                        ->first();
                }

                if (!$contato) {
                    $contato = ClienteContato::firstOrNew([
                        'parceiro_id' => $quote->parceiro_id,
                        'telefone' => $normalizedPhone,
                    ]);
                }

                $effectiveName = $contactName ?: ($contato->nome_contato ?: ($partnerDefaultName ?: 'Cliente'));
                if (empty($contato->nome_contato) && !empty($effectiveName)) {
                    $contato->nome_contato = $effectiveName;
                }

                if (!$contato->exists) {
                    $contato->codigo_sankhya_parc = $quote->parceiro?->codigo_sankhya;
                    $contato->origem = 'REPRESENTANTE';
                    $contato->criado_por = $user->id;
                    $contato->status = 'ATIVO';
                }

                $contato->qtd_envios = ($contato->qtd_envios ?? 0) + 1;
                $contato->ultimo_envio_em = now();
                $contato->save();
            } else {
                if (!empty($data['contato_id'])) {
                    $contato = ClienteContato::find($data['contato_id']);
                    if ($contato) {
                        $contato->increment('qtd_envios');
                        $contato->update(['ultimo_envio_em' => now()]);
                    }
                }
            }

            // Gravar registro em cotacao_envios guardando apenas o hash SHA-256 do token
            $envio = CotacaoEnvio::create([
                'cotacao_id' => $quote->id,
                'contato_id' => $contato?->id,
                'token_acesso' => $tokenHash,
                'canal' => $canal,
                'telefone_destino' => $normalizedPhone,
                'nome_contato_snapshot' => $contactName ?: ($contato?->nome_contato ?: ($partnerDefaultName ?: 'Cliente')),
                'mensagem_enviada' => $mensagemFinal,
                'enviado_por' => $user->id,
                'enviado_em' => now(),
                'expiracao_em' => $quote->data_validade ?? now()->addHours($quote->validade_horas ?: 24),
            ]);

            // Gravar histórico da cotação
            $eventoHistorico = $canal === 'WHATSAPP' ? 'PROPOSTA_ENVIADA_WHATSAPP' : 'PROPOSTA_COMPARTILHADA_PDF';
            $telFormatado = $contato ? $contato->telefone_formatado : $normalizedPhone;
            $histName = $envio->nome_contato_snapshot;

            CotacaoHistorico::create([
                'cotacao_id' => $quote->id,
                'evento' => $eventoHistorico,
                'usuario_id' => $user->id,
                'papel' => $user->papel,
                'condicao' => $canal === 'WHATSAPP'
                    ? "Proposta enviada para {$histName} ({$telFormatado}) via WhatsApp por {$user->nome}."
                    : "Proposta compartilhada via menu nativo do aparelho por {$user->nome}.",
            ]);

            return [
                'success' => true,
                'envio_id' => $envio->id,
                'link_proposta' => $linkProposta,
                'url_whatsapp' => $urlWhatsapp,
                'mensagem' => $mensagemFinal,
                'telefone_formatado' => $contato?->telefone_formatado ?? static::formatPhoneNumber($normalizedPhone),
                'nome_contato' => $histName,
                'contato_confirmado' => (bool)$contato?->confirmado,
                'warning' => $warning,
            ];
        });
    }

    /**
     * Converte valores monetários em formato americano dentro de um texto
     * (ex.: "R$ 13.00", "R$ 1021.39", "R$ 1,021.39") para o padrão brasileiro
     * ("R$ 13,00", "R$ 1.021,39"). Valores já corretos não são alterados.
     */
    public static function normalizeCurrencyText(?string $text): string
    {
        if ($text === null || $text === '') return (string)$text;

        return preg_replace_callback(
            '/R\$\s?((?:\d{1,3}(?:,\d{3})+|\d+)\.\d{2})(?!\d)/u',
            function ($m) {
                $num = (float)str_replace(',', '', $m[1]);
                return 'R$ ' . number_format($num, 2, ',', '.');
            },
            $text
        );
    }

    /**
     * Formata um telefone no padrão visual brasileiro (XX) 9XXXX-XXXX
     */
    public static function formatPhoneNumber(?string $phone): string
    {
        if (empty($phone)) return '';
        $digits = preg_replace('/\D/', '', $phone);
        if (strlen($digits) >= 12 && str_starts_with($digits, '55')) {
            $digits = substr($digits, 2);
        }
        if (strlen($digits) === 11) {
            return '(' . substr($digits, 0, 2) . ') ' . substr($digits, 2, 5) . '-' . substr($digits, 7);
        }
        if (strlen($digits) === 10) {
            return '(' . substr($digits, 0, 2) . ') ' . substr($digits, 2, 4) . '-' . substr($digits, 6);
        }
        return $phone;
    }
}
