<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Cotacao;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Recalcula subtotal, desconto e total de todas as cotações que possuem itens bloqueados
     * por restrição comercial ('B') ou recusados, garantindo que o total gravado no banco
     * exclua permanentemente itens não cotáveis (como verificado na COT-120).
     */
    public function up(): void
    {
        try {
            $quotes = Cotacao::whereHas('itens', function ($q) {
                $q->where('restricao_efeito', 'B')
                  ->orWhere('status_item', 'bloqueado')
                  ->orWhere('status_item', 'recusado')
                  ->orWhere('nao_encontrado', true);
            })->get();

            $totalFixed = 0;
            foreach ($quotes as $quote) {
                $totalAntes = (float)$quote->total;
                $quote->recalcularTotais();
                $quote->refresh();
                $totalDepois = (float)$quote->total;

                if (abs($totalAntes - $totalDepois) > 0.009) {
                    $totalFixed++;
                    Log::info("Migração: Cotação {$quote->numero} recalculada. Total ajustado de R$ {$totalAntes} para R$ {$totalDepois} (excluindo itens bloqueados).");
                }
            }

            Log::info("Migração concluída: {$totalFixed} cotação(ões) com itens bloqueados foram atualizadas no banco.");
        } catch (\Throwable $e) {
            Log::warning("Falha ao recalcular totais de cotações durante migration: " . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Operação de integridade de dados; reversão não necessária
    }
};
