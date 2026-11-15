<?php

namespace App\Support;

use App\Models\PerPersona;
use App\Models\SysAuditoria;
use App\Models\SysAuditoriaDetalle;
use App\Models\User;
use BackedEnum;
use DateTimeInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

// Bitácora (sys_auditoria). Se registra como singleton con alcance de petición (AppServiceProvider).
class Auditoria
{
    // tabla => [nombre en plural para la pantalla, nombre en singular para los motivos por defecto]
    public const TABLAS = [
        'users' => ['Usuarios', 'usuario'],
        'model_has_roles' => ['Roles de usuario', 'roles'],
        'per_persona' => ['Personas', 'persona'],
        'per_personal' => ['Personal', 'datos laborales'],
        'per_alumno' => ['Alumnos', 'alumno'],
        'per_alumno_salud' => ['Salud de alumnos', 'datos de salud'],
        'per_tutor_alumno' => ['Tutores de alumnos', 'tutor'],
        'aca_inscripcion' => ['Inscripciones', 'inscripción'],
        'aca_baja' => ['Bajas', 'baja'],
        'per_consentimiento' => ['Consentimientos', 'consentimiento'],
        'per_credencial_acceso' => ['Credenciales', 'credencial'],
        'trx_incidencia' => ['Incidencias', 'incidencia'],
        'trx_seguimiento_alumno' => ['Seguimiento de alumnos', 'caso de seguimiento'],
        'trx_seguimiento_nota' => ['Notas de seguimiento', 'nota de seguimiento'],
        'cat_tipo_incidencia' => ['Tipos de incidencia', 'tipo de incidencia'],
        'com_notificacion' => ['Notificaciones', 'notificación'],
        'trx_asistencia_diaria' => ['Asistencia diaria', 'asistencia'],
        'trx_asistencia_clase' => ['Asistencia por clase', 'asistencia de clase'],
        'aca_horario' => ['Horarios', 'clase del horario'],
        'trx_permiso_salida' => ['Permisos de salida', 'permiso de salida'],
        'trx_permiso_confirmacion' => ['Confirmaciones de permisos', 'confirmación de permiso'],
        'org_empresa' => ['Empresas', 'empresa'],
        'org_convenio' => ['Convenios', 'convenio'],
        'trx_aspirante' => ['Aspirantes', 'aspirante'],
        'aca_grupo_escolar' => ['Grupos', 'grupo'],
        'aca_grupo_asignatura' => ['Materias de grupo', 'materia de grupo'],
        'aca_evaluacion' => ['Evaluaciones', 'evaluación'],
        'trx_calificacion' => ['Calificaciones', 'calificación'],
        'trx_resultado_asignatura' => ['Resultados de materias', 'resultado de materia'],
        'aca_recursamiento' => ['Recursamientos', 'recursamiento'],
        'aca_periodo_escolar' => ['Periodos escolares', 'periodo escolar'],
        'aca_dia_inhabil' => ['Días inhábiles', 'día inhábil'],
        'aca_turno' => ['Turnos', 'turno'],
        'aca_carrera' => ['Carreras', 'carrera'],
        'aca_asignatura' => ['Asignaturas', 'asignatura'],
        'org_area' => ['Áreas', 'área'],
        'org_espacio_fisico' => ['Espacios', 'espacio'],
        'sys_configuracion' => ['Configuración del plantel', 'configuración del plantel'],
        'archivo' => ['Archivos', 'archivo'],
    ];

    // SELECT: consultas registradas a mano (p. ej. ver los datos de acceso de una cuenta), sin valores.
    public const ACCIONES = ['INSERT' => 'Alta', 'UPDATE' => 'Edición', 'DELETE' => 'Eliminación', 'SELECT' => 'Consulta'];

    private ?string $motivo = null;

    private static ?int $sistemaId = null;

    private static function instancia(): self
    {
        return app(self::class);
    }

    // Motivo de la operación en curso; aplica a todo lo que se registre después en esta petición.
    public static function motivo(string $motivo): void
    {
        static::instancia()->motivo = Str::limit(trim($motivo), 255, '');
    }

    // Igual que motivo(), pero solo si nadie fijó uno antes (por ejemplo, el controlador).
    public static function motivoPorDefecto(string $motivo): void
    {
        if (static::instancia()->motivo === null) {
            static::motivo($motivo);
        }
    }

    // Ejecuta $operacion con un motivo propio y luego restaura el que había (p. ej. cambios derivados de otra operación).
    public static function conMotivo(string $motivo, callable $operacion): mixed
    {
        $instancia = static::instancia();
        $anterior = $instancia->motivo;
        static::motivo($motivo);
        try {
            return $operacion();
        } finally {
            $instancia->motivo = $anterior;
        }
    }

    public static function motivoActual(): ?string
    {
        return static::instancia()->motivo;
    }

    /**
     * Registro manual. $campos = ['campo' => [valor anterior, valor nuevo]].
     */
    public static function registrar(string $tabla, int $registro, string $accion, array $campos, ?string $motivo = null, ?int $usuarioId = null): SysAuditoria
    {
        return DB::transaction(function () use ($tabla, $registro, $accion, $campos, $motivo, $usuarioId) {
            $cabecera = SysAuditoria::create([
                'user_id' => $usuarioId ?? static::usuarioId(),
                'tabla_afectada' => $tabla,
                'registro_id' => $registro,
                'accion' => $accion,
                'motivo' => Str::limit($motivo ?? static::motivoActual() ?? static::motivoPorOmision($tabla, $accion), 255, ''),
                'ip_address' => static::ip(),
            ]);
            $filas = [];
            foreach ($campos as $campo => [$anterior, $nuevo]) {
                $filas[] = ['sys_auditoria_id' => $cabecera->id, 'campo' => Str::limit($campo, 64, ''),
                    'valor_anterior' => static::texto($anterior), 'valor_nuevo' => static::texto($nuevo)];
            }
            if ($filas) {
                SysAuditoriaDetalle::insert($filas);
            }

            return $cabecera;
        });
    }

    public static function motivoPorOmision(string $tabla, string $accion): string
    {
        if ($tabla === 'model_has_roles') {
            return 'Cambio de roles';
        }

        return (self::ACCIONES[$accion] ?? $accion).' de '.(self::TABLAS[$tabla][1] ?? $tabla);
    }

    public static function usuarioId(): int
    {
        return Auth::id() ?? static::sistemaId();
    }

    // El usuario Sistema se busca una vez por proceso.
    public static function sistemaId(): int
    {
        return static::$sistemaId ??= User::whereHas('persona', fn ($personas) => $personas->where('curp', PerPersona::CURP_SISTEMA))->value('id')
            ?? throw new RuntimeException('No existe el usuario Sistema (CURP '.PerPersona::CURP_SISTEMA.'). Carga los datos iniciales antes de auditar.');
    }

    public static function ip(): string
    {
        return app()->runningInConsole() ? '127.0.0.1' : (request()->ip() ?? '127.0.0.1');
    }

    public static function texto(mixed $valor): string
    {
        return match (true) {
            $valor === null => '',
            is_bool($valor) => $valor ? 'Sí' : 'No',
            $valor instanceof DateTimeInterface => $valor->format('Y-m-d H:i:s'),
            $valor instanceof BackedEnum => (string) $valor->value,
            is_array($valor) || is_object($valor) => (string) json_encode($valor, JSON_UNESCAPED_UNICODE),
            default => (string) $valor,
        };
    }
}
