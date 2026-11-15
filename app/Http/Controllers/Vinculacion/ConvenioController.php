<?php

namespace App\Http\Controllers\Vinculacion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vinculacion\CancelarConvenioRequest;
use App\Http\Requests\Vinculacion\ConvenioRequest;
use App\Http\Requests\Vinculacion\FiltrarConveniosRequest;
use App\Models\OrgConvenio;
use App\Models\OrgEmpresa;
use App\Support\Auditoria;
use Illuminate\Http\Request;

// Vinculación → Convenios: dual.administrar administra; dual.revisar (Subdirector) consulta. El estado mostrado es el efectivo.
class ConvenioController extends Controller
{
    public function index(FiltrarConveniosRequest $solicitud)
    {
        $filtros = $solicitud->validated();
        $convenios = OrgConvenio::with('empresa')
            ->buscar($filtros['buscar'] ?? null)
            ->deTipo($filtros['tipo'] ?? null)
            ->deEstado($filtros['estado'] ?? null)
            ->deEmpresa(isset($filtros['empresa']) ? (int) $filtros['empresa'] : null)
            ->when(! empty($filtros['vence_pronto']), fn ($consulta) => $consulta->vencenPronto())
            ->recientes()->paginarListado();
        $porVencer = OrgConvenio::vencenPronto()->count();
        $empresas = OrgEmpresa::ordenados()->pluck('nombre', 'id');

        return view('vinculacion.convenios.index', compact('convenios', 'filtros', 'porVencer', 'empresas'));
    }

    public function create(Request $solicitud)
    {
        $convenio = new OrgConvenio(['org_empresa_id' => $solicitud->integer('empresa') ?: null]);

        return $this->formulario($convenio);
    }

    public function store(ConvenioRequest $solicitud)
    {
        $convenio = OrgConvenio::registrar($solicitud->safe()->except('documento'), $solicitud->file('documento'), $solicitud->user());

        return redirect()->route('convenios.show', $convenio)->with('status', 'Convenio registrado correctamente.');
    }

    public function show(OrgConvenio $convenio)
    {
        $convenio->load(['empresa', 'documento']);

        return view('vinculacion.convenios.show', compact('convenio'));
    }

    public function edit(OrgConvenio $convenio)
    {
        if (! $convenio->estaVigente()) {
            return redirect()->route('convenios.show', $convenio)->withErrors(['accion' => 'Solo se edita un convenio vigente; para renovarlo registra uno nuevo.']);
        }

        return $this->formulario($convenio->load('documento'));
    }

    public function update(ConvenioRequest $solicitud, OrgConvenio $convenio)
    {
        Auditoria::motivo($solicitud->validated('motivo'));
        $convenio->actualizar($solicitud->safe()->except(['documento', 'motivo']), $solicitud->file('documento'), $solicitud->user());

        return redirect()->route('convenios.show', $convenio)->with('status', 'Convenio actualizado correctamente.');
    }

    public function cancelar(CancelarConvenioRequest $solicitud, OrgConvenio $convenio)
    {
        Auditoria::motivo($solicitud->validated('motivo'));
        $convenio->cancelar();

        return redirect()->route('convenios.show', $convenio)->with('status', 'Convenio cancelado.');
    }

    private function formulario(OrgConvenio $convenio)
    {
        $empresas = OrgEmpresa::ordenados()->get()->mapWithKeys(fn ($empresa) => [$empresa->id => $empresa->nombre.' · '.$empresa->rfc]);
        $tipos = OrgConvenio::TIPOS;

        return view('vinculacion.convenios.form', compact('convenio', 'empresas', 'tipos'));
    }
}
