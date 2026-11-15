<?php

namespace App\Support\Reportes;

use App\Models\TrxAsistenciaClase;
use App\Models\User;
use Illuminate\Support\Collection;

// Reportes disponibles (clave de la URL => clase), su visibilidad y el formato de las celdas y del CSV.
class Catalogo
{
    public const REPORTES = [
        ReporteMatricula::CLAVE => ReporteMatricula::class,
        ReporteAsistenciaDiaria::CLAVE => ReporteAsistenciaDiaria::class,
        ReporteAsistenciaMateria::CLAVE => ReporteAsistenciaMateria::class,
        ReporteReprobacion::CLAVE => ReporteReprobacion::class,
        ReporteIncidencias::CLAVE => ReporteIncidencias::class,
        ReporteBajas::CLAVE => ReporteBajas::class,
        ReporteSeguimiento::CLAVE => ReporteSeguimiento::class,
    ];

    // Primer carácter que Excel interpreta como fórmula.
    private const INICIO_DE_FORMULA = '/^[=+\-@\t\r]/';

    /** @return Collection<string, class-string<Reporte>> */
    public static function para(User $usuario): Collection
    {
        return collect(self::REPORTES)->filter(fn (string $clase) => $clase::puedeVer($usuario));
    }

    /** @return class-string<Reporte>|null */
    public static function clase(string $clave): ?string
    {
        return self::REPORTES[$clave] ?? null;
    }

    // Celda en pantalla e impresión: porcentajes con "%", números con separador de miles.
    public static function celda(mixed $valor, bool $porcentaje): string
    {
        return match (true) {
            $porcentaje => TrxAsistenciaClase::formatear($valor === null ? null : (float) $valor),
            is_int($valor) => number_format($valor),
            $valor === null => '—',
            default => (string) $valor,
        };
    }

    // Celda del CSV: números sin formato (punto decimal) para que Excel los sume. Un texto que empieza con = + - @
    // (p. ej. un motivo capturado) se antepone con ' para que Excel no lo tome como fórmula.
    public static function celdaCsv(mixed $valor): string|int|float
    {
        if (is_string($valor) && preg_match(self::INICIO_DE_FORMULA, $valor)) {
            return "'".$valor;
        }

        return $valor ?? '';
    }

    /**
     * CSV en UTF-8 con BOM (Excel respeta los acentos), separado por comas: título, filtros, resumen y cada sección con su título.
     * @return resource
     */
    public static function csv(string $titulo, AlcanceDeReporte $alcance, array $datos)
    {
        $salida = fopen('php://temp', 'r+');
        fwrite($salida, "\xEF\xBB\xBF");
        fputcsv($salida, [$titulo]);
        fputcsv($salida, [$alcance->descripcion()]);
        fputcsv($salida, ['Generado el '.now()->format('d/m/Y H:i')]);
        fputcsv($salida, []);
        foreach ($datos['resumen'] as $indice => [$etiqueta, $valor]) {
            fputcsv($salida, [$etiqueta.(in_array($indice, $datos['porcentajesResumen'] ?? [], true) ? ' (%)' : ''), static::celdaCsv($valor)]);
        }
        foreach ($datos['secciones'] as $seccion) {
            fputcsv($salida, []);
            fputcsv($salida, [$seccion['titulo']]);
            fputcsv($salida, $seccion['columnas']);
            foreach ($seccion['filas'] as $fila) {
                fputcsv($salida, array_map([static::class, 'celdaCsv'], array_values($fila)));
            }
        }
        rewind($salida);

        return $salida;
    }
}
