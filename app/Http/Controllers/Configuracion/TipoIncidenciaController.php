<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Configuracion\TipoIncidenciaRequest;
use App\Models\CatTipoIncidencia;

class TipoIncidenciaController extends Controller
{
    public function index()
    {
        return $this->mostrar(new CatTipoIncidencia);
    }

    public function edit(CatTipoIncidencia $tipo)
    {
        return $this->mostrar($tipo);
    }

    private function mostrar(CatTipoIncidencia $registro)
    {
        $registros = CatTipoIncidencia::withCount('incidencias')->ordenados()->paginarListado();

        return view('configuracion.tipos-incidencia', compact('registro', 'registros'));
    }

    public function store(TipoIncidenciaRequest $solicitud)
    {
        CatTipoIncidencia::create($solicitud->validated());

        return redirect()->route('configuracion.tipos-incidencia.index')->with('status', 'Registro creado correctamente.');
    }

    public function update(TipoIncidenciaRequest $solicitud, CatTipoIncidencia $tipo)
    {
        $tipo->update($solicitud->validated());

        return redirect()->route('configuracion.tipos-incidencia.index')->with('status', 'Registro actualizado correctamente.');
    }

    public function destroy(CatTipoIncidencia $tipo)
    {
        return $tipo->eliminar()
            ? redirect()->route('configuracion.tipos-incidencia.index')->with('status', 'Registro eliminado correctamente.')
            : back()->withErrors(['accion' => 'No se puede eliminar porque ya está en uso.']);
    }
}
