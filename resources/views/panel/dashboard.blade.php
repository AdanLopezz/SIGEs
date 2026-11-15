@extends('layouts.app')

@section('titulo', 'Inicio')

@section('contenido')
    <x-page-header :titulo="'Hola, '.($usuario->persona?->nombre ?? $usuario->name)" subtitulo="Bienvenido al Sistema Integral de Gestión Escolar." />

    @can('configuracion.administrar')
        @if (! $plantelConfigurado)
            <x-aviso tipo="aviso">
                Aún no se capturan los datos del plantel (CCT y nombre). Hazlo en <a href="{{ route('configuracion.edit') }}">Configuración</a>.
            </x-aviso>
        @endif
    @endcan

    <div class="panel-grid">
        <x-tarjeta titulo="Tu cuenta" icono="bi-person-badge">
            <dl class="dato-lista">
                <dt>Nombre</dt><dd>{{ $usuario->name }}</dd>
                <dt>Correo</dt><dd>{{ $usuario->email }}</dd>
                <dt>CURP</dt><dd class="mono">{{ $usuario->persona?->curp ?? '—' }}</dd>
                <dt>Rol</dt>
                <dd>
                    @foreach ($usuario->getRoleNames() as $rol)
                        <span class="chip">{{ $rol }}</span>
                    @endforeach
                </dd>
            </dl>
        </x-tarjeta>

        <x-tarjeta titulo="Tus módulos" icono="bi-grid">
            @if ($modulos->isEmpty())
                <x-estado-vacio icono="bi-tools" texto="Los módulos se irán habilitando conforme se desarrollen. Aquí aparecerán solo los que tu rol puede usar." />
            @else
                <div class="modulos-grid">
                    @foreach ($modulos as $m)
                        <a href="{{ route($m['ruta']) }}" class="modulo-card">
                            <i class="bi {{ $m['icono'] }}" aria-hidden="true"></i>
                            <strong>{{ $m['titulo'] }}</strong>
                            <small>{{ $m['seccion'] }}</small>
                        </a>
                    @endforeach
                </div>
            @endif
        </x-tarjeta>
    </div>
@endsection
