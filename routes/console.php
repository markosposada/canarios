<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('audios:purge --hours=48')
    ->dailyAt('03:00');

Schedule::command('canarios:auto-facturar --days=3')
    ->dailyAt('02:00')
    ->timezone('America/Bogota')
    ->withoutOverlapping();