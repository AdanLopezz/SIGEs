@extends('layouts.app')
@php
    $decimales = \App\Models\TrxCalificacion::decimales();
    $paso = $decimales === 0 ? '1' : '0.'.str_repeat('0', $decimales - 1).'1';
    $situaciones = \App\Models\TrxResultadoAsignatura::SITUACIONES + [\App\Http\Requests\ControlEscolar\FiltrarRegularizacionRequest::TODAS => 'Todas'];
    $veFichas = auth()->user()->can('alumnos.ver');
@endphp
@section('titulo', 'Regularización')
@section('contenido')
    <x-page-header titulo="Regularización" subtitulo="Materias reprobadas: extraordinario (uno por materia, sobre el ordinario) y recursamiento en un periodo posterior." />
    @if (array_diff($errors->keys(), ['accion']))<x-errores-formulario />@endif

    <x-filtros :accion="route('regularizacion.index')" :limpiar="route('regularizacion.index')">
        <x-seleccion name="periodo" label="Periodo" col="" :valor="$filtros['periodo'] ?? null" placeholder="Todos" :opciones="$periodos" />
        <x-seleccion name="grupo" label="Grupo" col="" :valor="$filtros['grupo'] ?? null" placeholder="Todos"
            :opciones="$grupos->mapWithKeys(fn ($grupo) => [$grupo->id => $grupo->nombre_completo.' · '.$grupo->periodo->clave])" />
        <x-seleccion name="asignatura" label="Materia" col="" :valor="$filtros['asignatura'] ?? null" placeholder="Todas"
            :opciones="$asignaturas->mapWithKeys(fn ($asignatura) => [$asignatura->id => $asignatura->clave.' · '.$asignatura->nombre])" />
        <x-seleccion name="oportunidad" label="Oportunidad" col="" :valor="$filtros['oportunidad'] ?? null" placeholder="Todas" :opciones="\App\Models\TrxResultadoAsignatura::OPORTUNIDADES" />
        <x-seleccion name="situacion" label="Situación" col="" :valor="$filtros['situacion']" :opciones="$situaciones" />
    </x-filtros>

    <x-tarjeta sin-relleno etiqueta="Materias reprobadas">
        @if ($resultados->isEmpty())
            <x-estado-vacio icono="bi-emoji-smile" texto="No hay materias reprobadas con estos filtros." />
        @else
            <div class="table-responsive">
                <table class="table tabla-siges align-middle mb-0">
                    <thead><tr><th scope="col">Alumno</th><th scope="col">Materia</th><th scope="col">Grupo</th><th scope="col">Resultado</th><th scope="col">Situación</th><th scope="col" class="text-end">Acciones</th></tr></thead>
                    <tbody>
                    @foreach ($resultados as $resultado)
                        @php
                            $alumnoFila = $resultado->inscripcion->alumno;
                            $adeudo = $adeudos->get($alumnoFila->id, 0);
                            $situacion = $resultado->situacion();
                            $recursamiento = $resultado->recursamientos->first();
                            $nombre = $alumnoFila->persona->nombre_completo.' · '.$resultado->materia->asignatura->nombre;
                            $puedeExtraordinario = $resultado->problemasExtraordinario() === [];
                            $puedeRecursar = $resultado->problemasRecursamiento() === [];
                            $puedeCorregir = $resultado->oportunidad === \App\Models\TrxResultadoAsignatura::EXTRAORDINARIO && ! $recursamiento;
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ $alumnoFila->persona->nombre_completo }}</strong><small class="d-block mono">{{ $alumnoFila->matricula }}</small>
                                @if ($adeudo > $maximo)<x-badge-estado tono="peligro" :texto="'Adeuda '.$adeudo.' (máximo '.$maximo.')'" />@endif
                            </td>
                            <td><strong>{{ $resultado->materia->asignatura->nombre }}</strong><small class="d-block mono">{{ $resultado->materia->asignatura->clave }}</small></td>
                            <td>{{ $resultado->materia->grupo->identificador }}<small class="d-block texto-tenue">{{ $resultado->materia->grupo->periodo->clave }}</small></td>
                            <td>
                                <x-badge-estado :estado="$resultado->oportunidad" /> <span class="mono">{{ $resultado->calificacion }}</span>
                                <small class="d-block texto-tenue">{{ $resultado->fecha_cierre->format('d/m/Y') }}</small>
                            </td>
                            <td>
                                <x-badge-estado :estado="$situacion" />
                                @if ($recursamiento)<small class="d-block texto-tenue">En {{ $recursamiento->materia->grupo->identificador }} desde {{ $recursamiento->fecha_inicio->format('d/m/Y') }}</small>@endif
                            </td>
                            <td><div class="d-flex justify-content-end gap-2">
                                @if ($puedeExtraordinario)
                                    <button type="button" class="btn btn-sm btn-outline-siges" data-bs-toggle="modal" data-bs-target="#modalExtraordinario"
                                        data-accion="{{ route('regularizacion.extraordinario', $resultado) }}" data-nombre="{{ $nombre }}">Extraordinario</button>
                                @elseif ($puedeRecursar)
                                    <a class="btn btn-sm btn-outline-siges" href="{{ route('regularizacion.recursamiento', $resultado) }}" aria-label="Recursamiento de {{ $nombre }}">Recursamiento</a>
                                @endif
                                <x-menu-acciones :etiqueta="$nombre">
                                    @if ($puedeExtraordinario && $puedeRecursar)
                                        <li><a class="dropdown-item" href="{{ route('regularizacion.recursamiento', $resultado) }}"><i class="bi bi-arrow-repeat" aria-hidden="true"></i> Asignar recursamiento</a></li>
                                    @endif
                                    @if ($puedeCorregir)
                                        <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#modalCorregirExtraordinario"
                                            data-accion="{{ route('regularizacion.corregir-extraordinario', $resultado) }}" data-nombre="{{ $nombre }}"><i class="bi bi-pencil" aria-hidden="true"></i> Corregir extraordinario</button></li>
                                    @endif
                                    @if ($situacion === \App\Models\TrxResultadoAsignatura::EN_RECURSAMIENTO)
                                        <li><a class="dropdown-item" href="{{ route('calificaciones.materia', $recursamiento->aca_grupo_asignatura_id) }}"><i class="bi bi-clipboard-data" aria-hidden="true"></i> Calificaciones del recursamiento</a></li>
                                        <li><button type="button" class="dropdown-item peligro" data-bs-toggle="modal" data-bs-target="#modalQuitarRecursamiento"
                                            data-accion="{{ route('regularizacion.quitar', $recursamiento) }}" data-nombre="{{ $nombre }}"><i class="bi bi-x-circle" aria-hidden="true"></i> Quitar recursamiento</button></li>
                                    @endif
                                    @if ($veFichas)
                                        <li><a class="dropdown-item" href="{{ route('alumnos.show', $alumnoFila) }}"><i class="bi bi-person-vcard" aria-hidden="true"></i> Ficha del alumno</a></li>
                                    @endif
                                </x-menu-acciones>
                            </div></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        <div class="tabla-pie"><x-por-pagina :paginador="$resultados" singular="materia reprobada" plural="materias reprobadas" /></div>
    </x-tarjeta>
@endsection

@push('modales')
    <x-modal id="modalExtraordinario" titulo="Registrar extraordinario" icono="bi-pencil-square" boton="Registrar" confirmar>
        <p><strong data-campo="nombre"></strong>. Es el único extraordinario de la materia: aprueba con {{ $minimaExtraordinario }} o más.</p>
        <div class="row g-3">
            <x-campo name="calificacion" label="Calificación" type="number" col="col-sm-6" min="0" max="10" :step="$paso" inputmode="decimal" :requerido="true" />
            <x-campo name="fecha" label="Fecha del examen" type="date" col="col-sm-6" :value="today()->toDateString()" :max="today()->toDateString()" :requerido="true" />
            <x-campo name="motivo" label="Motivo" col="col-12" minlength="10" maxlength="255" :requerido="true" ayuda="Por ejemplo, el acta del examen. Queda en la bitácora con la calificación ordinaria anterior." />
        </div>
    </x-modal>
    <x-modal id="modalCorregirExtraordinario" titulo="Corregir extraordinario" icono="bi-pencil" metodo="PUT" boton="Corregir" confirmar>
        <p><strong data-campo="nombre"></strong>. Corrige el mismo examen; no es otro intento.</p>
        <div class="row g-3">
            <x-campo name="calificacion" id="calificacionCorregir" label="Calificación" type="number" col="col-sm-6" min="0" max="10" :step="$paso" inputmode="decimal" :requerido="true" />
            <x-campo name="fecha" id="fechaCorregir" label="Fecha del examen" type="date" col="col-sm-6" :value="today()->toDateString()" :max="today()->toDateString()" :requerido="true" />
            <x-campo name="motivo" id="motivoCorregir" label="Motivo de la corrección" col="col-12" minlength="10" maxlength="255" :requerido="true" ayuda="Queda en la bitácora con el valor anterior." />
        </div>
    </x-modal>
    <x-modal id="modalQuitarRecursamiento" titulo="Quitar recursamiento" icono="bi-x-circle" metodo="DELETE" boton="Quitar" peligro confirmar>
        <p>¿Quitar el recursamiento de <strong data-campo="nombre"></strong>? Solo es posible si aún no tiene calificaciones en el grupo destino.</p>
        <div class="row g-3"><x-campo name="motivo" id="motivoQuitar" label="Motivo" col="col-12" minlength="10" maxlength="255" :requerido="true" ayuda="Queda en la bitácora." /></div>
    </x-modal>
@endpush
