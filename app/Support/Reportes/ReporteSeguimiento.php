<?php

namespace App\Support\Reportes;

use App\Models\AcaGrupoEscolar;
use App\Models\TrxSeguimientoAlumno;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Seguimiento: SOLO cifras (por motivo y estado, en riesgo de abandono y por grupo); nunca nombres, descripciones ni notas.
 * Lo ven quienes tienen seguimiento.administrar y el Director. Administrador, Subdirector y Director: todos los grupos del alcance;
 * Orientador (y Tutor): solo los grupos que acompañan (AcaGrupoEscolar::scopeAcompanadosPor).
 */
class ReporteSeguimiento extends Reporte
{
    public const CLAVE = 'seguimiento';

    public const TITULO = 'Seguimiento';

    public const DESCRIPCION = 'Casos de seguimiento por motivo y estado, en riesgo de abandono y por grupo (solo cifras).';

    public const ICONO = 'bi-person-heart';

    public const ROLES_VEN_TODOS = ['Administrador', 'Subdirector', 'Director'];

    public static function puedeVer(User $usuario): bool
    {
        return parent::puedeVer($usuario) && ($usuario->can('seguimiento.administrar') || $usuario->hasRole('Director'));
    }

    public function generar(AlcanceDeReporte $alcance, array $opciones): array
    {
        $grupos = $alcance->grupoIds();
        if (! $alcance->usuario->hasAnyRole(self::ROLES_VEN_TODOS)) {
            $grupos = array_values(array_intersect($grupos, AcaGrupoEscolar::acompanadosPor($alcance->usuario, $alcance->periodo->id)->pluck('aca_grupo_escolar.id')->all()));
        }
        // Solo motivo, estado, riesgo y grupo: nunca descripcion ni notas.
        $base = fn () => DB::table('trx_seguimiento_alumno as s')->join('aca_inscripcion as si', 'si.id', '=', 's.aca_inscripcion_id')
            ->whereIn('si.aca_grupo_escolar_id', $grupos ?: [0])
            ->selectRaw("count(*) filter (where s.estado = 'ABIERTO') as abiertos")
            ->selectRaw("count(*) filter (where s.estado = 'EN_SEGUIMIENTO') as en_seguimiento")
            ->selectRaw("count(*) filter (where s.estado = 'CERRADO') as cerrados")
            ->selectRaw('count(*) filter (where s.riesgo_abandono) as en_riesgo')
            ->selectRaw("count(*) filter (where s.riesgo_abandono and s.estado <> 'CERRADO') as en_riesgo_abiertos")
            ->selectRaw('count(*) as total');
        $fila = fn ($f, string $etiqueta) => [$etiqueta, (int) $f->abiertos, (int) $f->en_seguimiento, (int) $f->cerrados, (int) $f->en_riesgo, (int) $f->total];
        $columnas = fn (string $primera) => [$primera, 'Abiertos', 'En seguimiento', 'Cerrados', 'En riesgo', 'Total'];

        $porMotivo = $base()->groupBy('s.motivo')->addSelect('s.motivo as etiqueta')->get()->keyBy('etiqueta');
        $motivos = collect(TrxSeguimientoAlumno::MOTIVOS)->filter(fn ($nombre, $clave) => $porMotivo->has($clave))->map(fn ($nombre, $clave) => $fila($porMotivo[$clave], $nombre));
        $porGrupo = $base()->groupBy('si.aca_grupo_escolar_id')->addSelect('si.aca_grupo_escolar_id as grupo')->get()
            ->sortBy(fn ($f) => $alcance->grupos->search(fn ($g) => $g->id === (int) $f->grupo))
            ->map(fn ($f) => $fila($f, $this->etiquetaGrupo($alcance, (int) $f->grupo)));
        $totales = $base()->first();

        return [
            'resumen' => [['Casos', (int) $totales->total], ['Sin cerrar', (int) $totales->abiertos + (int) $totales->en_seguimiento],
                ['En riesgo de abandono (sin cerrar)', (int) $totales->en_riesgo_abiertos]],
            'avisos' => ['Por privacidad, este reporte solo tiene cifras: sin nombres, descripciones ni notas.'],
            'secciones' => [
                $this->seccion('Por motivo', $columnas('Motivo'), $motivos, ['grafica' => ['etiqueta' => 0, 'valor' => 5]]),
                $this->seccion('Por grupo', $columnas('Grupo'), $porGrupo),
            ],
        ];
    }
}
