@extends('layouts.app')
@section('titulo', 'Resultados del grupo '.$grupo->identificador)
@section('contenido')
    <x-page-header :titulo="'Resultados · '.$grupo->nombre_completo" :subtitulo="'Periodo '.$grupo->periodo->nombre_visible.' ('.$grupo->periodo->clave.') · '.$grupo->semestre.'.º semestre · resultados ordinarios'"
        :volver="[route('calificaciones.index', ['grupo' => $grupo->id]), 'Volver a Calificaciones']">
        <x-slot:acciones>
            @if ($puedeCerrar)
                <button type="button" class="btn btn-siges" data-bs-toggle="modal" data-bs-target="#modalCerrarGrupo"
                    @if ($totales['listas'] > 0) data-accion="{{ route('calificaciones.cerrar-grupo', $grupo) }}" @endif data-nombre="{{ $grupo->nombre_completo }}">
                    <i class="bi bi-check2-all" aria-hidden="true"></i> Cerrar resultados del grupo</button>
            @endif
        </x-slot:acciones>
    </x-page-header>

    <x-tarjeta sin-relleno etiqueta="Materias del grupo">
        @if ($materias->isEmpty())
            <x-estado-vacio icono="bi-journal-bookmark" texto="El grupo no tiene materias asignadas." />
        @else
            <div class="table-responsive">
                <table class="table tabla-siges align-middle mb-0">
                    <thead><tr><th scope="col">Materia</th><th scope="col">Docente</th><th scope="col">Evaluaciones cerradas</th><th scope="col">Aprobados</th><th scope="col">Reprobados</th><th scope="col">Sin calificaciones</th><th scope="col">Cerrados</th><th scope="col">Estado</th><th scope="col" class="text-end">Acciones</th></tr></thead>
                    <tbody>
                    @foreach ($materias as $materia)
                        @php
                            $resumen = $resumenes[$materia->id];
                            $total = $materia->evaluaciones->count();
                        @endphp
                        <tr>
                            <td><strong>{{ $materia->asignatura->nombre }}</strong><small class="d-block mono">{{ $materia->asignatura->clave }}</small></td>
                            <td>{{ $materia->docente->nombre }}</td>
                            <td>{{ $materia->evaluaciones->where('cerrada', true)->count() }} de {{ $total }}</td>
                            <td>{{ $resumen['aprobados'] }}</td>
                            <td>{{ $resumen['reprobados'] }}</td>
                            <td>{{ count($resumen['faltantes']) }}</td>
                            <td>{{ $resumen['cerrados'] }} de {{ $resumen['total'] }}@if ($resumen['recursantes'] > 0)<small class="d-block texto-tenue">{{ $resumen['recursantes'] }} {{ $resumen['recursantes'] === 1 ? 'recursante' : 'recursantes' }}</small>@endif</td>
                            <td>
                                @if ($resumen['listo'])
                                    <x-badge-estado tono="exito" texto="Lista para cerrar" />
                                @else
                                    <x-badge-estado tono="aviso" texto="Pendiente" />
                                    <small class="d-block texto-tenue">{{ $resumen['total'] === 0 ? 'Sin alumnos inscritos.' : implode(' ', $resumen['pendientes']) }}</small>
                                @endif
                            </td>
                            <td class="text-end"><a class="btn btn-sm btn-outline-siges" href="{{ route('calificaciones.materia', $materia) }}" aria-label="Calificaciones de {{ $materia->asignatura->nombre }}">Calificaciones</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        <div class="tabla-pie"><span>{{ $materias->count() }} {{ $materias->count() === 1 ? 'materia' : 'materias' }} · {{ $totales['listas'] }} {{ $totales['listas'] === 1 ? 'lista' : 'listas' }} para cerrar</span></div>
    </x-tarjeta>
@endsection

@push('modales')
    @if ($puedeCerrar)
    <x-modal id="modalCerrarGrupo" titulo="Cerrar resultados del grupo" icono="bi-check2-all" boton="Cerrar resultados" confirmar>
        <p>Se cerrarán los resultados ordinarios de las materias listas de <strong data-campo="nombre"></strong>. Las demás quedan pendientes y se informan.</p>
        <dl class="dato-lista">
            <dt>Materias listas</dt><dd>{{ $totales['listas'] }} de {{ $materias->count() }}</dd>
            <dt>Aprobados</dt><dd>{{ $totales['aprobados'] }}</dd>
            <dt>Reprobados</dt><dd>{{ $totales['reprobados'] }}</dd>
            <dt>Sin todas sus calificaciones</dt><dd>{{ $totales['faltantes'] }}</dd>
        </dl>
        <p class="texto-tenue">Los totales cuentan alumno por materia, en todas las materias del grupo.</p>
        <h3 class="fs-6">Reglas</h3>
        @include('academico.calificaciones._reglas')
    </x-modal>
    @endif
@endpush
