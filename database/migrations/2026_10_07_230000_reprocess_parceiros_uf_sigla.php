<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Parceiro;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Update numeric UFs to standard Brazilian state acronyms in parceiros table
        $sankhyaMap = [
            '1'  => 'SP',
            '2'  => 'MG',
            '3'  => 'RJ',
            '4'  => 'BA',
            '5'  => 'RS',
            '6'  => 'PR',
            '7'  => 'PE',
            '8'  => 'CE',
            '9'  => 'SC',
            '10' => 'GO',
            '11' => 'MA',
            '12' => 'PB',
            '13' => 'ES',
            '14' => 'PA',
            '15' => 'RN',
            '16' => 'AL',
            '17' => 'PI',
            '18' => 'MT',
            '19' => 'DF',
            '20' => 'MS',
            '21' => 'SE',
            '22' => 'AM',
            '23' => 'RO',
            '24' => 'AC',
            '25' => 'AP',
            '26' => 'RR',
            '27' => 'TO',
            '28' => 'SE',
            '29' => 'BA',
            '31' => 'MG',
            '32' => 'ES',
            '33' => 'RJ',
            '35' => 'SP',
            '41' => 'PR',
            '42' => 'SC',
            '43' => 'RS',
            '50' => 'MS',
            '51' => 'MT',
            '52' => 'GO',
            '53' => 'DF',
        ];

        // Specific fix for UBERLANDIA (always MG)
        DB::statement("UPDATE parceiros SET uf = 'MG' WHERE UPPER(TRIM(cidade)) = 'UBERLANDIA'");

        foreach ($sankhyaMap as $code => $uf) {
            DB::statement("UPDATE parceiros SET uf = ? WHERE uf = ?", [$uf, (string)$code]);
            DB::statement("UPDATE parceiros SET endereco = REPLACE(endereco, ?, ?) WHERE endereco LIKE ?", [
                '/' . $code,
                '/' . $uf,
                '%/' . $code . '%'
            ]);
            DB::statement("UPDATE parceiros SET endereco = REPLACE(endereco, ?, ?) WHERE endereco LIKE ?", [
                '- ' . $code,
                '- ' . $uf,
                '%- ' . $code . '%'
            ]);
        }

        // Fix any remaining references to '/2' or '- 2' in endereco
        DB::statement("UPDATE parceiros SET endereco = REPLACE(endereco, '/2', '/MG') WHERE endereco LIKE '%/2%'");
        DB::statement("UPDATE parceiros SET endereco = REPLACE(endereco, '- 2', '- MG') WHERE endereco LIKE '%- 2%'");

        // 2. Reprocess partners via Eloquent service
        try {
            $service = new \App\Services\SankhyaDatabaseService();
            $service->reprocessExistingPartnersUf();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Migration reprocessExistingPartnersUf warning: " . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reversal needed as standardizing to UF acronyms is permanent
    }
};
