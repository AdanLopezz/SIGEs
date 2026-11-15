@extends('layouts.impresion')
@section('titulo', 'Datos de acceso · '.$usuario->persona->nombre_completo)
@section('contenido')
    <div class="impresion-contenedor">
        <div class="impresion-acciones no-imprimir">
            <a href="{{ $rutas['ver'] }}" class="volver mb-0"><i class="bi bi-arrow-left" aria-hidden="true"></i> Volver a Datos de acceso</a>
            <button type="button" class="btn btn-siges" data-imprimir><i class="bi bi-printer" aria-hidden="true"></i> Imprimir</button>
        </div>

        <article class="datos-acceso-ficha" aria-label="Datos de acceso de {{ $usuario->persona->nombre_completo }}">
            <header class="datos-acceso-encabezado">
                <img src="{{ asset('img/logo.png') }}" alt="">
                <div><strong>{{ $plantel?->nombre_corto ?: 'SIGEs' }}</strong><small>Sistema Integral de Gestión Escolar · Datos de acceso</small></div>
            </header>

            <dl class="datos-acceso-lista">
                <dt>Nombre</dt><dd>{{ $usuario->persona->nombre_completo }}</dd>
                <dt>{{ $usuario->roles->count() === 1 ? 'Rol' : 'Roles' }}</dt><dd>{{ $usuario->roles->pluck('name')->implode(', ') ?: 'Sin roles' }}</dd>
                <dt>Dirección</dt><dd class="mono">{{ url('/') }}</dd>
                <dt>Usuario</dt><dd class="mono datos-acceso-valor">{{ $usuario->email }}</dd>
                <dt>Contraseña temporal</dt><dd class="mono datos-acceso-valor">{{ $temporal['valor'] }}</dd>
            </dl>

            <ul class="datos-acceso-indicaciones">
                <li>Al entrar por primera vez deberás cambiar la contraseña por una tuya (mínimo 8 caracteres, con mayúsculas, minúsculas y números).</li>
                <li>El usuario no es un correo: no envíes mensajes a esa dirección. Si olvidas tu contraseña, usa «¿Olvidaste tu contraseña?» y el enlace llegará a tu correo personal.</li>
                <li>No compartas estos datos. Guarda esta hoja en un lugar seguro o destrúyela después de cambiar la contraseña.</li>
            </ul>

            <footer class="datos-acceso-pie">Entregado el {{ now()->format('d/m/Y H:i') }}</footer>
        </article>
    </div>
@endsection
