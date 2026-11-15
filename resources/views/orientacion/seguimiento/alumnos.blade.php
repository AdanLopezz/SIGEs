@extends('layouts.app')
@use('App\Models\TrxAsistenciaClase')
@use('App\Models\TrxSeguimientoAlumno')
@section('titulo', 'Alumnos a atender')
@section('contenido')
    <x-page-header titulo="Alumnos a atender" subtitulo="Alertas de los alumnos de tus grupos en el periodo elegido. Solo lectura: abre un caso si lo consideras necesario."
        :volver="[route('seguimiento.index'), 'Volver a Seguimiento']" />

    <x-aviso tipo="info">
        Aparece un alumno si tiene asistencia por clase bajo {{ TrxAsistenciaClase::formatear($reglas['minimo']) }} en alguna materia (Inasistencias),
        más de {{ $reglas['maximo'] }} materias adeudadas (Reprobación) o incidencias graves en el periodo (Conducta).
        @unless ($puedeAbrir) Estás consultando otro periodo: los casos nuevos se abren en el periodo activo. @endunless
    </x-aviso>

    <x-filtros :accion="route('seguimiento.alumnos')" :limpiar="route('seguimiento.alumnos')">
        <x-seleccion name="grupo" label="Grupo" col="" :opciones="$grupos->pluck('nombre_completo', 'id')" :valor="$filtros['grupo'] ?? ''" placeholder="Todos" />
        <x-seleccion name="motivo" label="Motivo sugerido" col="" :opciones="$motivos" :valor="$filtros['motivo'] ?? ''" placeholder="Todos" />
    </x-filtros>

    <x-tarjeta sin-relleno etiqueta="Alumnos a atender">
        @if ($filas->isEmpty())
            <x-estado-vacio icono="bi-emoji-smile" texto="Ningún alumno de tus grupos tiene alertas con los filtros elegidos." />
        @else
            <div class="table-responsive">
                <table class="table tabla-siges align-middle mb-0">
                    <thead><tr><th scope="col">Alumno</th><th scope="col">Grupo</th><th scope="col">Motivo sugerido</th><th scope="col" class="text-end">Acciones</th></tr></thead>
                    <tbody>
                    @foreach ($filas as $fila)
                        @php $alumno = $fila['inscripcion']->alumno; @endphp
                        <tr>
                            <td>
                                @can('ver', $alumno)<a href="{{ route('alumnos.show', $alumno) }}"><strong>{{ $alumno->persona->nombre_completo }}</strong></a>@else<strong>{{ $alumno->persona->nombre_completo }}</strong>@endcan
                                <small class="d-block mono">{{ $alumno->matricula }}</small>
                            </td>
                            <td>{{ $fila['inscripcion']->grupo->identificador }}</td>
                            <td>
                                @foreach ($fila['motivos'] as $motivo => $detalle)
                                    <div @class(['py-1', 'border-top' => ! $loop->first])>
                                        <strong>{{ TrxSeguimientoAlumno::MOTIVOS[$motivo] }}</strong>
                                        <small class="d-block texto-tenue">{{ $detalle }}</small>
                                    </div>
                                @endforeach
                            </td>
                            <td class="text-end">
                                @foreach ($fila['motivos'] as $motivo => $detalle)
                                    <div class="py-1">
                                        @if (isset($fila['abiertos'][$motivo]))
                                            <a class="btn btn-sm btn-outline-siges" href="{{ route('seguimiento.show', $fila['abiertos'][$motivo]) }}">Ver caso #{{ $fila['abiertos'][$motivo] }}</a>
                                        @elseif ($puedeAbrir)
                                            <a class="btn btn-sm btn-outline-siges" href="{{ route('seguimiento.create', ['alumno' => $alumno->id, 'motivo' => $motivo]) }}"
                                                aria-label="Abrir seguimiento de {{ $alumno->persona->nombre_completo }} por {{ mb_strtolower(TrxSeguimientoAlumno::MOTIVOS[$motivo]) }}">Abrir seguimiento</a>
                                        @else
                                            <span class="texto-tenue small">Sin caso abierto</span>
                                        @endif
                                    </div>
                                @endforeach
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        <div class="tabla-pie"><x-por-pagina :paginador="$filas" singular="alumno" plural="alumnos" /></div>
    </x-tarjeta>
@endsection
