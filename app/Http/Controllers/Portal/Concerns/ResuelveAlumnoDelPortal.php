<?php

namespace App\Http\Controllers\Portal\Concerns;

use App\Models\PerAlumno;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * El alumno del portal sale del contexto, nunca de la URL:
 * - "mi-escuela.*": el alumno de la persona de la cuenta (403 si no lo es);
 * - "mis-hijos.*": el hijo elegido en la sesión, comprobado en cada petición (si ya no es su tutor activo, vuelve al selector).
 */
trait ResuelveAlumnoDelPortal
{
    public const HIJO_ELEGIDO = 'portal.hijo';

    protected function contexto(Request $solicitud): string
    {
        return $solicitud->routeIs('mis-hijos.*') ? 'mis-hijos' : 'mi-escuela';
    }

    protected function alumnoDelPortal(Request $solicitud): PerAlumno
    {
        $usuario = $solicitud->user();
        if ($this->contexto($solicitud) === 'mi-escuela') {
            $alumno = PerAlumno::deLaCuenta($usuario);
            abort_unless($alumno && Gate::allows('portal-propio', $alumno), 403);

            return $alumno;
        }

        $elegido = $solicitud->session()->get(self::HIJO_ELEGIDO);
        $alumno = $elegido ? PerAlumno::find($elegido) : null;
        // El elegido dejó de ser válido (p. ej. el tutor se desactivó): se descarta.
        if ($alumno && Gate::denies('portal-hijo', $alumno)) {
            $alumno = null;
        }
        if (! $alumno) {
            // Con un solo hijo se elige solo.
            $hijos = PerAlumno::deTutor($usuario->per_persona_id)->limit(2)->get();
            $alumno = $hijos->count() === 1 ? $hijos->first() : null;
        }
        if (! $alumno || Gate::denies('portal-hijo', $alumno)) {
            $solicitud->session()->forget(self::HIJO_ELEGIDO);
            throw new HttpResponseException(redirect()->route('mis-hijos.seleccion.index'));
        }
        $solicitud->session()->put(self::HIJO_ELEGIDO, $alumno->id);

        return $alumno;
    }
}
