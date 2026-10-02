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
        // Load active items (not refused) for outputting to the PDF
        $quote->load(['parceiro', 'representante.equipe', 'itens' => function ($q) {
            $q->where('status_item', '!=', 'recusado')->with('produto');
        }]);

        $pdf = Pdf::loadView('pdf.cotacao', [
            'quote' => $quote,
            'items' => $quote->itens
        ]);

        // Customize paper dimensions and render settings
        $pdf->setPaper('a4', 'portrait')
            ->setWarnings(false)
            ->setOption('isRemoteEnabled', true);

        return $pdf;
    }
}
