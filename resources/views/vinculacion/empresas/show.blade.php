@extends('layouts.app')
@section('titulo', $empresa->nombre)
@section('contenido')
    <x-page-header :titulo="$empresa->nombre" :volver="[route('empresas.index'), 'Volver a Empresas']">
        <x-slot:subtitulo>RFC <span class="mono">{{ $empresa->rfc }}</span></x-slot:subtitulo>
        <x-slot:acciones>
            @can('dual.administrar')
                <a href="{{ route('empresas.edit', $empresa) }}" class="btn btn-outline-siges"><i class="bi bi-pencil" aria-hidden="true"></i> Editar</a>
                <a href="{{ route('convenios.create', ['empresa' => $empresa->id]) }}" class="btn btn-siges"><i class="bi bi-plus-lg" aria-hidden="true"></i> Nuevo convenio</a>
            @endcan
        </x-slot:acciones>
    </x-page-header>

    <div class="row g-4">
        <div class="col-lg-4">
            <x-tarjeta titulo="Contacto" icono="bi-person-lines-fill">
                <dl class="dato-lista">
                    <dt>Nombre</dt><dd>{{ $empresa->contacto_nombre }}</dd>
                    <dt>Teléfono</dt><dd>{{ $empresa->contacto_telefono }}</dd>
                    <dt>Correo</dt><dd>{{ $empresa->contacto_correo }}</dd>
                    <dt>Registrada</dt><dd>{{ $empresa->created_at?->format('d/m/Y') }}</dd>
                </dl>
            </x-tarjeta>
        </div>
        <div class="col-lg-8">
            <x-tarjeta titulo="Convenios" icono="bi-file-earmark-text" sin-relleno>
                @if ($convenios->isEmpty())
                    <x-estado-vacio icono="bi-file-earmark-text" texto="La empresa todavía no tiene convenios." />
                @else
                    <div class="table-responsive">
                        <table class="table tabla-siges align-middle mb-0">
                            <thead><tr><th scope="col">Número</th><th scope="col">Tipo</th><th scope="col">Vigencia</th><th scope="col">Estado</th><th scope="col" class="text-end">Acciones</th></tr></thead>
                            <tbody>
                            @foreach ($convenios as $convenio)
                                <tr>
                                    <td class="mono">{{ $convenio->numero }}</td>
                                    <td>{{ $convenio->tipo_nombre }}</td>
                                    <td class="text-nowrap">{{ $convenio->fecha_inicio->format('d/m/Y') }} al {{ $convenio->fecha_fin->format('d/m/Y') }}</td>
                                    <td><x-badge-estado :estado="$convenio->estado_efectivo" />@if ($convenio->vence_pronto) <x-badge-estado estado="VENCE_PRONTO" />@endif</td>
                                    <td class="text-end"><a class="btn btn-sm btn-outline-siges" href="{{ route('convenios.show', $convenio) }}" aria-label="Ver el convenio {{ $convenio->numero }}">Ver</a></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
                <div class="tabla-pie"><span>{{ $convenios->count() }} {{ $convenios->count() === 1 ? 'convenio' : 'convenios' }}</span></div>
            </x-tarjeta>
        </div>
    </div>
@endsection
