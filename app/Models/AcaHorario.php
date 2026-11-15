<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Una clase del horario semanal: materia de grupo, aula, día (1 = lunes … 6 = sábado), hora de inicio y de fin.
 * Reglas (guardar()): periodo preparable (activo o futuro), fin > inicio, dentro del turno del grupo, aula activa y sin
 * traslapes del mismo grupo, del mismo docente (en cualquier grupo del periodo) ni de la misma aula. La capacidad del aula
 * frente a los alumnos de la materia solo se avisa. Las capturas del mismo periodo se serializan con un candado de asesoría.
 */
class AcaHorario extends Model
{
    use Auditable;

    public const DIAS = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado'];

    public const GRUPO = 'grupo';

    public const DOCENTE = 'docente';

    public const AULA = 'aula';

    // pg_advisory_xact_lock(BLOQUEO, periodo): una captura de horario a la vez por periodo.
    private const BLOQUEO = 17424;

    protected $table = 'aca_horario';

    protected $fillable = ['aca_grupo_asignatura_id', 'org_espacio_fisico_id', 'dia_semana', 'hora_inicio', 'hora_fin'];

    protected function casts(): array
    {
        return ['aca_grupo_asignatura_id' => 'integer', 'org_espacio_fisico_id' => 'integer', 'dia_semana' => 'integer'];
    }

    public function materia(): BelongsTo
    {
        return $this->belongsTo(AcaGrupoAsignatura::class, 'aca_grupo_asignatura_id');
    }

    public function espacio(): BelongsTo
    {
        return $this->belongsTo(OrgEspacioFisico::class, 'org_espacio_fisico_id');
    }

    // HH:MM (la columna TIME llega como HH:MM:SS).
    public function getInicioAttribute(): string
    {
        return substr((string) $this->hora_inicio, 0, 5);
    }

    public function getFinAttribute(): string
    {
        return substr((string) $this->hora_fin, 0, 5);
    }

    public function getDiaNombreAttribute(): string
    {
        return self::DIAS[$this->dia_semana] ?? (string) $this->dia_semana;
    }

    public function getDiaMinusculaAttribute(): string
    {
        return mb_strtolower($this->dia_nombre);
    }

    public function getMinutosAttribute(): int
    {
        return static::aMinutos($this->fin) - static::aMinutos($this->inicio);
    }

    public static function aMinutos(string $hora): int
    {
        [$horas, $minutos] = array_map('intval', explode(':', $hora) + [0, 0]);

        return $horas * 60 + $minutos;
    }

    public static function aHora(int $minutos): string
    {
        return sprintf('%02d:%02d', intdiv($minutos, 60), $minutos % 60);
    }

    // "3 h 20 min" a partir de minutos.
    public static function duracion(int $minutos): string
    {
        $horas = intdiv($minutos, 60);
        $resto = $minutos % 60;

        return trim(($horas ? "{$horas} h" : '').($resto ? " {$resto} min" : '')) ?: '0 h';
    }

    public function scopeDelPeriodo(Builder $consulta, int $periodo): Builder
    {
        return $consulta->whereHas('materia.grupo', fn (Builder $grupos) => $grupos->where('aca_periodo_escolar_id', $periodo));
    }

    public function scopeDeGrupo(Builder $consulta, int $grupo): Builder
    {
        return $consulta->whereHas('materia', fn (Builder $materias) => $materias->where('aca_grupo_escolar_id', $grupo));
    }

    public function scopeDeDocente(Builder $consulta, int $docente): Builder
    {
        return $consulta->whereHas('materia', fn (Builder $materias) => $materias->where('docente_id', $docente));
    }

    public function scopeDeEspacio(Builder $consulta, int $espacio): Builder
    {
        return $consulta->where('org_espacio_fisico_id', $espacio);
    }

    public function scopeOrdenados(Builder $consulta): Builder
    {
        return $consulta->orderBy('dia_semana')->orderBy('hora_inicio')->orderBy('id');
    }

    public function scopeConDetalle(Builder $consulta): Builder
    {
        return $consulta->with(['materia.asignatura', 'materia.grupo', 'materia.docente.persona', 'espacio']);
    }

    /**
     * Alta o edición de una clase. Devuelve los avisos (capacidad del aula); los errores son ValidationException.
     * En una edición la clase no cambia de grupo.
     */
    public function guardar(array $datos): array
    {
        return DB::transaction(function () use ($datos) {
            $materia = AcaGrupoAsignatura::with(['grupo.turno', 'grupo.periodo', 'docente.persona'])->find($datos['aca_grupo_asignatura_id'] ?? 0)
                ?? throw ValidationException::withMessages(['aca_grupo_asignatura_id' => 'Elige una materia del grupo.']);
            if ($this->exists) {
                $actual = static::with('materia')->whereKey($this->getKey())->lockForUpdate()->firstOrFail();
                $actual->materia->grupo()->firstOrFail()->exigirPreparable();
                if ($actual->materia->aca_grupo_escolar_id !== $materia->aca_grupo_escolar_id) {
                    throw ValidationException::withMessages(['aca_grupo_asignatura_id' => 'Elige una materia del mismo grupo.']);
                }
            }
            $materia->grupo->exigirPreparable();
            DB::select('select pg_advisory_xact_lock(?, ?)', [self::BLOQUEO, $materia->grupo->aca_periodo_escolar_id]);

            $inicio = substr((string) ($datos['hora_inicio'] ?? ''), 0, 5);
            $fin = substr((string) ($datos['hora_fin'] ?? ''), 0, 5);
            $dia = (int) ($datos['dia_semana'] ?? 0);
            $espacio = OrgEspacioFisico::find($datos['org_espacio_fisico_id'] ?? 0);
            $this->validar($materia, $espacio, $dia, $inicio, $fin);

            $this->fill(['aca_grupo_asignatura_id' => $materia->id, 'org_espacio_fisico_id' => $espacio->id, 'dia_semana' => $dia,
                'hora_inicio' => $inicio, 'hora_fin' => $fin])->save();

            return array_values(array_filter([static::avisoDeCapacidad($materia, $espacio)]));
        });
    }

    public function eliminar(): void
    {
        DB::transaction(function () {
            $clase = static::with('materia.grupo')->whereKey($this->getKey())->lockForUpdate()->firstOrFail();
            $clase->materia->grupo->exigirPreparable();
            $this->delete();
        });
    }

    private function validar(AcaGrupoAsignatura $materia, ?OrgEspacioFisico $espacio, int $dia, string $inicio, string $fin): void
    {
        $errores = [];
        if (! isset(self::DIAS[$dia])) {
            $errores['dia_semana'] = 'Elige un día de lunes a sábado.';
        }
        $formato = '/^([01]\d|2[0-3]):[0-5]\d$/';
        if (! preg_match($formato, $inicio) || ! preg_match($formato, $fin)) {
            $errores['hora_inicio'] = 'Escribe las horas en formato HH:MM.';
        } elseif ($fin <= $inicio) {
            $errores['hora_fin'] = 'La hora de fin debe ser posterior a la de inicio.';
        } else {
            $turno = $materia->grupo->turno;
            $entrada = substr((string) $turno->hora_entrada, 0, 5);
            $salida = substr((string) $turno->hora_salida, 0, 5);
            if ($inicio < $entrada || $fin > $salida) {
                $errores['hora_inicio'] = "La clase debe quedar dentro del turno {$turno->nombre} ({$entrada} a {$salida}).";
            }
        }
        if (! $espacio?->activo) {
            $errores['org_espacio_fisico_id'] = 'Elige un aula activa.';
        }
        if ($errores) {
            throw ValidationException::withMessages($errores);
        }
        $choque = static::traslape($materia, $materia->docente_id, $espacio->id, $dia, $inicio, $fin, $this->id);
        if ($choque) {
            throw ValidationException::withMessages(['hora_inicio' => $choque]);
        }
    }

    /**
     * Primer traslape (en este orden: grupo, docente, aula) con otra clase del mismo periodo el mismo día, o null.
     * Dos clases se traslapan si una empieza antes de que termine la otra (terminar a las 8:00 y empezar a las 8:00 no choca).
     */
    public static function traslape(AcaGrupoAsignatura $materia, ?int $docente, ?int $espacio, int $dia, string $inicio, string $fin,
        ?int $excepto = null, array $revisar = [self::GRUPO, self::DOCENTE, self::AULA]): ?string
    {
        $choques = static::conDetalle()->delPeriodo($materia->grupo->aca_periodo_escolar_id)
            ->where('dia_semana', $dia)->where('hora_inicio', '<', $fin)->where('hora_fin', '>', $inicio)
            ->when($excepto, fn (Builder $clases) => $clases->whereKeyNot($excepto))
            ->ordenados()->get();
        $reglas = [
            self::GRUPO => fn (self $clase) => $clase->materia->aca_grupo_escolar_id === $materia->aca_grupo_escolar_id
                ? "El grupo {$clase->materia->grupo->identificador} ya tiene {$clase->materia->asignatura->nombre} el {$clase->dia_minuscula} de {$clase->inicio} a {$clase->fin}."
                : null,
            self::DOCENTE => fn (self $clase) => $docente && $clase->materia->docente_id === $docente
                ? 'El docente '.$clase->materia->docente->nombre." ya tiene clase en {$clase->materia->grupo->identificador} ({$clase->materia->asignatura->nombre}) el {$clase->dia_minuscula} de {$clase->inicio} a {$clase->fin}."
                : null,
            self::AULA => fn (self $clase) => $espacio && $clase->org_espacio_fisico_id === $espacio
                ? "El aula {$clase->espacio->nombre} ya está ocupada por {$clase->materia->grupo->identificador} ({$clase->materia->asignatura->nombre}) el {$clase->dia_minuscula} de {$clase->inicio} a {$clase->fin}."
                : null,
        ];
        foreach ($revisar as $tipo) {
            foreach ($choques as $clase) {
                if ($mensaje = $reglas[$tipo]($clase)) {
                    return $mensaje;
                }
            }
        }

        return null;
    }

    // Al cambiar el docente de una materia: el nuevo no debe tener otra clase a la misma hora en el periodo.
    public static function exigirDocenteLibre(AcaGrupoAsignatura $materia, int $docente): void
    {
        $materia->loadMissing('grupo');
        foreach (static::where('aca_grupo_asignatura_id', $materia->id)->ordenados()->get() as $clase) {
            $choque = static::traslape($materia, $docente, null, $clase->dia_semana, $clase->inicio, $clase->fin, $clase->id, [self::DOCENTE]);
            if ($choque) {
                throw ValidationException::withMessages(['docente_id' => $choque]);
            }
        }
    }

    // Solo aviso: el aula no alcanza para los inscritos y recursantes de la materia.
    public static function avisoDeCapacidad(AcaGrupoAsignatura $materia, OrgEspacioFisico $espacio): ?string
    {
        $alumnos = count($materia->inscripcionesCalificables());

        return $alumnos > $espacio->capacidad
            ? "El aula {$espacio->nombre} tiene capacidad para {$espacio->capacidad} y la materia tiene {$alumnos} alumnos (inscritos y recursantes)."
            : null;
    }

    /**
     * Copia el horario de $origen (mismo semestre y carrera, de cualquier periodo) a $destino, que no debe tener horario.
     * Cada clase va a la materia del destino con la misma asignatura y conserva su aula (o usa $espacio, si se elige uno).
     * Se omiten, con su motivo, las clases sin materia en el destino o que no pasan las validaciones (turno, traslapes, aula).
     * Devuelve ['copiadas' => n, 'omitidas' => [texto, …]].
     */
    public static function copiar(AcaGrupoEscolar $origen, AcaGrupoEscolar $destino, ?int $espacio = null): array
    {
        return DB::transaction(function () use ($origen, $destino, $espacio) {
            $destino->exigirPreparable();
            DB::select('select pg_advisory_xact_lock(?, ?)', [self::BLOQUEO, $destino->aca_periodo_escolar_id]);
            if ($origen->is($destino)) {
                throw ValidationException::withMessages(['origen' => 'Elige otro grupo como origen.']);
            }
            if ($origen->semestre !== $destino->semestre || $origen->aca_carrera_id !== $destino->aca_carrera_id) {
                throw ValidationException::withMessages(['origen' => 'El grupo origen debe ser del mismo semestre y carrera.']);
            }
            if (static::deGrupo($destino->id)->exists()) {
                throw ValidationException::withMessages(['origen' => "El grupo {$destino->identificador} ya tiene horario: solo se copia a un grupo sin horario."]);
            }
            $materias = $destino->materias()->get()->keyBy('aca_asignatura_id');
            $copiadas = 0;
            $omitidas = [];
            foreach (static::conDetalle()->deGrupo($origen->id)->ordenados()->get() as $clase) {
                $etiqueta = "{$clase->materia->asignatura->nombre} · {$clase->dia_nombre} {$clase->inicio} a {$clase->fin}";
                $materia = $materias->get($clase->materia->aca_asignatura_id);
                if (! $materia) {
                    $omitidas[] = "{$etiqueta}: el grupo {$destino->identificador} no tiene esta materia.";

                    continue;
                }
                try {
                    (new static)->guardar(['aca_grupo_asignatura_id' => $materia->id, 'org_espacio_fisico_id' => $espacio ?? $clase->org_espacio_fisico_id,
                        'dia_semana' => $clase->dia_semana, 'hora_inicio' => $clase->inicio, 'hora_fin' => $clase->fin]);
                    $copiadas++;
                } catch (ValidationException $excepcion) {
                    $omitidas[] = "{$etiqueta}: ".collect($excepcion->errors())->flatten()->first();
                }
            }

            return ['copiadas' => $copiadas, 'omitidas' => $omitidas];
        });
    }
}
