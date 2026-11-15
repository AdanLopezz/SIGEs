<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Configuracion\DiaInhabilRequest;
use App\Models\AcaDiaInhabil;

class DiaInhabilController extends Controller
{
    public function index()
    {
        return $this->mostrar(new AcaDiaInhabil);
    }

    public function edit(AcaDiaInhabil $dia)
    {
        return $this->mostrar($dia);
    }

    private function mostrar(AcaDiaInhabil $registro)
    {
        $registros = AcaDiaInhabil::ordenados()->paginarListado();

        return view('configuracion.dias-inhabiles', compact('registro', 'registros'));
    }

    public function store(DiaInhabilRequest $solicitud)
    {
        AcaDiaInhabil::create($solicitud->validated());

        return redirect()->route('configuracion.dias-inhabiles.index')->with('status', 'Registro creado correctamente.');
    }

    public function update(DiaInhabilRequest $solicitud, AcaDiaInhabil $dia)
    {
        $dia->update($solicitud->validated());

        return redirect()->route('configuracion.dias-inhabiles.index')->with('status', 'Registro actualizado correctamente.');
    }

    public function destroy(AcaDiaInhabil $dia)
    {
        $eliminado = $dia->eliminar();

        return $eliminado
            ? redirect()->route('configuracion.dias-inhabiles.index')->with('status', 'Registro eliminado correctamente.')
            : back()->withErrors(['accion' => 'No se puede eliminar porque ya está en uso.']);
    }
}
