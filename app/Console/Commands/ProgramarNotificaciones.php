<?php

namespace App\Console\Commands;

use App\Jobs\EnviarNotificacion;
use App\Models\ComNotificacion;
use App\Support\EnvioDeNotificaciones;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class ProgramarNotificaciones extends Command
{
    protected $signature = 'siges:programar-notificaciones';
    protected $description = 'Encola avisos de correo pendientes y rechazos reintentables';

    public function handle(EnvioDeNotificaciones $envios): int
    {
        if (! $envios->habilitado()) {
            $this->info('Envío desactivado o transporte distinto de SMTP.');
            return self::SUCCESS;
        }
        if (! Schema::hasColumn('com_notificacion', 'trx_incidencia_id')) {
            $this->error('Aplica primero la migración específica de notificaciones.');
            return self::FAILURE;
        }
        $conexion = config('queue.default');
        if (! in_array(config("queue.connections.{$conexion}.driver"), ['database', 'redis', 'sqs', 'beanstalkd'], true)) {
            $this->error('Configura una conexión de cola persistente antes de programar avisos.');
            return self::FAILURE;
        }
        $cantidad = 0;
        $avisos = ComNotificacion::where('canal', ComNotificacion::EMAIL)->whereNotNull('trx_incidencia_id')
            ->whereIn('estado', [ComNotificacion::PENDIENTE, ComNotificacion::FALLIDA])->lazyById(100);
        foreach ($avisos as $aviso) {
            if (! $envios->puedeIntentar($aviso)) {
                continue;
            }
            EnviarNotificacion::dispatch($aviso->id);
            if (++$cantidad >= max(1, (int) config('siges.notificaciones.lote'))) {
                break;
            }
        }
        $this->info("{$cantidad} avisos candidatos a la cola (los duplicados se descartan).");

        return self::SUCCESS;
    }
}
