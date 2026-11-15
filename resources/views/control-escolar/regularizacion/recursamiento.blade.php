@extends('layouts.app')
@php
    $alumno = $resultado->inscripcion->alumno;
    $materia = $resultado->materia;
    // Hoy, dentro del periodo activo.
    $fechaSugerida = $periodo ? today()->max($periodo->fecha_inicio)->min($periodo->fecha_fin) : today();
@endphp
@section('titulo', 'Asignar recursamiento')
@section('contenido')
    <x-page-header titulo="Asignar recursamiento" :subtitulo="$alumno->persona->nombre_completo.' · '.$materia->asignatura->nombre"
        :volver="[route('regularizacion.index'), 'Volver a Regularización']" />
    @if (array_diff($errors->keys(), ['accion']))<x-errores-formulario />@endif

    <div class="row g-4">
        <div class="col-lg-5">
            <x-tarjeta titulo="Materia reprobada" icono="bi-journal-x">
                <dl class="dato-lista mb-0">
                    <dt>Alumno</dt><dd>{{ $alumno->persona->nombre_completo }} <span class="mono">{{ $alumno->matricula }}</span></dd>
                    <dt>Grupo actual</dt><dd>{{ $alumno->inscripcionActual?->grupo->nombre_completo ?? 'Sin inscripción en el periodo activo' }}</dd>
                    <dt>Materia</dt><dd>{{ $materia->asignatura->clave }} · {{ $materia->asignatura->nombre }}</dd>
                    <dt>Reprobó en</dt><dd>{{ $materia->grupo->nombre_completo }} · {{ $materia->grupo->periodo->clave }}</dd>
                    <dt>Resultado</dt><dd><x-badge-estado :estado="$resultado->oportunidad" /> <x-badge-estado :estado="$resultado->estado" /> <span class="mono">{{ $resultado->calificacion }}</span></dd>
                </dl>
            </x-tarjeta>
        </div>
        <div class="col-lg-7">
            <x-tarjeta titulo="Grupo destino" icono="bi-arrow-repeat">
                @if ($problemas)
                    <x-aviso tipo="aviso">No se puede asignar el recursamiento. {{ implode(' ', $problemas) }}</x-aviso>
                @elseif ($destinos->isEmpty())
                    <x-estado-vacio icono="bi-people" :texto="'Ningún otro grupo del periodo '.$periodo?->clave.' imparte '.$materia->asignatura->nombre.'.'" />
                @else
                    <form method="POST" action="{{ route('regularizacion.asignar', $resultado) }}" novalidate>
                        @csrf
                        <fieldset>
                            <legend class="form-label">Grupo donde recursa <span class="requerido" aria-hidden="true">*</span></legend>
                            <div class="roles-grid">
                                @foreach ($destinos as $destino)
                                    <label class="rol-opcion">
                                        <input type="radio" name="aca_grupo_asignatura_id" value="{{ $destino->id }}" @checked((int) old('aca_grupo_asignatura_id', $destinos->count() === 1 ? $destino->id : null) === $destino->id) required>
                                        <span>{{ $destino->grupo->nombre_completo }}<small class="d-block texto-tenue">{{ $destino->docente->nombre }}</small></span>
                                    </label>
                                @endforeach
                            </div>
                            @error('aca_grupo_asignatura_id')<div class="texto-peligro mt-2">{{ $message }}</div>@enderror
                        </fieldset>
                        <div class="row g-3 mt-1">
                            <x-campo name="fecha_inicio" label="Fecha de inicio" type="date" col="col-sm-6" :value="$fechaSugerida->toDateString()"
                                :min="$periodo->fecha_inicio->toDateString()" :max="$periodo->fecha_fin->toDateString()" :requerido="true" :ayuda="'Dentro del periodo '.$periodo->clave.'.'" />
                        </div>
                        <p class="texto-tenue mt-3 mb-0">El alumno aparecerá en la sección "Recursamiento" de la captura de calificaciones de ese grupo. Al cerrar los resultados de la materia se asienta su resultado de recursamiento.</p>
                        <div class="form-acciones mt-4 mb-0">
                            <a class="btn btn-outline-secondary" href="{{ route('regularizacion.index') }}">Cancelar</a>
                            <button type="submit" class="btn btn-siges">Asignar recursamiento</button>
                        </div>
                    </form>
                @endif
            </x-tarjeta>
        </div>
    </div>
@endsection
