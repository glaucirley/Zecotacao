<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\Cotacao;
use App\Models\CondicaoPagamento;
use App\Models\ParametroSistema;

class WebViewController extends Controller
{
    /**
     * Master Quotations List (All authenticated users).
     */
    public function quotes()
    {
        if (Auth::user()->isRepresentante()) {
            return redirect('/painel-representante');
        }
        $condicoesPagamento = CondicaoPagamento::where('ativa', true)
            ->orderBy('descricao')
            ->get(['id', 'codigo_sankhya', 'descricao', 'venda_minima', 'venda_maxima']);
        return view('admin.quotes', compact('condicoesPagamento'));
    }

    /**
     * Quotation Sales Funnel.
     */
    public function funnel()
    {
        if (Auth::user()->isRepresentante() || Auth::user()->isSuporte()) {
            return redirect('/cotacoes');
        }
        return view('admin.funnel');
    }

    /**
     * Approvals List.
     */
    public function approvals()
    {
        if (Auth::user()->isRepresentante()) {
            return redirect('/painel-representante');
        }
        if (Auth::user()->isFaturamento()) {
            return redirect('/faturamento');
        }
        return view('admin.approvals');
    }

    /**
     * Approval Detail view.
     */
    public function approvalDetail($id)
    {
        if (Auth::user()->isRepresentante()) {
            return redirect('/painel-representante');
        }
        if (Auth::user()->isFaturamento()) {
            return redirect('/faturamento');
        }
        return view('admin.approval_detail', ['id' => $id]);
    }

    /**
     * Billing List view.
     */
    public function billing()
    {
        if (Auth::user()->isRepresentante()) {
            return redirect('/painel-representante');
        }
        if (!Auth::user()->isFaturamento() && !Auth::user()->isDiretor() && !Auth::user()->isAdministrador() && !Auth::user()->isSuporte()) {
            return redirect('/cotacoes');
        }
        return view('admin.billing');
    }

    /**
     * Billing Detail view.
     */
    public function billingDetail($id)
    {
        if (Auth::user()->isRepresentante()) {
            return redirect('/painel-representante');
        }
        if (!Auth::user()->isFaturamento() && !Auth::user()->isDiretor() && !Auth::user()->isAdministrador() && !Auth::user()->isSuporte()) {
            return redirect('/cotacoes');
        }
        return view('admin.billing_detail', ['id' => $id]);
    }

    /**
     * Parameters management view.
     */
    public function parameters()
    {
        if (!Auth::user()->isAdministrador() && !Auth::user()->isDiretor()) {
            return redirect('/cotacoes');
        }
        return view('admin.parameters');
    }

    /**
     * User Management view.
     */
    public function users()
    {
        if (!Auth::user()->isAdministrador() && !Auth::user()->isSuporte()) {
            return redirect('/cotacoes');
        }

        // Auto-migrate check for new roles (e.g. suporte)
        Cache::remember('auto_migrate_suporte_enum', 86400, function () {
            try {
                \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
            } catch (\Throwable $e) {
                try {
                    \Illuminate\Support\Facades\DB::statement("ALTER TABLE usuarios MODIFY COLUMN papel ENUM('representante', 'gestor', 'diretor', 'faturamento', 'administrador', 'suporte') NOT NULL");
                } catch (\Throwable $ex) {}
            }
            return true;
        });

        return view('admin.users');
    }

    /**
     * Client / Partner Management view.
     */
    public function partners()
    {
        if (!Auth::user()->isAdministrador() && !Auth::user()->isSuporte()) {
            return redirect('/cotacoes');
        }
        return view('admin.partners');
    }

    /**
     * Product Catalog Management view.
     */
    public function products()
    {
        if (!Auth::user()->isAdministrador() && !Auth::user()->isSuporte()) {
            return redirect('/cotacoes');
        }
        return view('admin.products');
    }

    /**
     * Product & Stock Query view for Retaguarda.
     */
    public function productQuery()
    {
        if (Auth::user()->isRepresentante()) {
            return redirect('/painel-representante');
        }
        return view('admin.product_query');
    }

    /**
     * Representative Mobile Dashboard.
     */
    public function representativeDashboard()
    {
        if (!Auth::user()->isRepresentante()) {
            return redirect('/cotacoes');
        }
        $condicoesPagamento = CondicaoPagamento::where('ativa', true)
            ->orderBy('descricao')
            ->get(['id', 'codigo_sankhya', 'descricao', 'venda_minima', 'venda_maxima']);
        return view('representative.dashboard', compact('condicoesPagamento'));
    }

    /**
     * General Business Intelligence Dashboard.
     */
    public function dashboard()
    {
        if (Auth::user()->isRepresentante() || Auth::user()->isSuporte()) {
            return redirect('/cotacoes');
        }
        return view('admin.dashboard');
    }

    /**
     * Chat Logs Auditor.
     */
    public function conversations()
    {
        if (!Auth::user()->hasChatAccess()) {
            return redirect('/cotacoes');
        }
        return view('admin.chat_audit');
    }

    /**
     * Quote Audit logs dedicated page.
     */
    public function quoteAudit($id)
    {
        return view('admin.quote_audit', ['id' => $id]);
    }

    /**
     * Authenticated View/Edit Quote by ID.
     */
    public function quoteDetail(Request $request, $id)
    {
        $quote = is_numeric($id) ? Cotacao::find($id) : Cotacao::where('numero', $id)->first();
        if (!$quote) {
            abort(404, 'Cotação não encontrada.');
        }
        if (!Auth::user()->canAccessQuote($quote)) {
            abort(403, 'Acesso não autorizado. Esta cotação pertence a outro representante.');
        }
        try {
            $quote->load([
                'parceiro',
                'representante.equipe.gestor',
                'itens.produto',
                'justificativas.anexos',
                'anexos',
                'historico.usuario',
                'envios.contato'
            ]);
        } catch (\Throwable $e) {
            Log::warning("Erro ao hidratar relações iniciais da cotação ID {$quote->id}: " . $e->getMessage());
        }

        $condicoesPagamento = Cache::remember('condicoes_pagamento_ativas_list', 3600, function () {
            return CondicaoPagamento::where('ativa', true)
                ->orderBy('descricao')
                ->get(['id', 'codigo_sankhya', 'descricao', 'venda_minima', 'venda_maxima']);
        });

        return view('quote.representative', [
            'quoteId' => $quote->id,
            'quote' => $quote,
            'initialQuoteJson' => $quote->toJson(),
            'condicoesPagamento' => $condicoesPagamento,
            'token' => null, // Não expõe o token secreto no HTML para usuário com sessão ativa
            'exigeAnexo' => filter_var(ParametroSistema::getVal('EXIGE_ANEXO_JUSTIFICATIVA', true), FILTER_VALIDATE_BOOLEAN),
            'regraLiberacao' => ParametroSistema::getVal('REGRA_LIBERACAO_DESCONTO', 'PRECO_MINIMO'),
        ]);
    }
}
