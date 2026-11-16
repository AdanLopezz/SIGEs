@extends('layouts.app')
@section('titulo', 'Egreso')
@section('contenido')
    <x-page-header titulo="Egreso" subtitulo="Revisa candidatos del último semestre y consulta los egresos registrados.">
        <x-slot:acciones>
            <a class="btn btn-outline-siges" href="{{ route('egreso.csv', $filtros) }}"><i class="bi bi-download" aria-hidden="true"></i> Descargar CSV</a>
            <a class="btn btn-outline-siges" href="{{ route('egreso.imprimir', $filtros) }}"><i class="bi bi-printer" aria-hidden="true"></i> Imprimir</a>
        </x-slot:acciones>
    </x-page-header>
    <x-errores-formulario />
    <x-filtros :accion="route('egreso.index')" :limpiar="route('egreso.index')">
        <x-seleccion name="lista" label="Mostrar" col="" :opciones="['candidatos' => 'Candidatos', 'egresados' => 'Egresados']" :valor="$filtros['lista']" />
        <x-campo name="buscar" label="Alumno o matrícula" col="" :value="$filtros['buscar'] ?? ''" />
        <x-seleccion name="carrera" label="Carrera" col="" :opciones="$carreras" :valor="$filtros['carrera'] ?? ''" placeholder="Todas" />
        <x-seleccion name="periodo" label="Periodo de último semestre" col="" :opciones="$periodos" :valor="$filtros['periodo'] ?? ''" placeholder="Todos" />
        <x-seleccion name="grupo" label="Grupo" col="" :opciones="$grupos" :valor="$filtros['grupo'] ?? ''" placeholder="Todos" />
    </x-filtros>
    <x-tarjeta sin-relleno etiqueta="Listado de egreso">
        @if ($alumnos->isEmpty())
            <x-estado-vacio icono="bi-mortarboard" texto="No hay alumnos con los filtros seleccionados." />
        @else
            <div class="table-responsive">
                <table class="table tabla-siges align-middle mb-0">
                    <thead><tr><th scope="col">Alumno</th><th scope="col">Carrera</th><th scope="col">Situación</th><th scope="col">Detalle</th><th scope="col" class="text-end">Acciones</th></tr></thead>
                    <tbody>
                    @foreach ($alumnos as $alumno)
                        <tr>
                            <td><strong>{{ $alumno->persona->nombre_completo }}</strong><small class="d-block mono">{{ $alumno->matricula }}</small></td>
                            <td>{{ $alumno->carrera->nombre }}<small class="d-block texto-tenue">Generación {{ $alumno->generacion }}</small></td>
                            <td>
                                @if ($alumno->egreso)
                                    <x-badge-estado estado="Egresado" />
                                @elseif ($revisiones[$alumno->id]['pendientes']->isNotEmpty())
                                    <x-badge-estado tono="aviso" texto="Pendientes académicos" />
                                @else
                                    <x-badge-estado tono="info" texto="Por confirmar historial" />
                                @endif
                            </td>
                            <td>
                                @if ($alumno->egreso)
                                    {{ $alumno->egreso->fecha_egreso->format('d/m/Y') }}<small class="d-block">Promedio {{ $alumno->egreso->promedio_general }}</small>
                                @else
                                    {{ $revisiones[$alumno->id]['pendientes']->count() }} pendientes académicos
                                @endif
                            </td>
                            <td class="text-end"><a class="btn btn-sm btn-outline-siges" href="{{ route('egreso.show', $alumno) }}" aria-label="Revisar egreso de {{ $alumno->persona->nombre_completo }}">Revisar</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        <div class="tabla-pie"><x-por-pagina :paginador="$alumnos" singular="alumno" plural="alumnos" /></div>
    </x-tarjeta>
@endsection
