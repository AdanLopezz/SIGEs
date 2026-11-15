@extends('layouts.app')
@use('App\Models\AcaHorario')
@php
    $administra = auth()->user()->can('horarios.administrar');
    $minutos = $cuadricula->minutosPorMateria();
    $turno = $grupo->turno;
    $editando ??= null;
@endphp
@section('titulo', 'Horario · '.$grupo->identificador)
@section('contenido')
    <x-page-header :titulo="'Horario del grupo '.$grupo->identificador"
        :volver="$administra ? [route('horarios.index'), 'Volver a Horarios'] : [route('asistencia-clase.index'), 'Volver a Asistencia por clase']">
        <x-slot:subtitulo>{{ $grupo->carrera->nombre }} · {{ $grupo->semestre }}.º semestre · Turno {{ $turno->nombre }} ({{ substr($turno->hora_entrada, 0, 5) }} a {{ substr($turno->hora_salida, 0, 5) }}) · {{ $grupo->periodo->nombre_visible }} <span class="mono">{{ $grupo->periodo->clave }}</span></x-slot:subtitulo>
        <x-slot:acciones><a href="{{ route('horarios.grupo.imprimir', $grupo) }}" class="btn btn-outline-siges"><i class="bi bi-printer" aria-hidden="true"></i> Imprimir</a></x-slot:acciones>
    </x-page-header>

    @foreach (session('avisos', []) as $aviso)
        <x-aviso tipo="aviso">{{ $aviso }}</x-aviso>
    @endforeach
    @if (session('omitidas'))
        <x-aviso tipo="aviso">
            <strong>Clases que no se copiaron:</strong>
            <ul class="mb-0">@foreach (session('omitidas') as $omitida)<li>{{ $omitida }}</li>@endforeach</ul>
        </x-aviso>
    @endif
    @if ($administra && ! $editable)
        <x-aviso tipo="info">El periodo {{ $grupo->periodo->nombre_visible }} ya pasó: su horario solo se consulta.</x-aviso>
    @endif

    <x-tarjeta titulo="Horario semanal" icono="bi-calendar-week" sin-relleno>
        @if ($materias->isEmpty())
            <x-estado-vacio icono="bi-journal-bookmark" texto="El grupo no tiene materias. Agrégalas en Académico → Grupos → Materias." />
        @elseif ($cuadricula->vacia() && ! $editable)
            <x-estado-vacio icono="bi-calendar-week" texto="El grupo todavía no tiene horario." />
        @else
            @include('academico.horarios._cuadricula', [
                'mostrar' => ['docente', 'aula'],
                'agregar' => $editable ? fn ($dia, $inicio) => route('horarios.grupo', [$grupo, 'dia' => $dia, 'inicio' => $inicio]).'#clase' : null,
                'editar' => $editable ? fn ($clase) => route('horarios.grupo', [$grupo, 'editar' => $clase->id]).'#clase' : null,
                'resaltar' => $editando?->id,
            ])
        @endif
        <div class="tabla-pie">
            <span>{{ $clases->count() }} {{ $clases->count() === 1 ? 'clase' : 'clases' }} · {{ AcaHorario::duracion($minutos->sum()) }} por semana</span>
            @if ($editable)<span>Elige una celda vacía para agregar una clase o una clase para editarla.</span>@endif
        </div>
    </x-tarjeta>

    @if ($editable && $materias->isNotEmpty())
        <div id="clase" class="row g-4 mb-4">
            <div class="col-lg-7">
                <x-tarjeta :titulo="$editando ? 'Editar clase' : 'Agregar clase'" :icono="$editando ? 'bi-pencil' : 'bi-plus-lg'" class="h-100">
                    <x-slot:acciones>
                        @if ($editando)
                            <x-menu-acciones etiqueta="la clase">
                                <li><button type="button" class="dropdown-item peligro" data-bs-toggle="modal" data-bs-target="#modalQuitarClase"><i class="bi bi-trash" aria-hidden="true"></i> Quitar clase</button></li>
                            </x-menu-acciones>
                        @endif
                    </x-slot:acciones>
                    <x-errores-formulario />
                    <form method="POST" action="{{ $editando ? route('horarios.update', $editando) : route('horarios.store', $grupo) }}">
                        @csrf
                        @if ($editando) @method('PUT') @endif
                        <div class="row g-3">
                            <x-seleccion name="aca_grupo_asignatura_id" label="Materia" col="col-md-6" requerido placeholder="Elige la materia" :valor="$propuesta['aca_grupo_asignatura_id']"
                                :opciones="$materias->mapWithKeys(fn ($materia) => [$materia->id => $materia->asignatura->nombre.' · '.$materia->docente?->nombre])" />
                            <x-seleccion name="org_espacio_fisico_id" label="Aula" col="col-md-6" requerido placeholder="Elige el aula" :valor="$propuesta['org_espacio_fisico_id']"
                                :opciones="$espacios->mapWithKeys(fn ($aula) => [$aula->id => $aula->nombre.' · capacidad '.$aula->capacidad])"
                                ayuda="Si el aula no alcanza para los alumnos de la materia, se avisa." />
                            <x-seleccion name="dia_semana" label="Día" col="col-md-4" requerido placeholder="Elige el día" :valor="$propuesta['dia_semana']" :opciones="AcaHorario::DIAS" />
                            <x-campo name="hora_inicio" label="Hora de inicio" type="time" col="col-md-4" requerido :value="$propuesta['hora_inicio']"
                                :min="substr($turno->hora_entrada, 0, 5)" :max="substr($turno->hora_salida, 0, 5)" />
                            <x-campo name="hora_fin" label="Hora de fin" type="time" col="col-md-4" requerido :value="$propuesta['hora_fin']"
                                :min="substr($turno->hora_entrada, 0, 5)" :max="substr($turno->hora_salida, 0, 5)" />
                        </div>
                        <div class="form-acciones">
                            <a href="{{ route('horarios.grupo', $grupo) }}" class="btn btn-outline-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-siges">{{ $editando ? 'Guardar cambios' : 'Agregar clase' }}</button>
                        </div>
                    </form>
                </x-tarjeta>
            </div>
            <div class="col-lg-5">
                <x-tarjeta titulo="Reglas" icono="bi-info-circle" class="h-100">
                    <ul class="mb-0">
                        <li>La clase queda dentro del turno: {{ substr($turno->hora_entrada, 0, 5) }} a {{ substr($turno->hora_salida, 0, 5) }}.</li>
                        <li>No se traslapa con otra clase del grupo, del mismo docente (en cualquier grupo del periodo) ni de la misma aula.</li>
                        <li>Terminar a las 8:00 y empezar otra a las 8:00 no es traslape.</li>
                        <li>Si una materia tiene dos clases el mismo día, se pasa lista una sola vez.</li>
                    </ul>
                </x-tarjeta>
            </div>
        </div>
    @endif

    <x-tarjeta titulo="Materias del grupo" icono="bi-journal-bookmark" sin-relleno>
        @if ($materias->isEmpty())
            <x-estado-vacio icono="bi-journal-bookmark" texto="Sin materias." />
        @else
            <div class="table-responsive">
                <table class="table tabla-siges align-middle mb-0">
                    <thead><tr><th scope="col">Materia</th><th scope="col">Docente</th><th scope="col">Horas por semana</th></tr></thead>
                    <tbody>
                    @foreach ($materias as $materia)
                        <tr>
                            <td><strong>{{ $materia->asignatura->nombre }}</strong><small class="d-block mono">{{ $materia->asignatura->clave }}</small></td>
                            <td>{{ $materia->docente?->nombre }}</td>
                            <td>@if ($minutos->has($materia->id)){{ AcaHorario::duracion($minutos->get($materia->id)) }}@else<x-badge-estado estado="SIN_HORARIO" />@endif</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        <div class="tabla-pie"><span>{{ $materias->count() }} {{ $materias->count() === 1 ? 'materia' : 'materias' }}</span></div>
    </x-tarjeta>

    @if ($editable && $clases->isEmpty())
        <x-tarjeta titulo="Copiar horario de otro grupo" icono="bi-copy">
            @if ($origenes->isEmpty())
                <p class="texto-tenue mb-0">No hay otro grupo de {{ $grupo->semestre }}.º semestre de {{ $grupo->carrera->nombre }} con horario para copiar.</p>
            @else
                <p class="texto-tenue">Sirve como punto de partida: cada clase va a la materia con la misma asignatura. Se omiten, con su motivo, las que no tienen la materia en este grupo, quedan fuera del turno o chocan por grupo, docente o aula.</p>
                <form method="POST" action="{{ route('horarios.copiar', $grupo) }}">
                    @csrf
                    <div class="row g-3">
                        <x-seleccion name="origen" label="Grupo origen" col="col-md-6" requerido placeholder="Elige el grupo"
                            :opciones="$origenes->mapWithKeys(fn ($origen) => [$origen->id => $origen->identificador.' · '.$origen->turno->nombre.' · '.$origen->periodo->nombre_visible.' ('.$origen->periodo->clave.')'])" />
                        <x-seleccion name="org_espacio_fisico_id" label="Aula" col="col-md-6" placeholder="Conservar el aula de cada clase"
                            :opciones="$espacios->mapWithKeys(fn ($aula) => [$aula->id => $aula->nombre.' · capacidad '.$aula->capacidad])"
                            ayuda="Elige un aula si todas las clases copiadas deben ir a la misma (p. ej. el salón del grupo)." />
                    </div>
                    <div class="form-acciones"><button type="submit" class="btn btn-siges"><i class="bi bi-copy" aria-hidden="true"></i> Copiar horario</button></div>
                </form>
            @endif
        </x-tarjeta>
    @endif
@endsection

@push('modales')
    @if ($editando)
        <x-modal id="modalQuitarClase" titulo="Quitar clase" icono="bi-trash" :accion="route('horarios.destroy', $editando)" metodo="DELETE" boton="Quitar" peligro>
            ¿Quieres quitar <strong>{{ $editando->materia->asignatura->nombre }}</strong> del {{ mb_strtolower($editando->dia_nombre) }} de {{ $editando->inicio }} a {{ $editando->fin }}?
            Las listas ya registradas de esa materia no se borran.
        </x-modal>
    @endif
@endpush
