@extends('layouts.app')
@php
    // Todas las materias de la lista son del usuario y del periodo de consulta: basta con sus permisos y el tipo de periodo.
    $usuario = auth()->user();
    $operable = \App\Support\PeriodoDeTrabajo::permite(\App\Support\PeriodoDeTrabajo::OPERAR);
    $puedeProgramar = $operable && $usuario->can('evaluaciones.programar');
    $puedeCerrar = $operable && $usuario->canAny(['calificaciones.capturar', 'calificaciones.corregir']);
    $veCalificaciones = $usuario->canAny(['calificaciones.capturar', 'calificaciones.corregir', 'resultados.cerrar']);
@endphp
@section('titulo', 'Evaluaciones')
@section('contenido')
    <x-page-header titulo="Evaluaciones" :subtitulo="$usuario->hasAnyRole(\App\Models\AcaGrupoAsignatura::ROLES_VEN_TODAS) ? 'Materias del periodo elegido con sus evaluaciones y pesos.' : 'Tus materias del periodo elegido con sus evaluaciones y pesos.'" />

    <x-filtros :accion="route('evaluaciones.index')" :limpiar="route('evaluaciones.index')">
        <x-seleccion name="grupo" label="Grupo" col="" :valor="$filtros['grupo'] ?? null" placeholder="Todos los grupos"
            :opciones="$grupos->mapWithKeys(fn ($grupo) => [$grupo->id => $grupo->nombre_completo.' · '.$grupo->semestre.'.º semestre'])" />
    </x-filtros>

    <x-tarjeta sin-relleno etiqueta="Materias y evaluaciones">
        @if ($materias->isEmpty())
            <x-estado-vacio icono="bi-card-checklist" texto="No hay materias que mostrar en el periodo elegido." />
        @else
            <div class="table-responsive">
                <table class="table tabla-siges align-middle mb-0">
                    <thead><tr><th scope="col">Materia</th><th scope="col">Grupo</th><th scope="col">Docente</th><th scope="col">Evaluaciones</th><th scope="col">Total</th><th scope="col" class="text-end">Acciones</th></tr></thead>
                    <tbody>
                    @foreach ($materias as $materia)
                        @php
                            $suma = $materia->sumaDePesos();
                            $abiertas = $materia->evaluaciones->reject->cerrada;
                        @endphp
                        <tr>
                            <td><strong>{{ $materia->asignatura->nombre }}</strong><small class="d-block mono">{{ $materia->asignatura->clave }}</small></td>
                            <td>{{ $materia->grupo->nombre_completo }}</td>
                            <td>{{ $materia->docente->nombre }}</td>
                            <td>
                                @forelse ($materia->evaluaciones as $evaluacion)
                                    <div class="d-flex align-items-center gap-2 @unless ($loop->first) mt-1 @endunless">
                                        <span>{{ $evaluacion->tipo->nombre }} · {{ $evaluacion->peso_porcentaje }} %</span>
                                        <x-badge-estado :estado="$evaluacion->cerrada ? 'CERRADA' : 'ABIERTA'" />
                                    </div>
                                @empty
                                    <span class="texto-tenue">Sin programar</span>
                                @endforelse
                            </td>
                            <td>
                                @if ($materia->evaluaciones->isEmpty())
                                    <span class="texto-tenue">—</span>
                                @elseif ($suma === \App\Models\AcaEvaluacion::PESO_TOTAL)
                                    <x-badge-estado tono="exito" texto="100 %" />
                                @else
                                    <x-badge-estado tono="aviso" :texto="\App\Models\TrxCalificacion::formatear($suma).' % (debe ser 100)'" />
                                @endif
                            </td>
                            <td><div class="d-flex justify-content-end gap-2">
                                <a class="btn btn-sm btn-outline-siges" href="{{ route('evaluaciones.edit', $materia) }}" aria-label="{{ $puedeProgramar ? 'Programar' : 'Ver' }} evaluaciones de {{ $materia->nombre }}">{{ $puedeProgramar ? 'Programar' : 'Ver' }}</a>
                                <x-menu-acciones :etiqueta="$materia->nombre">
                                    @if ($puedeProgramar)
                                        <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#modalEsquema"
                                            data-accion="{{ route('evaluaciones.estandar', $materia) }}" data-nombre="{{ $materia->nombre }}"><i class="bi bi-magic" aria-hidden="true"></i> Aplicar esquema estándar</button></li>
                                    @endif
                                    @if ($puedeCerrar)
                                        @foreach ($abiertas as $evaluacion)
                                            <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#modalCerrarEvaluacion"
                                                data-accion="{{ route('evaluaciones.cerrar', $evaluacion) }}" data-nombre="{{ $evaluacion->tipo->nombre }} de {{ $materia->nombre }}"><i class="bi bi-lock" aria-hidden="true"></i> Cerrar {{ $evaluacion->tipo->nombre }}</button></li>
                                        @endforeach
                                    @endif
                                    @if ($veCalificaciones)
                                        <li><a class="dropdown-item" href="{{ route('calificaciones.materia', $materia) }}"><i class="bi bi-clipboard-data" aria-hidden="true"></i> Calificaciones</a></li>
                                    @endif
                                </x-menu-acciones>
                            </div></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        <div class="tabla-pie"><x-por-pagina :paginador="$materias" singular="materia" plural="materias" /></div>
    </x-tarjeta>
@endsection

@push('modales')
    @if ($puedeProgramar)
    <x-modal id="modalEsquema" titulo="Aplicar esquema estándar" icono="bi-magic" boton="Aplicar" confirmar>
        <p>Las evaluaciones de <strong data-campo="nombre"></strong> quedarán así: {{ $esquemaEstandar ?: 'no hay tipos que cuenten para el promedio' }}.</p>
        <p class="mb-0 texto-tenue">Si alguna evaluación ya tiene calificaciones o está cerrada y su peso no coincide, no se aplica.</p>
    </x-modal>
    @endif
    @if ($puedeCerrar)
    <x-modal id="modalCerrarEvaluacion" titulo="Cerrar evaluación" icono="bi-lock" metodo="PATCH" boton="Cerrar evaluación" confirmar>
        <p>¿Cerrar <strong data-campo="nombre"></strong>? Todos los alumnos inscritos deben tener calificación.</p>
        <p class="mb-0 texto-tenue">Después solo Control Escolar podrá corregirla, con un motivo.</p>
    </x-modal>
    @endif
@endpush
