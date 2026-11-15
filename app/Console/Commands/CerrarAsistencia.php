<?php

namespace App\Console\Commands;

use App\Models\TrxAsistenciaDiaria;
use App\Support\Auditoria;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class CerrarAsistencia extends Command
{
    protected $signature = 'siges:cerrar-asistencia
                            {--fecha= : Día a cerrar (AAAA-MM-DD); por omisión, hoy}';

    protected $description = 'Registra FALTA a cada alumno inscrito sin asistencia en el día (idempotente)';

    public function handle(): int
    {
        $texto = $this->option('fecha') ?: today()->toDateString();
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $texto) || ! Carbon::canBeCreatedFromFormat($texto, 'Y-m-d')) {
            $this->error("La fecha «{$texto}» no tiene el formato AAAA-MM-DD.");

            return self::FAILURE;
        }
        $fecha = Carbon::createFromFormat('Y-m-d', $texto)->startOfDay();
        if ($fecha->isFuture()) {
            $this->error('No se puede cerrar un día futuro.');

            return self::FAILURE;
        }
        if (! TrxAsistenciaDiaria::esDiaHabil($fecha)) {
            $this->info("El {$fecha->format('d/m/Y')} no es día hábil (fin de semana, día inhábil o fuera del periodo activo): no se hizo nada.");

            return self::SUCCESS;
        }
        Auditoria::motivoPorDefecto('Cierre automático de asistencia del día');
        $faltas = TrxAsistenciaDiaria::cerrarDia($fecha);
        $this->info("Día {$fecha->format('d/m/Y')} cerrado: {$faltas} ".($faltas === 1 ? 'registro creado' : 'registros creados').' (falta, o justificada si una incidencia cubre el día).');

        return self::SUCCESS;
    }
}
