@props(['icono' => 'bi-inbox', 'texto'])
<div class="estado-vacio">
    <i class="bi {{ $icono }}" aria-hidden="true"></i>
    <p>{{ $texto }}</p>
</div>
