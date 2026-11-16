<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Support\Auditoria;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;

class TrxProcesoEgreso extends Model
{
    use Auditable;

    public const TIPOS = ['SERVICIO_SOCIAL' => 'Servicio social', 'PRACTICAS_PROFESIONALES' => 'Prácticas profesionales'];
    public const ESTADOS = ['EN_PROCESO' => 'En proceso', 'CONCLUIDO' => 'Concluido', 'CANCELADO' => 'Cancelado'];

    protected $table = 'trx_proceso_egreso';
    protected $fillable = ['tipo', 'org_convenio_id', 'estado', 'horas_acreditadas', 'fecha_inicio', 'fecha_fin'];

    protected function casts(): array
    {
        return ['fecha_inicio' => 'date', 'fecha_fin' => 'date', 'horas_acreditadas' => 'integer'];
    }

    protected static function booted(): void
    {
        static::deleting(fn () => throw new LogicException('Los procesos de egreso se conservan como historial.'));
    }

    public function alumno(): BelongsTo
    {
        return $this->belongsTo(PerAlumno::class, 'per_alumno_id');
    }

    public function convenio(): BelongsTo
    {
        return $this->belongsTo(OrgConvenio::class, 'org_convenio_id');
    }

    public static function guardarPara(PerAlumno $alumno, array $datos): self
    {
        return DB::transaction(function () use ($alumno, $datos) {
            $alumno->bloquear();
            if (! $alumno->estaActivo() && $alumno->estado->nombre !== CatEstadoAlumno::EGRESADO) {
                throw ValidationException::withMessages(['accion' => 'Solo se gestionan procesos de alumnos activos o egresados.']);
            }
            $convenio = OrgConvenio::whereKey($datos['org_convenio_id'])->lockForUpdate()->firstOrFail();
            $inicio = Carbon::parse($datos['fecha_inicio']);
            $fin = Carbon::parse($datos['fecha_fin']);
            if ($fin->lt($inicio)) {
                throw ValidationException::withMessages(['fecha_fin' => 'La fecha de fin no puede ser anterior al inicio.']);
            }
            if ($convenio->estado === OrgConvenio::CANCELADO || ! in_array($convenio->tipo, [$datos['tipo'], 'GENERAL'], true)
                || $inicio->lt($convenio->fecha_inicio) || $fin->gt($convenio->fecha_fin)) {
                throw ValidationException::withMessages(['org_convenio_id' => 'El convenio debe corresponder al proceso, no estar cancelado y cubrir sus fechas.']);
            }
            if ($datos['estado'] === 'CONCLUIDO' && ($fin->gt(today()) || (int) $datos['horas_acreditadas'] < 1)) {
                throw ValidationException::withMessages(['estado' => 'Para concluir registra horas acreditadas y una fecha de fin que no sea futura.']);
            }
            return Auditoria::conMotivo($datos['motivo'], function () use ($alumno, $datos) {
                $proceso = static::where('per_alumno_id', $alumno->id)->where('tipo', $datos['tipo'])->first() ?? new static;
                $proceso->forceFill(['per_alumno_id' => $alumno->id]);
                $proceso->fill(Arr::only($datos, ['tipo', 'org_convenio_id', 'estado', 'horas_acreditadas', 'fecha_inicio', 'fecha_fin']));
                $proceso->save();

                return $proceso;
            });
        });
    }
}
