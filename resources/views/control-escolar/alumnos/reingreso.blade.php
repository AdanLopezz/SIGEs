@extends('layouts.app')
@section('titulo', 'Reingreso')
@section('contenido')
    <x-page-header :titulo="'Reingreso · '.$alumno->persona->nombre_completo" :volver="[route('alumnos.show', $alumno), 'Volver a la ficha']">
        <x-slot:subtitulo>{{ $alumno->carrera->nombre }} · Estado actual: <x-badge-estado :estado="$alumno->estado->nombre" /></x-slot:subtitulo>
    </x-page-header>
    @if ($alumno->estado->nombre !== \App\Models\CatEstadoAlumno::BAJA_TEMPORAL)
        <x-aviso tipo="aviso">Solo se puede reingresar a un alumno con baja temporal.</x-aviso>
    @endif
    <div class="row g-4">
        <div class="col-lg-6">
            <x-tarjeta titulo="Grupo de reingreso" icono="bi-arrow-repeat">
                <x-errores-formulario />
                <p class="texto-tenue">El alumno vuelve a estar Activo y se inscribe en el grupo elegido. No puede regresar al grupo del que se dio de baja en este periodo. Si había perdido el rol Alumno, su cuenta lo recupera.</p>
                @if ($grupos->isEmpty())
                    <x-estado-vacio icono="bi-people" texto="No hay grupos disponibles de su carrera en el periodo activo." />
                @else
                    <form method="POST" action="{{ route('alumnos.reingresar', $alumno) }}" novalidate>
                        @csrf
                        <div class="row g-3">
                            <x-seleccion name="aca_grupo_escolar_id" label="Grupo" col="col-12" :requerido="true" placeholder="Selecciona un grupo"
                                :opciones="$grupos->mapWithKeys(fn ($grupo) => [$grupo->id => $grupo->nombre_completo.' · '.$grupo->semestre.'.º semestre'])" />
                            <x-campo name="motivo" label="Motivo del reingreso" col="col-12" maxlength="255" :requerido="true" ayuda="Queda registrado en la bitácora." />
                        </div>
                        <div class="form-acciones mt-4 mb-0">
                            <a class="btn btn-outline-secondary" href="{{ route('alumnos.show', $alumno) }}">Cancelar</a>
                            <button type="submit" class="btn btn-siges">Reingresar</button>
                        </div>
                    </form>
                @endif
            </x-tarjeta>
        </div>
    </div>
@endsection
