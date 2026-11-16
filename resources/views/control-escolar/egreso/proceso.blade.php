@extends('layouts.app')
@section('titulo', 'Proceso de egreso')
@section('contenido')
    <x-page-header :titulo="\App\Models\TrxProcesoEgreso::TIPOS[$proceso->tipo]" :subtitulo="$alumno->persona->nombre_completo.' · '.$alumno->matricula"
        :volver="[route('egreso.show', $alumno), 'Volver a la revisión de egreso']" />
    <x-errores-formulario />
    @if ($convenios->isEmpty())
        <x-aviso tipo="aviso">No hay convenios disponibles de este tipo. Solicita a Vinculación registrar el convenio antes de continuar.</x-aviso>
    @endif
    <form method="POST" action="{{ route('egreso.procesos.guardar', $alumno) }}" novalidate>
        @csrf
        <input type="hidden" name="tipo" value="{{ $proceso->tipo }}">
        <section class="form-seccion">
            <h2 class="form-seccion-titulo"><span>1</span> Convenio y periodo</h2>
            <div class="row g-3">
                <x-seleccion name="org_convenio_id" label="Convenio" col="col-12" :opciones="$convenios" :valor="$proceso->org_convenio_id" placeholder="Selecciona el convenio" :requerido="true" />
                <x-campo name="fecha_inicio" label="Fecha de inicio" type="date" :value="$proceso->fecha_inicio?->format('Y-m-d')" :requerido="true" />
                <x-campo name="fecha_fin" label="Fecha de fin" type="date" :value="$proceso->fecha_fin?->format('Y-m-d')" :requerido="true" ayuda="Ambas fechas deben estar cubiertas por el convenio." />
            </div>
        </section>
        <section class="form-seccion">
            <h2 class="form-seccion-titulo"><span>2</span> Acreditación</h2>
            <div class="row g-3">
                <x-seleccion name="estado" label="Estado" :opciones="\App\Models\TrxProcesoEgreso::ESTADOS" :valor="$proceso->estado" :requerido="true" />
                <x-campo name="horas_acreditadas" label="Horas acreditadas" type="number" min="0" max="32767" step="1" :value="$proceso->horas_acreditadas" :requerido="true" />
                <x-campo name="motivo" label="Motivo y referencia de la revisión" col="col-12" maxlength="255" :requerido="true" ayuda="Indica el respaldo de la acreditación. Quedará en la bitácora." />
            </div>
        </section>
        <div class="form-acciones">
            <a class="btn btn-outline-secondary" href="{{ route('egreso.show', $alumno) }}">Cancelar</a>
            <button class="btn btn-siges" type="submit">Guardar proceso</button>
        </div>
    </form>
@endsection
