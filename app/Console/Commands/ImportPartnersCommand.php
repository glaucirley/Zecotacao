<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PartnerImportService;

class ImportPartnersCommand extends Command
{
    protected $signature = 'clientes:importar {arquivo : Caminho completo ou relativo para o arquivo CSV}';

    protected $description = 'Importa clientes/parceiros em massa a partir de um arquivo CSV exportado do Sankhya';

    public function handle(PartnerImportService $importService)
    {
        $filePath = $this->argument('arquivo');

        if (!file_exists($filePath)) {
            $filePath = base_path($filePath);
        }

        if (!file_exists($filePath)) {
            $this->error("Arquivo não encontrado: {$filePath}");
            return Command::FAILURE;
        }

        $this->info("Iniciando importação de clientes a partir de: {$filePath}...");

        try {
            $res = $importService->importFromCsv($filePath);

            $this->info("Importação concluída com sucesso!");
            $this->line(" - Total de registros processados: " . $res['total']);
            $this->line(" - Novos clientes cadastrados:     " . $res['criados']);
            $this->line(" - Clientes atualizados:           " . $res['atualizados']);

            if (!empty($res['erros'])) {
                $this->warn("Avisos / Linhas ignoradas:");
                foreach ($res['erros'] as $err) {
                    $this->line("   * {$err}");
                }
            }

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Falha ao importar clientes: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
