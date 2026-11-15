@extends('layouts.app')
@section('titulo', 'Bajas')
@section('contenido')
    <x-page-header titulo="Bajas" subtitulo="Bajas temporales y definitivas registradas. Se registran desde la ficha del alumno." />

    <x-filtros :accion="route('bajas.index')" :limpiar="route('bajas.index')">
        <x-seleccion name="periodo" label="Periodo" col="" :opciones="$periodos" :valor="$filtros['periodo'] ?? ''" />
        <x-seleccion name="tipo" label="Tipo" col="" :opciones="$tipos" :valor="$filtros['tipo'] ?? ''" placeholder="Todos" />
        <x-campo name="desde" label="Desde" type="date" col="" :value="$filtros['desde'] ?? ''" />
        <x-campo name="hasta" label="Hasta" type="date" col="" :value="$filtros['hasta'] ?? ''" />
    </x-filtros>

    <x-tarjeta sin-relleno etiqueta="Lista de bajas">
        @if ($bajas->isEmpty())
            <x-estado-vacio icono="bi-person-dash" texto="No hay bajas con los filtros seleccionados." />
        @else
            <div class="table-responsive">
                <table class="table tabla-siges align-middle mb-0">
                    <thead><tr><th scope="col">Fecha</th><th scope="col">Alumno</th><th scope="col">Grupo</th><th scope="col">Tipo</th><th scope="col">Motivo</th><th scope="col">Registró</th></tr></thead>
                    <tbody>
                    @foreach ($bajas as $baja)
                        @php $alumnoBaja = $baja->inscripcion->alumno; @endphp
                        <tr>
                            <td class="text-nowrap">{{ $baja->fecha->format('d/m/Y') }}</td>
                            <td><a href="{{ route('alumnos.show', $alumnoBaja) }}"><strong>{{ $alumnoBaja->persona->nombre_completo }}</strong></a><small class="d-block mono">{{ $alumnoBaja->matricula }}</small></td>
                            <td>{{ $baja->inscripcion->grupo->nombre_completo }}<small class="d-block texto-tenue">{{ $baja->inscripcion->grupo->periodo->clave }}</small></td>
                            <td><x-badge-estado :estado="$baja->tipo" /></td>
                            <td>{{ $baja->motivo }}</td>
                            <td>{{ $baja->registradoPor?->name ?? '—' }}<small class="d-block texto-tenue">{{ $baja->created_at->format('d/m/Y H:i') }}</small></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        <div class="tabla-pie"><x-por-pagina :paginador="$bajas" singular="baja" plural="bajas" /></div>
    </x-tarjeta>
@endsection
