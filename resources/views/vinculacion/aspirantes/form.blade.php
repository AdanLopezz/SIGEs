@extends('layouts.app')
@php
    $editando = $aspirante->exists;
    $persona = $aspirante->persona;
@endphp
@section('titulo', $editando ? 'Editar aspirante' : 'Nuevo aspirante')
@section('contenido')
    <x-page-header :titulo="$editando ? 'Editar aspirante' : 'Nuevo aspirante'"
        :subtitulo="$editando ? 'Actualiza los datos de '.$persona->nombre_completo.'. Los cambios quedan en la bitácora.' : 'Registra a la persona y su interés de ingreso.'"
        :volver="$editando ? [route('aspirantes.show', $aspirante), 'Volver al aspirante'] : [route('aspirantes.index'), 'Volver a Aspirantes']" />
    <x-errores-formulario />

    <form method="POST" action="{{ $editando ? route('aspirantes.update', $aspirante) : route('aspirantes.store') }}" novalidate>
        @csrf
        @if ($editando) @method('PUT') @endif
        <section class="form-seccion">
            <h2 class="form-seccion-titulo"><span>1</span> Datos personales</h2>
            @if ($personaCompartida)
                <x-aviso tipo="info">Esta persona también es alumno, personal, tutor o tiene cuenta: sus datos no se editan desde Aspirantes.</x-aviso>
                <dl class="dato-lista">
                    <dt>CURP</dt><dd class="mono">{{ $persona->curp }}</dd>
                    <dt>Nombre</dt><dd>{{ $persona->nombre_completo }}</dd>
                    <dt>Contacto</dt><dd>{{ $persona->email_personal }} · {{ $persona->telefono }}</dd>
                </dl>
            @else
                @unless ($editando)
                    <p class="texto-tenue">Si la CURP ya está registrada, se reutiliza la persona y se conservan sus datos (no se piden).</p>
                @endunless
                <div class="row g-3">
                    @if ($editando)
                        <div class="col-md-4"><label for="curp" class="form-label"><i class="bi bi-lock" aria-hidden="true"></i> CURP</label><input id="curp" class="form-control mono" value="{{ $persona->curp }}" disabled><div class="form-text">La CURP no se modifica.</div></div>
                    @else
                        <x-campo name="curp" label="CURP" maxlength="18" class="text-uppercase" :requerido="true" />
                    @endif
                    <x-campo name="nombre" label="Nombre(s)" :value="$persona?->nombre" maxlength="100" :requerido="true" />
                    <x-campo name="primer_apellido" label="Primer apellido" :value="$persona?->primer_apellido" maxlength="100" :requerido="true" />
                    <x-campo name="segundo_apellido" label="Segundo apellido" :value="$persona?->segundo_apellido" maxlength="100" ayuda="Déjalo vacío si no tiene segundo apellido." />
                    <x-campo name="email_personal" label="Correo personal" type="email" :value="$persona?->email_personal" maxlength="150" :requerido="true" />
                    <x-campo name="telefono" label="Teléfono" type="tel" :value="$persona?->telefono" maxlength="10" inputmode="numeric" :requerido="true" ayuda="10 dígitos." />
                    <x-campo name="calle_numero" label="Calle y número" :value="$persona?->calle_numero" maxlength="150" :requerido="true" />
                    <x-campo name="colonia" label="Colonia" :value="$persona?->colonia" maxlength="100" :requerido="true" />
                    <x-campo name="municipio" label="Municipio" :value="$persona?->municipio" maxlength="100" :requerido="true" />
                    <x-campo name="codigo_postal" label="Código postal" :value="$persona?->codigo_postal" maxlength="5" inputmode="numeric" :requerido="true" ayuda="5 dígitos." />
                </div>
            @endif
        </section>
        <section class="form-seccion">
            <h2 class="form-seccion-titulo"><span>2</span> Interés de ingreso</h2>
            <div class="row g-3">
                <x-seleccion name="aca_carrera_id" label="Carrera" col="col-md-6" :opciones="$carreras" :valor="$aspirante->aca_carrera_id" placeholder="Selecciona una carrera" :requerido="true" />
                <x-seleccion name="cat_medio_difusion_id" label="¿Cómo se enteró?" col="col-md-6" :opciones="$medios" :valor="$aspirante->cat_medio_difusion_id" placeholder="Selecciona un medio" :requerido="true" />
                <x-campo name="escuela_procedencia" label="Escuela de procedencia" col="col-md-8" :value="$aspirante->escuela_procedencia" maxlength="150" :requerido="true" />
                <x-campo name="ciclo_ingreso" label="Ciclo de ingreso" :value="$aspirante->ciclo_ingreso" maxlength="9" class="mono" placeholder="AAAA-AAAA" :requerido="true" ayuda="Por ejemplo 2027-2028. Un registro por persona y ciclo." />
            </div>
        </section>
        @if ($editando)
            <section class="form-seccion">
                <h2 class="form-seccion-titulo"><span>3</span> Motivo del cambio</h2>
                <div class="row g-3"><x-campo name="motivo" label="Motivo del cambio" col="col-12" maxlength="255" :requerido="true" ayuda="Queda registrado en la bitácora junto con los cambios." /></div>
            </section>
        @endif
        <div class="form-acciones">
            <a href="{{ $editando ? route('aspirantes.show', $aspirante) : route('aspirantes.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            <button type="submit" class="btn btn-siges">{{ $editando ? 'Guardar cambios' : 'Registrar aspirante' }}</button>
        </div>
    </form>
@endsection
