@extends('layouts.app')
@php
    $editando = $grupo->exists;
    $sinPersonal = collect(['Orientador' => $orientadores, 'Tutor' => $tutores])->filter(fn ($lista) => $lista->isEmpty())->keys();
    $opcionesCarrera = $carreras->mapWithKeys(fn ($carrera) => [$carrera->id => $carrera->nombre.' ('.$carrera->total_semestres.' semestres)'.($carrera->activo ? '' : ' · inactiva')]);
@endphp
@section('titulo', $editando ? 'Editar grupo' : 'Nuevo grupo')
@section('contenido')
    <x-page-header :titulo="$editando ? 'Editar grupo' : 'Nuevo grupo'" :subtitulo="$editando ? 'Actualiza los datos y responsables de '.$grupo->nombre_completo.'.' : 'Registra un grupo del periodo y asigna a su orientador y tutor.'" />
    <x-errores-formulario />
    @if ($sinPersonal->isNotEmpty())
        <x-aviso tipo="aviso">
            No hay personal activo con el rol {{ $sinPersonal->implode(' ni con el rol ') }}.
            @can('usuarios.administrar')Asígnalo en <a href="{{ route('usuarios.index') }}">Usuarios</a>.@else Pide al administrador que lo asigne.@endcan
        </x-aviso>
    @endif
    <form method="POST" action="{{ $editando ? route('grupos.update', $grupo) : route('grupos.store') }}" novalidate>
        @csrf
        @if ($editando) @method('PUT') @endif
        <section class="form-seccion">
            <h2 class="form-seccion-titulo"><span>1</span> Datos del grupo</h2>
            <div class="row g-3">
                <x-seleccion name="aca_periodo_escolar_id" label="Periodo" :opciones="$periodos" :valor="$grupo->aca_periodo_escolar_id" placeholder="Selecciona un periodo" :requerido="true"
                    :bloqueado="$periodoBloqueado" :ayuda="$periodoBloqueado ? 'El periodo no se cambia porque el grupo ya tiene alumnos.' : null" />
                <x-seleccion name="aca_carrera_id" label="Carrera" :opciones="$opcionesCarrera" :valor="$grupo->aca_carrera_id" placeholder="Selecciona una carrera" :requerido="true" />
                <x-seleccion name="aca_turno_id" label="Turno" :opciones="$turnos" :valor="$grupo->aca_turno_id" placeholder="Selecciona un turno" :requerido="true" />
                <x-campo name="semestre" label="Semestre" type="number" :value="$grupo->semestre" min="1" max="10" step="1" :requerido="true"
                    :readonly="$semestreBloqueado" :ayuda="$semestreBloqueado ? 'El semestre no se cambia porque el grupo ya tiene materias.' : 'No debe superar los semestres de la carrera.'" />
                <x-campo name="identificador" label="Identificador" :value="$grupo->identificador" maxlength="20" class="text-uppercase" :requerido="true" ayuda="Letras y números, por ejemplo 1A o 3B. Único en el periodo." />
            </div>
        </section>
        <section class="form-seccion">
            <h2 class="form-seccion-titulo"><span>2</span> Responsables</h2>
            <div class="row g-3">
                <x-seleccion name="orientador_id" label="Orientador" col="col-md-6" :opciones="$orientadores->pluck('nombre', 'id')" :valor="$grupo->orientador_id" placeholder="Selecciona un orientador" :requerido="true" ayuda="Personal activo con el rol Orientador." />
                <x-seleccion name="tutor_id" label="Tutor" col="col-md-6" :opciones="$tutores->pluck('nombre', 'id')" :valor="$grupo->tutor_id" placeholder="Selecciona un tutor" :requerido="true" ayuda="Personal activo con el rol Tutor." />
            </div>
        </section>
        <div class="form-acciones"><a href="{{ route('grupos.index') }}" class="btn btn-outline-secondary">Cancelar</a><button type="submit" class="btn btn-siges">{{ $editando ? 'Guardar cambios' : 'Crear grupo' }}</button></div>
    </form>
@endsection
