@extends('layouts.app')
@php
    $puedeProgramar = auth()->user()->can('programar', $materia);
    $puedeCerrar = auth()->user()->can('cerrarEvaluacion', $materia);
@endphp
@section('titulo', 'Evaluaciones de '.$materia->asignatura->nombre)
@section('contenido')
    <x-page-header :titulo="'Evaluaciones · '.$materia->asignatura->nombre" :subtitulo="$materia->grupo->nombre_completo.' · '.$materia->docente->nombre"
        :volver="[route('evaluaciones.index', ['grupo' => $materia->aca_grupo_escolar_id]), 'Volver a Evaluaciones']">
        <x-slot:acciones>
            @if ($puedeProgramar)
                <button type="button" class="btn btn-outline-siges" data-bs-toggle="modal" data-bs-target="#modalEsquema"
                    data-accion="{{ route('evaluaciones.estandar', $materia) }}" data-nombre="{{ $materia->nombre }}"><i class="bi bi-magic" aria-hidden="true"></i> Aplicar esquema estándar</button>
            @endif
            @if (auth()->user()->canAny(['calificaciones.capturar', 'calificaciones.corregir', 'resultados.cerrar']))
                <a href="{{ route('calificaciones.materia', $materia) }}" class="btn btn-outline-siges"><i class="bi bi-clipboard-data" aria-hidden="true"></i> Calificaciones</a>
            @endif
        </x-slot:acciones>
    </x-page-header>

    <div class="row g-4">
        <div class="col-lg-7">
            <x-tarjeta titulo="Pesos por evaluación" icono="bi-percent">
                @if (array_diff($errors->keys(), ['accion']))<x-errores-formulario />@endif
                @unless ($puedeProgramar)
                    <x-aviso tipo="info">Solo el docente de la materia programa sus evaluaciones.</x-aviso>
                @endunless
                <form method="POST" action="{{ route('evaluaciones.update', $materia) }}" novalidate data-pesos-evaluacion>
                    @csrf
                    @method('PUT')
                    <div class="row g-3">
                        @foreach ($tipos as $tipo)
                            @php
                                $evaluacion = $evaluaciones->get($tipo->id);
                                $bloqueada = $evaluacion && ($evaluacion->cerrada || $evaluacion->calificaciones_count > 0);
                                $campo = "pesos.{$tipo->id}";
                                $id = "peso{$tipo->id}";
                                $ayuda = match (true) {
                                    $evaluacion?->cerrada => 'Cerrada: su peso no cambia.',
                                    $bloqueada => 'Ya tiene calificaciones: su peso no cambia.',
                                    ! $tipo->aplica_promedio => 'No forma parte del esquema estándar.',
                                    default => 'Vacío = sin esta evaluación.',
                                };
                            @endphp
                            <div class="col-sm-6 col-xl-4">
                                <label for="{{ $id }}" class="form-label">@if ($bloqueada)<i class="bi bi-lock" aria-hidden="true"></i> @endif{{ $tipo->nombre }} (%)</label>
                                <input type="number" id="{{ $id }}" name="pesos[{{ $tipo->id }}]" min="0.01" max="100" step="0.01" inputmode="decimal" data-peso
                                    value="{{ old($campo, $evaluacion?->peso_porcentaje) }}" @class(['form-control', 'is-invalid' => $errors->has($campo)])
                                    @readonly($bloqueada) @disabled(! $puedeProgramar) aria-describedby="{{ $id }}-ayuda">
                                @error($campo)<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <div class="form-text" id="{{ $id }}-ayuda">{{ $ayuda }}</div>
                            </div>
                        @endforeach
                    </div>
                    <p class="mt-3 mb-0">Total: <strong data-suma-pesos>{{ \App\Models\TrxCalificacion::formatear($materia->sumaDePesos($evaluaciones->values())) }}</strong> % <span class="texto-tenue">(debe sumar exactamente 100 %)</span></p>
                    @if ($puedeProgramar)
                        <div class="form-acciones mt-4 mb-0">
                            <a class="btn btn-outline-secondary" href="{{ route('evaluaciones.index', ['grupo' => $materia->aca_grupo_escolar_id]) }}">Cancelar</a>
                            <button type="submit" class="btn btn-siges">Guardar evaluaciones</button>
                        </div>
                    @endif
                </form>
            </x-tarjeta>
        </div>

        <div class="col-lg-5">
            <x-tarjeta titulo="Estado" icono="bi-list-check" class="mb-4">
                @if ($evaluaciones->isEmpty())
                    <x-estado-vacio icono="bi-card-checklist" texto="Sin evaluaciones programadas." />
                @else
                    @foreach ($evaluaciones->values() as $evaluacion)
                        <div class="d-flex align-items-center justify-content-between gap-2 py-2 @unless ($loop->first) border-top @endunless">
                            <div>
                                <strong>{{ $evaluacion->tipo->nombre }}</strong> · {{ $evaluacion->peso_porcentaje }} %
                                <small class="d-block texto-tenue">{{ $evaluacion->calificaciones_count }} {{ $evaluacion->calificaciones_count === 1 ? 'calificación' : 'calificaciones' }}</small>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <x-badge-estado :estado="$evaluacion->cerrada ? 'CERRADA' : 'ABIERTA'" />
                                @if ($puedeCerrar && ! $evaluacion->cerrada)
                                    <button type="button" class="btn btn-sm btn-outline-siges" data-bs-toggle="modal" data-bs-target="#modalCerrarEvaluacion"
                                        data-accion="{{ route('evaluaciones.cerrar', $evaluacion) }}" data-nombre="{{ $evaluacion->tipo->nombre }}">Cerrar</button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                @endif
            </x-tarjeta>
            <x-tarjeta titulo="Reglas" icono="bi-info-circle">
                <ul class="mb-0">
                    <li>Una evaluación por tipo; los pesos suman exactamente 100 %.</li>
                    <li>Una evaluación con calificaciones o cerrada no cambia su peso ni se quita.</li>
                    <li>Para cerrar una evaluación, todos los alumnos inscritos deben tener calificación.</li>
                </ul>
            </x-tarjeta>
        </div>
    </div>
@endsection

@push('modales')
    @if ($puedeProgramar)
        <x-modal id="modalEsquema" titulo="Aplicar esquema estándar" icono="bi-magic" boton="Aplicar" confirmar>
            @php $tiposPorId = $tipos->keyBy('id'); @endphp
            <p>Las evaluaciones de <strong data-campo="nombre"></strong> quedarán así:
                {{ collect($esquemaEstandar)->map(fn ($peso, $tipo) => $tiposPorId[$tipo]->nombre.' '.$peso.' %')->implode(', ') ?: 'no hay tipos que cuenten para el promedio' }}.</p>
            <p class="mb-0 texto-tenue">Si alguna evaluación ya tiene calificaciones o está cerrada y su peso no coincide, no se aplica.</p>
        </x-modal>
    @endif
    @if ($puedeCerrar)
        <x-modal id="modalCerrarEvaluacion" titulo="Cerrar evaluación" icono="bi-lock" metodo="PATCH" boton="Cerrar evaluación" confirmar>
            <p>¿Cerrar <strong data-campo="nombre"></strong> de {{ $materia->nombre }}? Todos los alumnos inscritos deben tener calificación.</p>
            <p class="mb-0 texto-tenue">Después solo Control Escolar podrá corregirla, con un motivo.</p>
        </x-modal>
    @endif
@endpush
