<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Configuracion\CarreraRequest;
use App\Models\AcaCarrera;

class CarreraController extends Controller
{
    public function index()
    {
        return $this->mostrar(new AcaCarrera);
    }

    public function edit(AcaCarrera $carrera)
    {
        return $this->mostrar($carrera);
    }

    private function mostrar(AcaCarrera $registro)
    {
        $registros = AcaCarrera::ordenados()->paginarListado();

        return view('configuracion.carreras', compact('registro', 'registros'));
    }

    public function store(CarreraRequest $solicitud)
    {
        AcaCarrera::create($solicitud->validated());

        return redirect()->route('configuracion.carreras.index')->with('status', 'Registro creado correctamente.');
    }

    public function update(CarreraRequest $solicitud, AcaCarrera $carrera)
    {
        $carrera->update($solicitud->validated());

        return redirect()->route('configuracion.carreras.index')->with('status', 'Registro actualizado correctamente.');
    }

    public function cambiarEstado(AcaCarrera $carrera)
    {
        $carrera->cambiarEstado();

        return redirect()->route('configuracion.carreras.index')->with('status', 'Estado actualizado correctamente.');
    }
}
