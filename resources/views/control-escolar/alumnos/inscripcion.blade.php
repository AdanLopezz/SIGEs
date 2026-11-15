@extends('layouts.app')
@php
    $nombre = $alumno->persona->nombre_completo;
    $verbo = $actual ? 'Cambiar de grupo' : 'Inscribir';
@endphp
@section('titulo', $verbo)
@section('contenido')
    <x-page-header :titulo="$verbo.' · '.$nombre" :subtitulo="$alumno->carrera->nombre.' · Periodo '.($periodoActivo?->clave ?? 'sin periodo activo')"
        :volver="[route('alumnos.show', $alumno), 'Volver a la ficha']" />
    <x-errores-formulario />
    @if (! $periodoActivo)
        <x-aviso tipo="aviso">No hay un periodo escolar activo.</x-aviso>
    @elseif (! $alumno->estado->permite_inscripcion)
        <x-aviso tipo="aviso">El estado del alumno ({{ $alumno->estado->nombre }}) no permite inscribirlo.</x-aviso>
    @endif
    <div class="row g-4">
        <div class="col-lg-6">
            <x-tarjeta :titulo="$actual ? 'Nuevo grupo' : 'Grupo'" icono="bi-journal-check">
                @if ($actual)<p>Grupo actual: <strong>{{ $actual->grupo->nombre_completo }}</strong> ({{ $actual->grupo->semestre }}.º semestre). La inscripción actual quedará como «Cambio de grupo».</p>@endif
                @if ($grupos->isEmpty())
                    <x-estado-vacio icono="bi-people" texto="No hay otros grupos de la carrera del alumno en el periodo activo." />
                @else
                    {{-- El select pertenece al formulario del modal de confirmación (atributo form). --}}
                    <div class="row g-3">
                        <x-seleccion name="aca_grupo_escolar_id" label="Grupo" col="col-12" form="formInscripcion" :requerido="true" placeholder="Selecciona un grupo"
                            :opciones="$grupos->mapWithKeys(fn ($grupo) => [$grupo->id => $grupo->nombre_completo.' · '.$grupo->semestre.'.º semestre'])"
                            :ayuda="'Grupos del periodo activo de la carrera '.$alumno->carrera->nombre.'.'" />
                    </div>
                    <div class="form-acciones mt-4 mb-0">
                        <a class="btn btn-outline-secondary" href="{{ route('alumnos.show', $alumno) }}">Cancelar</a>
                        <button type="button" class="btn btn-siges" data-bs-toggle="modal" data-bs-target="#modalInscripcion"
                            data-accion="{{ $actual ? route('alumnos.cambiar-grupo', $alumno) : route('alumnos.inscribir', $alumno) }}" data-nombre="{{ $nombre }}">{{ $verbo }}</button>
                    </div>
                @endif
            </x-tarjeta>
        </div>
    </div>
@endsection

@push('modales')
    <x-modal id="modalInscripcion" :titulo="$verbo" icono="bi-journal-check" :metodo="$actual ? 'PUT' : 'POST'" confirmar formulario="formInscripcion">
        <p>¿Confirmas {{ $actual ? 'el cambio de grupo de' : 'la inscripción de' }} <strong data-campo="nombre"></strong> en el grupo seleccionado?</p>
        @if ($actual)
            <div class="row g-3"><x-campo name="motivo" label="Motivo del cambio" col="col-12" maxlength="255" :requerido="true" ayuda="Queda registrado en la bitácora." /></div>
        @endif
    </x-modal>
@endpush
