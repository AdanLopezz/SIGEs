@extends('layouts.app')
@php $editando = $registro->exists; @endphp
@section('titulo', 'Materias del grupo')
@section('contenido')
    <x-page-header :titulo="'Materias · '.$grupo->nombre_completo" :subtitulo="'Periodo '.$grupo->periodo->nombre_visible.' ('.$grupo->periodo->clave.') · '.$grupo->semestre.'.º semestre'"
        :volver="[route('grupos.index', ['periodo' => $grupo->aca_periodo_escolar_id]), 'Volver a Grupos']" />
    @unless ($preparable)
        <x-aviso tipo="info">El grupo es de un periodo pasado: sus materias y docentes solo se consultan.</x-aviso>
    @endunless
    @if ($preparable && $docentes->isEmpty())
        <x-aviso tipo="aviso">
            No hay personal activo con el rol Docente.
            @can('usuarios.administrar')Asígnalo en <a href="{{ route('usuarios.index') }}">Usuarios</a>.@else Pide al administrador que lo asigne.@endcan
        </x-aviso>
    @endif
    <div class="row g-4">
        @if ($preparable)
        <div class="col-lg-4">
            <x-tarjeta :titulo="$editando ? 'Cambiar docente' : 'Agregar materia'" :icono="$editando ? 'bi-person-gear' : 'bi-plus-circle'">
                <x-errores-formulario />
                <form method="POST" action="{{ $editando ? route('grupos.materias.update', [$grupo, $registro]) : route('grupos.materias.store', $grupo) }}" novalidate>
                    @csrf
                    @if ($editando) @method('PUT') @endif
                    <div class="row g-3">
                        @if ($editando)
                            <div class="col-12">
                                <label for="aca_asignatura_id" class="form-label"><i class="bi bi-lock" aria-hidden="true"></i> Asignatura</label>
                                <input id="aca_asignatura_id" class="form-control" value="{{ $registro->asignatura->clave }} · {{ $registro->asignatura->nombre }}" disabled>
                            </div>
                        @else
                            <x-seleccion name="aca_asignatura_id" label="Asignatura" col="col-12" :requerido="true"
                                :opciones="$asignaturas->mapWithKeys(fn ($asignatura) => [$asignatura->id => $asignatura->clave.' · '.$asignatura->nombre])"
                                :placeholder="$asignaturas->isEmpty() ? 'No hay asignaturas disponibles' : 'Selecciona una asignatura'"
                                :ayuda="'Asignaturas activas de '.$grupo->semestre.'.º semestre que el grupo aún no tiene.'" />
                        @endif
                        <x-seleccion name="docente_id" label="Docente" col="col-12" :opciones="$docentes->pluck('nombre', 'id')" :valor="$registro->docente_id" placeholder="Selecciona un docente" :requerido="true" />
                    </div>
                    <div class="form-acciones mt-4 mb-0">
                        @if ($editando)<a class="btn btn-outline-secondary" href="{{ route('grupos.materias.index', $grupo) }}">Cancelar</a>@endif
                        <button type="submit" class="btn btn-siges">{{ $editando ? 'Guardar cambios' : 'Agregar' }}</button>
                    </div>
                </form>
            </x-tarjeta>
        </div>
        @endif
        <div @class(['col-lg-8' => $preparable, 'col-12' => ! $preparable])>
            <x-tarjeta sin-relleno etiqueta="Materias del grupo">
                @if ($materias->isEmpty())
                    <x-estado-vacio icono="bi-journal-bookmark" texto="El grupo aún no tiene materias asignadas." />
                @else
                    <div class="table-responsive">
                        <table class="table tabla-siges align-middle mb-0">
                            <thead><tr><th scope="col">Clave</th><th scope="col">Asignatura</th><th scope="col">Horas por semana</th><th scope="col">Docente</th><th scope="col" class="text-end">Acciones</th></tr></thead>
                            <tbody>
                            @foreach ($materias as $materia)
                                <tr>
                                    <td class="mono">{{ $materia->asignatura->clave }}</td>
                                    <td>{{ $materia->asignatura->nombre }}</td>
                                    <td>{{ $materia->asignatura->horas_semana }}</td>
                                    <td>{{ $materia->docente->nombre }}</td>
                                    <td><div class="d-flex justify-content-end gap-2">
                                        @if ($preparable)
                                        <a class="btn btn-sm btn-outline-siges" href="{{ route('grupos.materias.edit', [$grupo, $materia]) }}" aria-label="Cambiar docente de {{ $materia->asignatura->nombre }}">Cambiar docente</a>
                                        <x-menu-acciones :etiqueta="$materia->asignatura->nombre">
                                            <li><button type="button" class="dropdown-item peligro" data-bs-toggle="modal" data-bs-target="#modalQuitarMateria"
                                                data-accion="{{ route('grupos.materias.destroy', [$grupo, $materia]) }}" data-nombre="{{ $materia->asignatura->nombre }}"><i class="bi bi-x-circle" aria-hidden="true"></i> Quitar</button></li>
                                        </x-menu-acciones>
                                        @else
                                            <span class="texto-tenue">—</span>
                                        @endif
                                    </div></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
                <div class="tabla-pie"><span>{{ $materias->count() }} {{ $materias->count() === 1 ? 'materia' : 'materias' }}</span><span>Total: <strong>{{ $totalHoras }}</strong> horas por semana</span></div>
            </x-tarjeta>
        </div>
    </div>
@endsection

@push('modales')
    <x-modal id="modalQuitarMateria" titulo="Quitar materia" icono="bi-x-circle" metodo="DELETE" boton="Quitar" peligro confirmar>
        ¿Quieres quitar <strong data-campo="nombre"></strong> de {{ $grupo->nombre_completo }}? Solo es posible si aún no tiene evaluaciones, horarios ni calificaciones.
    </x-modal>
@endpush
