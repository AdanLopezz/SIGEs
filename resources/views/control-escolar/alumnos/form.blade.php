@extends('layouts.app')
@php
    $editando = $alumno->exists;
    $persona = $alumno->persona;
    $salud = $alumno->salud;
    $conSalud = $editando ? auth()->user()->can('verSalud', $alumno) : true;
    $seccion = 0;
@endphp
@section('titulo', $editando ? 'Editar alumno' : 'Nuevo alumno')
@section('contenido')
    <x-page-header :titulo="$editando ? 'Editar alumno' : 'Nuevo alumno'" :subtitulo="$editando ? 'Actualiza los datos de '.$persona->nombre_completo.'.' : 'Registra al alumno, a su tutor principal y su inscripción en el periodo activo.'" />
    <x-errores-formulario />
    @if (! $editando && (! $hayPeriodoActivo || $grupos->isEmpty()))
        <x-aviso tipo="aviso">
            {{ $hayPeriodoActivo ? 'No hay grupos en el periodo activo.' : 'No hay un periodo escolar activo.' }} Para registrar un alumno es necesario inscribirlo en un grupo.
            @can('horarios.administrar')Revísalo en <a href="{{ route('grupos.index') }}">Grupos</a>.@else Pide a Control Escolar que lo configure.@endcan
        </x-aviso>
    @endif
    <form method="POST" action="{{ $editando ? route('alumnos.update', $alumno) : route('alumnos.store') }}" enctype="multipart/form-data" novalidate>
        @csrf
        @if ($editando) @method('PUT') @endif
        <section class="form-seccion">
            <h2 class="form-seccion-titulo"><span>{{ ++$seccion }}</span> Datos personales</h2>
            <div class="row g-3">
                @if ($editando)
                    <div class="col-md-4"><label for="curp" class="form-label"><i class="bi bi-lock" aria-hidden="true"></i> CURP</label><input id="curp" class="form-control" value="{{ $persona->curp }}" disabled><div class="form-text">La CURP no se modifica desde esta pantalla.</div></div>
                @else
                    <x-campo name="curp" label="CURP" maxlength="18" class="text-uppercase" :requerido="true" />
                @endif
                <x-campo name="nombre" label="Nombre(s)" :value="$persona?->nombre" maxlength="100" :requerido="true" />
                <x-campo name="primer_apellido" label="Primer apellido" :value="$persona?->primer_apellido" maxlength="100" :requerido="true" />
                <x-campo name="segundo_apellido" label="Segundo apellido" :value="$persona?->segundo_apellido" maxlength="100" ayuda="Déjalo vacío si no tiene segundo apellido." />
                <x-campo name="email_personal" label="Correo personal" type="email" :value="$persona?->email_personal" maxlength="150" :requerido="true" />
                <x-campo name="telefono" label="Teléfono" type="tel" :value="$persona?->telefono" maxlength="10" inputmode="numeric" :requerido="true" ayuda="10 dígitos." />
            </div>
            <h3 class="form-subtitulo">Domicilio</h3>
            <div class="row g-3">
                <x-campo name="calle_numero" label="Calle y número" :value="$persona?->calle_numero" maxlength="150" :requerido="true" />
                <x-campo name="colonia" label="Colonia" :value="$persona?->colonia" maxlength="100" :requerido="true" />
                <x-campo name="municipio" label="Municipio" :value="$persona?->municipio" maxlength="100" :requerido="true" />
                <x-campo name="codigo_postal" label="Código postal" :value="$persona?->codigo_postal" maxlength="5" inputmode="numeric" :requerido="true" ayuda="5 dígitos." />
            </div>
        </section>

        <section class="form-seccion">
            <h2 class="form-seccion-titulo"><span>{{ ++$seccion }}</span> Datos escolares</h2>
            <div class="row g-3">
                <x-campo name="matricula" label="Matrícula" :value="$alumno->matricula" maxlength="30" class="text-uppercase" :requerido="true" />
                @if ($editando)
                    <x-campo name="email_institucional" label="Correo institucional" type="email" :value="$alumno->email_institucional" maxlength="150" :requerido="true" ayuda="Es su usuario si se le crea una cuenta. Cambiarlo aquí no cambia el usuario de una cuenta ya creada." />
                @else
                    <x-campo-usuario name="email_institucional" label="Correo institucional (usuario)" col="col-md-4" :automatico="true" nombre="nombre" apellido="primer_apellido"
                        ayuda="Lo genera el sistema; será también su usuario si se le crea una cuenta." />
                @endif
                <x-campo name="fecha_ingreso" label="Fecha de ingreso" type="date" :value="$alumno->fecha_ingreso?->format('Y-m-d')" :max="now()->format('Y-m-d')" :requerido="true" />
                <x-seleccion name="aca_carrera_id" label="Carrera" col="col-md-6" :requerido="true" placeholder="Selecciona una carrera"
                    :opciones="$carreras->mapWithKeys(fn ($carrera) => [$carrera->id => $carrera->nombre.($carrera->activo ? '' : ' · inactiva')])" :valor="$alumno->aca_carrera_id"
                    :bloqueado="$carreraBloqueada" :ayuda="$carreraBloqueada ? 'La carrera no se cambia porque el alumno está inscrito en el periodo activo.' : null" />
                @unless ($editando)
                    {{-- Las opciones llevan data-padre para filtrarse por carrera (campos-dependientes.js). --}}
                    <x-seleccion name="aca_grupo_escolar_id" label="Grupo del periodo activo" col="col-md-6" :requerido="true" placeholder="Selecciona un grupo"
                        data-depende-de="aca_carrera_id" ayuda="Solo aparecen los grupos de la carrera seleccionada.">
                        @foreach ($grupos as $grupo)<option value="{{ $grupo->id }}" data-padre="{{ $grupo->aca_carrera_id }}" @selected(old('aca_grupo_escolar_id') == $grupo->id)>{{ $grupo->nombre_completo }} · {{ $grupo->semestre }}.º semestre</option>@endforeach
                    </x-seleccion>
                @endunless
            </div>
        </section>

        @if ($conSalud)
            <section class="form-seccion">
                <h2 class="form-seccion-titulo"><span>{{ ++$seccion }}</span> Salud</h2>
                <div class="row g-3">
                    <x-campo name="nss" label="NSS" :value="$salud?->nss" maxlength="11" inputmode="numeric" :requerido="true" ayuda="Número de Seguridad Social, 11 dígitos." />
                    <x-seleccion name="tipo_sangre" label="Tipo de sangre" :opciones="array_combine($tiposSangre, $tiposSangre)" :valor="$salud?->tipo_sangre" placeholder="Selecciona" :requerido="true" />
                    @foreach (['alergias' => 'Alergias', 'condiciones_medicas' => 'Condiciones médicas'] as $campo => $etiqueta)
                        <div class="col-md-6">
                            <label for="{{ $campo }}" class="form-label">{{ $etiqueta }}</label>
                            <textarea id="{{ $campo }}" name="{{ $campo }}" rows="3" maxlength="1000" class="form-control @error($campo) is-invalid @enderror" aria-describedby="{{ $campo }}-ayuda">{{ old($campo, $salud && $salud->$campo !== \App\Models\PerAlumnoSalud::SIN_DATO ? $salud->$campo : '') }}</textarea>
                            @error($campo)<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text" id="{{ $campo }}-ayuda">Déjalo vacío si no tiene; se guardará «NINGUNA».</div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        @unless ($editando)
            <section class="form-seccion">
                <h2 class="form-seccion-titulo"><span>{{ ++$seccion }}</span> Padre, madre o tutor principal</h2>
                <p class="texto-tenue">Quedará como contacto de emergencia. Si la CURP ya está registrada, se reutiliza la persona y se conservan sus datos.</p>
                <div class="row g-3">
                    <x-campo name="tutor_curp" label="CURP" maxlength="18" class="text-uppercase" :requerido="true" />
                    <x-campo name="tutor_nombre" label="Nombre(s)" maxlength="100" :requerido="true" />
                    <x-campo name="tutor_primer_apellido" label="Primer apellido" maxlength="100" :requerido="true" />
                    <x-campo name="tutor_segundo_apellido" label="Segundo apellido" maxlength="100" />
                    <x-campo name="tutor_email_personal" label="Correo" type="email" maxlength="150" :requerido="true" ayuda="Aquí le llegarán los avisos y los enlaces para recuperar su contraseña." />
                    <x-campo name="tutor_telefono" label="Teléfono" type="tel" maxlength="10" inputmode="numeric" :requerido="true" ayuda="10 dígitos." />
                    <x-seleccion name="tutor_parentesco" label="Parentesco" :opciones="$parentescos" placeholder="Selecciona" :requerido="true" />
                    <div class="col-md-8 d-flex align-items-end">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="tutor_mismo_domicilio" name="tutor_mismo_domicilio" value="1" @checked(old('tutor_mismo_domicilio'))>
                            <label class="form-check-label" for="tutor_mismo_domicilio">Mismo domicilio que el alumno</label>
                        </div>
                    </div>
                </div>
                <div data-ocultar-con="tutor_mismo_domicilio">
                    <h3 class="form-subtitulo">Domicilio del tutor</h3>
                    <div class="row g-3">
                        <x-campo name="tutor_calle_numero" label="Calle y número" maxlength="150" :requerido="true" />
                        <x-campo name="tutor_colonia" label="Colonia" maxlength="100" :requerido="true" />
                        <x-campo name="tutor_municipio" label="Municipio" maxlength="100" :requerido="true" />
                        <x-campo name="tutor_codigo_postal" label="Código postal" maxlength="5" inputmode="numeric" :requerido="true" ayuda="5 dígitos." />
                    </div>
                </div>
            </section>
        @endunless

        <section class="form-seccion">
            <h2 class="form-seccion-titulo"><span>{{ ++$seccion }}</span> Expediente</h2>
            <p class="texto-tenue">Un solo PDF con el acta de nacimiento, la CURP y el certificado de secundaria.</p>
            @if ($editando)
                <p>Expediente actual: <x-archivo-enlace :archivo="$alumno->expediente" /></p>
            @endif
            <div class="row g-3">
                <x-archivo-input name="expediente" :label="$editando ? 'Reemplazar expediente' : 'Expediente'" col="col-md-6" :requerido="! $editando"
                    :ayuda="$editando ? 'Opcional. PDF o imagen, máximo 10 MB. El anterior se conserva.' : 'PDF o imagen, máximo 10 MB.'" />
            </div>
        </section>

        @if ($editando)
            <section class="form-seccion">
                <h2 class="form-seccion-titulo"><span>{{ ++$seccion }}</span> Motivo del cambio</h2>
                <div class="row g-3"><x-campo name="motivo" label="Motivo del cambio" col="col-12" maxlength="255" :requerido="true" ayuda="Queda registrado en la bitácora junto con los cambios." /></div>
            </section>
        @endif

        <div class="form-acciones">
            <a href="{{ $editando ? route('alumnos.show', $alumno) : route('alumnos.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            <button type="submit" class="btn btn-siges">{{ $editando ? 'Guardar cambios' : 'Registrar alumno' }}</button>
        </div>
    </form>
@endsection
