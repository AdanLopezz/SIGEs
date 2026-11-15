<nav aria-label="Secciones de configuración" class="mb-4">
    <ul class="nav nav-tabs flex-wrap">
        <li class="nav-item"><a class="nav-link {{ request()->routeIs('configuracion.edit', 'configuracion.update') ? 'active' : '' }}" href="{{ route('configuracion.edit') }}" @if(request()->routeIs('configuracion.edit', 'configuracion.update')) aria-current="page" @endif>Plantel</a></li>
        <li class="nav-item"><a class="nav-link {{ request()->routeIs('configuracion.periodos.*') ? 'active' : '' }}" href="{{ route('configuracion.periodos.index') }}" @if(request()->routeIs('configuracion.periodos.*')) aria-current="page" @endif>Periodos</a></li>
        <li class="nav-item"><a class="nav-link {{ request()->routeIs('configuracion.dias-inhabiles.*') ? 'active' : '' }}" href="{{ route('configuracion.dias-inhabiles.index') }}" @if(request()->routeIs('configuracion.dias-inhabiles.*')) aria-current="page" @endif>Días inhábiles</a></li>
        <li class="nav-item"><a class="nav-link {{ request()->routeIs('configuracion.turnos.*') ? 'active' : '' }}" href="{{ route('configuracion.turnos.index') }}" @if(request()->routeIs('configuracion.turnos.*')) aria-current="page" @endif>Turnos</a></li>
        <li class="nav-item"><a class="nav-link {{ request()->routeIs('configuracion.carreras.*') ? 'active' : '' }}" href="{{ route('configuracion.carreras.index') }}" @if(request()->routeIs('configuracion.carreras.*')) aria-current="page" @endif>Carreras</a></li>
        <li class="nav-item"><a class="nav-link {{ request()->routeIs('configuracion.asignaturas.*') ? 'active' : '' }}" href="{{ route('configuracion.asignaturas.index') }}" @if(request()->routeIs('configuracion.asignaturas.*')) aria-current="page" @endif>Asignaturas</a></li>
        <li class="nav-item"><a class="nav-link {{ request()->routeIs('configuracion.areas.*') ? 'active' : '' }}" href="{{ route('configuracion.areas.index') }}" @if(request()->routeIs('configuracion.areas.*')) aria-current="page" @endif>Áreas</a></li>
        <li class="nav-item"><a class="nav-link {{ request()->routeIs('configuracion.espacios.*') ? 'active' : '' }}" href="{{ route('configuracion.espacios.index') }}" @if(request()->routeIs('configuracion.espacios.*')) aria-current="page" @endif>Espacios</a></li>
        <li class="nav-item"><a class="nav-link {{ request()->routeIs('configuracion.tipos-incidencia.*') ? 'active' : '' }}" href="{{ route('configuracion.tipos-incidencia.index') }}" @if(request()->routeIs('configuracion.tipos-incidencia.*')) aria-current="page" @endif>Tipos de incidencia</a></li>
    </ul>
</nav>
