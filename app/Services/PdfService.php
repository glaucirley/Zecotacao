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
        // Load only approved items for outputting to the PDF
        $items = $quote->itens()
            ->where('status_item', 'aprovado')
            ->with('produto')
            ->get();

        if ($items->isEmpty()) {
            throw new \Exception('Esta cotação não possui itens aprovados para geração de PDF.');
        }

        $quote->load(['parceiro', 'representante.equipe']);

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
