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

    {{-- Indicadores por rol (App\Support\Tablero): una sección por rol, sin repetir. --}}
    @foreach ($secciones as $clave => $seccion)
        <section class="tablero-seccion" aria-labelledby="tablero-{{ $clave }}">
            <h2 class="tablero-titulo" id="tablero-{{ $clave }}"><i class="bi {{ $seccion['icono'] }}" aria-hidden="true"></i> {{ $seccion['titulo'] }}</h2>
            @if ($seccion['aviso'])
                <x-aviso tipo="info">{{ $seccion['aviso'] }}</x-aviso>
            @elseif (empty($seccion['tarjetas']))
                <x-estado-vacio icono="bi-graph-up" texto="Sin indicadores para el periodo elegido." />
            @else
                <div class="tablero-grid">
                    @foreach ($seccion['tarjetas'] as $tarjeta)
                        <x-indicador :titulo="$tarjeta['titulo']" :valor="$tarjeta['valor']" :detalle="$tarjeta['detalle']" :icono="$tarjeta['icono']"
                            :tono="$tarjeta['tono']" :url="$tarjeta['url']" :lista="$tarjeta['lista']" />
                    @endforeach
                </div>
            @endif
        </section>
    @endforeach

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
        @if (empty($secciones))
            <x-tarjeta titulo="Indicadores" icono="bi-graph-up">
                <x-estado-vacio icono="bi-tools" texto="Tu rol todavía no tiene indicadores en el inicio. Usa el menú para ir a tus módulos." />
            </x-tarjeta>
        @endif
    </div>
@endsection
