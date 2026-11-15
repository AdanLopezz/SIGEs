@extends('layouts.app')
@section('titulo', 'Cambiar mi contraseña')
@section('contenido')
    <x-page-header titulo="Cambiar mi contraseña" subtitulo="Al cambiarla se cerrarán tus sesiones abiertas en otros equipos." />

    @if ($obligatorio)
        <x-aviso tipo="aviso">Estás usando una contraseña temporal. Para continuar, crea una contraseña propia.</x-aviso>
    @endif
    <x-errores-formulario />

    <x-tarjeta titulo="Nueva contraseña" icono="bi-key">
        <form method="POST" action="{{ route('mi-cuenta.contrasena.update') }}" novalidate>
            @csrf
            @method('PUT')
            <div class="row g-3">
                <x-campo name="current_password" label="Contraseña actual" type="password" col="col-md-4" autocomplete="current-password" :requerido="true"
                    :ayuda="$obligatorio ? 'La contraseña temporal que te entregaron.' : null" />
                <x-campo name="password" label="Nueva contraseña" type="password" col="col-md-4" autocomplete="new-password" :requerido="true"
                    ayuda="Mínimo 8 caracteres, con mayúsculas, minúsculas y números. Debe ser distinta de la actual." />
                <x-campo name="password_confirmation" label="Confirmar nueva contraseña" type="password" col="col-md-4" autocomplete="new-password" :requerido="true" />
            </div>
            <div class="form-acciones">
                @unless ($obligatorio)<a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">Cancelar</a>@endunless
                <button type="submit" class="btn btn-siges">Cambiar contraseña</button>
            </div>
        </form>
    </x-tarjeta>
@endsection
