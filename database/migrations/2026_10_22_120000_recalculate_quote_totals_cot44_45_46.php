<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Models\Cotacao;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('cotacoes') || !Schema::hasTable('cotacao_itens')) {
            return;
        }

        // 1. Especificamente cotações COT-044, COT-045, COT-046 e variações
        $targetQuotes = Cotacao::where(function ($q) {
            $q->where('numero', 'like', '%000044%')
              ->orWhere('numero', 'like', '%000045%')
              ->orWhere('numero', 'like', '%000046%')
              ->orWhere('numero', 'like', '%-044%')
              ->orWhere('numero', 'like', '%-045%')
              ->orWhere('numero', 'like', '%-046%')
              ->orWhere('numero', 'like', '%COT-044%')
              ->orWhere('numero', 'like', '%COT-045%')
              ->orWhere('numero', 'like', '%COT-046%');
        })->get();

        foreach ($targetQuotes as $quote) {
            $quote->recalcularTotais();
        }

        // 2. Além disso, varre todas as cotações que possuem itens e recalcula caso os totais estejam divergentes
        $allQuotesWithItems = Cotacao::has('itens')->with('itens')->get();
        foreach ($allQuotesWithItems as $quote) {
            $validItens = $quote->itens->filter(function ($item) {
                return $item->status_item !== 'recusado' && !$item->nao_encontrado;
            });

            if ($validItens->isEmpty()) {
                continue;
            }

            $itemsSum = (float) $validItens->sum('subtotal');
            if (abs((float)$quote->total - $itemsSum) > 0.05) {
                $quote->recalcularTotais();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Operação de ajuste idempotente de dados. Não requer reversão.
    }
};
