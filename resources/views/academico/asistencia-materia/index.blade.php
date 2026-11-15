@extends('layouts.app')
@use('App\Models\AcaHorario')
@use('App\Models\TrxAsistenciaClase')
@section('titulo', 'Asistencia por materia')
@section('contenido')
    <x-page-header titulo="Asistencia por materia" subtitulo="Pase de lista por clase del periodo elegido: listas registradas y alumnos bajo el mínimo." />

    <x-aviso tipo="info">El porcentaje es (asistencias + retardos) entre las clases registradas, sin contar las faltas justificadas. Bajo {{ TrxAsistenciaClase::formatear($minimo) }} solo se avisa: el derecho a examen lo confirma Control Escolar.</x-aviso>

    <x-filtros :accion="route('asistencia-materia.index')" :limpiar="route('asistencia-materia.index')">
        <x-seleccion name="grupo" label="Grupo" col="" :valor="$filtros['grupo'] ?? null" placeholder="Todos los grupos"
            :opciones="$grupos->mapWithKeys(fn ($grupo) => [$grupo->id => $grupo->nombre_completo.' · '.$grupo->semestre.'.º semestre'])" />
        <x-seleccion name="asignatura" label="Materia" col="" :valor="$filtros['asignatura'] ?? null" placeholder="Todas las materias"
            :opciones="$asignaturas->mapWithKeys(fn ($asignatura) => [$asignatura->id => $asignatura->clave.' · '.$asignatura->nombre])" />
    </x-filtros>

    <x-tarjeta sin-relleno etiqueta="Materias">
        @if ($materias->isEmpty())
            <x-estado-vacio icono="bi-table" texto="No hay materias que mostrar en el periodo elegido." />
        @else
            <div class="table-responsive">
                <table class="table tabla-siges align-middle mb-0">
                    <thead><tr><th scope="col">Materia</th><th scope="col">Grupo</th><th scope="col">Docente</th><th scope="col">Días de clase</th><th scope="col">Listas registradas</th><th scope="col">Bajo el mínimo</th><th scope="col" class="text-end">Acciones</th></tr></thead>
                    <tbody>
                    @foreach ($materias as $materia)
                        @php $dias = $materia->horarios->pluck('dia_semana')->unique()->sort(); @endphp
                        <tr>
                            <td><strong>{{ $materia->asignatura->nombre }}</strong><small class="d-block mono">{{ $materia->asignatura->clave }}</small></td>
                            <td>{{ $materia->grupo->nombre_completo }}</td>
                            <td>{{ $materia->docente?->nombre }}</td>
                            <td>@if ($dias->isEmpty())<x-badge-estado estado="SIN_HORARIO" />@else{{ $dias->map(fn ($dia) => AcaHorario::DIAS[$dia])->implode(', ') }}@endif</td>
                            <td>{{ $listas->get($materia->id, 0) }}</td>
                            <td>@if ($bajoMinimo->get($materia->id, 0) > 0)<x-badge-estado estado="BAJO_MINIMO" :texto="$bajoMinimo->get($materia->id).' '.($bajoMinimo->get($materia->id) === 1 ? 'alumno' : 'alumnos')" />@else<span class="texto-tenue">Ninguno</span>@endif</td>
                            <td class="text-end"><a class="btn btn-sm btn-outline-siges" href="{{ route('asistencia-materia.show', $materia) }}" aria-label="Asistencia de {{ $materia->nombre }}">Asistencia</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        <div class="tabla-pie"><x-por-pagina :paginador="$materias" singular="materia" plural="materias" /></div>
    </x-tarjeta>
@endsection
