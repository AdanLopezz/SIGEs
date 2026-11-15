@extends('layouts.app')
@use('App\Models\TrxAsistenciaClase')
@php
    $ruta = fn ($mes) => route('asistencia-materia.show', [$materia, 'mes' => $mes->format('Y-m')]);
@endphp
@section('titulo', 'Asistencia · '.$materia->asignatura->nombre)
@section('contenido')
    <x-page-header :titulo="'Asistencia de '.$materia->asignatura->nombre" :volver="[route('asistencia-materia.index'), 'Volver a Asistencia por materia']">
        <x-slot:subtitulo>Grupo <strong>{{ $materia->grupo->nombre_completo }}</strong> · Docente: {{ $materia->docente?->nombre }} · {{ $materia->grupo->periodo->nombre_visible }} <span class="mono">{{ $materia->grupo->periodo->clave }}</span></x-slot:subtitulo>
        <x-slot:acciones>
            @if ($puedePasarLista)
                <a href="{{ route('pase-lista.lista', $materia) }}" class="btn btn-siges"><i class="bi bi-list-check" aria-hidden="true"></i> Pasar lista</a>
            @endif
        </x-slot:acciones>
    </x-page-header>

    <x-tarjeta sin-relleno :etiqueta="'Asistencia de '.$mes->copy()->locale('es')->translatedFormat('F Y')">
        <div class="tabla-pie">
            @if ($anterior)<a href="{{ $ruta($anterior) }}" class="btn btn-sm btn-outline-siges" aria-label="Mes anterior"><i class="bi bi-chevron-left" aria-hidden="true"></i></a>@else<span></span>@endif
            <strong>{{ ucfirst($mes->copy()->locale('es')->translatedFormat('F Y')) }}</strong>
            @if ($siguiente)<a href="{{ $ruta($siguiente) }}" class="btn btn-sm btn-outline-siges" aria-label="Mes siguiente"><i class="bi bi-chevron-right" aria-hidden="true"></i></a>@else<span></span>@endif
        </div>
        @if ($alumnos->isEmpty())
            <x-estado-vacio icono="bi-people" texto="La materia no tiene alumnos." />
        @else
            <div class="table-responsive">
                <table class="table tabla-siges asistencia-materia-tabla align-middle mb-0">
                    <thead><tr>
                        <th scope="col">Alumno</th>
                        @foreach ($fechas as $fecha)
                            @php $dia = \Illuminate\Support\Carbon::parse($fecha); @endphp
                            <th scope="col" class="text-center">
                                @if ($puedePasarLista)<a href="{{ route('pase-lista.lista', [$materia, 'fecha' => $fecha]) }}" aria-label="Lista del {{ $dia->format('d/m/Y') }}">{{ $dia->format('d') }}</a>@else{{ $dia->format('d') }}@endif
                            </th>
                        @endforeach
                        <th scope="col">Faltas</th><th scope="col">Retardos</th><th scope="col">Justificadas</th><th scope="col">Clases</th><th scope="col">% del periodo</th>
                    </tr></thead>
                    <tbody>
                    @foreach ($alumnos as $inscripcion)
                        @php
                            $total = $totales->get($inscripcion->id);
                            $porcentaje = $total?->porcentaje;
                            $bajo = TrxAsistenciaClase::esBajoMinimo($porcentaje);
                        @endphp
                        <tr @class(['bajo-minimo' => $bajo])>
                            <td>
                                <strong>{{ $inscripcion->alumno->persona->nombre_completo }}</strong>
                                <small class="d-block mono">{{ $inscripcion->alumno->matricula }}</small>
                                @if (in_array($inscripcion->id, $recursantes, true))<x-badge-estado estado="RECURSAMIENTO" />@endif
                            </td>
                            @foreach ($fechas as $fecha)
                                @php $codigo = $celdas->get($inscripcion->id)?->get($fecha); @endphp
                                <td class="asistencia-celda {{ $codigo ? 'marca-'.$codigo : '' }}">{{ $codigo ?? '·' }}</td>
                            @endforeach
                            <td>{{ $total?->faltas ?? 0 }}</td>
                            <td>{{ $total?->retardos ?? 0 }}</td>
                            <td>{{ $total?->justificadas ?? 0 }}</td>
                            <td>{{ $total?->clases ?? 0 }}</td>
                            <td>
                                <strong>{{ TrxAsistenciaClase::formatear($porcentaje) }}</strong>
                                @if ($bajo)<x-badge-estado estado="BAJO_MINIMO" class="d-block mt-1" />@endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        <div class="tabla-pie">
            <x-por-pagina :paginador="$alumnos" singular="alumno" plural="alumnos" />
            <span>{{ $fechas->count() }} {{ $fechas->count() === 1 ? 'lista' : 'listas' }} en el mes · A asistencia, F falta, R retardo, J justificada · mínimo {{ TrxAsistenciaClase::formatear($minimo) }} (solo aviso)</span>
        </div>
    </x-tarjeta>
@endsection
