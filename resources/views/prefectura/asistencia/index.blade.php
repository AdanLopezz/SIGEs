@extends('layouts.app')
@php
    // Justificar y cerrar el día solo en el periodo activo; un periodo pasado se consulta.
    $puedeJustificar = $operable && auth()->user()->canAny(\App\Http\Requests\Prefectura\PrefecturaRequest::PERMISOS_JUSTIFICAR);
    $etiquetas = \App\View\Components\BadgeEstado::ESTADOS;
@endphp
@section('titulo', 'Asistencia')
@section('contenido')
    <x-page-header titulo="Asistencia diaria" subtitulo="Entrada y salida por la puerta. La primera entrada del día define el estado.">
        <x-slot:acciones>
            @if ($operable && auth()->user()->can('acceso.registrar'))
                <form method="POST" action="{{ route('asistencia.cerrar') }}">
                    @csrf
                    <input type="hidden" name="fecha" value="{{ $fecha->toDateString() }}">
                    <button type="submit" class="btn btn-outline-siges" @disabled(! $esDiaHabil) title="Registra FALTA a quien no tenga registro ese día"><i class="bi bi-calendar-x" aria-hidden="true"></i> Cerrar día {{ $fecha->format('d/m/Y') }}</button>
                </form>
            @endif
        </x-slot:acciones>
    </x-page-header>
    <x-errores-formulario />

    <x-filtros :accion="route('asistencia.index')" :limpiar="route('asistencia.index')" boton="Ver" texto-limpiar="Hoy">
        <x-campo name="fecha" label="Fecha" type="date" col="" :value="$fecha->toDateString()" :min="$fechaMinima" :max="$fechaMaxima" />
        <x-seleccion name="grupo" label="Grupo" col="" :valor="$grupo?->id" :placeholder="$grupos->isEmpty() ? 'Sin grupos visibles' : null"
            :opciones="$grupos->mapWithKeys(fn ($opcion) => [$opcion->id => $opcion->nombre_completo.' · '.$opcion->semestre.'.º semestre'])" />
    </x-filtros>

    @unless ($esDiaHabil || ! $operable)
        <x-aviso tipo="info">El {{ $fecha->format('d/m/Y') }} no es día hábil (fin de semana, día inhábil o fuera del periodo activo): no cuenta asistencia.</x-aviso>
    @endunless

    @if ($grupo)
        <div class="d-flex flex-wrap gap-2 mb-3" aria-label="Totales del día">
            @foreach ($totales as $clave => $total)<x-badge-estado :estado="$clave" :texto="$etiquetas[$clave][1].': '.$total" />@endforeach
        </div>
    @endif

    <x-tarjeta sin-relleno etiqueta="Asistencia del grupo">
        @if ($filas->isEmpty())
            <x-estado-vacio icono="bi-calendar-check" :texto="$grupo ? 'El grupo no tiene alumnos inscritos.' : 'No tienes grupos visibles en el periodo elegido.'" />
        @else
            <div class="table-responsive">
                <table class="table tabla-siges align-middle mb-0">
                    <thead><tr><th scope="col">Alumno</th><th scope="col">Estado</th><th scope="col">Entrada</th><th scope="col">Salida</th>@if ($puedeJustificar)<th scope="col" class="text-end">Acciones</th>@endif</tr></thead>
                    <tbody>
                    @foreach ($filas as $fila)
                        @php $asistencia = $fila['asistencia']; $alumnoFila = $fila['inscripcion']->alumno; @endphp
                        <tr>
                            <td><strong>{{ $alumnoFila->persona->nombre_completo }}</strong><small class="d-block mono">{{ $alumnoFila->matricula }}</small></td>
                            <td><x-badge-estado :estado="$asistencia?->estado ?? 'SIN_REGISTRO'" /></td>
                            <td class="mono">{{ $fila['entrada']?->format('H:i') ?? '—' }}</td>
                            <td class="mono">{{ $fila['salida']?->format('H:i') ?? '—' }}</td>
                            @if ($puedeJustificar)
                                <td class="text-end">
                                    @if ($asistencia && in_array($asistencia->estado, [\App\Models\TrxAsistenciaDiaria::FALTA, \App\Models\TrxAsistenciaDiaria::RETARDO], true))
                                        <button type="button" class="btn btn-sm btn-outline-siges" data-bs-toggle="modal" data-bs-target="#modalJustificar"
                                            data-accion="{{ route('asistencia.justificar', $asistencia) }}" data-nombre="{{ $alumnoFila->persona->nombre_completo }}">Justificar</button>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        @if ($grupo)<div class="tabla-pie"><span>{{ $filas->count() }} {{ $filas->count() === 1 ? 'alumno' : 'alumnos' }} · {{ $grupo->nombre_completo }}</span></div>@endif
    </x-tarjeta>
@endsection

@push('modales')
    @if ($puedeJustificar)
        <x-modal id="modalJustificar" titulo="Justificar" icono="bi-check2-square" metodo="PATCH" boton="Justificar" confirmar>
            <p>La asistencia de <strong data-campo="nombre"></strong> del {{ $fecha->format('d/m/Y') }} quedará como Justificada.</p>
            <div class="row g-3"><x-campo name="motivo" label="Motivo" col="col-12" maxlength="255" :requerido="true" ayuda="Queda registrado en la bitácora." /></div>
        </x-modal>
    @endif
@endpush
