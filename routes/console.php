<?php

use App\Actions\SyncOverdueLoansAction;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('loans:sync-overdue', function (SyncOverdueLoansAction $action) {
    $vencidos = $action->execute();
    $this->info("Préstamos vencidos sincronizados: {$vencidos}");
})->purpose('Marca como vencidos los préstamos activos cuya fecha límite ya expiró');

Schedule::command('loans:sync-overdue')->hourly();
