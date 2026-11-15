@extends('layouts.app')
@section('titulo', 'Plantel')
@section('contenido')
    <x-page-header titulo="Configuración · Plantel" subtitulo="Captura la identidad del plantel y su calificación mínima aprobatoria." />
    @include('configuracion._pestanas')
    <x-errores-formulario />
    <form method="POST" action="{{ route('configuracion.update') }}" novalidate>
        @csrf @method('PUT')
        <section class="form-seccion">
            <h2 class="form-seccion-titulo"><span>1</span> Datos del plantel</h2>
            <div class="row g-3">
                <x-campo name="clave_cct" label="CCT" :value="$registro->clave_cct" maxlength="20" class="text-uppercase" ayuda="Ej. 15ETC0001Z" :requerido="true" />
                <x-campo name="nombre" label="Nombre del plantel" :value="$registro->nombre" col="col-md-8" maxlength="200" :requerido="true" />
                <x-campo name="nombre_corto" label="Nombre corto" :value="$registro->nombre_corto" maxlength="50" ayuda="Se muestra en el menú lateral." :requerido="true" />
                <x-campo name="calificacion_minima" label="Calificación mínima" type="number" :value="$registro->calificacion_minima" min="5" max="10" step="0.01" :requerido="true" />
            </div>
        </section>
        <div class="form-acciones"><button type="submit" class="btn btn-siges">Guardar cambios</button></div>
    </form>
@endsection
