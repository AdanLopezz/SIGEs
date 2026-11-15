@extends('layouts.app')
@use('App\Models\AcaHorario')
@section('titulo', 'Horarios')
@section('contenido')
    <x-page-header titulo="Horarios" subtitulo="Arma el horario semanal de cada grupo: materia, aula, día y hora.">
        <x-slot:acciones>
            <a href="{{ route('horarios.docentes', ['periodo' => $filtros['periodo'] ?? null]) }}" class="btn btn-outline-siges"><i class="bi bi-person-video3" aria-hidden="true"></i> Por docente</a>
            <a href="{{ route('horarios.aulas', ['periodo' => $filtros['periodo'] ?? null]) }}" class="btn btn-outline-siges"><i class="bi bi-door-closed" aria-hidden="true"></i> Por aula</a>
        </x-slot:acciones>
    </x-page-header>

    @unless ($preparable)
        <x-aviso tipo="info">Los horarios de un periodo pasado solo se consultan: no se agregan, editan ni quitan clases.</x-aviso>
    @endunless

    <x-filtros :accion="route('horarios.index')" :limpiar="route('horarios.index')">
        <x-seleccion name="periodo" label="Periodo" col="" :opciones="$periodos" :valor="$filtros['periodo'] ?? ''" :placeholder="$periodos->isEmpty() ? 'Sin periodos' : null" />
        <x-seleccion name="carrera" label="Carrera" col="" :opciones="$carreras" :valor="$filtros['carrera'] ?? ''" placeholder="Todas" />
        <x-seleccion name="turno" label="Turno" col="" :opciones="$turnos" :valor="$filtros['turno'] ?? ''" placeholder="Todos" />
        <x-seleccion name="semestre" label="Semestre" col="" :opciones="array_combine(range(1, 10), range(1, 10))" :valor="$filtros['semestre'] ?? ''" placeholder="Todos" />
    </x-filtros>

    <x-tarjeta sin-relleno etiqueta="Grupos y sus horarios">
        @if ($grupos->isEmpty())
            <x-estado-vacio icono="bi-calendar-week" texto="No hay grupos con los filtros seleccionados." />
        @else
            <div class="table-responsive">
                <table class="table tabla-siges align-middle mb-0">
                    <thead><tr><th scope="col">Grupo</th><th scope="col">Carrera</th><th scope="col">Turno</th><th scope="col">Materias con horario</th><th scope="col">Horas por semana</th><th scope="col" class="text-end">Acciones</th></tr></thead>
                    <tbody>
                    @foreach ($grupos as $grupo)
                        <tr>
                            <td><strong>{{ $grupo->identificador }}</strong><small class="d-block texto-tenue">{{ $grupo->semestre }}.º semestre</small></td>
                            <td>{{ $grupo->carrera->nombre }}</td>
                            <td>{{ $grupo->turno->nombre }}<small class="d-block texto-tenue">{{ substr($grupo->turno->hora_entrada, 0, 5) }} a {{ substr($grupo->turno->hora_salida, 0, 5) }}</small></td>
                            <td>
                                @if ($grupo->materias_count === 0)
                                    <span class="texto-tenue">Sin materias</span>
                                @elseif ($grupo->materias_con_horario_count === 0)
                                    <x-badge-estado estado="SIN_HORARIO" />
                                @else
                                    <x-badge-estado :tono="$grupo->materias_con_horario_count === $grupo->materias_count ? 'exito' : 'aviso'" :texto="$grupo->materias_con_horario_count.' de '.$grupo->materias_count" />
                                @endif
                            </td>
                            <td>{{ AcaHorario::duracion($minutos->get($grupo->id, 0)) }}</td>
                            <td><div class="d-flex justify-content-end gap-2">
                                <a class="btn btn-sm btn-outline-siges" href="{{ route('horarios.grupo', $grupo) }}" aria-label="Horario de {{ $grupo->nombre_completo }}">{{ $preparable ? 'Horario' : 'Ver horario' }}</a>
                                <x-menu-acciones :etiqueta="$grupo->nombre_completo">
                                    <li><a class="dropdown-item" href="{{ route('horarios.grupo.imprimir', $grupo) }}"><i class="bi bi-printer" aria-hidden="true"></i> Imprimir</a></li>
                                    <li><a class="dropdown-item" href="{{ route('grupos.materias.index', $grupo) }}"><i class="bi bi-journal-bookmark" aria-hidden="true"></i> Materias y docentes</a></li>
                                </x-menu-acciones>
                            </div></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        <div class="tabla-pie"><x-por-pagina :paginador="$grupos" singular="grupo" plural="grupos" /></div>
    </x-tarjeta>
@endsection
