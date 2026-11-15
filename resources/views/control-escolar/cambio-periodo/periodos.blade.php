@extends('layouts.app')
@php
    $tipos = [\App\Support\PeriodoDeTrabajo::ACTIVO => 'activo', \App\Support\PeriodoDeTrabajo::FUTURO => 'futuro', \App\Support\PeriodoDeTrabajo::PASADO => 'pasado'];
    $texto = fn ($periodo) => $periodo->nombre_visible.' ('.$periodo->clave.') · '.$tipos[\App\Support\PeriodoDeTrabajo::tipo($periodo)];
@endphp
@section('titulo', 'Cambio de periodo')
@section('contenido')
    <x-page-header titulo="Cambio de periodo" subtitulo="Copia los grupos al siguiente periodo y reinscribe a los alumnos en una sola operación." />
    @include('control-escolar.cambio-periodo._pasos', ['paso' => 1])
    <x-errores-formulario />

    @if ($destinos->isEmpty() || ! $origen)
        <x-aviso tipo="aviso">
            No hay un periodo destino: debe ser el periodo activo o uno futuro que inicie después del periodo origen.
            @can('configuracion.administrar')Créalo en <a href="{{ route('configuracion.periodos.index') }}">Configuración → Periodos</a>.@else Pide al administrador que lo cree en Configuración → Periodos.@endcan
        </x-aviso>
    @else
        <x-tarjeta titulo="Periodos" icono="bi-calendar-range">
            <form method="GET" action="{{ route('cambio-periodo.grupos') }}">
                <div class="row g-3">
                    <x-seleccion name="origen" label="Periodo origen" col="col-md-6" :valor="$origen->id" :requerido="true"
                        :opciones="$origenes->mapWithKeys(fn ($periodo) => [$periodo->id => $texto($periodo)])" ayuda="Los alumnos inscritos en este periodo pasan al siguiente semestre." />
                    <x-seleccion name="destino" label="Periodo destino" col="col-md-6" :valor="$destino?->id" :requerido="true"
                        :opciones="$destinos->mapWithKeys(fn ($periodo) => [$periodo->id => $texto($periodo)])" ayuda="El activo o uno futuro que inicie después del origen. La fecha de inscripción será su fecha de inicio." />
                </div>
                <p class="texto-tenue mt-3 mb-0">
                    ¿Falta el periodo destino?
                    @can('configuracion.administrar')Créalo en <a href="{{ route('configuracion.periodos.index') }}">Configuración → Periodos</a>.@else Pide al administrador que lo cree en Configuración → Periodos.@endcan
                </p>
                <div class="form-acciones">
                    <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-siges">Continuar <i class="bi bi-arrow-right" aria-hidden="true"></i></button>
                </div>
            </form>
        </x-tarjeta>
    @endif
@endsection
