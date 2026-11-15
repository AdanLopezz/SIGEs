@extends('layouts.app')
@section('titulo', 'Permisos de salida')
@section('contenido')
    <x-page-header titulo="Permisos de salida" subtitulo="El permiso se autoriza o rechaza con la confirmación de un tutor del alumno." />
    <x-errores-formulario />
    <div class="row g-4">
        <div class="col-lg-4">
            <x-tarjeta titulo="Nuevo permiso" icono="bi-door-open">
                <x-buscador-alumno :accion="route('permisos-salida.index')" :buscar="$buscar" />
                @if (filled($buscar) && ! $elegido)
                    @forelse ($encontrados as $alumno)
                        <div class="d-flex align-items-center justify-content-between gap-2 py-2 border-top">
                            <div><strong>{{ $alumno->persona->nombre_completo }}</strong><small class="d-block mono">{{ $alumno->matricula }} · {{ $alumno->inscripcionActual?->grupo->identificador }}</small></div>
                            <a class="btn btn-sm btn-outline-siges" href="{{ route('permisos-salida.index', ['buscar' => $buscar, 'alumno' => $alumno->id]) }}">Elegir</a>
                        </div>
                    @empty
                        <p class="texto-tenue">No se encontraron alumnos inscritos de tus grupos.</p>
                    @endforelse
                @endif
                @if ($elegido)
                    <form method="POST" action="{{ route('permisos-salida.store') }}" novalidate>
                        @csrf
                        <input type="hidden" name="per_alumno_id" value="{{ $elegido->id }}">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label" for="alumnoElegido"><i class="bi bi-lock" aria-hidden="true"></i> Alumno</label>
                                <input id="alumnoElegido" class="form-control" value="{{ $elegido->persona->nombre_completo }} · {{ $elegido->inscripcionActual?->grupo->identificador }}" disabled>
                                @error('per_alumno_id')<div class="texto-peligro small">{{ $message }}</div>@enderror
                                <a class="small" href="{{ route('permisos-salida.index') }}">Elegir otro</a>
                            </div>
                            <div class="col-12">
                                <label for="motivo" class="form-label">Motivo <span class="requerido" aria-hidden="true">*</span></label>
                                <textarea id="motivo" name="motivo" rows="3" class="form-control @error('motivo') is-invalid @enderror" minlength="5" maxlength="1000" required>{{ old('motivo') }}</textarea>
                                @error('motivo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <x-campo name="salida_prevista_at" label="Salida prevista" type="datetime-local" col="col-12" :value="now()->addHour()->format('Y-m-d\TH:i')" :min="now()->format('Y-m-d\TH:i')" :requerido="true" ayuda="Hoy (a partir de ahora) o después." />
                        </div>
                        <div class="form-acciones mt-4 mb-0">
                            <a class="btn btn-outline-secondary" href="{{ route('permisos-salida.index') }}">Cancelar</a>
                            <button type="submit" class="btn btn-siges">Registrar permiso</button>
                        </div>
                    </form>
                @elseif (! filled($buscar))
                    <p class="texto-tenue small mb-0">Busca y elige al alumno para registrar el permiso.</p>
                @endif
            </x-tarjeta>
        </div>
        <div class="col-lg-8">
            <x-tarjeta titulo="Hoy y próximos" icono="bi-calendar-event" sin-relleno>
                @if ($permisos->isEmpty())
                    <x-estado-vacio icono="bi-door-open" texto="No hay permisos de salida de hoy en adelante." />
                @else
                    <div class="table-responsive">
                        <table class="table tabla-siges align-middle mb-0">
                            <thead><tr><th scope="col">Salida</th><th scope="col">Alumno</th><th scope="col">Motivo</th><th scope="col">Estado</th><th scope="col" class="text-end">Acciones</th></tr></thead>
                            <tbody>
                            @foreach ($permisos as $permiso)
                                @php $alumnoPermiso = $permiso->inscripcion->alumno; $confirmacion = $permiso->confirmaciones->first(); @endphp
                                <tr>
                                    <td class="text-nowrap">{{ $permiso->salida_prevista_at->format('d/m/Y') }}<small class="d-block mono">{{ $permiso->salida_prevista_at->format('H:i') }}</small></td>
                                    <td><strong>{{ $alumnoPermiso->persona->nombre_completo }}</strong><small class="d-block mono">{{ $alumnoPermiso->matricula }} · {{ $permiso->inscripcion->grupo->identificador }}</small></td>
                                    <td>{{ $permiso->motivo }}<small class="d-block texto-tenue">Solicitó: {{ $permiso->solicitadoPor?->name }}</small></td>
                                    <td><x-badge-estado :estado="$permiso->estado" />
                                        @if ($confirmacion)<small class="d-block texto-tenue mt-1">{{ $confirmacion->tutor->persona->nombre_completo }} · {{ $metodos[$confirmacion->metodo][0] }}: {{ $confirmacion->evidencia }}</small>@endif</td>
                                    <td><div class="d-flex justify-content-end gap-2">
                                        @if ($permiso->estado === \App\Models\TrxPermisoSalida::PENDIENTE)
                                            <button type="button" class="btn btn-sm btn-outline-siges" data-bs-toggle="modal" data-bs-target="#modalConfirmar{{ $permiso->id }}">Confirmar</button>
                                        @endif
                                        <x-menu-acciones :etiqueta="$alumnoPermiso->persona->nombre_completo">
                                            @if ($permiso->puedeCancelarse())
                                                <li><button type="button" class="dropdown-item peligro" data-bs-toggle="modal" data-bs-target="#modalCancelar"
                                                    data-accion="{{ route('permisos-salida.cancelar', $permiso) }}" data-nombre="{{ $alumnoPermiso->persona->nombre_completo }}"><i class="bi bi-x-circle" aria-hidden="true"></i> Cancelar permiso</button></li>
                                            @endif
                                        </x-menu-acciones>
                                    </div></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
                <div class="tabla-pie"><x-por-pagina :paginador="$permisos" singular="permiso" plural="permisos" /></div>
            </x-tarjeta>
        </div>
    </div>
@endsection

@push('modales')
    @foreach ($permisos as $permiso)
        @if ($permiso->estado === \App\Models\TrxPermisoSalida::PENDIENTE)
            <x-modal :id="'modalConfirmar'.$permiso->id" titulo="Confirmación del tutor" icono="bi-telephone" :accion="route('permisos-salida.confirmar', $permiso)" boton="Registrar confirmación">
                <p>{{ $permiso->inscripcion->alumno->persona->nombre_completo }} · salida {{ $permiso->salida_prevista_at->format('d/m/Y H:i') }}</p>
                <div class="row g-3">
                    <x-seleccion name="per_tutor_alumno_id" :id="'tutor'.$permiso->id" label="Tutor" col="col-12" :requerido="true"
                        :opciones="$permiso->inscripcion->alumno->tutores->where('activo', true)->mapWithKeys(fn ($tutor) => [$tutor->id => $tutor->persona->nombre_completo.' · '.$tutor->parentesco_nombre.' · '.$tutor->persona->telefono])" />
                    <x-seleccion name="decision" :id="'decision'.$permiso->id" label="Decisión" col="col-md-6" :opciones="$decisiones" :requerido="true" />
                    <x-seleccion name="metodo" :id="'metodo'.$permiso->id" label="Método" col="col-md-6" :opciones="collect($metodos)->map(fn ($metodo) => $metodo[0])" :requerido="true" />
                    <x-campo name="evidencia" :id="'evidencia'.$permiso->id" label="Evidencia" col="col-12" maxlength="255" :requerido="true"
                        :ayuda="collect($metodos)->map(fn ($metodo) => $metodo[0].': '.mb_strtolower($metodo[1]))->implode(' · ').'.'" />
                </div>
            </x-modal>
        @endif
    @endforeach
    <x-modal id="modalCancelar" titulo="Cancelar permiso" icono="bi-x-circle" metodo="PATCH" boton="Cancelar permiso" peligro confirmar>
        <p>Se cancelará el permiso de salida de <strong data-campo="nombre"></strong>.</p>
        <div class="row g-3"><x-campo name="motivo" id="motivoCancelar" label="Motivo" col="col-12" maxlength="255" :requerido="true" ayuda="Queda registrado en la bitácora." /></div>
    </x-modal>
@endpush
