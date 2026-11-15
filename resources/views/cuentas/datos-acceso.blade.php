@extends('layouts.app')
@section('titulo', 'Datos de acceso')
@section('contenido')
    <x-page-header titulo="Datos de acceso" :volver="$rutas['volver']">
        <x-slot:subtitulo>{{ $usuario->persona->nombre_completo }}</x-slot:subtitulo>
        <x-slot:acciones>
            @if ($temporal['valor'] !== null)
                <a href="{{ $rutas['imprimir'] }}" class="btn btn-siges"><i class="bi bi-printer" aria-hidden="true"></i> Imprimir</a>
            @endif
        </x-slot:acciones>
    </x-page-header>

    {{-- Errores del modal "Generar nueva contraseña" (el modal se cierra al recargar). --}}
    @error('motivo')<x-errores-formulario />@enderror

    @if (! $permitido)
        <x-aviso tipo="info">Esta cuenta también es de personal. Sus datos de acceso se administran en Administración → Usuarios.</x-aviso>
    @endif

    <x-tarjeta titulo="Cuenta" icono="bi-key">
        <dl class="dato-lista">
            <dt>Nombre</dt><dd>{{ $usuario->persona->nombre_completo }}</dd>
            <dt>{{ $usuario->roles->count() === 1 ? 'Rol' : 'Roles' }}</dt>
            <dd>@forelse ($usuario->roles as $rol)<span class="chip">{{ $rol->name }}</span>@empty<span class="texto-tenue">Sin roles (sin acceso)</span>@endforelse</dd>
            <dt>Usuario</dt><dd class="mono">{{ $usuario->email }}</dd>
            @if ($temporal['valor'] !== null)
                <dt>Contraseña temporal</dt><dd class="mono datos-acceso-contrasena">{{ $temporal['valor'] }}</dd>
            @endif
        </dl>

        @if ($permitido)
            @if ($temporal['valor'] !== null)
                <x-aviso tipo="info" class="mt-3 mb-0">Entrega estos datos a la persona. Al entrar por primera vez deberá cambiar la contraseña; desde ese momento ya no se podrá consultar. Cada consulta queda en la bitácora.</x-aviso>
            @elseif ($temporal['ilegible'])
                <x-aviso tipo="peligro" class="mt-3 mb-0">La contraseña temporal no se puede leer (cambió la llave de cifrado del sistema). Genera una nueva.</x-aviso>
            @else
                <x-aviso tipo="info" class="mt-3 mb-0">El usuario ya estableció su propia contraseña.</x-aviso>
            @endif
            @if ($puedeGenerar)
                <div class="form-acciones">
                    <button type="button" class="btn {{ $temporal['valor'] !== null ? 'btn-outline-siges' : 'btn-siges' }}" data-bs-toggle="modal" data-bs-target="#modalNuevaContrasena">
                        <i class="bi bi-arrow-repeat" aria-hidden="true"></i> Generar nueva contraseña
                    </button>
                </div>
            @elseif ($usuario->is(auth()->user()))
                <p class="texto-tenue mt-3 mb-0">Para tu propia cuenta usa «Cambiar mi contraseña» en el menú de usuario.</p>
            @endif
        @endif
    </x-tarjeta>
@endsection

@push('modales')
    @if ($puedeGenerar)
        <x-modal id="modalNuevaContrasena" titulo="Generar nueva contraseña" icono="bi-arrow-repeat" metodo="PATCH" :accion="$rutas['nueva']" boton="Generar">
            <p>Se generará una contraseña temporal nueva para <strong>{{ $usuario->persona->nombre_completo }}</strong>. La actual dejará de funcionar, se cerrarán sus sesiones y deberá cambiarla al entrar.</p>
            <div class="row g-3"><x-campo name="motivo" label="Motivo" col="col-12" maxlength="255" :requerido="true" ayuda="Queda registrado en la bitácora." /></div>
        </x-modal>
    @endif
@endpush
