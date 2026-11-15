@extends('layouts.app')
@php
    $editando = $registro->exists;
    $tutoresActivos = $tutores->where('activo', true)->count();
@endphp
@section('titulo', 'Tutores del alumno')
@section('contenido')
    <x-page-header :titulo="'Tutores · '.$alumno->persona->nombre_completo" subtitulo="Debe quedar al menos un tutor activo y al menos un contacto de emergencia activo."
        :volver="[route('alumnos.show', $alumno), 'Volver a la ficha']" />
    <div class="row g-4">
        <div class="col-lg-4">
            <x-tarjeta :titulo="$editando ? 'Editar tutor' : 'Agregar tutor'" :icono="$editando ? 'bi-pencil-square' : 'bi-person-plus'">
                <x-errores-formulario />
                <form method="POST" action="{{ $editando ? route('alumnos.tutores.update', [$alumno, $registro]) : route('alumnos.tutores.store', $alumno) }}" novalidate>
                    @csrf
                    @if ($editando) @method('PUT') @endif
                    <div class="row g-3">
                        @if ($editando)
                            <div class="col-12"><label for="tutor" class="form-label"><i class="bi bi-lock" aria-hidden="true"></i> Persona</label>
                                <input id="tutor" class="form-control" value="{{ $registro->persona->nombre_completo }}" disabled><div class="form-text mono">{{ $registro->persona->curp }}</div></div>
                        @else
                            <x-campo name="curp" label="CURP" col="col-12" maxlength="18" class="text-uppercase" :requerido="true" ayuda="Si ya está registrada, se reutiliza y se conservan sus datos." />
                            <x-campo name="nombre" label="Nombre(s)" col="col-12" maxlength="100" :requerido="true" />
                            <x-campo name="primer_apellido" label="Primer apellido" col="col-12" maxlength="100" :requerido="true" />
                            <x-campo name="segundo_apellido" label="Segundo apellido" col="col-12" maxlength="100" />
                            <x-campo name="email_personal" label="Correo" type="email" col="col-12" maxlength="150" :requerido="true" />
                            <x-campo name="telefono" label="Teléfono" type="tel" col="col-12" maxlength="10" inputmode="numeric" :requerido="true" ayuda="10 dígitos." />
                            <div class="col-12"><div class="form-check">
                                <input class="form-check-input" type="checkbox" id="mismo_domicilio" name="mismo_domicilio" value="1" @checked(old('mismo_domicilio'))>
                                <label class="form-check-label" for="mismo_domicilio">Mismo domicilio que el alumno</label>
                            </div></div>
                            <div class="col-12" data-ocultar-con="mismo_domicilio"><div class="row g-3">
                                <x-campo name="calle_numero" label="Calle y número" col="col-12" maxlength="150" :requerido="true" />
                                <x-campo name="colonia" label="Colonia" col="col-12" maxlength="100" :requerido="true" />
                                <x-campo name="municipio" label="Municipio" col="col-12" maxlength="100" :requerido="true" />
                                <x-campo name="codigo_postal" label="Código postal" col="col-12" maxlength="5" inputmode="numeric" :requerido="true" />
                            </div></div>
                        @endif
                        <x-seleccion name="parentesco" label="Parentesco" col="col-12" :opciones="$parentescos" :valor="$registro->parentesco" placeholder="Selecciona" :requerido="true" />
                        <div class="col-12">
                            @foreach (['es_contacto_emergencia' => 'Contacto de emergencia', 'recibe_notificaciones' => 'Recibe notificaciones'] as $campo => $etiqueta)
                                @php $bloqueada = $campo === 'recibe_notificaciones' && $notificaciones; @endphp
                                <div class="form-check">
                                    <input class="form-check-input @error($campo) is-invalid @enderror" type="checkbox" id="{{ $campo }}" name="{{ $campo }}" value="1" @disabled($bloqueada)
                                        @checked($bloqueada ? $notificaciones->otorgado : (session()->hasOldInput() ? old($campo) : ($editando ? $registro->$campo : $campo === 'recibe_notificaciones')))>
                                    <label class="form-check-label" for="{{ $campo }}">{{ $etiqueta }}</label>
                                    @error($campo)<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    @if ($bloqueada)<div class="form-text">Lo define el consentimiento de notificaciones {{ $notificaciones->otorgado ? 'otorgado' : 'revocado' }} el {{ $notificaciones->fecha->format('d/m/Y') }}.</div>@endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="form-acciones mt-4 mb-0">
                        @if ($editando)<a class="btn btn-outline-secondary" href="{{ route('alumnos.tutores.index', $alumno) }}">Cancelar</a>@endif
                        <button type="submit" class="btn btn-siges">{{ $editando ? 'Guardar cambios' : 'Agregar' }}</button>
                    </div>
                </form>
            </x-tarjeta>
        </div>
        <div class="col-lg-8">
            <x-tarjeta sin-relleno etiqueta="Tutores del alumno">
                <div class="table-responsive">
                    <table class="table tabla-siges align-middle mb-0">
                        <thead><tr><th scope="col">Nombre</th><th scope="col">Parentesco</th><th scope="col">Emergencia</th><th scope="col">Notificaciones</th><th scope="col">Estado</th><th scope="col" class="text-end">Acciones</th></tr></thead>
                        <tbody>
                        @foreach ($tutores as $tutor)
                            <tr>
                                <td><strong>{{ $tutor->persona->nombre_completo }}</strong><small class="d-block texto-tenue">{{ $tutor->persona->telefono }} · {{ $tutor->persona->email_personal }}</small></td>
                                <td>{{ $tutor->parentesco_nombre }}</td>
                                <td>{{ $tutor->es_contacto_emergencia ? 'Sí' : 'No' }}</td>
                                <td>{{ $tutor->recibe_notificaciones ? 'Sí' : 'No' }}</td>
                                <td><x-badge-estado :estado="$tutor->activo ? 'TUTOR_ACTIVO' : 'TUTOR_INACTIVO'" /></td>
                                <td><div class="d-flex justify-content-end gap-2">
                                    <a class="btn btn-sm btn-outline-siges" href="{{ route('alumnos.tutores.edit', [$alumno, $tutor]) }}" aria-label="Editar a {{ $tutor->persona->nombre_completo }}">Editar</a>
                                    <x-menu-acciones :etiqueta="$tutor->persona->nombre_completo">
                                        {{-- Con historial no se borra: se desactiva o se reactiva. Sin historial, se quita como antes. --}}
                                        @php $conHistorial = $tutor->consentimientos_exists || $tutor->confirmaciones_exists; @endphp
                                        @if (! $tutor->activo)
                                            <li><form method="POST" action="{{ route('alumnos.tutores.reactivar', [$alumno, $tutor]) }}">@csrf @method('PATCH')<button type="submit" class="dropdown-item"><i class="bi bi-person-check" aria-hidden="true"></i> Reactivar</button></form></li>
                                        @elseif ($conHistorial && $tutoresActivos > 1)
                                            <li><button type="button" class="dropdown-item peligro" data-bs-toggle="modal" data-bs-target="#modalDesactivarTutor"
                                                data-accion="{{ route('alumnos.tutores.desactivar', [$alumno, $tutor]) }}" data-nombre="{{ $tutor->persona->nombre_completo }}"><i class="bi bi-person-slash" aria-hidden="true"></i> Desactivar</button></li>
                                        @endif
                                        @if (! $conHistorial && ($tutoresActivos > 1 || ! $tutor->activo))
                                            <li><button type="button" class="dropdown-item peligro" data-bs-toggle="modal" data-bs-target="#modalQuitarTutor"
                                                data-accion="{{ route('alumnos.tutores.destroy', [$alumno, $tutor]) }}" data-nombre="{{ $tutor->persona->nombre_completo }}"><i class="bi bi-person-x" aria-hidden="true"></i> Quitar</button></li>
                                        @endif
                                    </x-menu-acciones>
                                </div></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="tabla-pie"><span>{{ $tutores->count() }} {{ $tutores->count() === 1 ? 'tutor' : 'tutores' }} · {{ $tutoresActivos }} {{ $tutoresActivos === 1 ? 'activo' : 'activos' }}</span><span>Con historial (consentimientos o permisos) un tutor no se quita: se desactiva.</span></div>
            </x-tarjeta>
        </div>
    </div>
@endsection

@push('modales')
    <x-modal id="modalQuitarTutor" titulo="Quitar tutor" icono="bi-person-x" metodo="DELETE" boton="Quitar" peligro confirmar>
        ¿Quieres quitar a <strong data-campo="nombre"></strong> como tutor de {{ $alumno->persona->nombre_completo }}? Sus datos personales y su cuenta se conservan.
    </x-modal>
    <x-modal id="modalDesactivarTutor" titulo="Desactivar tutor" icono="bi-person-slash" metodo="PATCH" boton="Desactivar" peligro confirmar>
        <p><strong data-campo="nombre"></strong> dejará de ser tutor de {{ $alumno->persona->nombre_completo }}: no recibirá avisos ni verá al alumno en el portal. Su historial se conserva y se puede reactivar.</p>
        <div class="row g-3"><x-campo name="motivo" label="Motivo" col="col-12" minlength="10" maxlength="255" :requerido="true" ayuda="Queda registrado en la bitácora." /></div>
    </x-modal>
@endpush
