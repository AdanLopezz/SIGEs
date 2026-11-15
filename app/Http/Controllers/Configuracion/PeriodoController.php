<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Configuracion\PeriodoRequest;
use App\Models\AcaPeriodoEscolar;
use Illuminate\Validation\ValidationException;

class PeriodoController extends Controller
{
    public function index()
    {
        return $this->mostrar(new AcaPeriodoEscolar);
    }

    public function edit(AcaPeriodoEscolar $periodo)
    {
        return $this->mostrar($periodo);
    }

    private function mostrar(AcaPeriodoEscolar $registro)
    {
        $registros = AcaPeriodoEscolar::ordenados()->paginarListado();

        return view('configuracion.periodos', compact('registro', 'registros'));
    }

    public function store(PeriodoRequest $solicitud)
    {
        (new AcaPeriodoEscolar)->guardar($solicitud->validated());

        return redirect()->route('configuracion.periodos.index')->with('status', 'Registro creado correctamente.');
    }

    public function update(PeriodoRequest $solicitud, AcaPeriodoEscolar $periodo)
    {
        $periodo->guardar($solicitud->validated());

        return redirect()->route('configuracion.periodos.index')->with('status', 'Registro actualizado correctamente.');
    }

    public function destroy(AcaPeriodoEscolar $periodo)
    {
        try {
            $eliminado = $periodo->eliminar();
        } catch (ValidationException $excepcion) {
            return back()->withErrors($excepcion->errors());
        }

        return $eliminado
            ? redirect()->route('configuracion.periodos.index')->with('status', 'Registro eliminado correctamente.')
            : back()->withErrors(['accion' => 'No se puede eliminar porque ya está en uso.']);
    }

    public function activar(AcaPeriodoEscolar $periodo)
    {
        $periodo->activar();

        return redirect()->route('configuracion.periodos.index')->with('status', 'Periodo activado correctamente.');
    }
}
