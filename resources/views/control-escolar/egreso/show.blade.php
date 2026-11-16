@extends('layouts.app')
@section('titulo', 'Revisión de egreso')
@section('contenido')
    <x-page-header titulo="Revisión de egreso" :subtitulo="$alumno->persona->nombre_completo.' · '.$alumno->matricula"
        :volver="[route('egreso.index'), 'Volver a Egreso']" />
    <x-errores-formulario />
    <x-tarjeta titulo="Situación del alumno" icono="bi-mortarboard">
        <div class="row g-3">
            <div class="col-md-4"><span class="texto-tenue">Carrera</span><strong class="d-block">{{ $alumno->carrera->nombre }}</strong></div>
            <div class="col-md-4"><span class="texto-tenue">Generación</span><strong class="d-block">{{ $alumno->generacion }}</strong></div>
            <div class="col-md-4"><span class="texto-tenue d-block">Estado</span><x-badge-estado :estado="$alumno->estado->nombre" /></div>
        </div>
    </x-tarjeta>
    @if ($alumno->egreso)
        <x-tarjeta titulo="Egreso registrado" icono="bi-award">
            <div class="row g-3">
                <div class="col-md-3"><span class="texto-tenue">Fecha</span><strong class="d-block">{{ $alumno->egreso->fecha_egreso->format('d/m/Y') }}</strong></div>
                <div class="col-md-3"><span class="texto-tenue">Promedio general</span><strong class="d-block mono">{{ $alumno->egreso->promedio_general }}</strong></div>
                <div class="col-md-3"><span class="texto-tenue">Certificado entregado</span><strong class="d-block">{{ $alumno->egreso->certificado_entregado ? 'Sí' : 'No' }}</strong></div>
                <div class="col-md-3"><span class="texto-tenue">Titulado</span><strong class="d-block">{{ $alumno->egreso->titulado ? 'Sí' : 'No' }}</strong></div>
            </div>
        </x-tarjeta>
        @can('egreso-confirmar')
            <form method="POST" action="{{ route('egreso.documentacion', $alumno) }}" novalidate>
                @csrf
                @method('PUT')
                <section class="form-seccion">
                    <h2 class="form-seccion-titulo"><span>1</span> Certificado y titulación</h2>
                    <div class="row g-3">
                        <x-seleccion name="certificado_entregado" label="Certificado entregado" :opciones="[0 => 'No', 1 => 'Sí']" :valor="(int) $alumno->egreso->certificado_entregado" :requerido="true" />
                        <x-seleccion name="titulado" label="Titulado" :opciones="[0 => 'No', 1 => 'Sí']" :valor="(int) $alumno->egreso->titulado" :requerido="true" />
                        <x-campo name="motivo" label="Motivo y referencia documental" col="col-12" maxlength="255" :requerido="true" />
                    </div>
                </section>
                <div class="form-acciones"><button class="btn btn-siges" type="submit">Actualizar documentación</button></div>
            </form>
        @endcan
    @else
        <x-tarjeta titulo="Revisión académica" icono="bi-journal-check">
            @if (! $revision['inscripcion_final'])
                <x-aviso tipo="aviso">No hay una inscripción válida de último semestre. Puedes dar seguimiento a sus procesos, pero todavía no es candidato a egreso.</x-aviso>
            @else
                <p>Último semestre: <strong>{{ $revision['inscripcion_final']->grupo->identificador }}</strong> · {{ $revision['inscripcion_final']->grupo->periodo->nombre_visible }}</p>
            @endif
            <p>Semestres registrados: {{ $revision['semestres_registrados']->implode(', ') ?: 'Ninguno' }}.</p>
            @if ($revision['pendientes']->isEmpty())
                <x-aviso tipo="exito">No se encontraron materias pendientes en el historial registrado. Antes de confirmar el egreso, coteja también el historial oficial completo.</x-aviso>
            @else
                <div class="table-responsive">
                    <table class="table tabla-siges mb-0">
                        <thead><tr><th scope="col">Grupo</th><th scope="col">Materia</th><th scope="col">Pendiente</th></tr></thead>
                        <tbody>
                        @foreach ($revision['pendientes'] as $pendiente)
                            <tr><td>{{ $pendiente['grupo'] }}</td><td>{{ $pendiente['materia'] }}</td><td>{{ $pendiente['motivo'] }}</td></tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-tarjeta>
        @can('egreso-confirmar')
            @if ($alumno->estaActivo() && $revision['inscripcion_final'] && $revision['pendientes']->isEmpty())
                <form method="POST" action="{{ route('egreso.store', $alumno) }}" novalidate>
                    @csrf
                    <section class="form-seccion">
                        <h2 class="form-seccion-titulo"><span>1</span> Confirmación del egreso</h2>
                        <x-aviso tipo="aviso">Al confirmar, el alumno quedará Egresado y su inscripción de último semestre se cerrará para la operación diaria. Se desactivarán sus credenciales de acceso; conservará su historial y su cuenta del portal.</x-aviso>
                        <div class="row g-3">
                            <x-campo name="fecha_egreso" label="Fecha de egreso" type="date" :value="today()->format('Y-m-d')" :max="today()->format('Y-m-d')" :requerido="true" />
                            <x-campo name="promedio_general" label="Promedio general oficial" type="number" min="0" max="10" step="0.01" :requerido="true" ayuda="Captura el promedio del historial oficial. No se calcula a partir de esta pantalla." />
                            <x-campo name="motivo" label="Motivo y referencia del historial revisado" col="col-12" maxlength="255" :requerido="true" />
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="historial_verificado" name="historial_verificado" value="1" @checked(old('historial_verificado')) required>
                                    <label class="form-check-label" for="historial_verificado">Cotejé el historial oficial completo, incluidas equivalencias y materias que pudieran no estar registradas en SIGEs, y confirmo que cumple los requisitos académicos de egreso.</label>
                                </div>
                            </div>
                        </div>
                    </section>
                    <div class="form-acciones"><button class="btn btn-siges" type="submit"><i class="bi bi-mortarboard" aria-hidden="true"></i> Confirmar egreso</button></div>
                </form>
            @endif
        @else
            <x-aviso tipo="info">La confirmación del egreso y el promedio oficial corresponden a Control Escolar o Administración.</x-aviso>
        @endcan
    @endif
    <x-aviso tipo="info">Servicio social y prácticas profesionales son informativos y no bloquean el egreso. Pueden actualizarse después del egreso.</x-aviso>
    <x-tarjeta titulo="Procesos de egreso" icono="bi-briefcase" sin-relleno>
        <div class="table-responsive">
            <table class="table tabla-siges align-middle mb-0">
                <thead><tr><th scope="col">Proceso</th><th scope="col">Convenio</th><th scope="col">Estado</th><th scope="col">Horas</th><th scope="col">Fechas</th><th scope="col" class="text-end">Acciones</th></tr></thead>
                <tbody>
                @foreach (\App\Models\TrxProcesoEgreso::TIPOS as $tipo => $nombre)
                    @php $proceso = $alumno->procesosEgreso->firstWhere('tipo', $tipo); @endphp
                    <tr>
                        <td>{{ $nombre }}</td>
                        <td>{{ $proceso?->convenio?->numero ?? '—' }}<small class="d-block texto-tenue">{{ $proceso?->convenio?->empresa?->nombre }}</small></td>
                        <td><x-badge-estado :tono="$proceso?->estado === 'CONCLUIDO' ? 'exito' : 'aviso'" :texto="\App\Models\TrxProcesoEgreso::ESTADOS[$proceso?->estado] ?? 'Sin registrar'" /></td>
                        <td class="mono">{{ $proceso?->horas_acreditadas ?? '—' }}</td>
                        <td>@if ($proceso) {{ $proceso->fecha_inicio->format('d/m/Y') }} — {{ $proceso->fecha_fin->format('d/m/Y') }} @else — @endif</td>
                        <td class="text-end">
                            @if ($alumno->estaActivo() || $alumno->estado->nombre === \App\Models\CatEstadoAlumno::EGRESADO)
                                <a class="btn btn-sm btn-outline-siges" href="{{ route('egreso.proceso', [$alumno, $tipo]) }}" aria-label="{{ $proceso ? 'Editar' : 'Registrar' }} {{ $nombre }}">{{ $proceso ? 'Editar' : 'Registrar' }}</a>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </x-tarjeta>
    <x-historial tabla="per_alumno" :registro="$alumno->id"
        :relacionados="[['trx_egreso', $alumno->egreso?->id ? [$alumno->egreso->id] : []], ['trx_proceso_egreso', $alumno->procesosEgreso->pluck('id')->all()]]" />
@endsection
