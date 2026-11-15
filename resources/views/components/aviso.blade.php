{{-- Aviso (alerta-siges) con icono según el tipo: info, aviso, peligro o exito. --}}
@props(['tipo' => 'info'])
@php $iconos = ['info' => 'bi-info-circle', 'aviso' => 'bi-exclamation-triangle', 'peligro' => 'bi-x-octagon', 'exito' => 'bi-check-circle']; @endphp
<div {{ $attributes->class(['alerta-siges', $tipo]) }} role="{{ in_array($tipo, ['aviso', 'peligro'], true) ? 'alert' : 'status' }}">
    <i class="bi {{ $iconos[$tipo] ?? 'bi-info-circle' }}" aria-hidden="true"></i>
    <div>{{ $slot }}</div>
</div>
