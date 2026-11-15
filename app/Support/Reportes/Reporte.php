<?php

namespace App\Support\Reportes;

use App\Models\User;
use Illuminate\Database\Query\Builder as Consulta;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Base de los reportes (Reportes → reportes.show / csv / imprimir). Cada reporte arma, sobre AlcanceDeReporte:
 *  ['resumen' => [[etiqueta, valor]], 'avisos' => [texto], 'secciones' => [sección], 'descripcion' => filtros (opcional; si no, la del alcance)]
 * Sección: ['titulo', 'columnas', 'filas' (Collection de arrays con valores crudos), 'porcentajes' (índices de columnas en %),
 *   'grafica' => ['etiqueta' => índice, 'valor' => índice] | null, 'listado' => bool (lista de alumnos: se pagina en pantalla), 'vacio' => texto].
 * Nunca se seleccionan datos de salud, descripciones de incidencias, notas ni descripciones de seguimiento.
 */
abstract class Reporte
{
    public const CLAVE = '';

    public const TITULO = '';

    public const DESCRIPCION = '';

    public const ICONO = 'bi-bar-chart';

    // false: el reporte no depende de grupos (p. ej. aspirantes) y no muestra los filtros de periodo, turno y grupo.
    public const FILTROS_DE_GRUPO = true;

    // Además de reportes.ver (que exige la ruta).
    public static function puedeVer(User $usuario): bool
    {
        return $usuario->can('reportes.ver');
    }

    // Filtros propios del reporte: [nombre => ['label', 'opciones' => [valor => texto], 'omision']].
    public static function opciones(): array
    {
        return [];
    }

    abstract public function generar(AlcanceDeReporte $alcance, array $opciones): array;

    // ── Utilidades para los reportes ─────────────────────────────────────────

    protected function seccion(string $titulo, array $columnas, Collection $filas, array $extra = []): array
    {
        // $extra sobrescribe los valores por omisión (con "+" ganarían los de la izquierda).
        return array_merge(['titulo' => $titulo, 'columnas' => $columnas, 'filas' => $filas->values(), 'porcentajes' => [], 'grafica' => null, 'listado' => false,
            'vacio' => 'Sin datos con los filtros elegidos.'], $extra);
    }

    // Inscripciones (de cualquier estado) de los grupos del alcance.
    protected function inscripciones(AlcanceDeReporte $alcance, string $alias = 'i'): Consulta
    {
        return DB::table("aca_inscripcion as {$alias}")->whereIn("{$alias}.aca_grupo_escolar_id", $alcance->grupoIds() ?: [0]);
    }

    protected static function porcentaje(int $parte, int $total): ?float
    {
        return $total > 0 ? round(100 * $parte / $total, 1) : null;
    }

    // Nombre del alumno (SQL), apellidos primero.
    protected static function nombreAlumno(string $persona = 'p'): string
    {
        return "concat_ws(' ', {$persona}.primer_apellido, nullif({$persona}.segundo_apellido, ''), {$persona}.nombre)";
    }

    protected function etiquetaGrupo(AlcanceDeReporte $alcance, int $grupo): string
    {
        return $alcance->grupoPorId($grupo)?->identificador ?? '#'.$grupo;
    }

    // Agrupa filas [clave => [contadores]] sumando los contadores por $clave(fila).
    protected static function sumarPor(Collection $filas, callable $clave, array $campos): Collection
    {
        return $filas->groupBy($clave)->map(fn (Collection $grupo) => collect($campos)->mapWithKeys(fn ($campo) => [$campo => (int) $grupo->sum($campo)])->all());
    }
}
