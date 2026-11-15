<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Configuracion\EspacioRequest;
use App\Http\Requests\Configuracion\FiltrarCatalogoRequest;
use App\Models\OrgEspacioFisico;

class EspacioController extends Controller
{
    public function index(FiltrarCatalogoRequest $solicitud)
    {
        return $this->mostrar(new OrgEspacioFisico, $solicitud->validated());
    }

    public function edit(FiltrarCatalogoRequest $solicitud, OrgEspacioFisico $espacio)
    {
        return $this->mostrar($espacio, $solicitud->validated());
    }

    private function mostrar(OrgEspacioFisico $registro, array $filtros = [])
    {
        $registros = OrgEspacioFisico::ordenados()->buscar($filtros['buscar'] ?? null)->paginarListado();

        return view('configuracion.espacios', compact('registro', 'registros', 'filtros'));
    }

    public function store(EspacioRequest $solicitud)
    {
        OrgEspacioFisico::create($solicitud->validated());

        return redirect()->route('configuracion.espacios.index')->with('status', 'Registro creado correctamente.');
    }

    public function update(EspacioRequest $solicitud, OrgEspacioFisico $espacio)
    {
        $espacio->update($solicitud->validated());

        return redirect()->route('configuracion.espacios.index')->with('status', 'Registro actualizado correctamente.');
    }

    public function cambiarEstado(OrgEspacioFisico $espacio)
    {
        $espacio->cambiarEstado();

        return redirect()->route('configuracion.espacios.index')->with('status', 'Estado actualizado correctamente.');
    }
}
