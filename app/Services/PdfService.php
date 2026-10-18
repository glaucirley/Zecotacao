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
        foreach ($items as $it) {
            if (!$it->validade && (!$it->produto || !$it->produto->validade_proxima)) {
                if ($it->produto && $it->produto->codigo_sankhya) {
                    $missingCodProds[] = (string)$it->produto->codigo_sankhya;
                }
            }
        }

        if (!empty($missingCodProds)) {
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

        // Recalculate totals based strictly on approved items to guarantee accuracy in the PDF
        $approvedSubtotal = 0;
        $approvedTotal = 0;
        foreach ($items as $it) {
            $sug = (float)($it->preco_unit_sugerido ?? $it->preco_unit_proposto);
            $prop = (float)$it->preco_unit_proposto;
            $approvedSubtotal += $it->qtd * max($sug, $prop);
            $approvedTotal += (float)$it->subtotal;
        }
        $approvedDiscount = max(0, $approvedSubtotal - $approvedTotal);

        // Keep quote object in sync for the PDF template view
        $quote->subtotal = $approvedSubtotal;
        $quote->desconto = $approvedDiscount;
        $quote->total = $approvedTotal;

        $pdf = Pdf::loadView('pdf.cotacao', [
            'quote' => $quote,
            'items' => $items
        ]);

        // Customize paper dimensions and render settings
        $pdf->setPaper('a4', 'portrait')
            ->setWarnings(false)
            ->setOption('isRemoteEnabled', true);

        return $pdf;
    }
}
