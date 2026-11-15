<?php

namespace App\Http\Controllers\Vinculacion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vinculacion\EmpresaRequest;
use App\Http\Requests\Vinculacion\FiltrarEmpresasRequest;
use App\Models\OrgConvenio;
use App\Models\OrgEmpresa;
use App\Support\Auditoria;

// Vinculación → Empresas: dual.administrar administra; dual.revisar (Subdirector) consulta.
class EmpresaController extends Controller
{
    public function index(FiltrarEmpresasRequest $solicitud)
    {
        $buscar = $solicitud->validated('buscar');
        $empresas = OrgEmpresa::withCount(['convenios', 'convenios as vigentes_count' => fn ($convenios) => $convenios->deEstado(OrgConvenio::VIGENTE)])
            ->buscar($buscar)->ordenados()->paginarListado();

        return view('vinculacion.empresas.index', compact('empresas', 'buscar'));
    }

    public function create()
    {
        return view('vinculacion.empresas.form', ['empresa' => new OrgEmpresa]);
    }

    public function store(EmpresaRequest $solicitud)
    {
        Auditoria::motivoPorDefecto('Registro de empresa');
        $empresa = OrgEmpresa::create($solicitud->validated());

        return redirect()->route('empresas.show', $empresa)->with('status', 'Empresa registrada correctamente.');
    }

    public function show(OrgEmpresa $empresa)
    {
        $convenios = $empresa->convenios()->recientes()->get();

        return view('vinculacion.empresas.show', compact('empresa', 'convenios'));
    }

    public function edit(OrgEmpresa $empresa)
    {
        return view('vinculacion.empresas.form', compact('empresa'));
    }

    public function update(EmpresaRequest $solicitud, OrgEmpresa $empresa)
    {
        Auditoria::motivo($solicitud->validated('motivo'));
        $empresa->update($solicitud->safe()->except('motivo'));

        return redirect()->route('empresas.show', $empresa)->with('status', 'Empresa actualizada correctamente.');
    }

    public function destroy(OrgEmpresa $empresa)
    {
        Auditoria::motivoPorDefecto('Eliminación de empresa');

        return $empresa->eliminar()
            ? redirect()->route('empresas.index')->with('status', 'Empresa eliminada correctamente.')
            : back()->withErrors(['accion' => 'No se puede eliminar la empresa porque tiene convenios.']);
    }
}
