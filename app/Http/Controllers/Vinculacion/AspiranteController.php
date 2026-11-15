<?php

namespace App\Http\Controllers\Vinculacion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vinculacion\AspiranteRequest;
use App\Http\Requests\Vinculacion\EstadoAspiranteRequest;
use App\Http\Requests\Vinculacion\FiltrarAspirantesRequest;
use App\Models\AcaCarrera;
use App\Models\CatMedioDifusion;
use App\Models\TrxAspirante;
use App\Support\Auditoria;

/**
 * Vinculación → Aspirantes (aspirantes.administrar). Control Escolar (alumnos.editar) los consulta en solo lectura,
 * por defecto los REGISTRADO, y los convierte en alumno desde Alumnos → Nuevo alumno (?aspirante=).
 */
class AspiranteController extends Controller
{
    public function index(FiltrarAspirantesRequest $solicitud)
    {
        $filtros = $solicitud->validated();
        // Sin filtro de estado, quien solo convierte (no administra aspirantes) ve los REGISTRADO.
        if (! isset($filtros['estado']) && ! $solicitud->user()->can('aspirantes.administrar')) {
            $filtros['estado'] = TrxAspirante::REGISTRADO;
        }
        $aspirantes = TrxAspirante::with(['persona', 'carrera', 'medio'])
            ->buscar($filtros['buscar'] ?? null)
            ->deCiclo($filtros['ciclo'] ?? null)
            ->deCarrera(isset($filtros['carrera']) ? (int) $filtros['carrera'] : null)
            ->deMedio(isset($filtros['medio']) ? (int) $filtros['medio'] : null)
            ->deEstado(($filtros['estado'] ?? null) === 'TODOS' ? null : ($filtros['estado'] ?? null))
            ->recientes()->paginarListado();
        $ciclos = TrxAspirante::ciclos();
        $carreras = AcaCarrera::ordenados()->pluck('nombre', 'id');
        $medios = CatMedioDifusion::ordenados()->pluck('nombre', 'id');
        $estados = TrxAspirante::ESTADOS;

        return view('vinculacion.aspirantes.index', compact('aspirantes', 'filtros', 'ciclos', 'carreras', 'medios', 'estados'));
    }

    public function create()
    {
        return $this->formulario(new TrxAspirante(['ciclo_ingreso' => TrxAspirante::cicloSugerido()]));
    }

    public function store(AspiranteRequest $solicitud)
    {
        $aspirante = TrxAspirante::registrar($solicitud->validated(), $solicitud->user());
        $mensaje = 'Aspirante registrado correctamente.'.($aspirante->personaReutilizada
            ? ' La persona ya estaba registrada: se conservaron sus datos (Control Escolar los revisa al darla de alta como alumno).' : '');

        return redirect()->route('aspirantes.show', $aspirante)->with('status', $mensaje);
    }

    public function show(TrxAspirante $aspirante)
    {
        $aspirante->load(['persona.alumno', 'carrera', 'medio']);
        $estados = collect(TrxAspirante::ESTADOS)->only(TrxAspirante::ESTADOS_MANUALES)->except($aspirante->estado)->all();

        return view('vinculacion.aspirantes.show', compact('aspirante', 'estados'));
    }

    public function edit(TrxAspirante $aspirante)
    {
        if (! $aspirante->esEditable()) {
            return redirect()->route('aspirantes.show', $aspirante)->withErrors(['accion' => 'Solo se edita un aspirante prospecto o registrado.']);
        }

        return $this->formulario($aspirante->load('persona'));
    }

    public function update(AspiranteRequest $solicitud, TrxAspirante $aspirante)
    {
        Auditoria::motivo($solicitud->validated('motivo'));
        $aspirante->actualizar($solicitud->safe()->except('motivo'));

        return redirect()->route('aspirantes.show', $aspirante)->with('status', 'Aspirante actualizado correctamente.');
    }

    public function estado(EstadoAspiranteRequest $solicitud, TrxAspirante $aspirante)
    {
        Auditoria::motivo($solicitud->validated('motivo'));
        $aspirante->cambiarEstado($solicitud->validated('estado'));

        return redirect()->route('aspirantes.show', $aspirante)->with('status', 'Estado actualizado: '.$aspirante->fresh()->estado_nombre.'.');
    }

    private function formulario(TrxAspirante $aspirante)
    {
        $carreras = AcaCarrera::where(fn ($consulta) => $consulta->where('activo', true)
            ->when($aspirante->exists, fn ($consulta) => $consulta->orWhere('id', $aspirante->aca_carrera_id)))->ordenados()->pluck('nombre', 'id');
        $medios = CatMedioDifusion::ordenados()->pluck('nombre', 'id');
        $personaCompartida = $aspirante->exists && $aspirante->personaCompartida();

        return view('vinculacion.aspirantes.form', compact('aspirante', 'carreras', 'medios', 'personaCompartida'));
    }
}
