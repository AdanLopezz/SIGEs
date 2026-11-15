@extends('layouts.app')
@php $errCancelar = $errors->getBag('cancelar'); @endphp
@section('titulo', 'Convenio '.$convenio->numero)
@section('contenido')
    <x-page-header :titulo="'Convenio '.$convenio->numero" :volver="[route('convenios.index'), 'Volver a Convenios']">
        <x-slot:subtitulo>{{ $convenio->tipo_nombre }} · <x-badge-estado :estado="$convenio->estado_efectivo" />@if ($convenio->vence_pronto) <x-badge-estado estado="VENCE_PRONTO" />@endif</x-slot:subtitulo>
        <x-slot:acciones>
            @can('dual.administrar')
                @if ($convenio->estaVigente())
                    <a href="{{ route('convenios.edit', $convenio) }}" class="btn btn-outline-siges"><i class="bi bi-pencil" aria-hidden="true"></i> Editar</a>
                    <button type="button" class="btn btn-outline-siges" data-bs-toggle="modal" data-bs-target="#modalCancelar"><i class="bi bi-x-circle" aria-hidden="true"></i> Cancelar convenio</button>
                @endif
            @endcan
        </x-slot:acciones>
    </x-page-header>

    @if ($convenio->vence_pronto)
        <x-aviso tipo="aviso">Vence {{ $convenio->dias_restantes === 0 ? 'hoy' : 'en '.$convenio->dias_restantes.' '.($convenio->dias_restantes === 1 ? 'día' : 'días') }}. Para renovarlo registra un convenio nuevo.</x-aviso>
    @elseif ($convenio->estado_efectivo !== \App\Models\OrgConvenio::VIGENTE)
        <x-aviso tipo="info">Convenio {{ mb_strtolower(\App\Models\OrgConvenio::ESTADOS[$convenio->estado_efectivo]) }}: solo consulta.</x-aviso>
    @endif

    <x-tarjeta titulo="Datos del convenio" icono="bi-file-earmark-text">
        <dl class="dato-lista">
            <dt>Empresa</dt><dd><a href="{{ route('empresas.show', $convenio->empresa) }}">{{ $convenio->empresa->nombre }}</a> <span class="mono small">{{ $convenio->empresa->rfc }}</span></dd>
            <dt>Número</dt><dd class="mono">{{ $convenio->numero }}</dd>
            <dt>Tipo</dt><dd>{{ $convenio->tipo_nombre }}</dd>
            <dt>Vigencia</dt><dd>Del {{ $convenio->fecha_inicio->format('d/m/Y') }} al {{ $convenio->fecha_fin->format('d/m/Y') }}@if ($convenio->dias_restantes !== null) · {{ $convenio->dias_restantes }} {{ $convenio->dias_restantes === 1 ? 'día restante' : 'días restantes' }}@endif</dd>
            <dt>Convenio firmado</dt><dd><x-archivo-enlace :archivo="$convenio->documento" /></dd>
            <dt>Registrado</dt><dd>{{ $convenio->created_at?->format('d/m/Y') }}</dd>
        </dl>
    </x-tarjeta>
@endsection

@push('modales')
    @can('dual.administrar')
        @if ($convenio->estaVigente())
            <x-modal id="modalCancelar" titulo="Cancelar convenio" icono="bi-x-circle" metodo="PATCH" :accion="route('convenios.cancelar', $convenio)" boton="Cancelar convenio" peligro
                :data-abrir-al-cargar="$errCancelar->any() ? true : null">
                <p>Un convenio cancelado ya no se edita ni se reactiva; para continuar la relación registra uno nuevo.</p>
                <label for="cancelarMotivo" class="form-label">Motivo <span class="requerido" aria-hidden="true">*</span></label>
                <input type="text" id="cancelarMotivo" name="motivo" maxlength="255" required value="{{ $errCancelar->any() ? old('motivo') : '' }}" class="form-control @if ($errCancelar->has('motivo')) is-invalid @endif">
                @if ($errCancelar->has('motivo'))<div class="invalid-feedback">{{ $errCancelar->first('motivo') }}</div>@endif
                <div class="form-text">Queda registrado en la bitácora.</div>
            </x-modal>
        @endif
    @endcan
@endpush
