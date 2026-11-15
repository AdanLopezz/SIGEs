<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Cierre de asistencia al final del turno vespertino (hora del plantel, config/app.php). Ver CLAUDE.md para activarlo en Windows.
Schedule::command('siges:cerrar-asistencia')->weekdays()->dailyAt('21:00')->withoutOverlapping();
// Convenios vigentes cuya fecha de fin ya pasó → VENCIDO (las pantallas ya los muestran vencidos aunque no corra: estado efectivo).
Schedule::command('siges:vencer-convenios')->dailyAt('00:10')->withoutOverlapping();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
