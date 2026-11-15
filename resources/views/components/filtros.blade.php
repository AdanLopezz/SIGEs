{{-- Formulario de filtros (GET): los campos van en el slot y se acomodan en columnas automáticas; al final, Filtrar y Limpiar. --}}
@props(['accion', 'limpiar' => null, 'boton' => 'Filtrar', 'textoLimpiar' => 'Limpiar'])
<form action="{{ $accion }}" method="GET" {{ $attributes->class(['filtros panel-card mb-4']) }}>
    {{ $slot }}
    <div class="filtros-botones">
        <button class="btn btn-siges" type="submit">{{ $boton }}</button>
        @if ($limpiar)<a href="{{ $limpiar }}" class="btn btn-outline-siges">{{ $textoLimpiar }}</a>@endif
    </div>
</form>
