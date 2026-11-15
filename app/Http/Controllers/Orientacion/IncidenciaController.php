<?php

namespace App\Http\Controllers\Orientacion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Orientacion\BuscarAlumnoIncidenciaRequest;
use App\Http\Requests\Orientacion\CerrarIncidenciaRequest;
use App\Http\Requests\Orientacion\FiltrarIncidenciasRequest;
use App\Http\Requests\Orientacion\IncidenciaRequest;
use App\Models\AcaGrupoEscolar;
use App\Models\CatTipoIncidencia;
use App\Models\PerAlumno;
use App\Models\TrxIncidencia;
use App\Support\Auditoria;
use App\Support\PeriodoDeTrabajo;

class IncidenciaController extends Controller
{
    public function index(FiltrarIncidenciasRequest $solicitud)
    {
        $usuario = $solicitud->user();
        $filtros = $solicitud->validated();
        // Las del periodo de consulta (por la inscripción de la incidencia).
        $incidencias = TrxIncidencia::with(['alumno.persona', 'inscripcion.grupo', 'tipo', 'reportadoPor'])->visiblesPara($usuario)
            ->delPeriodo(PeriodoDeTrabajo::idConsulta())
            ->deEstado($filtros['estado'] ?? null)
            ->deGrupo(isset($filtros['grupo']) ? (int) $filtros['grupo'] : null)
            ->deCategoria($filtros['categoria'] ?? null)
            ->deGravedad($filtros['gravedad'] ?? null)
            ->entreFechas($filtros['desde'] ?? null, $filtros['hasta'] ?? null)
            ->deAlumno(isset($filtros['alumno']) ? (int) $filtros['alumno'] : null)
            ->recientes()->paginarListado();
        $grupos = AcaGrupoEscolar::visiblesEnAsistencia($usuario, PeriodoDeTrabajo::idConsulta())->with(['carrera', 'turno'])->ordenados()->get();
        $alumnoFiltrado = isset($filtros['alumno']) ? PerAlumno::with('persona')->find($filtros['alumno']) : null;
        $estados = TrxIncidencia::ESTADOS;
        $categorias = CatTipoIncidencia::CATEGORIAS;
        $gravedades = CatTipoIncidencia::GRAVEDADES;

        return view('orientacion.incidencias.index', compact('incidencias', 'filtros', 'grupos', 'alumnoFiltrado', 'estados', 'categorias', 'gravedades'));
    }

    public function create(BuscarAlumnoIncidenciaRequest $solicitud)
    {
        $usuario = $solicitud->user();
        $buscar = $solicitud->validated('buscar');
        $encontrados = filled($buscar)
            ? TrxIncidencia::alumnosElegibles($usuario)->with(['persona', 'inscripcionActual.grupo'])->buscar($buscar)->ordenados()->limit(10)->get()
            : collect();
        $elegido = $solicitud->validated('alumno')
            ? TrxIncidencia::alumnosElegibles($usuario)->with(['persona', 'inscripcionActual.grupo'])->find($solicitud->validated('alumno'))
            : null;

        return $this->formulario(new TrxIncidencia, compact('buscar', 'encontrados', 'elegido'));
    }

    public function store(IncidenciaRequest $solicitud)
    {
        Auditoria::motivoPorDefecto('Registro de incidencia');
        $incidencia = TrxIncidencia::registrar($solicitud->safe()->except('documento'), $solicitud->file('documento'), $solicitud->user());

        return redirect()->route('incidencias.show', $incidencia)->with('status', 'Incidencia registrada correctamente.');
    }

    public function show(TrxIncidencia $incidencia)
    {
        $incidencia->load(['alumno.persona', 'inscripcion.grupo', 'tipo', 'documento', 'reportadoPor', 'resueltaPor']);
        $lineaDeTiempo = $incidencia->lineaDeTiempo();

        return view('orientacion.incidencias.show', compact('incidencia', 'lineaDeTiempo'));
    }

    public function edit(TrxIncidencia $incidencia)
    {
        $incidencia->load(['alumno.persona', 'inscripcion.grupo', 'documento']);

        return $this->formulario($incidencia, ['buscar' => null, 'encontrados' => collect(), 'elegido' => $incidencia->alumno]);
    }

    public function update(IncidenciaRequest $solicitud, TrxIncidencia $incidencia)
    {
        Auditoria::motivo($solicitud->validated('motivo'));
        $incidencia->actualizar($solicitud->safe()->except(['documento', 'motivo']), $solicitud->file('documento'), $solicitud->user());

        return redirect()->route('incidencias.show', $incidencia)->with('status', 'Incidencia actualizada correctamente.');
    }

    public function seguimiento(TrxIncidencia $incidencia)
    {
        $incidencia->tomarSeguimiento();

        return back()->with('status', 'La incidencia quedó en seguimiento.');
    }

    public function cerrar(CerrarIncidenciaRequest $solicitud, TrxIncidencia $incidencia)
    {
        $incidencia->cerrar($solicitud->validated('resolucion'), $solicitud->user());

        return back()->with('status', 'Incidencia cerrada correctamente.');
    }

    private function formulario(TrxIncidencia $incidencia, array $alumno)
    {
        // Tipos agrupados por categoría para el <optgroup>.
        $tipos = CatTipoIncidencia::ordenados()->get()->groupBy('categoria');
        $categorias = CatTipoIncidencia::CATEGORIAS;

        return view('orientacion.incidencias.form', compact('incidencia', 'tipos', 'categorias') + $alumno);
    }
}
