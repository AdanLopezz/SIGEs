{{-- Tarjeta (panel-card) con título e icono opcionales; slot "acciones" a la derecha del título. :sin-relleno para tablas a ras. --}}
@props(['titulo' => null, 'icono' => null, 'sinRelleno' => false, 'etiqueta' => null])
<section {{ $attributes->class(['panel-card', 'p-0' => $sinRelleno]) }} @if ($etiqueta ?? $titulo) aria-label="{{ $etiqueta ?? $titulo }}" @endif>
    @if ($titulo)
        <div class="panel-card-encabezado">
            <h2 class="panel-card-titulo">@if ($icono)<i class="bi {{ $icono }}" aria-hidden="true"></i> @endif{{ $titulo }}</h2>
            @isset($acciones)
                @if ($acciones->isNotEmpty())<div class="panel-card-acciones">{{ $acciones }}</div>@endif
            @endisset
        </div>
    @endif
    {{ $slot }}
</section>
