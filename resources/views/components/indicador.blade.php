{{-- Tarjeta de indicador del tablero (App\Support\Tablero): valor grande, detalle, lista opcional y enlace a la pantalla donde se atiende.
     tono = exito|aviso|peligro|info|neutro (colores de base/variables.css). Sin url, la tarjeta no es enlace. --}}
@props(['titulo', 'valor', 'detalle' => null, 'icono' => 'bi-graph-up', 'tono' => 'neutro', 'url' => null, 'lista' => []])
@php $etiqueta = $url ? 'a' : 'div'; @endphp
<{{ $etiqueta }} @if ($url) href="{{ $url }}" @endif {{ $attributes->class(['indicador', 'indicador-'.$tono, 'indicador-enlace' => $url]) }}>
    <span class="indicador-encabezado"><i class="bi {{ $icono }}" aria-hidden="true"></i> {{ $titulo }}</span>
    <strong class="indicador-valor">{{ $valor }}</strong>
    @if ($detalle)<small class="indicador-detalle">{{ $detalle }}</small>@endif
    @if ($lista)
        <dl class="indicador-lista">
            @foreach ($lista as [$nombre, $cantidad])
                <div><dt>{{ $nombre }}</dt><dd>{{ $cantidad }}</dd></div>
            @endforeach
        </dl>
    @endif
</{{ $etiqueta }}>
