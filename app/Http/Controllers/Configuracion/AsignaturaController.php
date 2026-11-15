<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Configuracion\AsignaturaRequest;
use App\Http\Requests\Configuracion\FiltrarCatalogoRequest;
use App\Models\AcaAsignatura;

class AsignaturaController extends Controller
{
    public function index(FiltrarCatalogoRequest $solicitud)
    {
        return $this->mostrar(new AcaAsignatura, $solicitud->validated());
    }

    public function edit(FiltrarCatalogoRequest $solicitud, AcaAsignatura $asignatura)
    {
        return $this->mostrar($asignatura, $solicitud->validated());
    }

    private function mostrar(AcaAsignatura $registro, array $filtros = [])
    {
        $registros = AcaAsignatura::ordenados()->buscar($filtros['buscar'] ?? null)->delSemestre(isset($filtros['semestre']) ? (int) $filtros['semestre'] : null)->paginarListado();

        return view('configuracion.asignaturas', compact('registro', 'registros', 'filtros'));
    }

    public function store(AsignaturaRequest $solicitud)
    {
        AcaAsignatura::create($solicitud->validated());

        return redirect()->route('configuracion.asignaturas.index')->with('status', 'Registro creado correctamente.');
    }

    public function update(AsignaturaRequest $solicitud, AcaAsignatura $asignatura)
    {
        $asignatura->update($solicitud->validated());

        return redirect()->route('configuracion.asignaturas.index')->with('status', 'Registro actualizado correctamente.');
    }

    public function cambiarEstado(AcaAsignatura $asignatura)
    {
        $asignatura->cambiarEstado();

        return redirect()->route('configuracion.asignaturas.index')->with('status', 'Estado actualizado correctamente.');
    }
}
