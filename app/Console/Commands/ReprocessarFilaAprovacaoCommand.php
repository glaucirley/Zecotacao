<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Cotacao;
use App\Models\CotacaoItem;
use App\Models\CotacaoHistorico;
use App\Models\Notificacao;
use Illuminate\Support\Facades\DB;

class ReprocessarFilaAprovacaoCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cotacoes:reavaliar-fila 
                            {--devolver : Devolve as cotacoes da fila ao representante como DEVOLVIDA sem aprovar} 
                            {--cancelar : Cancela as cotacoes da fila marcando-as como PERDIDA} 
                            {--id= : Processa apenas uma cotacao especifica pelo ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reavalia a fila de aprovação (AGUARDANDO_GESTOR/COM_DIRETOR) aplicando a regra de preço mínimo, ou devolve/cancela cotações pendentes.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $id = $this->option('id');
        $devolver = $this->option('devolver');
        $cancelar = $this->option('cancelar');

        $query = Cotacao::with(['itens.produto', 'representante'])
            ->whereIn('status', ['AGUARDANDO_GESTOR', 'COM_DIRETOR']);

        if (!empty($id)) {
            $query->where('id', $id);
        }

        $quotes = $query->get();

        if ($quotes->isEmpty()) {
            $this->info("Nenhuma cotação pendente de aprovação encontrada.");
            return Command::SUCCESS;
        }

        $this->info("Encontrada(s) {$quotes->count()} cotação(ões) na fila de aprovação.");

        $autoAprovadas = 0;
        $devolvidas = 0;
        $canceladas = 0;
        $mantidas = 0;

        foreach ($quotes as $quote) {
            DB::beginTransaction();
            try {
                if ($cancelar) {
                    $quote->update(['status' => 'PERDIDA']);
                    CotacaoHistorico::create([
                        'cotacao_id' => $quote->id,
                        'evento' => 'COTACAO_CANCELADA',
                        'usuario_id' => null,
                        'papel' => 'sistema',
                        'condicao' => 'Cotação cancelada e removida da fila de aprovação via comando.',
                    ]);
                    $canceladas++;
                    $this->line("• Cotação #{$quote->id} ({$quote->numero}) cancelada (PERDIDA).");
                } elseif ($devolver) {
                    $quote->update(['status' => 'DEVOLVIDA']);
                    CotacaoItem::where('cotacao_id', $quote->id)->update(['status_item' => 'pendente']);
                    CotacaoHistorico::create([
                        'cotacao_id' => $quote->id,
                        'evento' => 'DEVOLVIDA_AO_REPRESENTANTE',
                        'usuario_id' => null,
                        'papel' => 'sistema',
                        'condicao' => 'Cotação devolvida ao representante para liberação com a nova regra de preço mínimo.',
                    ]);
                    if ($quote->representante_id) {
                        Notificacao::create([
                            'usuario_id' => $quote->representante_id,
                            'titulo' => 'Cotação Devolvida para Ajuste',
                            'mensagem' => "A cotação {$quote->numero} foi devolvida ao seu painel para envio.",
                            'link' => url("/cotacoes/id/{$quote->id}"),
                            'lida' => false,
                        ]);
                    }
                    $devolvidas++;
                    $this->line("• Cotação #{$quote->id} ({$quote->numero}) devolvida ao representante.");
                } else {
                    // Modo Padrão: Reavaliar com a regra de Preço Mínimo
                    $hasItemsBelowMin = false;
                    foreach ($quote->itens as $item) {
                        if ($item->isBloqueado()) {
                            continue;
                        }
                        $suggested = round((float)$item->preco_unit_sugerido, 2);
                        $minPrice = round((float)$item->preco_minimo, 2);
                        $proposedPrice = round((float)$item->preco_unit_proposto, 2);
                        $isInconsistent = ($suggested > 0 && $minPrice > $suggested);
                        $effectiveMin = $isInconsistent ? $suggested : $minPrice;

                        if ($proposedPrice < ($effectiveMin - 0.005)) {
                            $hasItemsBelowMin = true;
                            break;
                        }
                    }

                    if (!$hasItemsBelowMin) {
                        // Todos os itens respeitam o mínimo: aprova automaticamente!
                        $quote->update(['status' => 'APROVADA']);
                        foreach ($quote->itens as $item) {
                            if ($item->status_item !== 'aprovado') {
                                $item->update(['status_item' => 'aprovado']);
                            }
                        }

                        CotacaoHistorico::create([
                            'cotacao_id' => $quote->id,
                            'evento' => 'LIBERADA_AUTOMATICAMENTE',
                            'usuario_id' => null,
                            'papel' => 'sistema',
                            'condicao' => 'Liberada automaticamente: todos os itens respeitam o preço mínimo (reprocessamento de fila).',
                        ]);

                        if ($quote->representante_id) {
                            Notificacao::create([
                                'usuario_id' => $quote->representante_id,
                                'titulo' => 'Cotação Aprovada!',
                                'mensagem' => "A cotação {$quote->numero} foi aprovada automaticamente no preço mínimo e está pronta para envio.",
                                'link' => url("/cotacoes/id/{$quote->id}"),
                                'lida' => false,
                            ]);
                        }

                        $autoAprovadas++;
                        $this->info("✓ Cotação #{$quote->id} ({$quote->numero}) respeita o preço mínimo -> APROVADA automaticamente!");
                    } else {
                        $mantidas++;
                        $this->warn("⚠️ Cotação #{$quote->id} ({$quote->numero}) possui item abaixo do preço mínimo e foi mantida na fila.");
                    }
                }
                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                $this->error("Erro ao processar cotação #{$quote->id}: " . $e->getMessage());
            }
        }

        $this->newLine();
        $this->info("=== Resumo do Processamento ===");
        if ($cancelar) {
            $this->info("Canceladas: {$canceladas}");
        } elseif ($devolver) {
            $this->info("Devolvidas: {$devolvidas}");
        } else {
            $this->info("Aprovadas automaticamente (Preço Mínimo): {$autoAprovadas}");
            $this->info("Mantidas na fila (Abaixo do Mínimo): {$mantidas}");
        }

        return Command::SUCCESS;
    }
}
