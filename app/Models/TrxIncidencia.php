<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Policies\IncidenciaPolicy;
use App\Support\Auditoria;
use App\Support\PeriodoDeTrabajo;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

// Excepción documentada: fecha_fin, archivo_id y los datos de resolución son opcionales. No se borra.
#[UsePolicy(IncidenciaPolicy::class)]
class TrxIncidencia extends Model
{
    use Auditable;

    public const ABIERTA = 'ABIERTA';

    public const EN_SEGUIMIENTO = 'EN_SEGUIMIENTO';

    public const CERRADA = 'CERRADA';

    public const ESTADOS = [self::ABIERTA => 'Abierta', self::EN_SEGUIMIENTO => 'En seguimiento', self::CERRADA => 'Cerrada'];

    // Ven todas las incidencias; los demás, las de alumnos de sus grupos y las que reportaron.
    public const ROLES_VEN_TODAS = ['Administrador', 'Director', 'Subdirector', 'Prefectura'];

    public const DIAS_ATRAS_MAXIMO = 30;

    protected $table = 'trx_incidencia';

    protected $fillable = ['cat_tipo_incidencia_id', 'descripcion', 'fecha_inicio', 'fecha_fin'];

    protected function casts(): array
    {
        return ['fecha_inicio' => 'date', 'fecha_fin' => 'date', 'fecha_resolucion' => 'date', 'cat_tipo_incidencia_id' => 'integer'];
    }

    public function inscripcion(): BelongsTo
    {
        return $this->belongsTo(AcaInscripcion::class, 'aca_inscripcion_id');
    }

    public function alumno(): BelongsTo
    {
        return $this->belongsTo(PerAlumno::class, 'per_alumno_id');
    }

    public function tipo(): BelongsTo
    {
        return $this->belongsTo(CatTipoIncidencia::class, 'cat_tipo_incidencia_id');
    }

    public function documento(): BelongsTo
    {
        return $this->belongsTo(Archivo::class, 'archivo_id');
    }

    public function reportadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reportado_por_user_id');
    }

    public function resueltaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resuelta_por_user_id');
    }

    /**
     * Alumnos con inscripción INSCRITO vigente en grupos que el usuario puede ver
     * (orientador, tutor o docente; todos para ROLES_VEN_TODAS).
     */
    public static function alumnosElegibles(User $usuario): Builder
    {
        $todos = $usuario->hasAnyRole(self::ROLES_VEN_TODAS);

        return PerAlumno::query()->whereHas('inscripciones', fn (Builder $inscripciones) => $inscripciones->inscritas()->delPeriodoActivo()
            ->when(! $todos, fn (Builder $inscripciones) => $inscripciones->whereHas('grupo', fn (Builder $grupos) => $grupos->visiblesEnAsistencia($usuario))));
    }

    public function scopeVisiblesPara(Builder $consulta, User $usuario): Builder
    {
        if ($usuario->hasAnyRole(self::ROLES_VEN_TODAS)) {
            return $consulta;
        }

        // Las que reportó y las de alumnos inscritos en sus grupos del periodo activo o del de consulta.
        $periodos = array_values(array_unique(array_filter([PeriodoDeTrabajo::periodoActivo()?->id, PeriodoDeTrabajo::periodoConsulta()?->id])));
        $alumnos = PerAlumno::query()->whereHas('inscripciones', fn (Builder $inscripciones) => $inscripciones->inscritas()
            ->whereHas('grupo', fn (Builder $grupos) => $grupos->visiblesEnAsistencia($usuario, $periodos)));

        return $consulta->where(fn (Builder $incidencias) => $incidencias->where('reportado_por_user_id', $usuario->id)
            ->orWhereIn('per_alumno_id', $alumnos->select('per_alumno.id')));
    }

    // Incidencias de una inscripción del periodo $periodo (el listado y la ficha usan el de consulta).
    public function scopeDelPeriodo(Builder $consulta, ?int $periodo): Builder
    {
        return $consulta->whereHas('inscripcion.grupo', fn (Builder $grupos) => $grupos->where('aca_periodo_escolar_id', $periodo ?? 0));
    }

    // Registrar y editar solo en el periodo activo; tomar seguimiento y cerrar, en cualquiera (no se dejan casos abiertos).
    public function exigirPeriodoOperable(): void
    {
        PeriodoDeTrabajo::exigir(PeriodoDeTrabajo::OPERAR, $this->inscripcion()->with('grupo.periodo')->first()?->grupo->periodo);
    }

    public function esVisiblePara(User $usuario): bool
    {
        return static::whereKey($this->getKey())->visiblesPara($usuario)->exists();
    }

    public function scopeDeEstado(Builder $consulta, ?string $estado): Builder
    {
        return $estado ? $consulta->where('estado', $estado) : $consulta;
    }

    public function scopeDeGrupo(Builder $consulta, ?int $grupo): Builder
    {
        return $grupo ? $consulta->whereHas('inscripcion', fn (Builder $inscripciones) => $inscripciones->where('aca_grupo_escolar_id', $grupo)) : $consulta;
    }

    public function scopeDeCategoria(Builder $consulta, ?string $categoria): Builder
    {
        return $categoria ? $consulta->whereHas('tipo', fn (Builder $tipos) => $tipos->where('categoria', $categoria)) : $consulta;
    }

    public function scopeDeGravedad(Builder $consulta, ?string $gravedad): Builder
    {
        return $gravedad ? $consulta->whereHas('tipo', fn (Builder $tipos) => $tipos->where('nivel_gravedad', $gravedad)) : $consulta;
    }

    public function scopeDeAlumno(Builder $consulta, ?int $alumno): Builder
    {
        return $alumno ? $consulta->where('per_alumno_id', $alumno) : $consulta;
    }

    public function scopeEntreFechas(Builder $consulta, ?string $desde, ?string $hasta): Builder
    {
        return $consulta->when($desde, fn ($consulta) => $consulta->where('fecha_inicio', '>=', $desde))
            ->when($hasta, fn ($consulta) => $consulta->where('fecha_inicio', '<=', $hasta));
    }

    public function scopeRecientes(Builder $consulta): Builder
    {
        return $consulta->orderByDesc('fecha_inicio')->orderByDesc('id');
    }

    public function estaAbierta(): bool
    {
        return $this->estado === self::ABIERTA;
    }

    public function getEstadoNombreAttribute(): string
    {
        return self::ESTADOS[$this->estado] ?? $this->estado;
    }

    public static function datosDe(array $datos): array
    {
        return Arr::only($datos, ['cat_tipo_incidencia_id', 'descripcion', 'fecha_inicio', 'fecha_fin']);
    }

    public static function registrar(array $datos, ?UploadedFile $documento, User $usuario): self
    {
        return DB::transaction(function () use ($datos, $documento, $usuario) {
            $alumno = static::alumnosElegibles($usuario)->whereKey($datos['per_alumno_id'] ?? 0)->first();
            if (! $alumno) {
                throw ValidationException::withMessages(['per_alumno_id' => 'Elige un alumno de tus grupos con inscripción vigente.']);
            }
            $alumno->bloquear();
            $inscripcion = $alumno->inscripcionActual()->first();
            if (! $inscripcion) {
                throw ValidationException::withMessages(['per_alumno_id' => 'El alumno no tiene inscripción vigente en el periodo activo.']);
            }
            $tipo = CatTipoIncidencia::findOrFail($datos['cat_tipo_incidencia_id']);
            $fechas = static::validarFechas($tipo, $datos['fecha_inicio'], $datos['fecha_fin'] ?? null);
            if ($tipo->categoria === 'INCAPACIDAD' && ! $documento) {
                throw ValidationException::withMessages(['documento' => 'La incapacidad requiere el documento (receta o constancia médica).']);
            }

            $incidencia = new static(['cat_tipo_incidencia_id' => $tipo->id, 'descripcion' => $datos['descripcion']] + $fechas);
            $incidencia->forceFill([
                'aca_inscripcion_id' => $inscripcion->id,
                'per_alumno_id' => $alumno->id,
                'estado' => self::ABIERTA,
                'reportado_por_user_id' => $usuario->id,
                'archivo_id' => $documento ? Archivo::guardar($documento, 'incidencias', $usuario)->id : null,
            ])->save();

            $incidencia->justificarFaltas();
            $incidencia->notificarTutores();

            return $incidencia;
        });
    }

    // Solo mientras esté ABIERTA. Si cambian el tipo o las fechas, se justifica el nuevo rango (el anterior no se revierte).
    public function actualizar(array $datos, ?UploadedFile $documento, User $usuario): void
    {
        $this->exigirPeriodoOperable();
        DB::transaction(function () use ($datos, $documento, $usuario) {
            $this->bloquear();
            if (! $this->estaAbierta()) {
                throw ValidationException::withMessages(['accion' => 'Solo se edita una incidencia abierta.']);
            }
            $tipo = CatTipoIncidencia::findOrFail($datos['cat_tipo_incidencia_id']);
            $fechas = static::validarFechas($tipo, $datos['fecha_inicio'], $datos['fecha_fin'] ?? null);
            if ($tipo->categoria === 'INCAPACIDAD' && ! $documento && ! $this->archivo_id) {
                throw ValidationException::withMessages(['documento' => 'La incapacidad requiere el documento (receta o constancia médica).']);
            }
            $this->fill(['cat_tipo_incidencia_id' => $tipo->id, 'descripcion' => $datos['descripcion']] + $fechas);
            // El documento anterior se conserva como evidencia; solo se apunta al nuevo.
            if ($documento) {
                $this->archivo_id = Archivo::guardar($documento, 'incidencias', $usuario)->id;
            }
            $this->save();
            $this->unsetRelation('tipo');
            $this->justificarFaltas();
        });
    }

    public function tomarSeguimiento(): void
    {
        DB::transaction(function () {
            $this->bloquear();
            if (! $this->estaAbierta()) {
                throw ValidationException::withMessages(['accion' => 'Solo se toma el seguimiento de una incidencia abierta.']);
            }
            Auditoria::motivoPorDefecto('Seguimiento de la incidencia');
            // estado no es asignable en masa (solo cambia por estos métodos).
            $this->forceFill(['estado' => self::EN_SEGUIMIENTO])->save();
        });
    }

    public function cerrar(string $resolucion, User $usuario): void
    {
        DB::transaction(function () use ($resolucion, $usuario) {
            $this->bloquear();
            if ($this->estado === self::CERRADA) {
                throw ValidationException::withMessages(['accion' => 'La incidencia ya está cerrada.']);
            }
            Auditoria::motivoPorDefecto('Cierre de la incidencia');
            $this->forceFill([
                'estado' => self::CERRADA,
                'resolucion' => $resolucion,
                'resuelta_por_user_id' => $usuario->id,
                'fecha_resolucion' => today()->toDateString(),
            ])->save();
        });
    }

    /**
     * fecha_inicio: dentro del periodo activo y no más de DIAS_ATRAS_MAXIMO días atrás (puede ser futura).
     * fecha_fin: obligatoria en SUSPENSION e INCAPACIDAD (>= inicio, dentro del periodo); en CONDUCTA se guarda null.
     */
    public static function validarFechas(CatTipoIncidencia $tipo, mixed $inicio, mixed $fin): array
    {
        $periodo = AcaPeriodoEscolar::activo();
        if (! $periodo) {
            throw ValidationException::withMessages(['fecha_inicio' => 'No hay un periodo escolar activo.']);
        }
        $inicio = Carbon::parse($inicio)->startOfDay();
        $minima = today()->subDays(self::DIAS_ATRAS_MAXIMO)->max($periodo->fecha_inicio)->copy();
        if ($inicio->lt($minima) || $inicio->gt($periodo->fecha_fin)) {
            throw ValidationException::withMessages(['fecha_inicio' => 'La fecha de inicio debe estar entre el '.$minima->format('d/m/Y').' y el '.$periodo->fecha_fin->format('d/m/Y').'.']);
        }
        if (! $tipo->requiereFechaFin()) {
            return ['fecha_inicio' => $inicio->toDateString(), 'fecha_fin' => null];
        }
        if (! filled($fin)) {
            throw ValidationException::withMessages(['fecha_fin' => 'Indica el último día que cubre la '.mb_strtolower($tipo->categoria_nombre).'.']);
        }
        $fin = Carbon::parse($fin)->startOfDay();
        if ($fin->lt($inicio) || $fin->gt($periodo->fecha_fin)) {
            throw ValidationException::withMessages(['fecha_fin' => 'La fecha de fin debe estar entre el '.$inicio->format('d/m/Y').' y el '.$periodo->fecha_fin->format('d/m/Y').'.']);
        }

        return ['fecha_inicio' => $inicio->toDateString(), 'fecha_fin' => $fin->toDateString()];
    }

    public function cubre(CarbonInterface $dia): bool
    {
        return $dia->toDateString() >= $this->fecha_inicio->toDateString()
            && $dia->toDateString() <= ($this->fecha_fin ?? $this->fecha_inicio)->toDateString();
    }

    private function justificarFaltas(): void
    {
        if ($this->tipo->justifica_falta) {
            TrxAsistenciaDiaria::justificarPorIncidencia($this);
            TrxAsistenciaClase::justificarPorIncidencia($this);
        }
    }

    // Una notificación PENDIENTE por tutor activo que recibe notificaciones. Sin la descripción ni datos de salud.
    private function notificarTutores(): void
    {
        if (! $this->tipo->notifica_tutor) {
            return;
        }
        $alumno = $this->alumno()->with(['persona', 'tutores' => fn ($tutores) => $tutores->activos()->where('recibe_notificaciones', true)])->first();
        $nombre = $alumno->persona->nombre_completo;
        $fecha = $this->fecha_inicio->format('d/m/Y');
        foreach ($alumno->tutores as $tutor) {
            ComNotificacion::create([
                'per_persona_id' => $tutor->per_persona_id,
                'canal' => ComNotificacion::EMAIL,
                'asunto' => Str::limit("Incidencia de {$nombre}: {$this->tipo->nombre}", 200, ''),
                'mensaje' => "Le informamos que el {$fecha} se registró una incidencia de tipo «{$this->tipo->nombre}» para {$nombre}. "
                    .'Le pedimos acudir con el área de Orientación del plantel.',
                'estado' => ComNotificacion::PENDIENTE,
            ]);
        }
    }

    /**
     * Línea de tiempo: registrada → seguimiento → cerrada. La fecha del seguimiento sale de la bitácora
     * (no tiene columna propia).
     */
    public function lineaDeTiempo(): array
    {
        $seguimiento = SysAuditoria::with('usuario')->where('tabla_afectada', $this->getTable())->where('registro_id', $this->id)->where('accion', 'UPDATE')
            ->whereHas('detalles', fn (Builder $detalles) => $detalles->where('campo', 'estado')->where('valor_nuevo', self::EN_SEGUIMIENTO))
            ->orderBy('id')->first();

        return [
            ['titulo' => 'Registrada', 'icono' => 'bi-flag', 'hecho' => true, 'fecha' => $this->created_at, 'quien' => $this->reportadoPor?->name],
            ['titulo' => 'En seguimiento', 'icono' => 'bi-eye', 'hecho' => $seguimiento !== null || ($this->estado === self::EN_SEGUIMIENTO),
                'fecha' => $seguimiento?->created_at, 'quien' => $seguimiento?->usuario_nombre],
            ['titulo' => 'Cerrada', 'icono' => 'bi-check2-circle', 'hecho' => $this->estado === self::CERRADA, 'fecha' => $this->fecha_resolucion, 'quien' => $this->resueltaPor?->name],
        ];
    }

    private function bloquear(): void
    {
        static::whereKey($this->getKey())->lockForUpdate()->firstOrFail();
        $this->refresh();
    }
}
