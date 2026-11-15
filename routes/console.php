<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Cierre de asistencia al final del turno vespertino (hora del plantel, config/app.php). Ver CLAUDE.md para activarlo en Windows.
Schedule::command('siges:cerrar-asistencia')->weekdays()->dailyAt('21:00')->withoutOverlapping();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
