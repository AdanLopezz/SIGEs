<?php

namespace App\Models;

use App\Support\ResultadoAcceso;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use LogicException;

// Bitácora propia de la puerta: no se audita aparte (sería duplicarla) y no se edita ni se borra.
class TrxRegistroAcceso extends Model
{
    public const ENTRADA = 'ENTRADA';

    public const SALIDA = 'SALIDA';

    public const QR = 'QR';

    public const MANUAL = 'MANUAL';

    // Una segunda lectura del mismo alumno dentro de este lapso se toma como repetida.
    public const SEGUNDOS_LECTURA_REPETIDA = 60;

    public $timestamps = false;

    protected $table = 'trx_registro_acceso';

    protected $fillable = ['per_credencial_acceso_id', 'tipo_movimiento', 'metodo', 'registrado_por_user_id', 'fecha_hora'];

    protected function casts(): array
    {
        return ['fecha_hora' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Los registros de acceso no se modifican.'));
        static::deleting(fn () => throw new LogicException('Los registros de acceso no se borran.'));
    }

    public function credencial(): BelongsTo
    {
        return $this->belongsTo(PerCredencialAcceso::class, 'per_credencial_acceso_id');
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por_user_id');
    }

    public function scopeDelDia(Builder $consulta, CarbonInterface $dia): Builder
    {
        return $consulta->where('fecha_hora', '>=', $dia->copy()->startOfDay())->where('fecha_hora', '<', $dia->copy()->addDay()->startOfDay());
    }

    public function scopeDeAlumnos(Builder $consulta, array $alumnos): Builder
    {
        return $consulta->whereHas('credencial', fn (Builder $credenciales) => $credenciales->whereIn('per_alumno_id', $alumnos ?: [0]));
    }

    public static function registrar(PerCredencialAcceso $credencial, string $metodo, User $usuario): ResultadoAcceso
    {
        return DB::transaction(function () use ($credencial, $metodo, $usuario) {
            $credencial = PerCredencialAcceso::whereKey($credencial->getKey())->lockForUpdate()->firstOrFail();
            $alumno = $credencial->alumno;
            // Serializa las lecturas del mismo alumno (por ejemplo, dos lectores a la vez).
            $alumno->bloquear();
            $alumno->load(['persona', 'estado', 'inscripcionActual.grupo.turno', 'inscripcionActual.grupo.carrera']);

            $rechazo = match (true) {
                ! $credencial->activa => 'Credencial inactiva (fue repuesta o el alumno tiene una baja).',
                ! $alumno->estaActivo() => "El alumno no está Activo ({$alumno->estado->nombre}).",
                ! $alumno->inscripcionActual => 'El alumno no tiene inscripción vigente en el periodo activo.',
                default => null,
            };
            if ($rechazo) {
                return ResultadoAcceso::rechazo($rechazo, $alumno);
            }

            $ahora = now()->startOfSecond();
            $ultimo = static::deAlumnos([$alumno->id])->delDia($ahora)->orderByDesc('fecha_hora')->orderByDesc('id')->first();
            if ($ultimo && $ultimo->fecha_hora->gt($ahora->copy()->subSeconds(self::SEGUNDOS_LECTURA_REPETIDA))) {
                return new ResultadoAcceso(true, $alumno, $ultimo, repetida: true);
            }

            $tipo = $ultimo?->tipo_movimiento === self::ENTRADA ? self::SALIDA : self::ENTRADA;
            $registro = static::create([
                'per_credencial_acceso_id' => $credencial->id,
                'tipo_movimiento' => $tipo,
                'metodo' => $metodo,
                'registrado_por_user_id' => $usuario->id,
                'fecha_hora' => $ahora,
            ]);
            $resultado = new ResultadoAcceso(true, $alumno, $registro);
            $inscripcion = $alumno->inscripcionActual;

            // La primera entrada de un día hábil define la asistencia.
            if ($tipo === self::ENTRADA && ! $ultimo && TrxAsistenciaDiaria::esDiaHabil($ahora)) {
                $resultado->asistencia = TrxAsistenciaDiaria::registrarEntrada($inscripcion, $ahora)->estado;
            }
            if ($tipo === self::SALIDA) {
                $permiso = TrxPermisoSalida::autorizadoHoyPara($inscripcion);
                if ($permiso) {
                    $resultado->aviso = "Salida con permiso: {$permiso->motivo} (prevista a las {$permiso->salida_prevista_at->format('H:i')})";
                } elseif ($ahora->format('H:i:s') < $inscripcion->grupo->turno->hora_salida) {
                    $resultado->alerta = 'Salida sin permiso';
                }
            }

            return $resultado;
        });
    }
}
