@extends('layouts.app')
@use('App\Models\TrxAsistenciaClase')
@php $veFichas = auth()->user()->can('alumnos.ver'); @endphp
@section('titulo', 'Asistencia por clase')
@section('contenido')
    <x-page-header titulo="Asistencia por clase" subtitulo="Porcentaje de asistencia por materia de los alumnos de tus grupos, en el periodo elegido." />

    <x-aviso tipo="info">Porcentaje = (asistencias + retardos) entre las clases registradas, sin contar las faltas justificadas. Las filas bajo {{ TrxAsistenciaClase::formatear($minimo) }} se resaltan; es solo aviso: el derecho a examen lo confirma Control Escolar.</x-aviso>

    <x-filtros :accion="route('asistencia-clase.index')" :limpiar="route('asistencia-clase.index')">
        <x-seleccion name="grupo" label="Grupo" col="" :valor="$filtros['grupo'] ?? null" placeholder="Todos mis grupos"
            :opciones="$grupos->mapWithKeys(fn ($grupo) => [$grupo->id => $grupo->nombre_completo.' · '.$grupo->semestre.'.º semestre'])" />
        <x-seleccion name="asignatura" label="Materia" col="" :valor="$filtros['asignatura'] ?? null" placeholder="Todas las materias"
            :opciones="$asignaturas->mapWithKeys(fn ($asignatura) => [$asignatura->id => $asignatura->clave.' · '.$asignatura->nombre])" />
        <x-seleccion name="bajo_minimo" label="Mostrar" col="" :valor="$filtros['bajo_minimo'] ?? null" placeholder="Todos" :opciones="['1' => 'Solo bajo el mínimo']" />
    </x-filtros>

    <x-tarjeta titulo="Alumnos y materias" icono="bi-person-check" sin-relleno>
        <x-slot:acciones>
            @if (isset($filtros['grupo']) && $grupos->contains('id', (int) $filtros['grupo']))
                <a href="{{ route('horarios.grupo', (int) $filtros['grupo']) }}" class="btn btn-sm btn-outline-siges"><i class="bi bi-calendar-week" aria-hidden="true"></i> Horario del grupo</a>
            @endif
        </x-slot:acciones>
        @if ($filas->isEmpty())
            <x-estado-vacio icono="bi-person-check" texto="Sin listas registradas con los filtros elegidos." />
        @else
            <div class="table-responsive">
                <table class="table tabla-siges align-middle mb-0">
                    <thead><tr><th scope="col">Alumno</th><th scope="col">Grupo</th><th scope="col">Materia</th><th scope="col">Clases</th><th scope="col">Faltas</th><th scope="col">Retardos</th><th scope="col">Justificadas</th><th scope="col">Porcentaje</th>@if ($veFichas)<th scope="col" class="text-end">Acciones</th>@endif</tr></thead>
                    <tbody>
                    @foreach ($filas as $fila)
                        <tr @class(['bajo-minimo' => $fila->bajo_minimo])>
                            <td><strong>{{ $fila->inscripcion->alumno->persona->nombre_completo }}</strong><small class="d-block mono">{{ $fila->inscripcion->alumno->matricula }}</small></td>
                            <td>{{ $fila->inscripcion->grupo->identificador }}</td>
                            <td>{{ $fila->materia->asignatura->nombre }}<small class="d-block texto-tenue">{{ $fila->materia->docente?->nombre }}</small></td>
                            <td>{{ $fila->clases }}</td>
                            <td>{{ $fila->faltas }}</td>
                            <td>{{ $fila->retardos }}</td>
                            <td>{{ $fila->justificadas }}</td>
                            <td><strong>{{ TrxAsistenciaClase::formatear($fila->porcentaje) }}</strong>@if ($fila->bajo_minimo) <x-badge-estado estado="BAJO_MINIMO" />@endif</td>
                            @if ($veFichas)<td class="text-end"><a class="btn btn-sm btn-outline-siges" href="{{ route('alumnos.show', $fila->inscripcion->per_alumno_id) }}" aria-label="Ficha de {{ $fila->inscripcion->alumno->persona->nombre_completo }}">Ficha</a></td>@endif
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        <div class="tabla-pie"><x-por-pagina :paginador="$filas" singular="registro" plural="registros" /></div>
    </x-tarjeta>
@endsection
