@extends('layouts.app')
@php
    $administra = auth()->user()->can('aspirantes.administrar');
    $convierte = auth()->user()->can('alumnos.editar') && auth()->user()->can('alumnos.salud.ver');
@endphp
@section('titulo', 'Aspirantes')
@section('contenido')
    <x-page-header titulo="Aspirantes" :subtitulo="$administra ? 'Captación de aspirantes por ciclo de ingreso.' : 'Aspirantes registrados por Vinculación, listos para darlos de alta como alumnos.'">
        <x-slot:acciones>
            @if ($administra)<a href="{{ route('aspirantes.create') }}" class="btn btn-siges"><i class="bi bi-plus-lg" aria-hidden="true"></i> Nuevo aspirante</a>@endif
        </x-slot:acciones>
    </x-page-header>

    @unless ($administra)
        <x-aviso tipo="info">Solo consulta. "Convertir en alumno" abre el alta de alumno con los datos del aspirante; al guardar, el aspirante pasa a Inscrito.</x-aviso>
    @endunless

    <x-filtros :accion="route('aspirantes.index')" :limpiar="route('aspirantes.index')">
        <x-campo name="buscar" label="Buscar" col="" :value="$filtros['buscar'] ?? ''" maxlength="100" placeholder="Nombre, CURP o escuela" />
        <x-seleccion name="ciclo" label="Ciclo" col="" :opciones="array_combine($ciclos, $ciclos) ?: []" :valor="$filtros['ciclo'] ?? ''" placeholder="Todos" />
        <x-seleccion name="carrera" label="Carrera" col="" :opciones="$carreras" :valor="$filtros['carrera'] ?? ''" placeholder="Todas" />
        <x-seleccion name="medio" label="Medio de difusión" col="" :opciones="$medios" :valor="$filtros['medio'] ?? ''" placeholder="Todos" />
        <x-seleccion name="estado" label="Estado" col="" :opciones="$estados + ($administra ? [] : ['TODOS' => 'Todos'])" :valor="$filtros['estado'] ?? ''" :placeholder="$administra ? 'Todos' : null" />
    </x-filtros>

    <x-tarjeta sin-relleno etiqueta="Lista de aspirantes">
        @if ($aspirantes->isEmpty())
            <x-estado-vacio icono="bi-person-plus" texto="No hay aspirantes con los filtros seleccionados." />
        @else
            <div class="table-responsive">
                <table class="table tabla-siges align-middle mb-0">
                    <thead><tr><th scope="col">Aspirante</th><th scope="col">Carrera</th><th scope="col">Ciclo</th><th scope="col">Medio</th><th scope="col">Estado</th><th scope="col" class="text-end">Acciones</th></tr></thead>
                    <tbody>
                    @foreach ($aspirantes as $aspirante)
                        <tr>
                            <td><strong>{{ $aspirante->persona->nombre_completo }}</strong><small class="d-block mono">{{ $aspirante->persona->curp }}</small><small class="d-block texto-tenue">{{ $aspirante->escuela_procedencia }}</small></td>
                            <td>{{ $aspirante->carrera->nombre }}</td>
                            <td class="mono">{{ $aspirante->ciclo_ingreso }}</td>
                            <td>{{ $aspirante->medio->nombre }}</td>
                            <td><x-badge-estado :estado="$aspirante->estado" /></td>
                            <td><div class="d-flex justify-content-end gap-2">
                                @if ($convierte && $aspirante->estado === \App\Models\TrxAspirante::REGISTRADO)
                                    <a class="btn btn-sm btn-siges" href="{{ route('alumnos.create', ['aspirante' => $aspirante->id]) }}" aria-label="Convertir en alumno a {{ $aspirante->persona->nombre_completo }}">Convertir en alumno</a>
                                @endif
                                <a class="btn btn-sm btn-outline-siges" href="{{ route('aspirantes.show', $aspirante) }}" aria-label="Ver a {{ $aspirante->persona->nombre_completo }}">Ver</a>
                                @if ($administra && $aspirante->esEditable())
                                    <x-menu-acciones :etiqueta="$aspirante->persona->nombre_completo">
                                        <li><a class="dropdown-item" href="{{ route('aspirantes.edit', $aspirante) }}"><i class="bi bi-pencil" aria-hidden="true"></i> Editar</a></li>
                                    </x-menu-acciones>
                                @endif
                            </div></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        <div class="tabla-pie"><x-por-pagina :paginador="$aspirantes" singular="aspirante" plural="aspirantes" /></div>
    </x-tarjeta>
@endsection
