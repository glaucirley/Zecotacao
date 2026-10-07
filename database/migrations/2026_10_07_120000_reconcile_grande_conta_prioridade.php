<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\ParametroSistema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $valorLimite = (float) ParametroSistema::getVal('ALCADA_GRANDE_CONTA_VALOR', 10000.00);
        $qtdLimite = (int) ParametroSistema::getVal('ALCADA_GRANDE_CONTA_QTD', 100);

        // Remove o selo de Grande Conta (prioridade) de qualquer cotação que esteja abaixo dos limites de valor e quantidade
        $cotacoesPrioritarias = DB::table('cotacoes')
            ->where('prioridade', true)
            ->where('total', '<', $valorLimite)
            ->get();

        foreach ($cotacoesPrioritarias as $c) {
            $totalQtd = DB::table('cotacao_itens')
                ->where('cotacao_id', $c->id)
                ->where('status_item', '!=', 'recusado')
                ->sum('qtd');

            if ($totalQtd < $qtdLimite) {
                DB::table('cotacoes')
                    ->where('id', $c->id)
                    ->update(['prioridade' => false]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reversal needed
    }
};
