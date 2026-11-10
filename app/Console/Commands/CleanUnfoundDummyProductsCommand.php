<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanUnfoundDummyProductsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'produtos:limpar-nao-encontrados {--dry-run : Apenas simular sem aplicar exclusões}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove produtos dummy criados para itens não encontrados da tabela produtos, mantendo a descrição na cotação';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');

        $this->info("=== Limpeza de Produtos Dummy (Itens Não Encontrados) ===");
        if ($isDryRun) {
            $this->warn("[MODO DE SIMULAÇÃO - NENHUM DADO SERÁ MODIFICADO]");
        }

        // 1. Produtos com código SEM_CODIGO_%
        $semCodigoProducts = DB::table('produtos')
            ->where('codigo_sankhya', 'like', 'SEM_CODIGO%')
            ->get(['id', 'codigo_sankhya', 'descricao']);

        // 2. Produtos inativos registrados em produtos_nao_encontrados e sem preço na tabela
        $missingInativos = DB::table('produtos')
            ->where('ativo', false)
            ->whereIn('codigo_sankhya', function ($sub) {
                $sub->select('codigo_sankhya')->from('produtos_nao_encontrados');
            })
            ->whereNotExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('tabela_precos_itens')
                    ->whereColumn('tabela_precos_itens.produto_id', 'produtos.id');
            })
            ->get(['id', 'codigo_sankhya', 'descricao']);

        $allDummyProducts = $semCodigoProducts->concat($missingInativos)->unique('id');

        $this->line("Total de produtos dummy identificados para remoção: " . $allDummyProducts->count());

        $detachedItemsCount = 0;
        $deletedProductsCount = 0;

        foreach ($allDummyProducts as $p) {
            $itensQuery = DB::table('cotacao_itens')->where('produto_id', $p->id);
            $countItens = $itensQuery->count();

            if ($countItens > 0) {
                $this->line("- Produto ID {$p->id} (Cód: {$p->codigo_sankhya}, '{$p->descricao}') vinculado a {$countItens} item(ns) de cotação.");
                if (!$isDryRun) {
                    // Preserva a descrição original se estiver vazia
                    DB::table('cotacao_itens')
                        ->where('produto_id', $p->id)
                        ->where(function ($q) {
                            $q->whereNull('descricao_original')->orWhere('descricao_original', '');
                        })
                        ->update(['descricao_original' => $p->descricao]);

                    // Desvincula o produto do item da cotação
                    DB::table('cotacao_itens')
                        ->where('produto_id', $p->id)
                        ->update([
                            'produto_id' => null,
                            'nao_encontrado' => true,
                        ]);
                }
                $detachedItemsCount += $countItens;
            }

            if (!$isDryRun) {
                DB::table('produtos')->where('id', $p->id)->delete();
            }
            $deletedProductsCount++;
        }

        $this->newLine();
        $this->info("Concluído!");
        $this->info("- Itens de cotação desvinculados preservando descrição: {$detachedItemsCount}");
        $this->info("- Produtos dummy excluídos do catálogo: {$deletedProductsCount}");

        return 0;
    }
}
