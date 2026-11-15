<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Configuracion\AreaRequest;
use App\Models\OrgArea;

class AreaController extends Controller
{
    public function index()
    {
        return $this->mostrar(new OrgArea);
    }

    public function edit(OrgArea $area)
    {
        return $this->mostrar($area);
    }

    private function mostrar(OrgArea $registro)
    {
        $registros = OrgArea::withCount('personal')->ordenados()->paginarListado();

        return view('configuracion.areas', compact('registro', 'registros'));
    }

    public function store(AreaRequest $solicitud)
    {
        OrgArea::create($solicitud->validated());

        return redirect()->route('configuracion.areas.index')->with('status', 'Registro creado correctamente.');
    }

    public function update(AreaRequest $solicitud, OrgArea $area)
    {
        $area->update($solicitud->validated());

        return redirect()->route('configuracion.areas.index')->with('status', 'Registro actualizado correctamente.');
    }

    public function destroy(OrgArea $area)
    {
        $eliminado = $area->eliminar();

        return $eliminado
            ? redirect()->route('configuracion.areas.index')->with('status', 'Registro eliminado correctamente.')
            : back()->withErrors(['accion' => 'No se puede eliminar porque ya está en uso.']);
    }
}
