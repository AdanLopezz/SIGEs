<?php

namespace App\Http\Controllers\ControlEscolar;

use App\Http\Controllers\Controller;
use App\Http\Requests\ControlEscolar\FiltrarEgresoRequest;
use App\Http\Requests\ControlEscolar\ProcesoEgresoRequest;
use App\Http\Requests\ControlEscolar\RegistrarEgresoRequest;
use App\Http\Requests\ControlEscolar\DocumentacionEgresoRequest;
use App\Models\AcaCarrera;
use App\Models\AcaGrupoEscolar;
use App\Models\AcaPeriodoEscolar;
use App\Models\OrgConvenio;
use App\Models\PerAlumno;
use App\Models\SysConfiguracion;
use App\Models\TrxProcesoEgreso;
use App\Models\TrxEgreso;
use Illuminate\Support\Carbon;
use App\Support\PeriodoDeTrabajo;
use App\Support\Reportes\Catalogo;
use App\Support\RevisionEgreso;

class EgresoController extends Controller
{
    public function index(FiltrarEgresoRequest $solicitud)
    {
        $filtros = $this->filtros($solicitud);
        $alumnos = RevisionEgreso::consulta($filtros)->paginarListado();
        $revisiones = $alumnos->getCollection()->filter(fn ($alumno) => ! $alumno->egreso)
            ->mapWithKeys(fn ($alumno) => [$alumno->id => RevisionEgreso::academica($alumno)]);
        $carreras = AcaCarrera::ordenados()->pluck('nombre', 'id');
        $periodos = AcaPeriodoEscolar::ordenados()->get()->mapWithKeys(fn ($p) => [$p->id => $p->nombre_visible.' · '.$p->clave]);
        $grupos = AcaGrupoEscolar::with('periodo')->delPeriodo(isset($filtros['periodo']) ? (int) $filtros['periodo'] : null)
            ->ordenados()->get()->mapWithKeys(fn ($g) => [$g->id => $g->identificador.' · '.$g->periodo->clave]);

        return view('control-escolar.egreso.index', compact('alumnos', 'filtros', 'revisiones', 'carreras', 'periodos', 'grupos'));
    }

    public function show(PerAlumno $alumno)
    {
        $alumno->load(['persona', 'carrera', 'estado', 'egreso', 'procesosEgreso.convenio.empresa']);
        $revision = RevisionEgreso::academica($alumno);

        return view('control-escolar.egreso.show', compact('alumno', 'revision'));
    }

    public function proceso(PerAlumno $alumno, string $tipo)
    {
        abort_unless(array_key_exists($tipo, TrxProcesoEgreso::TIPOS), 404);
        $alumno->load(['persona', 'carrera', 'estado']);
        $proceso = $alumno->procesosEgreso()->where('tipo', $tipo)->first()
            ?? new TrxProcesoEgreso(['tipo' => $tipo, 'estado' => 'EN_PROCESO', 'horas_acreditadas' => 0]);
        // Se permiten registros históricos bajo convenios vencidos que cubran las fechas del proceso.
        $convenios = OrgConvenio::with('empresa')->where('estado', '!=', OrgConvenio::CANCELADO)
            ->whereIn('tipo', [$tipo, 'GENERAL'])->orderByDesc('fecha_inicio')->get()
            ->mapWithKeys(fn ($c) => [$c->id => $c->numero.' · '.$c->empresa->nombre.' · '.$c->fecha_inicio->format('d/m/Y').' — '.$c->fecha_fin->format('d/m/Y')]);

        return view('control-escolar.egreso.proceso', compact('alumno', 'proceso', 'convenios'));
    }

    public function guardarProceso(ProcesoEgresoRequest $solicitud, PerAlumno $alumno)
    {
        TrxProcesoEgreso::guardarPara($alumno, $solicitud->validated());

        return redirect()->route('egreso.show', $alumno)->with('status', 'Proceso de egreso actualizado. Los cambios quedaron en la bitácora.');
    }

    public function store(RegistrarEgresoRequest $solicitud, PerAlumno $alumno)
    {
        TrxEgreso::registrar($alumno, Carbon::parse($solicitud->validated('fecha_egreso')),
            (string) $solicitud->validated('promedio_general'), $solicitud->validated('motivo'), $solicitud->user());

        return redirect()->route('egreso.show', $alumno)->with('status', 'Egreso registrado. El historial se conserva y las credenciales de acceso quedaron desactivadas.');
    }

    public function documentacion(DocumentacionEgresoRequest $solicitud, PerAlumno $alumno)
    {
        $egreso = $alumno->egreso()->firstOrFail();
        $egreso->actualizarDocumentacion($solicitud->boolean('certificado_entregado'), $solicitud->boolean('titulado'),
            $solicitud->validated('motivo'), $solicitud->user());

        return redirect()->route('egreso.show', $alumno)->with('status', 'Situación de certificado y titulación actualizada.');
    }

    public function csv(FiltrarEgresoRequest $solicitud)
    {
        $filtros = $this->filtros($solicitud);

        return response()->streamDownload(function () use ($filtros) {
            $salida = fopen('php://output', 'w');
            fwrite($salida, "\xEF\xBB\xBF");
            fputcsv($salida, ['Matrícula', 'Alumno', 'Carrera', 'Situación', 'Pendientes académicos', 'Fecha de egreso', 'Promedio', 'Certificado entregado', 'Titulado']);
            foreach (RevisionEgreso::consulta($filtros)->lazy(100) as $alumno) {
                $revision = $alumno->egreso ? null : RevisionEgreso::academica($alumno);
                fputcsv($salida, array_map([Catalogo::class, 'celdaCsv'], [
                    $alumno->matricula, $alumno->persona->nombre_completo, $alumno->carrera->nombre,
                    $alumno->egreso ? 'Egresado' : 'Candidato', $revision ? $revision['pendientes']->count() : '',
                    $alumno->egreso?->fecha_egreso->format('Y-m-d'), $alumno->egreso?->promedio_general,
                    $alumno->egreso ? ($alumno->egreso->certificado_entregado ? 'Sí' : 'No') : '',
                    $alumno->egreso ? ($alumno->egreso->titulado ? 'Sí' : 'No') : '',
                ]));
            }
            fclose($salida);
        }, 'egreso-'.$filtros['lista'].'.csv', ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'private, no-store']);
    }

    public function imprimir(FiltrarEgresoRequest $solicitud)
    {
        $filtros = $this->filtros($solicitud);
        $alumnos = RevisionEgreso::consulta($filtros)->get();
        $plantel = SysConfiguracion::actual();

        return response()->view('control-escolar.egreso.imprimir', compact('alumnos', 'filtros', 'plantel'))
            ->header('Cache-Control', 'private, no-store');
    }

    private function filtros(FiltrarEgresoRequest $solicitud): array
    {
        $filtros = $solicitud->validated();
        $filtros['lista'] ??= 'candidatos';
        if (! $solicitud->exists('periodo') && $filtros['lista'] === 'candidatos') {
            $filtros['periodo'] = PeriodoDeTrabajo::periodoConsulta()?->id;
        }

        return $filtros;
    }
}
