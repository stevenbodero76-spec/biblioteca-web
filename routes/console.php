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
    $this->info("Préstamos vencidos: {$vencidos} | Reservas/órdenes impagas liberadas: {$action->lastExpiredReleased}");
})->purpose('Sincroniza préstamos vencidos y libera stock de reservas/órdenes impagas tras 24h');

Schedule::command('loans:sync-overdue')->hourly();
