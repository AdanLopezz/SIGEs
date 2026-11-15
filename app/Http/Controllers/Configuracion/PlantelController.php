<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Configuracion\PlantelRequest;
use App\Models\SysConfiguracion;

class PlantelController extends Controller
{
    public function edit()
    {
        return view('configuracion.plantel', ['registro' => SysConfiguracion::actual() ?? new SysConfiguracion(['calificacion_minima' => 6])]);
    }

    public function update(PlantelRequest $solicitud)
    {
        SysConfiguracion::guardar($solicitud->validated());

        return redirect()->route('configuracion.edit')->with('status', 'Datos del plantel actualizados correctamente.');
    }
}
