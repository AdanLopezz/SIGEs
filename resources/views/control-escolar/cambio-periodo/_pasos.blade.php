{{-- Indicador de pasos del asistente Cambio de periodo. Recibe $paso (1 a 4). --}}
@php $pasos = [1 => 'Periodos', 2 => 'Grupos', 3 => 'Alumnos', 4 => 'Resumen']; @endphp
<ol class="pasos" aria-label="Pasos del cambio de periodo">
    @foreach ($pasos as $numero => $nombre)
        <li @class(['hecho' => $numero < $paso, 'actual' => $numero === $paso]) @if ($numero === $paso) aria-current="step" @endif>
            <span class="pasos-numero">@if ($numero < $paso)<i class="bi bi-check" aria-hidden="true"></i><span class="visually-hidden">{{ $numero }}</span>@else{{ $numero }}@endif</span> {{ $nombre }}
        </li>
    @endforeach
</ol>
