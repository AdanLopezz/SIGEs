@extends('layouts.app')
@section('titulo', 'Grupos')
@section('contenido')
    <x-page-header titulo="Grupos" subtitulo="Organiza los grupos del periodo, sus responsables y sus materias.">
        <x-slot:acciones>@if ($hayPreparables)<a href="{{ route('grupos.create') }}" class="btn btn-siges"><i class="bi bi-plus-lg" aria-hidden="true"></i> Nuevo grupo</a>@endif</x-slot:acciones>
    </x-page-header>

    @unless ($hayPeriodoActivo)
        <x-aviso tipo="aviso">
            No hay un periodo escolar activo.
            @can('configuracion.administrar')Actívalo en <a href="{{ route('configuracion.periodos.index') }}">Configuración → Periodos</a>.@else Pide al administrador que active uno.@endcan
        </x-aviso>
    @endunless

    @unless ($preparable)
        <x-aviso tipo="info">Los grupos de un periodo pasado solo se consultan: no se editan ni se eliminan, y sus materias no cambian.</x-aviso>
    @endunless

    <x-filtros :accion="route('grupos.index')" :limpiar="route('grupos.index')">
        <x-seleccion name="periodo" label="Periodo" col="" :opciones="$periodos" :valor="$filtros['periodo'] ?? ''" :placeholder="$periodos->isEmpty() ? 'Sin periodos' : null" />
        <x-seleccion name="carrera" label="Carrera" col="" :opciones="$carreras" :valor="$filtros['carrera'] ?? ''" placeholder="Todas" />
        <x-seleccion name="turno" label="Turno" col="" :opciones="$turnos" :valor="$filtros['turno'] ?? ''" placeholder="Todos" />
        <x-seleccion name="semestre" label="Semestre" col="" :opciones="array_combine(range(1, 10), range(1, 10))" :valor="$filtros['semestre'] ?? ''" placeholder="Todos" />
    </x-filtros>

    <x-tarjeta sin-relleno etiqueta="Lista de grupos">
        @if ($grupos->isEmpty())
            <x-estado-vacio icono="bi-people" texto="No hay grupos con los filtros seleccionados." />
        @else
            <div class="table-responsive">
                <table class="table tabla-siges align-middle mb-0">
                    <thead><tr><th scope="col">Grupo</th><th scope="col">Semestre</th><th scope="col">Carrera</th><th scope="col">Turno</th><th scope="col">Orientador</th><th scope="col">Tutor</th><th scope="col">Materias</th><th scope="col">Alumnos</th><th scope="col" class="text-end">Acciones</th></tr></thead>
                    <tbody>
                    @foreach ($grupos as $grupo)
                        <tr>
                            <td><strong>{{ $grupo->identificador }}</strong></td>
                            <td>{{ $grupo->semestre }}</td>
                            <td>{{ $grupo->carrera->nombre }}</td>
                            <td>{{ $grupo->turno->nombre }}</td>
                            <td>{{ $grupo->orientador->nombre }}</td>
                            <td>{{ $grupo->tutor->nombre }}</td>
                            <td>{{ $grupo->materias_count }}</td>
                            <td>{{ $grupo->alumnos_count }}</td>
                            <td><div class="d-flex justify-content-end gap-2">
                                @if ($preparable)
                                    <a class="btn btn-sm btn-outline-siges" href="{{ route('grupos.edit', $grupo) }}" aria-label="Editar {{ $grupo->nombre_completo }}">Editar</a>
                                @else
                                    <a class="btn btn-sm btn-outline-siges" href="{{ route('grupos.materias.index', $grupo) }}" aria-label="Ver materias de {{ $grupo->nombre_completo }}">Ver materias</a>
                                @endif
                                <x-menu-acciones :etiqueta="$grupo->nombre_completo">
                                    <li><a class="dropdown-item" href="{{ route('grupos.materias.index', $grupo) }}"><i class="bi bi-journal-bookmark" aria-hidden="true"></i> Materias</a></li>
                                    @if ($preparable && $grupo->materias_count === 0 && $grupo->inscripciones_count === 0)
                                        <li><button type="button" class="dropdown-item peligro" data-bs-toggle="modal" data-bs-target="#modalEliminarGrupo"
                                            data-accion="{{ route('grupos.destroy', $grupo) }}" data-nombre="{{ $grupo->nombre_completo }}"><i class="bi bi-trash" aria-hidden="true"></i> Eliminar</button></li>
                                    @endif
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

@push('modales')
    <x-modal id="modalEliminarGrupo" titulo="Eliminar grupo" icono="bi-trash" metodo="DELETE" boton="Eliminar" peligro confirmar>
        ¿Quieres eliminar el grupo <strong data-campo="nombre"></strong>? Esta acción no se puede deshacer.
    </x-modal>
@endpush
