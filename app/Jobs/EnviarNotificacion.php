<?php

namespace App\Jobs;

use App\Support\EnvioDeNotificaciones;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class EnviarNotificacion implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    public int $tries = 1;
    public int $timeout = 60;
    public int $uniqueFor = 300;

    public function __construct(public readonly int $notificacionId)
    {
        $this->onQueue(config('siges.notificaciones.cola'));
        $this->afterCommit();
    }

    public function uniqueId(): string
    {
        return (string) $this->notificacionId;
    }

    public function handle(EnvioDeNotificaciones $envios): void
    {
        $envios->enviar($this->notificacionId);
    }
}
