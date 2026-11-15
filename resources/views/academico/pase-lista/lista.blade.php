@extends('layouts.app')
@use('App\Models\CatTipoAsistencia')
@use('App\Models\TrxAsistenciaClase')
@use('App\Models\TrxResultadoAsignatura')
@php
    $tipos = CatTipoAsistencia::porCodigo();
    $fecha = $dia->toDateString();
    $volver = $esDocente && auth()->user()->can('asistencia_clase.registrar')
        ? [route('pase-lista.index', ['fecha' => $fecha]), 'Volver a Mis clases']
        : [route('asistencia-materia.show', [$materia, 'mes' => $dia->format('Y-m')]), 'Volver a la asistencia de la materia'];
    $conteo = $filas->countBy(fn ($fila) => old('marcas.'.$fila->inscripcion->id, $fila->codigo));
@endphp
@section('titulo', 'Lista · '.$materia->asignatura->nombre)
@section('contenido')
    <x-page-header :titulo="'Lista de '.$materia->asignatura->nombre" :volver="$volver">
        <x-slot:subtitulo>Grupo <strong>{{ $materia->grupo->identificador }}</strong> · {{ ucfirst($dia->copy()->locale('es')->translatedFormat('l j \d\e F Y')) }}@if ($clasesDelDia->isNotEmpty()) · {{ $clasesDelDia->map(fn ($clase) => $clase->inicio.' – '.$clase->fin.' ('.$clase->espacio->nombre.')')->implode(', ') }}@endif · Docente: {{ $materia->docente?->nombre }}</x-slot:subtitulo>
        <x-slot:acciones><a href="{{ route('asistencia-materia.show', [$materia, 'mes' => $dia->format('Y-m')]) }}" class="btn btn-outline-siges"><i class="bi bi-table" aria-hidden="true"></i> Asistencia de la materia</a></x-slot:acciones>
    </x-page-header>

    @if ($problema)
        <x-aviso tipo="info">{{ $problema }}</x-aviso>
    @elseif (! $modo)
        <x-aviso tipo="info">El plazo para pasar o corregir esta lista fue de {{ $diasParaCorregir }} {{ $diasParaCorregir === 1 ? 'día' : 'días' }} después de la clase. Ahora solo la corrige Control Escolar, con motivo.</x-aviso>
    @elseif ($modo === TrxAsistenciaClase::CORRECCION)
        <x-aviso tipo="aviso">Estás corrigiendo como Control Escolar: el motivo es obligatorio y queda en la bitácora.</x-aviso>
    @endif

    <x-filtros :accion="route('pase-lista.lista', $materia)" :limpiar="route('pase-lista.lista', $materia)">
        <x-campo name="fecha" label="Fecha" type="date" col="" :value="$fecha" :max="today()->toDateString()" />
    </x-filtros>

    <x-errores-formulario />

    <form method="POST" action="{{ route('pase-lista.guardar', $materia) }}">
        @csrf
        @method('PUT')
        <input type="hidden" name="fecha" value="{{ $fecha }}">
        <x-tarjeta titulo="Alumnos" icono="bi-people" sin-relleno>
            <x-slot:acciones>@if ($editable)<span class="texto-tenue">Todos inician en A; marca F, R o J.</span>@endif</x-slot:acciones>
            @if ($filas->isEmpty())
                <x-estado-vacio icono="bi-people" texto="No hay alumnos inscritos en esta materia en esa fecha." />
            @else
                <div role="list">
                @foreach ($filas as $fila)
                    @php
                        $id = $fila->inscripcion->id;
                        $actual = old('marcas.'.$id, $fila->codigo);
                        $nombre = $fila->alumno->persona->nombre_completo;
                    @endphp
                    <div class="pase-lista-fila" role="listitem">
                        <div>
                            <strong>{{ $nombre }}</strong>
                            <small class="d-block mono">{{ $fila->alumno->matricula }}</small>
                            <div class="d-flex flex-wrap gap-1 mt-1">
                                @if ($fila->oportunidad === TrxResultadoAsignatura::RECURSAMIENTO)<x-badge-estado estado="RECURSAMIENTO" />@endif
                                @if ($fila->cubierta)<x-badge-estado tono="info" icono="bi-file-earmark-medical" texto="Incidencia que justifica la falta" />@endif
                                @unless ($fila->editable)<x-badge-estado tono="neutro" texto="Ya no está en la lista" />@endunless
                            </div>
                        </div>
                        @if ($editable && $fila->editable)
                            <div class="pase-lista-marcas" role="radiogroup" aria-label="Asistencia de {{ $nombre }}">
                                @foreach ($tipos as $codigo => $tipo)
                                    @php $bloqueada = $fila->cubierta && $codigo === CatTipoAsistencia::FALTA; @endphp
                                    <input type="radio" class="btn-check" name="marcas[{{ $id }}]" id="marca-{{ $id }}-{{ $codigo }}" value="{{ $codigo }}" autocomplete="off" @checked($actual === $codigo) @disabled($bloqueada)>
                                    <label class="btn btn-sm btn-outline-siges marca-{{ $codigo }}" for="marca-{{ $id }}-{{ $codigo }}" title="{{ $bloqueada ? 'No puede ser falta: tiene una incidencia que la justifica' : $tipo->nombre }}">
                                        {{ $codigo }}<span class="visually-hidden"> · {{ $tipo->nombre }}</span>
                                    </label>
                                @endforeach
                            </div>
                        @else
                            @if ($fila->registro)
                                <x-badge-estado :estado="CatTipoAsistencia::ESTADOS[$fila->codigo]" />
                            @else
                                <span class="texto-tenue">Sin registro</span>
                            @endif
                        @endif
                    </div>
                @endforeach
                </div>
            @endif
            <div class="tabla-pie">
                <span>{{ $filas->count() }} {{ $filas->count() === 1 ? 'alumno' : 'alumnos' }} · {{ $guardada ? 'Lista guardada' : 'Lista sin guardar' }}</span>
                <span class="d-flex flex-wrap gap-1">
                    @foreach ($tipos as $codigo => $tipo)
                        <x-badge-estado :estado="CatTipoAsistencia::ESTADOS[$codigo]" :texto="$tipo->nombre.' ('.$codigo.'): '.($conteo[$codigo] ?? 0)" />
                    @endforeach
                </span>
            </div>
        </x-tarjeta>

        @if ($editable && $filas->contains('editable', true))
            <x-tarjeta>
                @if ($pideMotivo)
                    <div class="row g-3">
                        <x-campo name="motivo" label="Motivo del cambio" col="col-12" maxlength="255"
                            :requerido="$modo === TrxAsistenciaClase::CORRECCION"
                            :ayuda="$modo === TrxAsistenciaClase::CORRECCION ? 'Obligatorio en una corrección de Control Escolar.' : 'Obligatorio si cambias una marca ya guardada de otro día.'" />
                    </div>
                @endif
                <div class="form-acciones">
                    <a href="{{ $volver[0] }}" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-siges"><i class="bi bi-check2-square" aria-hidden="true"></i> Guardar lista</button>
                </div>
            </x-tarjeta>
        @endif
    </form>
@endsection
