<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Support\Auditoria;
use App\Support\RevisionEgreso;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class TrxEgreso extends Model
{
    use Auditable;

    protected $table = 'trx_egreso';
    protected $fillable = [];

    protected function casts(): array
    {
        return ['fecha_egreso' => 'date', 'promedio_general' => 'decimal:2',
            'certificado_entregado' => 'boolean', 'titulado' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::deleting(fn () => throw new LogicException('Los egresos se conservan como historial.'));
    }

    public function alumno(): BelongsTo
    {
        return $this->belongsTo(PerAlumno::class, 'per_alumno_id');
    }

    public static function registrar(PerAlumno $alumno, CarbonInterface $fecha, string $promedio, string $motivo, User $usuario): self
    {
        Gate::forUser($usuario)->authorize('egreso-confirmar');

        return DB::transaction(function () use ($alumno, $fecha, $promedio, $motivo) {
            $alumno->bloquear();
            if (! $alumno->estaActivo() || $alumno->egreso()->exists()) {
                throw ValidationException::withMessages(['accion' => 'El alumno debe estar activo y no tener un egreso registrado.']);
            }
            $inscripciones = $alumno->inscripciones()->orderBy('id')->lockForUpdate()->get();
            $materias = AcaGrupoAsignatura::whereIn('aca_grupo_escolar_id', $inscripciones->pluck('aca_grupo_escolar_id'))->pluck('id');
            $destinos = AcaRecursamiento::whereHas('origen', fn ($r) => $r->whereIn('aca_inscripcion_id', $inscripciones->pluck('id')))->pluck('aca_grupo_asignatura_id');
            // Mismo bloqueo que captura/cierre de calificaciones, antes de revisar los resultados.
            AcaEvaluacion::whereIn('aca_grupo_asignatura_id', $materias->merge($destinos)->unique())->orderBy('id')->lockForUpdate()->get();
            $resultados = TrxResultadoAsignatura::whereIn('aca_inscripcion_id', $inscripciones->pluck('id'))->orderBy('id')->lockForUpdate()->get();
            $revision = RevisionEgreso::academica($alumno);
            $final = $revision['inscripcion_final'];
            if (! $final || $final->estado !== AcaInscripcion::INSCRITO || $revision['pendientes']->isNotEmpty()) {
                throw ValidationException::withMessages(['accion' => 'Se requiere inscripción de último semestre y no tener materias pendientes ni evaluaciones sin cerrar.']);
            }
            $posteriores = $alumno->inscripciones()->inscritas()->whereHas('grupo.periodo', fn ($p) => $p->where('fecha_inicio', '>', $final->grupo->periodo->fecha_inicio))->exists();
            if ($posteriores) {
                throw ValidationException::withMessages(['accion' => 'Hay una inscripción posterior al último semestre revisado. Resuelve esa inscripción antes de registrar el egreso.']);
            }
            $minima = collect([$final->fecha_inscripcion->format('Y-m-d'), $final->grupo->periodo->fecha_inicio->format('Y-m-d')])
                ->merge($resultados->map(fn ($r) => $r->fecha_cierre->format('Y-m-d')))->max();
            static::validarFechaYPromedio($fecha, $promedio);
            if ($fecha->format('Y-m-d') < $minima) {
                throw ValidationException::withMessages(['fecha_egreso' => 'La fecha no puede ser anterior a la inscripción ni al cierre de los resultados ('.$minima.').']);
            }

            return Auditoria::conMotivo($motivo, function () use ($alumno, $fecha, $promedio, $final) {
                $egreso = new static;
                $egreso->forceFill(['per_alumno_id' => $alumno->id, 'fecha_egreso' => $fecha->format('Y-m-d'),
                    'promedio_general' => $promedio, 'certificado_entregado' => false, 'titulado' => false])->save();
                $final->update(['estado' => AcaInscripcion::EGRESADO]);
                $alumno->forceFill(['cat_estado_alumno_id' => CatEstadoAlumno::porNombre(CatEstadoAlumno::EGRESADO)->id])->save();
                $alumno->credenciales()->activas()->lockForUpdate()->get()->each->desactivar();

                return $egreso;
            });
        });
    }

    public function actualizarDocumentacion(bool $certificado, bool $titulado, string $motivo, User $usuario): void
    {
        Gate::forUser($usuario)->authorize('egreso-confirmar');
        DB::transaction(function () use ($certificado, $titulado, $motivo) {
            $actual = static::whereKey($this->id)->lockForUpdate()->firstOrFail();
            Auditoria::conMotivo($motivo, fn () => $actual->forceFill(['certificado_entregado' => $certificado, 'titulado' => $titulado])->save());
        });
    }

    private static function validarFechaYPromedio(CarbonInterface $fecha, string $promedio): void
    {
        if ($fecha->startOfDay()->gt(today())) {
            throw ValidationException::withMessages(['fecha_egreso' => 'La fecha de egreso no puede ser futura.']);
        }
        if (! preg_match('/^(?:[0-9](?:\.[0-9]{1,2})?|10(?:\.0{1,2})?)$/', $promedio)) {
            throw ValidationException::withMessages(['promedio_general' => 'Captura un promedio entre 0 y 10, con hasta dos decimales, según el historial oficial.']);
        }
    }
}
