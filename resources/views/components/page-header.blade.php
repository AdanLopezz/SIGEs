{{-- Encabezado de página. $volver = [url, texto] opcional; slot "acciones" a la derecha; "subtitulo" puede ser texto o un slot con HTML. --}}
@props(['titulo', 'subtitulo' => null, 'volver' => null])
<div {{ $attributes->class(['page-header']) }}>
    <div class="page-header-texto">
        @if ($volver)<a href="{{ $volver[0] }}" class="volver"><i class="bi bi-arrow-left" aria-hidden="true"></i> {{ $volver[1] }}</a>@endif
        <h1>{{ $titulo }}</h1>
        @if (filled((string) $subtitulo))<p>{{ $subtitulo }}</p>@endif
    </div>
    @isset($acciones)
        @if ($acciones->isNotEmpty())<div class="page-header-acciones">{{ $acciones }}</div>@endif
    @endisset
</div>
