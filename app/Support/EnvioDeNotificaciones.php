<?php

namespace App\Support;

use App\Mail\AvisoEscolar;
use App\Models\ComNotificacion;
use App\Models\ComNotificacionIntento;
use App\Models\PerPersona;
use App\Models\PerTutorAlumno;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Mailer\Exception\UnexpectedResponseException;
use Throwable;

class EnvioDeNotificaciones
{
    public function habilitado(): bool
    {
        $mailer = config('siges.notificaciones.mailer');

        // Un transporte de log/array o failover a log no acredita un envío real.
        return (bool) config('siges.notificaciones.habilitadas')
            && config("mail.mailers.{$mailer}.transport") === 'smtp';
    }

    public function puedeIntentar(ComNotificacion $aviso): bool
    {
        if ($aviso->canal !== ComNotificacion::EMAIL || ! $aviso->trx_incidencia_id
            || ! in_array($aviso->estado, [ComNotificacion::PENDIENTE, ComNotificacion::FALLIDA], true)
            || $aviso->intentos()->where('exitoso', true)->exists()) {
            return false;
        }
        $ultimo = $aviso->intentos()->latest('id')->first();
        if (! $ultimo || $ultimo->resultado === ComNotificacionIntento::REINTENTO) {
            return true;
        }
        if ($ultimo->resultado !== ComNotificacionIntento::ERROR) {
            return false;
        }
        $reinicio = $aviso->intentos()->where('resultado', ComNotificacionIntento::REINTENTO)->max('id') ?? 0;

        return $aviso->intentos()->where('id', '>', $reinicio)->count() < max(1, (int) config('siges.notificaciones.maximo_intentos'))
            && $ultimo->fecha_intento->lte(now()->subMinutes(max(1, (int) config('siges.notificaciones.espera_minutos'))));
    }

    public function enviar(int $id): void
    {
        if (! $this->habilitado()) {
            return;
        }
        // Reserva confirmada antes de contactar SMTP. Si muere el proceso no se vuelve a enviar a ciegas.
        $intentoId = DB::transaction(function () use ($id) {
            $aviso = ComNotificacion::whereKey($id)->lockForUpdate()->first();
            if (! $aviso || ! $this->puedeIntentar($aviso)) {
                return null;
            }

            return Auditoria::conMotivo('Reserva de envío de aviso escolar', fn () => $aviso->intentos()->create([
                'fecha_intento' => now(), 'exitoso' => false, 'resultado' => ComNotificacionIntento::EN_PROCESO,
            ])->id);
        });
        if (! $intentoId) {
            return;
        }

        // Sin reintentos de transacción: el correo es un efecto externo que no admite rollback.
        DB::transaction(function () use ($id, $intentoId) {
            $aviso = ComNotificacion::whereKey($id)->lockForUpdate()->firstOrFail();
            $intento = ComNotificacionIntento::findOrFail($intentoId);
            if ($intento->resultado !== ComNotificacionIntento::EN_PROCESO
                || (int) $aviso->intentos()->max('id') !== $intento->id) {
                return;
            }
            if (! in_array($aviso->estado, [ComNotificacion::PENDIENTE, ComNotificacion::FALLIDA], true)) {
                $this->terminar($aviso, $intento, ComNotificacionIntento::OMITIDO);
                return;
            }
            // El bloqueo de la tutela serializa el envío respecto de una revocación concurrente.
            $incidencia = $aviso->incidencia;
            $tutor = $incidencia ? PerTutorAlumno::where('per_alumno_id', $incidencia->per_alumno_id)
                ->where('per_persona_id', $aviso->per_persona_id)->activos()->where('recibe_notificaciones', true)
                ->lockForUpdate()->first() : null;
            if (! $tutor || ! $incidencia->tipo?->notifica_tutor) {
                $this->terminar($aviso, $intento, 'Sin autorización vigente para notificar esta incidencia.');
                return;
            }
            $persona = PerPersona::whereKey($aviso->per_persona_id)->lockForUpdate()->first();
            $correo = trim((string) $persona?->email_personal);
            if (! filter_var($correo, FILTER_VALIDATE_EMAIL)
                || strcasecmp(substr(strrchr($correo, '@') ?: '', 1), config('siges.cuentas.dominio')) === 0) {
                $this->terminar($aviso, $intento, 'El destinatario no tiene un correo personal válido.');
                return;
            }
            try {
                $enviado = Mail::mailer(config('siges.notificaciones.mailer'))->to($correo)->send(new AvisoEscolar($aviso->id));
                // Un listener puede cancelar el envío devolviendo false.
                $resultado = $enviado === null ? ComNotificacionIntento::INCIERTO : ComNotificacionIntento::ENVIADO;
            } catch (UnexpectedResponseException $error) {
                // Solo un rechazo explícito SMTP es reintentable sin asumir una entrega.
                $resultado = $error->getCode() >= 400 && $error->getCode() < 600
                    ? ComNotificacionIntento::ERROR : ComNotificacionIntento::INCIERTO;
            } catch (Throwable) {
                // No almacenar mensajes SMTP: pueden incluir credenciales, direcciones o contenido.
                $resultado = ComNotificacionIntento::INCIERTO;
            }
            $this->terminar($aviso, $intento, $resultado);
        });
    }

    private function terminar(ComNotificacion $aviso, ComNotificacionIntento $intento, string $resultado): void
    {
        Auditoria::conMotivo('Resultado del envío de aviso escolar', function () use ($aviso, $intento, $resultado) {
            $exitoso = $resultado === ComNotificacionIntento::ENVIADO;
            $intento->update(['resultado' => $resultado, 'exitoso' => $exitoso]);
            if (! $aviso->estaLeida() && $resultado !== ComNotificacionIntento::OMITIDO) {
                $aviso->update(['estado' => $exitoso ? ComNotificacion::ENVIADA : ComNotificacion::FALLIDA]);
            }
        });
    }

    public function reprogramar(ComNotificacion $aviso, string $motivo, bool $entregaDescartada): void
    {
        DB::transaction(function () use ($aviso, $motivo, $entregaDescartada) {
            $actual = ComNotificacion::whereKey($aviso->id)->lockForUpdate()->firstOrFail();
            $ultimo = $actual->intentos()->latest('id')->first();
            if ($actual->canal !== ComNotificacion::EMAIL || ! $actual->trx_incidencia_id
                || ! in_array($actual->estado, [ComNotificacion::PENDIENTE, ComNotificacion::FALLIDA], true)
                || $actual->intentos()->where('exitoso', true)->exists() || ! $ultimo) {
                throw ValidationException::withMessages(['motivo' => 'Este aviso no admite reintento.']);
            }
            if (in_array($ultimo->resultado, [ComNotificacionIntento::EN_PROCESO, ComNotificacionIntento::INCIERTO], true)
                && (! $entregaDescartada || $ultimo->fecha_intento->gt(now()->subMinutes(5)))) {
                throw ValidationException::withMessages(['entrega_descartada' => 'Espera cinco minutos y confirma con el proveedor que no hubo entrega antes de reintentar.']);
            }
            if ($ultimo->resultado === ComNotificacionIntento::REINTENTO) {
                throw ValidationException::withMessages(['motivo' => 'El aviso ya está programado para reintento.']);
            }
            Auditoria::conMotivo($motivo, function () use ($actual, $ultimo) {
                if ($ultimo->resultado === ComNotificacionIntento::EN_PROCESO) {
                    $ultimo->update(['resultado' => ComNotificacionIntento::INCIERTO]);
                }
                $actual->intentos()->create(['fecha_intento' => now(), 'exitoso' => false, 'resultado' => ComNotificacionIntento::REINTENTO]);
                $actual->update(['estado' => ComNotificacion::PENDIENTE]);
            });
        });
    }
}
