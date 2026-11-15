@extends('layouts.app')
@php
    $usuario = auth()->user();
    $cierraResultados = $usuario->can('resultados.cerrar');
@endphp
@section('titulo', 'Calificaciones')
@section('contenido')
    <x-page-header titulo="Calificaciones" subtitulo="Elige una materia para capturar o consultar sus calificaciones del periodo elegido." />

    <x-filtros :accion="route('calificaciones.index')" :limpiar="route('calificaciones.index')">
        <x-seleccion name="grupo" label="Grupo" col="" :valor="$filtros['grupo'] ?? null" placeholder="Todos los grupos"
            :opciones="$grupos->mapWithKeys(fn ($grupo) => [$grupo->id => $grupo->nombre_completo.' · '.$grupo->semestre.'.º semestre'])" />
        <x-seleccion name="asignatura" label="Materia" col="" :valor="$filtros['asignatura'] ?? null" placeholder="Todas las materias"
            :opciones="$asignaturas->mapWithKeys(fn ($asignatura) => [$asignatura->id => $asignatura->clave.' · '.$asignatura->nombre])" />
    </x-filtros>

    <x-tarjeta sin-relleno etiqueta="Materias">
        @if ($materias->isEmpty())
            <x-estado-vacio icono="bi-clipboard-data" texto="No hay materias que mostrar en el periodo elegido." />
        @else
            <div class="table-responsive">
                <table class="table tabla-siges align-middle mb-0">
                    <thead><tr><th scope="col">Materia</th><th scope="col">Grupo</th><th scope="col">Docente</th><th scope="col">Evaluaciones cerradas</th><th scope="col">Resultados</th><th scope="col" class="text-end">Acciones</th></tr></thead>
                    <tbody>
                    @foreach ($materias as $materia)
                        @php
                            $total = $materia->evaluaciones->count();
                            $cerradas = $materia->evaluaciones->where('cerrada', true)->count();
                        @endphp
                        <tr>
                            <td><strong>{{ $materia->asignatura->nombre }}</strong><small class="d-block mono">{{ $materia->asignatura->clave }}</small></td>
                            <td>{{ $materia->grupo->nombre_completo }}</td>
                            <td>{{ $materia->docente->nombre }}</td>
                            <td>
                                @if ($total === 0)
                                    <span class="texto-tenue">Sin programar</span>
                                @else
                                    <x-badge-estado :tono="$cerradas === $total ? 'exito' : 'aviso'" :texto="$cerradas.' de '.$total" />
                                @endif
                            </td>
                            <td>
                                @if ($materia->resultados_count > 0)
                                    {{ $materia->resultados_count }} de {{ $materia->grupo->inscritos_count }} cerrados
                                @else
                                    <x-badge-estado estado="SIN_RESULTADO" />
                                @endif
                            </td>
                            <td><div class="d-flex justify-content-end gap-2">
                                <a class="btn btn-sm btn-outline-siges" href="{{ route('calificaciones.materia', $materia) }}" aria-label="Calificaciones de {{ $materia->nombre }}">Calificaciones</a>
                                @if ($cierraResultados)
                                    <x-menu-acciones :etiqueta="$materia->nombre">
                                        <li><a class="dropdown-item" href="{{ route('calificaciones.grupo', $materia->aca_grupo_escolar_id) }}"><i class="bi bi-people" aria-hidden="true"></i> Resultados del grupo {{ $materia->grupo->identificador }}</a></li>
                                    </x-menu-acciones>
                                @endif
                            </div></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        <div class="tabla-pie"><x-por-pagina :paginador="$materias" singular="materia" plural="materias" /></div>
    </x-tarjeta>
@endsection
