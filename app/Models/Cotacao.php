<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cotacao extends Model
{
    protected $table = 'cotacoes';

    protected $fillable = [
        'numero',
        'parceiro_id',
        'representante_id',
        'status',
        'prioridade',
        'origem',
        'data_emissao',
        'validade_horas',
        'data_validade',
        'subtotal',
        'desconto',
        'total',
        'forma_pagamento',
        'condicao_pagamento_id',
        'prazo_entrega',
        'frete_tipo',
        'transportadora',
        'observacao_cliente',
        'observacao_interna',
        'token_representante',
        'token_acesso_em',
    ];

    protected $hidden = [
        'token_representante',
    ];

    protected $casts = [
        'data_emissao' => 'datetime',
        'data_validade' => 'datetime',
        'token_acesso_em' => 'datetime',
        'subtotal' => 'decimal:2',
        'desconto' => 'decimal:2',
        'total' => 'decimal:2',
        'prioridade' => 'boolean',
    ];

    protected static ?bool $hasCondicaoPagamentoIdColumn = null;

    public static function hasCondicaoPagamentoIdColumn(): bool
    {
        if (static::$hasCondicaoPagamentoIdColumn === null) {
            try {
                static::$hasCondicaoPagamentoIdColumn = \Illuminate\Support\Facades\Schema::hasColumn('cotacoes', 'condicao_pagamento_id');
            } catch (\Throwable $e) {
                static::$hasCondicaoPagamentoIdColumn = false;
            }
        }
        return static::$hasCondicaoPagamentoIdColumn;
    }

    protected static function booted()
    {
        static::saving(function (Cotacao $quote) {
            if (!static::hasCondicaoPagamentoIdColumn()) {
                unset($quote->attributes['condicao_pagamento_id']);
            }
        });

        static::updating(function (Cotacao $quote) {
            // Se a cotação for passada para outro vendedor, o token antigo deixa de valer
            if ($quote->isDirty('representante_id')) {
                $quote->token_representante = null;
                $quote->token_acesso_em = null;
            }
        });
    }

    public function condicaoPagamento()
    {
        return $this->belongsTo(CondicaoPagamento::class, 'condicao_pagamento_id');
    }

    public function parceiro()
    {
        return $this->belongsTo(Parceiro::class, 'parceiro_id');
    }

    public function representante()
    {
        return $this->belongsTo(User::class, 'representante_id');
    }

    public function itens()
    {
        return $this->hasMany(CotacaoItem::class, 'cotacao_id');
    }

    public function vinculos()
    {
        return $this->hasMany(CotacaoVinculoItem::class, 'cotacao_id');
    }

    public function justificativas()
    {
        return $this->hasMany(CotacaoJustificativa::class, 'cotacao_id');
    }

    public function anexos()
    {
        return $this->hasMany(CotacaoAnexo::class, 'cotacao_id');
    }

    public function historico()
    {
        return $this->hasMany(CotacaoHistorico::class, 'cotacao_id');
    }

    public function pedidoExterno()
    {
        return $this->hasOne(PedidoExterno::class, 'cotacao_id');
    }

    public function envios()
    {
        return $this->hasMany(CotacaoEnvio::class, 'cotacao_id')->orderByDesc('id');
    }

    public function ultimoEnvio()
    {
        return $this->hasOne(CotacaoEnvio::class, 'cotacao_id')->latestOfMany();
    }

    public function ultimoEnvioAberto()
    {
        return $this->hasOne(CotacaoEnvio::class, 'cotacao_id')
            ->ofMany(['id' => 'max'], function ($query) {
                $query->whereNotNull('aberto_em');
            });
    }

    public function ultimoEnvioWhatsApp()
    {
        return $this->hasOne(CotacaoEnvio::class, 'cotacao_id')
            ->ofMany(['id' => 'max'], function ($query) {
                $query->where('canal', 'WHATSAPP');
            });
    }

    /**
     * Generate the next short sequential quotation number: COT-YYYY-XXXXXX
     */
    public static function generateNextNumero(): string
    {
        $year = date('Y');
        $prefix = "COT-{$year}-";

        $latest = self::where('numero', 'like', "{$prefix}%")
            ->orderByDesc('id')
            ->value('numero');

        $nextSeq = 1;
        if ($latest && preg_match('/COT-\d{4}-(\d+)/', $latest, $matches)) {
            $nextSeq = ((int)$matches[1]) + 1;
        }

        return $prefix . str_pad($nextSeq, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Recalcula os totais (subtotal, desconto, total) da cotação a partir dos itens.
     * Itens recusados ou não encontrados pela IA são ignorados na soma.
     */
    public function recalcularTotais(): self
    {
        $this->unsetRelation('itens');
        $itens = $this->itens()->get();

        $subtotal = 0.00;
        $total = 0.00;

        foreach ($itens as $item) {
            if ($item->status_item === 'recusado' || (bool)$item->nao_encontrado) {
                continue;
            }

            $qtd = (float)($item->qtd ?? 0);
            $sug = (float)($item->preco_unit_sugerido ?? 0);
            $prop = (float)($item->preco_unit_proposto ?? 0);
            $itemSubtotal = (float)($item->subtotal ?? 0);

            // Garante subtotal no item se estiver zerado
            if ($itemSubtotal <= 0 && $qtd > 0) {
                $unit = $prop > 0 ? $prop : $sug;
                $itemSubtotal = round($qtd * $unit, 2);
                if ($itemSubtotal > 0 && $item->exists) {
                    $item->update(['subtotal' => $itemSubtotal]);
                }
            }

            $subtotal += $qtd * max($sug, $prop);
            $total += $itemSubtotal;
        }

        $subtotal = round($subtotal, 2);
        $total = round($total, 2);
        $desconto = round(max(0.00, $subtotal - $total), 2);

        $this->update([
            'subtotal' => $subtotal,
            'desconto' => $desconto,
            'total' => $total,
        ]);

        return $this;
    }

    /**
     * Localiza cotação pelo token do representante.
     * O token enviado pelo WhatsApp é conferido contra o SHA-256 armazenado no banco.
     * Suporta fallback para tokens em texto simples gerados antes desta implementação.
     */
    public static function findQuoteByRepresentativeToken(string $token): ?self
    {
        $token = trim($token);
        if ($token === '') {
            return null;
        }

        $hash = hash('sha256', $token);

        return static::where('token_representante', $hash)
            ->orWhere('token_representante', $token)
            ->first();
    }

    /**
     * Gera um novo token seguro (32 bytes aleatórios = 64 hex) para o representante,
     * gravando no banco somente o hash SHA-256. Retorna o token em texto puro para envio.
     */
    public function generateRepresentativeToken(): string
    {
        $plainToken = bin2hex(random_bytes(32));
        $hash = hash('sha256', $plainToken);

        $this->updateQuietly([
            'token_representante' => $hash,
            'token_acesso_em' => null,
        ]);

        return $plainToken;
    }

    /**
     * Verifica se a cotação está expirada por data de validade ou status.
     */
    public function isExpired(): bool
    {
        if ($this->data_validade && $this->data_validade->isPast()) {
            return true;
        }

        if (in_array($this->status, ['EXPIRADA', 'CANCELADA'], true)) {
            return true;
        }

        return false;
    }
}
