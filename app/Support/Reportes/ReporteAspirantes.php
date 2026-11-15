<?php

namespace App\Support\Reportes;

use App\Models\AcaCarrera;
use App\Models\CatMedioDifusion;
use App\Models\TrxAspirante;
use Illuminate\Support\Facades\DB;

// Captación de aspirantes (trx_aspirante): por medio de difusión, por carrera y por estado. Solo cifras. Cualquiera con reportes.ver.
// No depende de grupos: filtros propios ciclo (por omisión el más reciente) y carrera.
class ReporteAspirantes extends Reporte
{
    public const CLAVE = 'aspirantes';

    public const TITULO = 'Captación de aspirantes';

    public const DESCRIPCION = 'Aspirantes por medio de difusión, carrera y estado en un ciclo de ingreso.';

    public const ICONO = 'bi-person-plus';

    public const FILTROS_DE_GRUPO = false;

    public static function opciones(): array
    {
        $ciclos = TrxAspirante::ciclos();

        return ['ciclo' => ['label' => 'Ciclo de ingreso', 'opciones' => array_combine($ciclos, $ciclos) + ['TODOS' => 'Todos los ciclos'], 'omision' => $ciclos[0] ?? 'TODOS']];
    }

    public function generar(AlcanceDeReporte $alcance, array $opciones): array
    {
        $ciclo = ($opciones['ciclo'] ?? 'TODOS') === 'TODOS' ? null : $opciones['ciclo'];
        $base = fn () => DB::table('trx_aspirante as a')
            ->when($ciclo, fn ($consulta) => $consulta->where('a.ciclo_ingreso', $ciclo))
            ->when($alcance->carrera, fn ($consulta) => $consulta->where('a.aca_carrera_id', $alcance->carrera))
            ->selectRaw("count(*) filter (where a.estado = 'PROSPECTO') as prospectos")
            ->selectRaw("count(*) filter (where a.estado = 'REGISTRADO') as registrados")
            ->selectRaw("count(*) filter (where a.estado = 'INSCRITO') as inscritos")
            ->selectRaw("count(*) filter (where a.estado = 'DESCARTADO') as descartados")
            ->selectRaw('count(*) as total');
        $fila = fn ($f, string $etiqueta) => [$etiqueta, (int) $f->prospectos, (int) $f->registrados, (int) $f->inscritos, (int) $f->descartados, (int) $f->total,
            static::porcentaje((int) $f->inscritos, (int) $f->total)];
        $columnas = fn (string $primera) => [$primera, 'Prospectos', 'Registrados', 'Inscritos', 'Descartados', 'Total', 'Conversión (%)'];
        $conGrafica = ['porcentajes' => [6], 'grafica' => ['etiqueta' => 0, 'valor' => 5]];

        $porMedio = $base()->join('cat_medio_difusion as m', 'm.id', '=', 'a.cat_medio_difusion_id')->groupBy('m.nombre')->addSelect('m.nombre as etiqueta')
            ->orderByDesc('total')->orderBy('m.nombre')->get()->map(fn ($f) => $fila($f, $f->etiqueta));
        $porCarrera = $base()->join('aca_carrera as c', 'c.id', '=', 'a.aca_carrera_id')->groupBy('c.nombre')->addSelect('c.nombre as etiqueta')
            ->orderByDesc('total')->orderBy('c.nombre')->get()->map(fn ($f) => $fila($f, $f->etiqueta));
        $totales = $base()->first();
        $porEstado = collect(TrxAspirante::ESTADOS)->map(fn ($nombre, $estado) => [$nombre, match ($estado) {
            TrxAspirante::PROSPECTO => (int) $totales->prospectos, TrxAspirante::REGISTRADO => (int) $totales->registrados,
            TrxAspirante::INSCRITO => (int) $totales->inscritos, default => (int) $totales->descartados}]);

        $partes = ['Ciclo: '.($ciclo ?? 'todos')];
        if ($alcance->carrera) {
            $partes[] = 'Carrera: '.AcaCarrera::find($alcance->carrera)?->nombre;
        }

        return [
            'descripcion' => implode(' · ', $partes),
            'resumen' => [['Aspirantes', (int) $totales->total], ['Inscritos', (int) $totales->inscritos], ['Descartados', (int) $totales->descartados],
                ['Conversión', static::porcentaje((int) $totales->inscritos, (int) $totales->total)]],
            'porcentajesResumen' => [3],
            'avisos' => ['Conversión = aspirantes inscritos como alumnos entre el total. Solo cifras, sin nombres.'],
            'secciones' => [
                $this->seccion('Por medio de difusión', $columnas('Medio'), $porMedio, $conGrafica),
                $this->seccion('Por carrera', $columnas('Carrera'), $porCarrera, $conGrafica),
                $this->seccion('Por estado', ['Estado', 'Aspirantes'], $porEstado, ['grafica' => ['etiqueta' => 0, 'valor' => 1]]),
            ],
        ];
    }
}
