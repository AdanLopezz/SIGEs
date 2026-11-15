@props(['archivo' => null])
@if ($archivo)
    @php $icono = $archivo->es_pdf ? 'bi-file-earmark-pdf' : ($archivo->es_imagen ? 'bi-file-earmark-image' : 'bi-file-earmark'); @endphp
    @can('ver', $archivo)
        <a href="{{ route('archivos.ver', $archivo) }}" target="_blank" rel="noopener" {{ $attributes->class(['archivo-enlace']) }}>
            <i class="bi {{ $icono }}" aria-hidden="true"></i><span>{{ $archivo->nombre_original }}</span><small class="texto-tenue">{{ $archivo->tamano_legible }}</small>
            <span class="visually-hidden">(se abre en una pestaña nueva)</span>
        </a>
    @else
        <span {{ $attributes->class(['archivo-enlace']) }}>
            <i class="bi {{ $icono }}" aria-hidden="true"></i><span>{{ $archivo->nombre_original }}</span><small class="texto-tenue">{{ $archivo->tamano_legible }}</small>
        </span>
    @endcan
@else
    <span class="texto-tenue">Sin archivo</span>
@endif
