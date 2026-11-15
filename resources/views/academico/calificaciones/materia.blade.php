@extends('layouts.app')
@php
    $inscrito = \App\Models\AcaInscripcion::INSCRITO;
    $puedeGuardar = $permisos['capturar'] || $permisos['corregir'];
    $paso = $reglas['decimales'] === 0 ? '1' : '0.'.str_repeat('0', $reglas['decimales'] - 1).'1';
    $suma = $materia->sumaDePesos($evaluaciones);
    // Alumnos del grupo y, en una sección aparte, los recursantes de otros grupos.
    $todas = $filas->concat($recursantes)->values();
@endphp
@section('titulo', 'Calificaciones de '.$materia->asignatura->nombre)
@section('contenido')
    <x-page-header :titulo="'Calificaciones · '.$materia->asignatura->nombre" :subtitulo="$materia->grupo->nombre_completo.' · '.$materia->docente->nombre"
        :volver="[route('calificaciones.index', ['grupo' => $materia->aca_grupo_escolar_id]), 'Volver a Calificaciones']">
        <x-slot:acciones>
            @if (auth()->user()->canAny(['evaluaciones.programar', 'calificaciones.corregir', 'resultados.cerrar']))
                <a href="{{ route('evaluaciones.edit', $materia) }}" class="btn btn-outline-siges"><i class="bi bi-card-checklist" aria-hidden="true"></i> Evaluaciones</a>
            @endif
            @if ($permisos['cerrarResultados'])
                <a href="{{ route('calificaciones.grupo', $materia->aca_grupo_escolar_id) }}" class="btn btn-outline-siges"><i class="bi bi-people" aria-hidden="true"></i> Resultados del grupo</a>
                <button type="button" class="btn btn-siges" data-bs-toggle="modal" data-bs-target="#modalCerrarResultados"
                    @if ($resumen['listo']) data-accion="{{ route('calificaciones.cerrar-materia', $materia) }}" @endif data-nombre="{{ $materia->nombre }}">
                    <i class="bi bi-check2-all" aria-hidden="true"></i> Cerrar resultados de la materia</button>
            @endif
        </x-slot:acciones>
    </x-page-header>
    @if (array_diff($errors->keys(), ['accion']))<x-errores-formulario />@endif

    @if ($evaluaciones->isEmpty())
        <x-aviso tipo="aviso">La materia no tiene evaluaciones programadas. <a href="{{ route('evaluaciones.edit', $materia) }}">Prográmalas</a> para capturar calificaciones.</x-aviso>
    @elseif ($suma !== \App\Models\AcaEvaluacion::PESO_TOTAL)
        <x-aviso tipo="aviso">Los pesos de las evaluaciones suman {{ \App\Models\TrxCalificacion::formatear($suma) }} % y deben sumar 100 %. <a href="{{ route('evaluaciones.edit', $materia) }}">Ajústalos</a>.</x-aviso>
    @endif
    @if (! $puedeGuardar)
        <x-aviso tipo="info">Consulta en solo lectura: solo el docente de la materia captura y Control Escolar corrige.</x-aviso>
    @elseif (! $permisos['capturar'] && $materia->grupo->periodo && ! $materia->grupo->periodo->activo)
        <x-aviso tipo="aviso">Periodo pasado: solo se corrigen calificaciones, con motivo. Si el alumno tiene un resultado ordinario cerrado, se recalcula; si ya tiene extraordinario o recursamiento, el resultado vigente no cambia y se te avisará.</x-aviso>
    @elseif (! $permisos['capturar'])
        <x-aviso tipo="info">Cualquier cambio que guardes es una corrección: te pediremos el motivo, que queda en la bitácora.</x-aviso>
    @endif

    <x-tarjeta sin-relleno etiqueta="Calificaciones de la materia">
        @if ($todas->isEmpty() || $evaluaciones->isEmpty())
            <x-estado-vacio icono="bi-clipboard-data" :texto="$todas->isEmpty() ? 'El grupo no tiene alumnos inscritos ni recursantes.' : 'Sin evaluaciones programadas.'" />
        @else
            <div class="table-responsive">
                <table class="table tabla-siges tabla-calificaciones align-middle mb-0" data-tabla-calificaciones data-formulario="formCalificaciones"
                    data-decimales="{{ $reglas['decimales'] }}" data-redondear="{{ $reglas['redondear'] ? '1' : '0' }}" data-minima="{{ $reglas['minima'] }}">
                    <thead>
                        <tr>
                            <th scope="col">Alumno</th>
                            @foreach ($evaluaciones as $evaluacion)
                                <th scope="col" class="text-center">
                                    {{ $evaluacion->tipo->nombre }} <span class="texto-tenue">{{ $evaluacion->peso_porcentaje }} %</span>
                                    <div class="d-flex justify-content-center align-items-center gap-1 mt-1">
                                        <x-badge-estado :estado="$evaluacion->cerrada ? 'CERRADA' : 'ABIERTA'" />
                                        @if ($permisos['cerrarEvaluacion'] && ! $evaluacion->cerrada)
                                            <button type="button" class="btn btn-sm btn-outline-siges" data-bs-toggle="modal" data-bs-target="#modalCerrarEvaluacion"
                                                data-accion="{{ route('evaluaciones.cerrar', $evaluacion) }}" data-nombre="{{ $evaluacion->tipo->nombre }}" aria-label="Cerrar {{ $evaluacion->tipo->nombre }}">Cerrar</button>
                                        @endif
                                    </div>
                                </th>
                            @endforeach
                            <th scope="col" class="text-center">Promedio ponderado</th>
                            <th scope="col">Resultado</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach ($todas as $numero => $inscripcion)
                        @php
                            $alumnoFila = $inscripcion->alumno;
                            $deLaFila = $calificaciones->get($inscripcion->id, collect());
                            $resultado = $resultados->get($inscripcion->id);
                            $esRecursante = $numero >= $filas->count();
                        @endphp
                        @if ($esRecursante && $numero === $filas->count())
                            <tr class="fila-seccion"><th scope="rowgroup" colspan="{{ $evaluaciones->count() + 3 }}"><i class="bi bi-arrow-repeat" aria-hidden="true"></i> Recursamiento · alumnos de otros grupos que recursan la materia</th></tr>
                        @endif
                        <tr data-fila-calificaciones>
                            <th scope="row">
                                <strong>{{ $alumnoFila->persona->nombre_completo }}</strong><small class="d-block mono">{{ $alumnoFila->matricula }}</small>
                                @if ($esRecursante)
                                    <small class="d-block texto-tenue">Reprobó en {{ $inscripcion->grupo->identificador }} · {{ $inscripcion->grupo->periodo->clave }}</small>
                                    @unless ($alumnoFila->estaActivo())<x-badge-estado :estado="$alumnoFila->estado->nombre" />@endunless
                                @elseif ($inscripcion->estado !== $inscrito)
                                    <x-badge-estado :estado="$inscripcion->estado" />
                                @endif
                            </th>
                            @foreach ($evaluaciones as $columna => $evaluacion)
                                @php
                                    $valor = $deLaFila->get($evaluacion->id)?->valor;
                                    $editable = isset($calificables[$inscripcion->id]) && ($evaluacion->cerrada ? $permisos['corregir'] : $puedeGuardar);
                                    $campo = "calificaciones.{$inscripcion->id}.{$evaluacion->id}";
                                @endphp
                                <td class="text-center" data-evaluacion data-peso="{{ $evaluacion->peso_porcentaje }}" @unless ($editable) data-valor="{{ $valor }}" @endunless>
                                    @if ($editable)
                                        <input type="number" form="formCalificaciones" name="calificaciones[{{ $inscripcion->id }}][{{ $evaluacion->id }}]"
                                            value="{{ old($campo, $valor) }}" min="0" max="10" step="{{ $paso }}" inputmode="decimal"
                                            @class(['form-control', 'text-end', 'is-invalid' => $errors->has($campo)])
                                            data-calificacion data-columna="{{ $columna }}" data-fila="{{ $numero }}" data-original="{{ $valor }}" data-cerrada="{{ $evaluacion->cerrada ? '1' : '0' }}"
                                            aria-label="{{ $evaluacion->tipo->nombre }} de {{ $alumnoFila->persona->nombre_completo }}">
                                    @else
                                        <span class="mono">{{ $valor ?? '—' }}</span>
                                    @endif
                                </td>
                            @endforeach
                            <td class="text-center mono" data-promedio>—</td>
                            <td>
                                @if ($resultado)
                                    <x-badge-estado :estado="$resultado->estado" :texto="\App\View\Components\BadgeEstado::ESTADOS[$resultado->estado][1].' · '.$resultado->calificacion" />
                                    @if ($resultado->oportunidad !== \App\Models\TrxResultadoAsignatura::ORDINARIO)<small class="d-block texto-tenue">{{ \App\Models\TrxResultadoAsignatura::OPORTUNIDADES[$resultado->oportunidad] ?? $resultado->oportunidad }}</small>@endif
                                @else
                                    <x-badge-estado estado="SIN_RESULTADO" />
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        <div class="tabla-pie">
            <span>{{ $filas->count() }} {{ $filas->count() === 1 ? 'alumno' : 'alumnos' }}@if ($recursantes->isNotEmpty()) · {{ $recursantes->count() }} {{ $recursantes->count() === 1 ? 'recursante' : 'recursantes' }}@endif</span>
            @if ($puedeGuardar && $todas->isNotEmpty() && $evaluaciones->isNotEmpty())<span>Tab o Enter: siguiente alumno · Mayús + Tab: anterior. El promedio es una vista previa; el sistema lo recalcula al cerrar.</span>@endif
        </div>
    </x-tarjeta>

    @if ($puedeGuardar && $todas->isNotEmpty() && $evaluaciones->isNotEmpty())
        <div class="form-acciones mt-4">
            <a class="btn btn-outline-secondary" href="{{ route('calificaciones.index', ['grupo' => $materia->aca_grupo_escolar_id]) }}">Cancelar</a>
            <button type="submit" form="formCalificaciones" class="btn btn-siges"><i class="bi bi-save" aria-hidden="true"></i> Guardar</button>
        </div>
    @endif
@endsection

@push('modales')
    @if ($puedeGuardar)
        {{-- Este es el formulario de la tabla (los campos usan form="formCalificaciones"). El modal solo se abre si hace falta el motivo. --}}
        <x-modal id="modalMotivo" titulo="Motivo de la corrección" icono="bi-pencil-square" metodo="PUT" :accion="route('calificaciones.guardar', $materia)"
            boton="Guardar corrección" formulario="formCalificaciones" :data-motivo-siempre="$permisos['capturar'] ? '0' : '1'">
            <p>Vas a modificar calificaciones {{ $permisos['capturar'] ? 'de una evaluación cerrada' : 'como corrección' }}. El cambio (antes → después) y el motivo quedan en la bitácora.</p>
            <div class="row g-3"><x-campo name="motivo" label="Motivo" col="col-12" maxlength="255" ayuda="Obligatorio para corregir." /></div>
        </x-modal>
    @endif
    @if ($permisos['cerrarEvaluacion'])
        <x-modal id="modalCerrarEvaluacion" titulo="Cerrar evaluación" icono="bi-lock" metodo="PATCH" boton="Cerrar evaluación" confirmar>
            <p>¿Cerrar <strong data-campo="nombre"></strong> de {{ $materia->nombre }}? Todos los alumnos inscritos deben tener calificación.</p>
            <p class="mb-0 texto-tenue">Guarda antes los cambios pendientes. Después solo Control Escolar podrá corregirla, con un motivo.</p>
        </x-modal>
    @endif
    @if ($permisos['cerrarResultados'])
        <x-modal id="modalCerrarResultados" titulo="Cerrar resultados de la materia" icono="bi-check2-all" boton="Cerrar resultados" confirmar>
            <p>Resultados de <strong data-campo="nombre"></strong> (ordinario para los inscritos; recursamiento para los recursantes):</p>
            <dl class="dato-lista">
                <dt>Inscritos</dt><dd>{{ $resumen['inscritos'] }}</dd>
                <dt>Recursantes</dt><dd>{{ $resumen['recursantes'] }}</dd>
                <dt>Aprobados</dt><dd>{{ $resumen['aprobados'] }}</dd>
                <dt>Reprobados</dt><dd>{{ $resumen['reprobados'] }}</dd>
                <dt>Sin todas sus calificaciones</dt><dd>{{ count($resumen['faltantes']) }}@if ($resumen['faltantes'])<small class="d-block texto-tenue">{{ implode(', ', $resumen['faltantes']) }}</small>@endif</dd>
                <dt>Ya cerrados</dt><dd>{{ $resumen['cerrados'] }}</dd>
            </dl>
            @if (! $resumen['listo'])
                <x-aviso tipo="aviso">Aún no se puede cerrar. {{ $resumen['total'] === 0 ? 'No tiene alumnos inscritos ni recursantes.' : implode(' ', $resumen['pendientes']) }}</x-aviso>
            @endif
            <h3 class="fs-6 mt-3">Reglas</h3>
            @include('academico.calificaciones._reglas')
            <p class="mt-3 mb-0 texto-tenue">Si se vuelve a cerrar, solo se actualizan los resultados que cambiaron.</p>
        </x-modal>
    @endif
@endpush
