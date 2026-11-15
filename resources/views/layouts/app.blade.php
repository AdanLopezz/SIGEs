<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('titulo', 'Inicio') | SIGEs</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="panel-body">

{{-- Menú lateral --}}
<aside class="panel-sidebar" id="panelSidebar">
    <div class="sidebar-brand">
        <img src="{{ asset('img/logo.png') }}" alt="Logo SIGEs">
        <div>
            <strong>SIGEs</strong>
            <small>{{ $plantel }}</small>
        </div>
    </div>

    <nav class="sidebar-nav">
        @foreach ($menu as $grupo)
                @if ($grupo['seccion'])
                    <div class="sidebar-section">{{ $grupo['seccion'] }}</div>
                @endif

                @foreach ($grupo['opciones'] as $o)
                    <a href="{{ route($o['ruta']) }}" class="sidebar-link {{ request()->routeIs($o['ruta'], $o['patron']) ? 'active' : '' }}">
                        <i class="bi {{ $o['icono'] }}" aria-hidden="true"></i>
                        <span>{{ $o['titulo'] }}</span>
                    </a>
                @endforeach
        @endforeach
    </nav>
</aside>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

{{-- Contenido --}}
<div class="panel-main">
    <header class="panel-topbar">
        <div class="topbar-left">
            <button type="button" class="topbar-menu-btn" id="sidebarToggle" aria-label="Abrir menú">
                <i class="bi bi-list" aria-hidden="true"></i>
            </button>
            {{-- Periodo de consulta: abre el modal "Selecciona periodo". El activo oficial solo cambia en Configuración → Periodos. --}}
            @if ($periodosSelector->isNotEmpty())
                <button type="button" class="topbar-periodo selector-periodo-boton" data-bs-toggle="modal" data-bs-target="#modalSeleccionaPeriodo"
                    title="{{ $periodoConsulta ? $periodoConsulta->nombre_visible.' ('.$periodoConsulta->clave.')' : 'Sin periodo' }}">
                    <span class="d-none d-sm-inline">Periodo:</span>
                    <strong>{{ $periodoConsulta?->nombre_visible ?? 'Sin periodo' }}</strong>
                    @if ($periodoConsulta)<small class="mono texto-tenue d-none d-md-inline">{{ $periodoConsulta->clave }}</small>@endif
                    <i class="bi bi-chevron-down" aria-hidden="true"></i>
                </button>
            @else
                <span class="topbar-periodo">Periodo: <strong>{{ $periodoActivo?->nombre_visible ?? 'sin periodo activo' }}</strong></span>
            @endif
        </div>


        @php
            // Con más de 2 roles: los 2 primeros y "+N"; la lista completa va en el tooltip.
            $rolesPanel = $usuarioPanel->getRoleNames();
            $rolesVisibles = $rolesPanel->take(2)->implode(', ').($rolesPanel->count() > 2 ? ' +'.($rolesPanel->count() - 2) : '');
        @endphp
        <div class="topbar-user">
            @if ($avisosSinLeer !== null)
                <a href="{{ route('avisos.index') }}" class="topbar-avisos" title="Avisos" aria-label="Avisos{{ $avisosSinLeer > 0 ? ': '.$avisosSinLeer.' sin leer' : '' }}">
                    <i class="bi bi-bell" aria-hidden="true"></i>
                    @if ($avisosSinLeer > 0)<span class="topbar-avisos-contador" aria-hidden="true">{{ $avisosSinLeer > 99 ? '99+' : $avisosSinLeer }}</span>@endif
                </a>
            @endif
            <div class="topbar-user-info" title="{{ $usuarioPanel->name }} · {{ $rolesPanel->implode(', ') }}">
                <strong>{{ $usuarioPanel->name }}</strong>
                <small>{{ $rolesVisibles }}</small>
            </div>
            <div class="topbar-avatar" title="{{ $usuarioPanel->name }} · {{ $rolesPanel->implode(', ') }}">{{ $usuarioPanel->persona?->iniciales }}</div>

            {{-- Menú del usuario: cambiar la contraseña y cerrar sesión. --}}
            <div class="dropdown">
                <button type="button" class="topbar-logout" data-bs-toggle="dropdown" aria-expanded="false" title="Mi cuenta" aria-label="Mi cuenta">
                    <i class="bi bi-person-gear" aria-hidden="true"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="{{ route('mi-cuenta.contrasena.edit') }}"><i class="bi bi-key" aria-hidden="true"></i> Cambiar mi contraseña</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item"><i class="bi bi-box-arrow-right" aria-hidden="true"></i> Cerrar sesión</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </header>

    <main class="panel-content">
        @if ($periodoActivo && $periodoConsulta && ! $periodoConsulta->is($periodoActivo))
            <x-aviso :tipo="$tipoPeriodo === 'FUTURO' ? 'info' : 'aviso'" class="aviso-periodo">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <span>
                        @if ($tipoPeriodo === 'FUTURO' && $soloPortal)
                            <strong>Próximo periodo</strong> · {{ $periodoConsulta->nombre_visible }}. Aún no inicia; aquí verás tus calificaciones y asistencia cuando comience.
                        @elseif ($tipoPeriodo === 'FUTURO')
                            Estás preparando <strong>{{ $periodoConsulta->nombre_visible }}</strong> ({{ $periodoConsulta->clave }}): solo se preparan grupos, materias y docentes.
                        @else
                            Estás consultando <strong>{{ $periodoConsulta->nombre_visible }}</strong> ({{ $periodoConsulta->clave }}), un periodo pasado: solo lectura.
                        @endif
                        @unless ($soloPortal)Las acciones de operación (inscripciones, acceso, asistencia, permisos) siguen usando el periodo activo.@endunless
                    </span>
                    <form method="POST" action="{{ route('periodo-consulta.destroy') }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-siges"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Volver al periodo activo</button>
                    </form>
                </div>
            </x-aviso>
        @endif
        <x-alertas />
        @yield('contenido')
    </main>
</div>
@stack('modales')
@if ($periodosSelector->isNotEmpty())
    <x-modal id="modalSeleccionaPeriodo" titulo="Selecciona periodo" icono="bi-calendar3" metodo="PUT" :accion="route('periodo-consulta.update')" :sin-boton="true">
        <div class="d-flex justify-content-end mb-2">
            <button type="button" class="btn btn-sm btn-outline-siges" data-ordenar-periodos aria-pressed="false">
                <i class="bi bi-sort-down" aria-hidden="true"></i> <span data-orden-texto>Más reciente primero</span>
            </button>
        </div>
        <ul class="selector-periodo-lista" data-lista-periodos>
            @foreach ($periodosSelector as $periodo)
                @php $elegido = $periodoConsulta?->is($periodo); @endphp
                <li>
                    <button type="submit" name="periodo" value="{{ $periodo->id }}" @class(['selector-periodo-opcion', 'elegido' => $elegido]) @if ($elegido) aria-current="true" @endif>
                        <span class="selector-periodo-nombre">{{ $periodo->nombre_visible }}</span>
                        <small class="mono">{{ $periodo->clave }}</small>
                        @if ($periodo->activo)<span class="selector-periodo-activo">Activo</span>
                        @elseif (\App\Support\PeriodoDeTrabajo::tipo($periodo) === 'FUTURO')<span class="selector-periodo-activo">Próximo periodo</span>@endif
                    </button>
                </li>
            @endforeach
        </ul>
    </x-modal>
@endif
</body>
</html>
