{{-- Pie de un listado paginado (va dentro de .tabla-pie): total, "Mostrar [n] por página" y enlaces.
     El selector es un formulario GET a la misma pantalla con los filtros actuales (sin page: vuelve a la página 1).
     Con JS se envía al cambiar (por-pagina.js oculta "Aplicar"); sin JS se usa el botón.
     Props: paginador (LengthAwarePaginator), singular y plural del total ("1 alumno", "128 alumnos"). --}}
@props(['paginador', 'singular' => 'registro', 'plural' => 'registros'])
@php
    $total = $paginador->total();
    $idSelector = 'porPagina-'.$paginador->getPageName();
    // Filtros actuales como campos ocultos (también arreglos, p. ej. roles[]); sin page ni por_pagina.
    $campos = [];
    $agregar = function (string $nombre, mixed $valor) use (&$campos, &$agregar) {
        if (is_array($valor)) {
            foreach ($valor as $clave => $interno) {
                $agregar($nombre.'['.$clave.']', $interno);
            }
        } elseif (is_scalar($valor)) {
            $campos[] = [$nombre, (string) $valor];
        }
    };
    foreach (request()->query() as $nombre => $valor) {
        if (! in_array($nombre, [$paginador->getPageName(), \App\Support\Paginacion::PARAMETRO], true)) {
            $agregar((string) $nombre, $valor);
        }
    }
@endphp
<div class="por-pagina">
    <span class="por-pagina-total">{{ number_format($total) }} {{ $total === 1 ? $singular : $plural }}</span>
    @if ($total > 0)
        <form method="GET" action="{{ url()->current() }}" class="por-pagina-form" data-por-pagina>
            @foreach ($campos as [$nombre, $valor])<input type="hidden" name="{{ $nombre }}" value="{{ $valor }}">@endforeach
            <label for="{{ $idSelector }}">Mostrar</label>
            <select id="{{ $idSelector }}" name="{{ \App\Support\Paginacion::PARAMETRO }}" class="form-select form-select-sm">
                @foreach (\App\Support\Paginacion::opciones() as $opcion)
                    <option value="{{ $opcion }}" @selected($opcion === $paginador->perPage())>{{ $opcion }}</option>
                @endforeach
            </select>
            <span>por página</span>
            <button type="submit" class="btn btn-sm btn-outline-siges" data-por-pagina-boton>Aplicar</button>
        </form>
    @endif
</div>
@if ($paginador->hasPages())
    {{ $paginador->onEachSide(1)->links() }}
@endif
