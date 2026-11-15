<?php

namespace App\Support;

use App\Models\AcaGrupoEscolar;
use App\Models\AcaInscripcion;
use App\Models\TrxAsistenciaClase;
use App\Models\TrxIncidencia;
use App\Models\TrxResultadoAsignatura;
use App\Models\TrxSeguimientoAlumno;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Alertas de Seguimiento (solo lectura): alumnos INSCRITO de los grupos que el usuario acompaña en $periodo con
 *  - asistencia por clase bajo el mínimo en alguna materia → INASISTENCIAS;
 *  - más materias adeudadas que regularizacion.maximo_reprobadas_para_reinscripcion → REPROBACION;
 *  - incidencias de gravedad GRAVE en el periodo → CONDUCTA.
 * Cada fila: ['inscripcion', 'motivos' => [MOTIVO => detalle], 'abiertos' => [MOTIVO => id del caso abierto visible]].
 */
class AlumnosAAtender
{
    public const MOTIVOS = ['INASISTENCIAS', 'REPROBACION', 'CONDUCTA'];

    public static function para(User $usuario, int $periodo, ?int $grupo = null, ?string $motivo = null): Collection
    {
        $grupos = AcaGrupoEscolar::acompanadosPor($usuario, $periodo)->when($grupo, fn (Builder $grupos) => $grupos->whereKey($grupo))->pluck('aca_grupo_escolar.id');
        $inscripciones = AcaInscripcion::inscritas()->whereIn('aca_grupo_escolar_id', $grupos)->with(['alumno.persona', 'grupo'])->get()->keyBy('id');
        if ($inscripciones->isEmpty()) {
            return collect();
        }
        $ids = $inscripciones->keys()->all();
        $motivos = [];

        // Asistencia por clase bajo el mínimo, por materia.
        TrxAsistenciaClase::resumen()->bajoMinimo()->whereIn('trx_asistencia_clase.aca_inscripcion_id', $ids)->with('materia.asignatura')->get()
            ->groupBy('aca_inscripcion_id')
            ->each(function (Collection $filas, $inscripcion) use (&$motivos) {
                $motivos[$inscripcion]['INASISTENCIAS'] = $filas->map(fn ($fila) => $fila->materia->asignatura->nombre.' '.TrxAsistenciaClase::formatear($fila->porcentaje))->join(', ');
            });

        // Materias adeudadas (de cualquier periodo) por encima del máximo.
        $maximo = TrxResultadoAsignatura::maximoReprobadas();
        $adeudos = TrxResultadoAsignatura::adeudosPorAlumno($inscripciones->pluck('per_alumno_id')->unique()->values()->all());
        foreach ($inscripciones as $id => $inscripcion) {
            $total = $adeudos->get($inscripcion->per_alumno_id, 0);
            if ($total > $maximo) {
                $motivos[$id]['REPROBACION'] = $total.' materias adeudadas (máximo '.$maximo.')';
            }
        }

        // Incidencias graves de la inscripción (del periodo).
        TrxIncidencia::whereIn('aca_inscripcion_id', $ids)->whereHas('tipo', fn (Builder $tipos) => $tipos->where('nivel_gravedad', 'GRAVE'))
            ->groupBy('aca_inscripcion_id')->selectRaw('aca_inscripcion_id, count(*) as total')->pluck('total', 'aca_inscripcion_id')
            ->each(function ($total, $inscripcion) use (&$motivos) {
                $motivos[$inscripcion]['CONDUCTA'] = $total.' '.((int) $total === 1 ? 'incidencia grave' : 'incidencias graves');
            });

        if ($motivo) {
            $motivos = array_filter($motivos, fn (array $delAlumno) => isset($delAlumno[$motivo]));
        }
        if ($motivos === []) {
            return collect();
        }

        // Casos abiertos que el usuario ve, por alumno y motivo (de cualquier periodo).
        $alumnos = collect(array_keys($motivos))->map(fn ($id) => $inscripciones[$id]->per_alumno_id)->unique()->values()->all();
        $abiertos = TrxSeguimientoAlumno::abiertos()->visiblesPara($usuario)->with('inscripcion:id,per_alumno_id')
            ->whereHas('inscripcion', fn (Builder $inscripciones) => $inscripciones->whereIn('per_alumno_id', $alumnos))
            ->orderBy('id')->get()
            ->groupBy(fn ($caso) => $caso->inscripcion->per_alumno_id)
            ->map(fn (Collection $casos) => $casos->mapWithKeys(fn ($caso) => [$caso->motivo => $caso->id])->all());

        return collect($motivos)->map(fn (array $delAlumno, $id) => [
            'inscripcion' => $inscripciones[$id],
            // En el orden de MOTIVOS.
            'motivos' => array_filter(array_merge(array_fill_keys(self::MOTIVOS, null), $delAlumno)),
            'abiertos' => $abiertos->get($inscripciones[$id]->per_alumno_id, []),
        ])->sortBy(fn (array $fila) => $fila['inscripcion']->grupo->identificador.' '.$fila['inscripcion']->alumno->persona->nombre_completo)->values();
    }
}
