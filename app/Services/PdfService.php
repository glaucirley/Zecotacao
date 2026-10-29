<?php

namespace App\Services;

use App\Models\Cotacao;
use Barryvdh\DomPDF\Facade\Pdf;

class PdfService
{
    /**
     * Generate PDF stream for the given quotation.
     *
     * @param Cotacao $quote
     * @return \Barryvdh\DomPDF\PDF
     */
    public function generateQuotePdf(Cotacao $quote)
    {
        // Load only approved and found items for outputting to the PDF
        $items = $quote->itens()
            ->where('status_item', 'aprovado')
            ->where(function ($q) {
                $q->where('nao_encontrado', false)->orWhereNull('nao_encontrado');
            })
            ->with('produto')
            ->get();

        if ($items->isEmpty()) {
            throw new \Exception('Esta cotação não possui itens aprovados para geração de PDF.');
        }

        $quote->load(['parceiro', 'representante.equipe']);

        // Resolve expiration dates (validade) for items missing validade
        $missingCodProds = [];
        $hasValCol = \App\Models\CotacaoItem::hasValidadeColumn();
        $hasProdValCol = \App\Models\Produto::hasValidadeProximaColumn();

        foreach ($items as $it) {
            $itVal = $hasValCol ? $it->validade : null;
            $prodVal = ($hasProdValCol && $it->produto) ? $it->produto->validade_proxima : null;
            if (!$itVal && !$prodVal) {
                if ($it->produto && $it->produto->codigo_sankhya) {
                    $missingCodProds[] = (string)$it->produto->codigo_sankhya;
                }
            }
        }

        if (!empty($missingCodProds) && $hasProdValCol) {
            try {
                $db = resolve(SankhyaDatabaseService::class);
                $rows = $db->fetchValidadesFromView($missingCodProds);
                if (!empty($rows)) {
                    $db->saveValidadesFromViewRows($rows);
                    foreach ($items as $it) {
                        if ($it->produto) {
                            $it->produto->refresh();
                        }
                    }
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("PdfService - Could not fetch live validades: " . $e->getMessage());
            }
        }

        // Snapshot validade on items if empty but present on produto
        if ($hasValCol && $hasProdValCol) {
            foreach ($items as $it) {
                if (empty($it->validade) && $it->produto && $it->produto->validade_proxima) {
                    try {
                        $it->validade = $it->produto->validade_proxima;
                        $it->save();
                    } catch (\Throwable $e) {
                        // ignore
                    }
                }
            }
        }

        // Recalculate totals based strictly on approved items to guarantee accuracy in the PDF
        $approvedSubtotal = 0;
        $approvedTotal = 0;
        $brandSummary = [];

        foreach ($items as $it) {
            $sug = (float)($it->preco_unit_sugerido ?? $it->preco_unit_proposto);
            $prop = (float)$it->preco_unit_proposto;
            $approvedSubtotal += $it->qtd * max($sug, $prop);
            $approvedTotal += (float)$it->subtotal;

            // Group summary by brand (Marca)
            $marca = trim((string)($it->produto?->marca ?? ''));
            if ($marca === '') {
                $marca = 'OUTRAS / DIVERSOS';
            }
            if (!isset($brandSummary[$marca])) {
                $brandSummary[$marca] = [
                    'marca' => $marca,
                    'qtd_itens' => 0,
                    'qtd_produtos' => 0,
                    'total' => 0.0,
                ];
            }
            $brandSummary[$marca]['qtd_itens'] += (int)$it->qtd;
            $brandSummary[$marca]['qtd_produtos'] += 1;
            $brandSummary[$marca]['total'] += (float)$it->subtotal;
        }
        $approvedDiscount = max(0, $approvedSubtotal - $approvedTotal);

        // Sort brands by total descending
        uasort($brandSummary, fn($a, $b) => $b['total'] <=> $a['total']);

        // Keep quote object in sync for the PDF template view
        $quote->subtotal = $approvedSubtotal;
        $quote->desconto = $approvedDiscount;
        $quote->total = $approvedTotal;

        $pdf = Pdf::loadView('pdf.cotacao', [
            'quote' => $quote,
            'items' => $items,
            'brandSummary' => $brandSummary,
        ]);

        // Customize paper dimensions and render settings
        $pdf->setPaper('a4', 'portrait')
            ->setWarnings(false)
            ->setOption('isRemoteEnabled', true);

        return $pdf;
    }
}
