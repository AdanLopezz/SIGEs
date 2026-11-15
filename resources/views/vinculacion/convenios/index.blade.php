@extends('layouts.app')
@use('App\Models\OrgConvenio')
@section('titulo', 'Convenios')
@section('contenido')
    <x-page-header titulo="Convenios" subtitulo="Convenios con empresas: formación dual, servicio social, prácticas profesionales y generales.">
        <x-slot:acciones>
            @can('dual.administrar')<a href="{{ route('convenios.create') }}" class="btn btn-siges"><i class="bi bi-plus-lg" aria-hidden="true"></i> Nuevo convenio</a>@endcan
        </x-slot:acciones>
    </x-page-header>

    @if ($porVencer > 0)
        <x-aviso tipo="aviso">
            {{ $porVencer }} {{ $porVencer === 1 ? 'convenio vence' : 'convenios vencen' }} en los próximos {{ OrgConvenio::DIAS_AVISO }} días.
            <a href="{{ route('convenios.index', ['vence_pronto' => 1]) }}">Ver cuáles</a>.
        </x-aviso>
    @endif

    <x-filtros :accion="route('convenios.index')" :limpiar="route('convenios.index')">
        <x-campo name="buscar" label="Buscar" col="" :value="$filtros['buscar'] ?? ''" maxlength="100" placeholder="Número o empresa" />
        <x-seleccion name="tipo" label="Tipo" col="" :opciones="OrgConvenio::TIPOS" :valor="$filtros['tipo'] ?? ''" placeholder="Todos" />
        <x-seleccion name="estado" label="Estado" col="" :opciones="OrgConvenio::ESTADOS" :valor="$filtros['estado'] ?? ''" placeholder="Todos" />
        <x-seleccion name="empresa" label="Empresa" col="" :opciones="$empresas" :valor="$filtros['empresa'] ?? ''" placeholder="Todas" />
        <x-seleccion name="vence_pronto" label="Vencimiento" col="" :opciones="['1' => 'Vence en '.OrgConvenio::DIAS_AVISO.' días']" :valor="$filtros['vence_pronto'] ?? ''" placeholder="Todos" />
    </x-filtros>

    <x-tarjeta sin-relleno etiqueta="Lista de convenios">
        @if ($convenios->isEmpty())
            <x-estado-vacio icono="bi-file-earmark-text" texto="No hay convenios con los filtros seleccionados." />
        @else
            <div class="table-responsive">
                <table class="table tabla-siges align-middle mb-0">
                    <thead><tr><th scope="col">Número</th><th scope="col">Empresa</th><th scope="col">Tipo</th><th scope="col">Vigencia</th><th scope="col">Estado</th><th scope="col" class="text-end">Acciones</th></tr></thead>
                    <tbody>
                    @foreach ($convenios as $convenio)
                        <tr>
                            <td class="mono">{{ $convenio->numero }}</td>
                            <td>{{ $convenio->empresa->nombre }}<small class="d-block mono">{{ $convenio->empresa->rfc }}</small></td>
                            <td>{{ $convenio->tipo_nombre }}</td>
                            <td class="text-nowrap">{{ $convenio->fecha_inicio->format('d/m/Y') }} al {{ $convenio->fecha_fin->format('d/m/Y') }}</td>
                            <td><x-badge-estado :estado="$convenio->estado_efectivo" />@if ($convenio->vence_pronto)<span class="d-block mt-1"><x-badge-estado estado="VENCE_PRONTO" /></span>@endif</td>
                            <td><div class="d-flex justify-content-end gap-2">
                                <a class="btn btn-sm btn-outline-siges" href="{{ route('convenios.show', $convenio) }}" aria-label="Ver el convenio {{ $convenio->numero }}">Ver</a>
                                @can('dual.administrar')
                                    @if ($convenio->estaVigente())
                                        <x-menu-acciones :etiqueta="'el convenio '.$convenio->numero">
                                            <li><a class="dropdown-item" href="{{ route('convenios.edit', $convenio) }}"><i class="bi bi-pencil" aria-hidden="true"></i> Editar</a></li>
                                        </x-menu-acciones>
                                    @endif
                                @endcan
                            </div></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        <div class="tabla-pie"><x-por-pagina :paginador="$convenios" singular="convenio" plural="convenios" /></div>
    </x-tarjeta>
@endsection
