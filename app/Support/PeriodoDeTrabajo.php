<?php

namespace App\Support;

use App\Http\Controllers\Portal\HijoController;
use App\Models\AcaPeriodoEscolar;
use App\Models\PerAlumno;
use App\Models\User;
use App\Policies\PortalPolicy;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Periodo de trabajo. Se registra con alcance de petición (AppServiceProvider).
 * - periodoActivo(): el oficial (aca_periodo_escolar.activo); solo cambia en Configuración → Periodos.
 * - periodoConsulta(): el que el usuario eligió en la barra superior (session('periodo_consulta')) o, si no, el activo.
 *   Decide qué se LISTA y qué botones se ven. Las escrituras las decide el periodo del registro (exigir()).
 * - tipo(): ACTIVO, FUTURO (inicia después del activo; sin activo, después de hoy) o PASADO.
 * Acciones: PREPARAR (grupos, materias y docentes: ACTIVO y FUTURO), REINSCRIBIR (ACTIVO y FUTURO; solo la usa
 * PerAlumno::reinscribir(), es decir, el asistente "Cambio de periodo"), OPERAR (todo lo demás: solo ACTIVO)
 * y CORREGIR (corrección de calificaciones por Control Escolar: ACTIVO y PASADO).
 */
class PeriodoDeTrabajo
{
    public const SESION = 'periodo_consulta';

    public const ACTIVO = 'ACTIVO';

    public const FUTURO = 'FUTURO';

    public const PASADO = 'PASADO';

    public const PREPARAR = 'PREPARAR';

    public const OPERAR = 'OPERAR';

    public const CORREGIR = 'CORREGIR';

    // Reinscripción de un periodo al siguiente. Inscribir, cambiar de grupo y reingresar siguen exigiendo el periodo activo.
    public const REINSCRIBIR = 'REINSCRIBIR';

    private const PERMITIDAS = [
        self::ACTIVO => [self::PREPARAR, self::REINSCRIBIR, self::OPERAR, self::CORREGIR],
        self::FUTURO => [self::PREPARAR, self::REINSCRIBIR],
        self::PASADO => [self::CORREGIR],
    ];

    private bool $activoCargado = false;

    private ?AcaPeriodoEscolar $activo = null;

    private bool $consultaCargada = false;

    private ?AcaPeriodoEscolar $consulta = null;

    private static function instancia(): self
    {
        return app(self::class);
    }

    public static function periodoActivo(): ?AcaPeriodoEscolar
    {
        $instancia = static::instancia();
        if (! $instancia->activoCargado) {
            $instancia->activo = AcaPeriodoEscolar::activo();
            $instancia->activoCargado = true;
        }

        return $instancia->activo;
    }

    // El elegido en la sesión, si sigue siendo válido para el usuario; si no, el activo (o el más reciente permitido).
    public static function periodoConsulta(): ?AcaPeriodoEscolar
    {
        $instancia = static::instancia();
        if ($instancia->consultaCargada) {
            return $instancia->consulta;
        }
        $elegido = request()->hasSession() ? request()->session()->get(self::SESION) : null;
        $usuario = auth()->user();
        $permitidos = $usuario ? static::periodosPermitidos($usuario) : null;
        $consulta = $elegido ? $permitidos?->firstWhere('id', (int) $elegido) : null;
        $instancia->consulta = $consulta ?? static::periodoActivo() ?? $permitidos?->first();
        $instancia->consultaCargada = true;

        return $instancia->consulta;
    }

    // Olvida lo calculado en esta petición (después de cambiar la sesión o el hijo elegido).
    public static function reiniciar(): void
    {
        $instancia = static::instancia();
        $instancia->activoCargado = $instancia->consultaCargada = false;
        $instancia->activo = $instancia->consulta = null;
    }

    public static function tipo(?AcaPeriodoEscolar $periodo = null): ?string
    {
        $periodo ??= static::periodoConsulta();
        if (! $periodo) {
            return null;
        }
        $activo = static::periodoActivo();
        if ($activo && $periodo->is($activo)) {
            return self::ACTIVO;
        }
        $referencia = $activo?->fecha_inicio ?? today();

        return $periodo->fecha_inicio->gt($referencia) ? self::FUTURO : self::PASADO;
    }

    public static function esConsultaHistorica(): bool
    {
        $consulta = static::periodoConsulta();

        return $consulta !== null && ! $consulta->is(static::periodoActivo());
    }

    // id del periodo de consulta (0 si no hay ninguno, para que las consultas no devuelvan nada).
    public static function idConsulta(): int
    {
        return static::periodoConsulta()?->id ?? 0;
    }

    public static function permite(string $accion, ?AcaPeriodoEscolar $periodo = null): bool
    {
        $tipo = static::tipo($periodo);

        return $tipo !== null && in_array($accion, self::PERMITIDAS[$tipo], true);
    }

    // Validación del servidor: la acción sobre un registro de $periodo debe estar permitida por su tipo.
    public static function exigir(string $accion, ?AcaPeriodoEscolar $periodo, string $campo = 'accion'): void
    {
        if (static::permite($accion, $periodo)) {
            return;
        }
        $nombre = $periodo?->nombre_visible ?? 'sin periodo';
        $mensaje = match (static::tipo($periodo)) {
            self::FUTURO => "El periodo {$nombre} es futuro: solo se preparan grupos, materias y docentes, y se reinscribe desde Cambio de periodo.",
            self::PASADO => "El periodo {$nombre} ya pasó: solo se consulta.",
            default => 'No hay un periodo escolar al que aplicar esta acción.',
        };

        throw ValidationException::withMessages([$campo => $mensaje]);
    }

    // Periodos en los que se pueden preparar grupos (el activo y los futuros), del más reciente al más antiguo.
    public static function periodosPreparables(): Collection
    {
        return AcaPeriodoEscolar::ordenados()->get()->filter(fn (AcaPeriodoEscolar $periodo) => static::permite(self::PREPARAR, $periodo))->values();
    }

    /**
     * Periodos que el usuario puede elegir en el selector (del más reciente al más antiguo).
     * Personal (o cuentas mixtas): todos. Solo Alumno o Padre de familia: los periodos con inscripción de su alumno
     * o del hijo elegido (si no ha elegido, de todos sus hijos).
     */
    public static function periodosPermitidos(User $usuario): Collection
    {
        $todos = AcaPeriodoEscolar::ordenados()->get();
        $roles = $usuario->getRoleNames();
        if ($roles->isEmpty() || $roles->diff(PortalPolicy::ROLES)->isNotEmpty()) {
            return $todos;
        }
        $alumnos = static::alumnosDelPortal($usuario);
        $conInscripcion = AcaPeriodoEscolar::whereHas('grupos.inscripciones', fn ($inscripciones) => $inscripciones->whereIn('per_alumno_id', $alumnos))->pluck('id');

        return $todos->whereIn('id', $conInscripcion->all())->values();
    }

    private static function alumnosDelPortal(User $usuario): array
    {
        $ids = [];
        if ($usuario->hasRole('Alumno', 'web')) {
            $propio = PerAlumno::deLaCuenta($usuario);
            if ($propio) {
                $ids[] = $propio->id;
            }
        }
        if ($usuario->hasRole('Padre de familia', 'web')) {
            $elegido = request()->hasSession() ? request()->session()->get(HijoController::HIJO_ELEGIDO) : null;
            $hijo = $elegido ? PerAlumno::find($elegido) : null;
            $ids = array_merge($ids, $hijo && Gate::forUser($usuario)->allows('portal-hijo', $hijo)
                ? [$hijo->id]
                : PerAlumno::deTutor($usuario->per_persona_id)->pluck('id')->all());
        }

        return $ids;
    }
}
