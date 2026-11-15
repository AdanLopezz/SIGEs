{{-- Gráfica de barras horizontales con CSS (sin JS ni librerías): una barra por fila con <progress>, colores de base/variables.css.
     :filas = filas de una sección de reporte; etiqueta y valor = índices de columna; porcentaje = el valor es % (escala 0–100);
     si no, la escala es el mayor valor. Se muestran hasta :limite filas. --}}
@props(['filas', 'etiqueta' => 0, 'valor' => 1, 'porcentaje' => false, 'limite' => 20, 'titulo' => 'Gráfica'])
@php
    $filas = collect($filas)->take($limite);
    $maximo = $porcentaje ? 100 : max(1, (float) $filas->max(fn ($fila) => (float) ($fila[$valor] ?? 0)));
@endphp
@if ($filas->isNotEmpty())
    <figure {{ $attributes->class(['barras']) }}>
        <figcaption class="visually-hidden">{{ $titulo }}</figcaption>
        <ul class="barras-lista">
            @foreach ($filas as $fila)
                @php $cantidad = $fila[$valor] ?? null; @endphp
                <li class="barras-fila">
                    <span class="barras-etiqueta">{{ $fila[$etiqueta] }}</span>
                    <progress class="barras-progreso" max="{{ $maximo }}" value="{{ (float) ($cantidad ?? 0) }}" aria-hidden="true"></progress>
                    <span class="barras-valor">{{ \App\Support\Reportes\Catalogo::celda($cantidad, $porcentaje) }}</span>
                </li>
            @endforeach
        </ul>
    </figure>
@endif
