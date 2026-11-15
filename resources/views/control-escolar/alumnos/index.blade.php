@extends('layouts.app')
@section('titulo', 'Alumnos')
@section('contenido')
    <x-page-header titulo="Alumnos" subtitulo="Consulta a los alumnos, su grupo actual y su expediente.">
        <x-slot:acciones>
            @can('alumnos.editar')
                @can('alumnos.salud.ver')<a href="{{ route('alumnos.create') }}" class="btn btn-siges"><i class="bi bi-person-plus" aria-hidden="true"></i> Nuevo alumno</a>@endcan
            @endcan
        </x-slot:acciones>
    </x-page-header>

    <x-filtros :accion="route('alumnos.index')" :limpiar="route('alumnos.index')">
        <x-campo name="buscar" label="Buscar" col="" :value="$filtros['buscar'] ?? ''" maxlength="100" placeholder="Nombre, CURP o matrícula" />
        <x-seleccion name="grupo" label="Grupo" col="" :opciones="$grupos->pluck('nombre_completo', 'id')" :valor="$filtros['grupo'] ?? ''" placeholder="Todos" />
        <x-seleccion name="carrera" label="Carrera" col="" :opciones="$carreras" :valor="$filtros['carrera'] ?? ''" placeholder="Todas" />
        <x-seleccion name="semestre" label="Semestre" col="" :opciones="array_combine(range(1, 10), range(1, 10))" :valor="$filtros['semestre'] ?? ''" placeholder="Todos" />
        <x-seleccion name="estado" label="Estado" col="" :opciones="$estados" :valor="$filtros['estado'] ?? ''" placeholder="Todos" />
    </x-filtros>

    <x-tarjeta sin-relleno etiqueta="Lista de alumnos">
        @if ($alumnos->isEmpty())
            <x-estado-vacio icono="bi-mortarboard" texto="No se encontraron alumnos con los filtros seleccionados." />
        @else
            <div class="table-responsive">
                <table class="table tabla-siges align-middle mb-0">
                    <thead><tr><th scope="col">Matrícula</th><th scope="col">Nombre y CURP</th><th scope="col">Carrera</th><th scope="col">Grupo actual</th><th scope="col">Estado</th><th scope="col" class="text-end">Acciones</th></tr></thead>
                    <tbody>
                    @foreach ($alumnos as $alumno)
                        <tr>
                            <td class="mono">{{ $alumno->matricula }}</td>
                            <td><strong>{{ $alumno->persona->nombre_completo }}</strong><small class="d-block mono">{{ $alumno->persona->curp }}</small></td>
                            <td>{{ $alumno->carrera->nombre }}</td>
                            {{-- Grupo del periodo de consulta. --}}
                            <td>{{ $alumno->inscripcionDeConsulta?->grupo->identificador ?? '—' }}@if ($alumno->inscripcionDeConsulta)<small class="d-block texto-tenue">{{ $alumno->inscripcionDeConsulta->grupo->semestre }}.º semestre</small>@endif</td>
                            <td><x-badge-estado :estado="$alumno->estado->nombre" /></td>
                            <td><div class="d-flex justify-content-end gap-2">
                                <a class="btn btn-sm btn-outline-siges" href="{{ route('alumnos.show', $alumno) }}" aria-label="Ver a {{ $alumno->persona->nombre_completo }}">Ver</a>
                                {{-- La lista ya está filtrada por visiblesPara: basta con el permiso. --}}
                                <x-menu-acciones :etiqueta="$alumno->persona->nombre_completo">
                                    @can('alumnos.editar')<li><a class="dropdown-item" href="{{ route('alumnos.edit', $alumno) }}"><i class="bi bi-pencil" aria-hidden="true"></i> Editar</a></li>@endcan
                                </x-menu-acciones>
                            </div></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        <div class="tabla-pie"><x-por-pagina :paginador="$alumnos" singular="alumno" plural="alumnos" /></div>
    </x-tarjeta>
@endsection
