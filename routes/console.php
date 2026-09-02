<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\ParametroSistema;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schema;

// Dynamic automatic synchronization scheduling with Sankhya
if (Schema::hasTable('parametros_sistema')) {
    $autoSync = ParametroSistema::getVal('SANKHYA_SYNC_AUTO', false);

    if ($autoSync) {
        $interval = ParametroSistema::getVal('SANKHYA_SYNC_INTERVALO', 'DIARIO');
        
        // Command schedule configuration
        $scheduleEvent = Schedule::command('sankhya:sync');

        if ($interval === 'HORARIO') {
            $scheduleEvent->hourly();
        } elseif ($interval === 'CADA_6_HORAS') {
            $scheduleEvent->everySixHours();
        } elseif ($interval === 'CADA_12_HORAS') {
            $scheduleEvent->everyTwelveHours();
        } else {
            // Default: Daily at 02:00 AM
            $scheduleEvent->dailyAt('02:00');
        }
    }
}
