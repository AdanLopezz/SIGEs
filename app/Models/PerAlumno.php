<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\DatosDelPortal;
use App\Policies\AlumnoPolicy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Support\Auditoria;
use App\Support\PeriodoDeTrabajo;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

#[UsePolicy(AlumnoPolicy::class)]
class PerAlumno extends Model
{
    use Auditable;

    use DatosDelPortal;

    // Roles que ven a todos los alumnos; Orientador y Tutor solo ven a los de sus grupos.
    // Prefectura está aquí para el acceso y los permisos de salida: la ficha y los archivos exigen además alumnos.ver.
    public const ROLES_VEN_TODOS = ['Administrador', 'Director', 'Subdirector', 'Control Escolar', 'Prefectura'];

    public bool $tutorReutilizado = false;

    protected $table = 'per_alumno';

    protected $fillable = ['aca_carrera_id', 'matricula', 'email_institucional', 'fecha_ingreso'];

    protected function casts(): array
    {
        return ['fecha_ingreso' => 'date', 'aca_carrera_id' => 'integer'];
    }

    public function persona(): BelongsTo
    {
        return $this->belongsTo(PerPersona::class, 'per_persona_id');
    }

    public function carrera(): BelongsTo
    {
        return $this->belongsTo(AcaCarrera::class, 'aca_carrera_id');
    }

    public function estado(): BelongsTo
    {
        return $this->belongsTo(CatEstadoAlumno::class, 'cat_estado_alumno_id');
    }

    public function expediente(): BelongsTo
    {
        return $this->belongsTo(Archivo::class, 'expediente_archivo_id');
    }

    public function salud(): HasOne
    {
        return $this->hasOne(PerAlumnoSalud::class, 'per_alumno_id');
    }

    public function tutores(): HasMany
    {
        return $this->hasMany(PerTutorAlumno::class, 'per_alumno_id');
    }

    public function inscripciones(): HasMany
    {
        return $this->hasMany(AcaInscripcion::class, 'per_alumno_id');
    }

    // Operación: la inscripción vigente (INSCRITO) del periodo activo.
    public function inscripcionActual(): HasOne
    {
        return $this->hasOne(AcaInscripcion::class, 'per_alumno_id')->inscritas()->delPeriodoActivo();
    }

    // Consulta: la inscripción del periodo de consulta (la INSCRITO o, si ya no la hay, la más reciente del periodo).
    public function inscripcionDeConsulta(): HasOne
    {
        return $this->hasOne(AcaInscripcion::class, 'per_alumno_id')->delPeriodoConsulta()
            ->orderByRaw('CASE WHEN aca_inscripcion.estado = ? THEN 0 ELSE 1 END', [AcaInscripcion::INSCRITO])
            ->orderByDesc('aca_inscripcion.fecha_inscripcion')->orderByDesc('aca_inscripcion.id');
    }

    public function bajas(): HasManyThrough
    {
        return $this->hasManyThrough(AcaBaja::class, AcaInscripcion::class, 'per_alumno_id', 'aca_inscripcion_id');
    }

    public function consentimientos(): HasManyThrough
    {
        return $this->hasManyThrough(PerConsentimiento::class, PerTutorAlumno::class, 'per_alumno_id', 'per_tutor_alumno_id');
    }

    public function credenciales(): HasMany
    {
        return $this->hasMany(PerCredencialAcceso::class, 'per_alumno_id');
    }

    public function credencialActiva(): HasOne
    {
        return $this->hasOne(PerCredencialAcceso::class, 'per_alumno_id')->activas()->latest('fecha_emision');
    }

    public function estaActivo(): bool
    {
        return $this->estado->nombre === CatEstadoAlumno::ACTIVO;
    }

    // El parámetro {tutor} de las rutas anidadas se resuelve con la relación "tutores".
    protected function childRouteBindingRelationshipName($childType)
    {
        return $childType === 'tutor' ? 'tutores' : parent::childRouteBindingRelationshipName($childType);
    }

    public function getGeneracionAttribute(): string
    {
        $inicio = (int) $this->fecha_ingreso->format('Y');

        return $inicio.'–'.($inicio + (int) ceil(($this->carrera?->total_semestres ?? 6) / 2));
    }

    public function scopeOrdenados(Builder $consulta): Builder
    {
        return $consulta->orderBy(PerPersona::select(DB::raw("concat_ws(' ', primer_apellido, segundo_apellido, nombre)"))
            ->whereColumn('per_persona.id', 'per_alumno.per_persona_id'))->orderBy('id');
    }

    public function scopeBuscar(Builder $consulta, ?string $texto): Builder
    {
        if (! filled($texto)) {
            return $consulta;
        }
        $patron = '%'.trim($texto).'%';

        return $consulta->where(fn (Builder $alumnos) => $alumnos->where('matricula', 'ilike', $patron)
            ->orWhereHas('persona', fn (Builder $personas) => $personas->where('curp', 'ilike', $patron)
                ->orWhereRaw("concat_ws(' ', nombre, primer_apellido, segundo_apellido) ILIKE ?", [$patron])));
    }

    public function scopeDeCarrera(Builder $consulta, ?int $carrera): Builder
    {
        return $carrera ? $consulta->where('aca_carrera_id', $carrera) : $consulta;
    }

    public function scopeDeGrupo(Builder $consulta, ?int $grupo): Builder
    {
        return $grupo ? $consulta->whereHas('inscripciones', fn (Builder $inscripciones) => $inscripciones->inscritas()->where('aca_grupo_escolar_id', $grupo)) : $consulta;
    }

    // El alumno no tiene semestre propio: es el del grupo en el que está inscrito en el periodo de consulta.
    public function scopeDeSemestre(Builder $consulta, ?int $semestre): Builder
    {
        return $semestre ? $consulta->whereHas('inscripciones', fn (Builder $inscripciones) => $inscripciones->inscritas()->delPeriodoConsulta()
            ->whereHas('grupo', fn (Builder $grupos) => $grupos->where('semestre', $semestre))) : $consulta;
    }

    public function scopeConEstado(Builder $consulta, ?int $estado): Builder
    {
        return $estado ? $consulta->where('cat_estado_alumno_id', $estado) : $consulta;
    }

    /**
     * Orientador y Tutor: los alumnos inscritos en sus grupos de $periodos. Sin $periodos, del periodo activo y del de
     * consulta (así la ficha de un alumno actual se sigue viendo al consultar otro periodo). El listado pasa solo el de consulta.
     */
    public function scopeVisiblesPara(Builder $consulta, User $usuario, ?array $periodos = null): Builder
    {
        $periodos ??= array_values(array_unique(array_filter([PeriodoDeTrabajo::periodoActivo()?->id, PeriodoDeTrabajo::periodoConsulta()?->id])));
        if ($usuario->hasAnyRole(self::ROLES_VEN_TODOS)) {
            return $consulta;
        }
        $personal = $usuario->persona?->personal;
        if (! $personal?->activo || ! $usuario->hasAnyRole(['Orientador', 'Tutor'])) {
            return $consulta->whereRaw('1 = 0');
        }

        return $consulta->whereHas('inscripciones', fn (Builder $inscripciones) => $inscripciones->inscritas()
            ->whereHas('grupo', fn (Builder $grupos) => $grupos->whereIn('aca_periodo_escolar_id', $periodos)->where(fn (Builder $responsables) => $responsables
                ->where('orientador_id', $personal->id)->orWhere('tutor_id', $personal->id))));
    }

    public function esVisiblePara(User $usuario): bool
    {
        return static::whereKey($this->getKey())->visiblesPara($usuario)->exists();
    }

    public static function datosDe(array $datos): array
    {
        return Arr::only($datos, ['aca_carrera_id', 'matricula', 'email_institucional', 'fecha_ingreso']);
    }

    // Toma los campos con prefijo (tutor_curp → curp) del formulario de alta.
    private static function conPrefijo(array $datos, string $prefijo): array
    {
        $resultado = [];
        foreach ($datos as $campo => $valor) {
            if (str_starts_with($campo, $prefijo)) {
                $resultado[substr($campo, strlen($prefijo))] = $valor;
            }
        }

        return $resultado;
    }

    public static function registrar(array $datos, UploadedFile $expediente, User $usuario): self
    {
        return DB::transaction(function () use ($datos, $expediente, $usuario) {
            abort_if($datos['curp'] === PerPersona::CURP_SISTEMA, 404);
            $persona = PerPersona::where('curp', $datos['curp'])->lockForUpdate()->first();
            if ($persona?->alumno()->exists()) {
                throw ValidationException::withMessages(['curp' => 'Esta persona ya está registrada como alumno.']);
            }
            $persona = PerPersona::updateOrCreate(['curp' => $datos['curp']], PerPersona::datosDe($datos));
            $persona->user?->update(['name' => $persona->nombre_completo]);
            $archivo = Archivo::guardar($expediente, 'expedientes', $usuario);

            $alumno = new static(static::datosDe($datos));
            $alumno->forceFill([
                'per_persona_id' => $persona->id,
                'cat_estado_alumno_id' => CatEstadoAlumno::activo()->id,
                'expediente_archivo_id' => $archivo->id,
            ])->save();
            $alumno->salud()->create(PerAlumnoSalud::datosDe($datos));

            try {
                $tutor = PerTutorAlumno::registrar($alumno, static::conPrefijo($datos, 'tutor_') + ['es_contacto_emergencia' => true, 'recibe_notificaciones' => true]);
            } catch (ValidationException $excepcion) {
                // En el alta los campos del tutor llevan prefijo: el error de "curp" va en "tutor_curp".
                throw ValidationException::withMessages(collect($excepcion->errors())->mapWithKeys(fn ($mensajes, $campo) => ["tutor_{$campo}" => $mensajes])->all());
            }
            $alumno->tutorReutilizado = $tutor->personaReutilizada;
            $alumno->inscribir(AcaGrupoEscolar::findOrFail($datos['aca_grupo_escolar_id']));

            return $alumno;
        });
    }

    public function actualizarDatos(array $datos, ?UploadedFile $expediente, User $usuario, bool $conSalud): void
    {
        DB::transaction(function () use ($datos, $expediente, $usuario, $conSalud) {
            $this->bloquear();
            if (isset($datos['aca_carrera_id']) && (int) $datos['aca_carrera_id'] !== $this->aca_carrera_id && $this->inscripcionActual()->exists()) {
                throw ValidationException::withMessages(['aca_carrera_id' => 'No se puede cambiar la carrera porque el alumno está inscrito en el periodo activo.']);
            }
            $persona = $this->persona;
            $persona->update(PerPersona::datosDe(array_diff_key($datos, ['curp' => true])));
            $persona->user?->update(['name' => $persona->nombre_completo]);
            $this->fill(static::datosDe($datos));
            // El expediente anterior se conserva como evidencia; solo se apunta al nuevo.
            if ($expediente) {
                $this->expediente_archivo_id = Archivo::guardar($expediente, 'expedientes', $usuario)->id;
            }
            $this->save();
            if ($conSalud) {
                $this->salud()->updateOrCreate([], PerAlumnoSalud::datosDe($datos));
            }
        });
    }

    public function inscribir(AcaGrupoEscolar $grupo): AcaInscripcion
    {
        return DB::transaction(function () use ($grupo) {
            $this->bloquear();
            $this->validarGrupo($grupo);
            if ($this->inscripcionActual()->exists()) {
                throw ValidationException::withMessages(['aca_grupo_escolar_id' => 'El alumno ya está inscrito en un grupo del periodo activo.']);
            }

            return $this->inscribirEn($grupo);
        });
    }

    /**
     * Reinscripción en el grupo de otro periodo (asistente "Cambio de periodo"): el activo o uno futuro (REINSCRIBIR).
     * La fecha de inscripción es la que indica el asistente (fecha_inicio del destino). No toca la inscripción del origen.
     */
    public function reinscribir(AcaGrupoEscolar $grupo, CarbonInterface $fecha): AcaInscripcion
    {
        return DB::transaction(function () use ($grupo, $fecha) {
            $this->bloquear();
            $grupo->loadMissing('periodo');
            PeriodoDeTrabajo::exigir(PeriodoDeTrabajo::REINSCRIBIR, $grupo->periodo, 'aca_grupo_escolar_id');
            $this->validarGrupo($grupo, false);
            $inscrito = $this->inscripciones()->inscritas()->delPeriodoConsulta($grupo->aca_periodo_escolar_id)->with('grupo')->first();
            if ($inscrito) {
                throw ValidationException::withMessages(['aca_grupo_escolar_id' => "Ya está inscrito en {$inscrito->grupo->identificador}."]);
            }

            return $this->inscribirEn($grupo, $fecha);
        });
    }

    public function cambiarGrupo(AcaGrupoEscolar $grupo): AcaInscripcion
    {
        return DB::transaction(function () use ($grupo) {
            $this->bloquear();
            $actual = $this->inscripcionActual()->first();
            if (! $actual) {
                throw ValidationException::withMessages(['aca_grupo_escolar_id' => 'El alumno no está inscrito en el periodo activo; usa «Inscribir».']);
            }
            if ($actual->aca_grupo_escolar_id === $grupo->id) {
                throw ValidationException::withMessages(['aca_grupo_escolar_id' => 'El alumno ya está en ese grupo.']);
            }
            $this->validarGrupo($grupo);
            $actual->update(['estado' => AcaInscripcion::CAMBIO_GRUPO]);

            return $this->inscribirEn($grupo);
        });
    }

    // El usuario del alumno es su correo institucional (en las altas nuevas ya viene generado con nombre.apellido + 4 dígitos @dominio).
    public function crearCuenta(): User
    {
        return User::darAccesoControlEscolar($this->persona, $this->email_institucional, 'Alumno');
    }

    public function darDeBaja(string $tipo, CarbonInterface $fecha, string $motivo, User $usuario): AcaBaja
    {
        return DB::transaction(function () use ($tipo, $fecha, $motivo, $usuario) {
            $this->bloquear();
            $inscripcion = $this->inscripcionActual()->with('grupo.periodo')->lockForUpdate()->first();
            if (! $inscripcion) {
                throw ValidationException::withMessages(['accion' => 'El alumno no tiene una inscripción vigente en el periodo activo.']);
            }
            [$minima, $maxima] = static::limitesFechaBaja($inscripcion);
            if ($fecha->lt($minima) || $fecha->gt($maxima)) {
                throw ValidationException::withMessages(['fecha' => 'La fecha debe estar entre el '.$minima->format('d/m/Y').' y el '.$maxima->format('d/m/Y').'.']);
            }
            Auditoria::motivo($motivo);

            $baja = AcaBaja::create([
                'aca_inscripcion_id' => $inscripcion->id,
                'tipo' => $tipo,
                'fecha' => $fecha->toDateString(),
                'motivo' => $motivo,
                'registrado_por_user_id' => $usuario->id,
            ]);
            $inscripcion->update(['estado' => AcaInscripcion::BAJA]);
            // Reinscripciones a periodos posteriores (asistente "Cambio de periodo"): se borran con el mismo motivo (bitácora).
            $this->inscripciones()->whereHas('grupo.periodo', fn (Builder $periodos) => $periodos->where('fecha_inicio', '>', $inscripcion->grupo->periodo->fecha_inicio))
                ->lockForUpdate()->get()->each->delete();
            $estado = $tipo === AcaBaja::DEFINITIVA ? CatEstadoAlumno::BAJA_DEFINITIVA : CatEstadoAlumno::BAJA_TEMPORAL;
            $this->forceFill(['cat_estado_alumno_id' => CatEstadoAlumno::porNombre($estado)->id])->save();
            $this->credenciales()->activas()->get()->each->desactivar();
            // La baja definitiva quita el acceso de alumno; la temporal lo conserva. (El cambio de roles queda en la bitácora.)
            $cuenta = $this->persona->user;
            if ($tipo === AcaBaja::DEFINITIVA && $cuenta?->hasRole('Alumno')) {
                $cuenta->removeRole('Alumno');
            }

            return $baja;
        });
    }

    // La baja va dentro del periodo, no antes de la inscripción y no en el futuro.
    public static function limitesFechaBaja(AcaInscripcion $inscripcion): array
    {
        $periodo = $inscripcion->grupo->periodo;
        // copy(): max()/min() devuelven la misma instancia del modelo.
        $minima = $inscripcion->fecha_inscripcion->max($periodo->fecha_inicio)->copy();
        $maxima = $periodo->fecha_fin->min(today())->copy();

        return [$minima, $maxima];
    }

    public function reingresar(AcaGrupoEscolar $grupo, User $usuario): AcaInscripcion
    {
        return DB::transaction(function () use ($grupo) {
            $this->bloquear();
            if ($this->estado->nombre !== CatEstadoAlumno::BAJA_TEMPORAL) {
                throw ValidationException::withMessages(['accion' => 'Solo se puede reingresar a un alumno con baja temporal.']);
            }
            // UNIQUE (alumno, grupo) y aca_baja única por inscripción: no se puede volver al mismo grupo del que se dio de baja.
            if ($this->inscripciones()->where('aca_grupo_escolar_id', $grupo->id)->where('estado', AcaInscripcion::BAJA)->exists()) {
                throw ValidationException::withMessages(['aca_grupo_escolar_id' => 'El alumno se dio de baja de este grupo en el periodo y no puede inscribirse de nuevo en él. Elige otro grupo de su carrera.']);
            }
            $this->forceFill(['cat_estado_alumno_id' => CatEstadoAlumno::activo()->id])->save();
            $this->unsetRelation('estado');
            $inscripcion = $this->inscribir($grupo);
            $cuenta = $this->persona->user;
            if ($cuenta && ! $cuenta->hasRole('Alumno')) {
                $cuenta->assignRole('Alumno');
            }

            return $inscripcion;
        });
    }

    // Devuelve el token en claro, que solo se usa para dibujar el QR de esta impresión.
    public function emitirCredencial(User $usuario): string
    {
        return DB::transaction(function () {
            $this->bloquear();
            if (! $this->estaActivo()) {
                throw ValidationException::withMessages(['accion' => 'Solo se emite credencial a alumnos con estado Activo.']);
            }
            Auditoria::motivoPorDefecto('Emisión de credencial');
            $this->credenciales()->activas()->lockForUpdate()->get()->each->desactivar();
            $token = Str::random(40);
            $this->credenciales()->create(['token_hash' => PerCredencialAcceso::hashDe($token), 'fecha_emision' => now(), 'activa' => true]);

            return $token;
        });
    }

    // Serializa los cambios del alumno (inscripciones y tutores) y recarga sus datos.
    public function bloquear(): void
    {
        static::whereKey($this->getKey())->lockForUpdate()->firstOrFail();
        $this->refresh();
    }

    // $soloActivo: inscribir, cambiar de grupo y reingresar solo usan el periodo activo (la reinscripción valida su periodo aparte).
    private function validarGrupo(AcaGrupoEscolar $grupo, bool $soloActivo = true): void
    {
        $grupo->loadMissing('periodo');
        $error = match (true) {
            ! $this->estado->permite_inscripcion => "El estado del alumno ({$this->estado->nombre}) no permite inscribirlo.",
            (int) $grupo->aca_carrera_id !== (int) $this->aca_carrera_id => 'El grupo no es de la carrera del alumno.',
            $soloActivo && ! $grupo->periodo->activo => 'El grupo no pertenece al periodo activo.',
            default => null,
        };
        if ($error) {
            throw ValidationException::withMessages(['aca_grupo_escolar_id' => $error]);
        }
    }

    private function inscribirEn(AcaGrupoEscolar $grupo, ?CarbonInterface $fecha = null): AcaInscripcion
    {
        $fecha = ($fecha ?? today())->toDateString();
        // UNIQUE (alumno, grupo): si ya estuvo en este grupo y salió por cambio de grupo, se reactiva esa inscripción.
        $previa = $this->inscripciones()->where('aca_grupo_escolar_id', $grupo->id)->lockForUpdate()->first();
        if ($previa?->estado === AcaInscripcion::BAJA) {
            throw ValidationException::withMessages(['aca_grupo_escolar_id' => 'El alumno tiene una baja registrada en ese grupo; el reingreso se gestiona desde Bajas.']);
        }
        if ($previa) {
            $previa->update(['estado' => AcaInscripcion::INSCRITO, 'fecha_inscripcion' => $fecha]);

            return $previa;
        }

        return $this->inscripciones()->create([
            'aca_grupo_escolar_id' => $grupo->id,
            'estado' => AcaInscripcion::INSCRITO,
            'fecha_inscripcion' => $fecha,
        ]);
    }
}
