<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Configuracion\TurnoRequest;
use App\Models\AcaTurno;

class TurnoController extends Controller
{
    public function index()
    {
        return $this->mostrar(new AcaTurno);
    }

    public function edit(AcaTurno $turno)
    {
        return $this->mostrar($turno);
    }

    private function mostrar(AcaTurno $registro)
    {
        $registros = AcaTurno::ordenados()->paginarListado();

        return view('configuracion.turnos', compact('registro', 'registros'));
    }

    public function store(TurnoRequest $solicitud)
    {
        AcaTurno::create($solicitud->validated());

        return redirect()->route('configuracion.turnos.index')->with('status', 'Registro creado correctamente.');
    }

    public function update(TurnoRequest $solicitud, AcaTurno $turno)
    {
        $turno->update($solicitud->validated());

        return redirect()->route('configuracion.turnos.index')->with('status', 'Registro actualizado correctamente.');
    }

    public function destroy(AcaTurno $turno)
    {
        $eliminado = $turno->eliminar();

        return $eliminado
            ? redirect()->route('configuracion.turnos.index')->with('status', 'Registro eliminado correctamente.')
            : back()->withErrors(['accion' => 'No se puede eliminar porque ya está en uso.']);
    }
}
