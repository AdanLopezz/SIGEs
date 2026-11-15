@extends('layouts.app')
@php
    $editando = $usuario->exists;
    $persona = $usuario->persona;
    $personal = $persona?->personal;
    $laboralesObligatorios = ! $editando || $personal !== null;
    $rolesElegidos = session()->hasOldInput() ? old('roles', []) : $usuario->roles->pluck('name')->all();
    $rolesElegidos = is_array($rolesElegidos) ? $rolesElegidos : [];
@endphp
@section('titulo', $editando ? 'Editar usuario' : 'Nuevo usuario')
@section('contenido')
    <x-page-header :titulo="$editando ? 'Editar usuario' : 'Nuevo usuario'" :subtitulo="$editando ? 'Actualiza los datos y roles de la cuenta.' : 'Registra al personal. El sistema genera su usuario y una contraseña temporal.'" />
    <x-errores-formulario />
    @if ($areas->isEmpty())<x-aviso tipo="aviso">Aún no hay áreas disponibles. Es necesario contar con un área para registrar los datos laborales.</x-aviso>@endif
    <form method="POST" action="{{ $editando ? route('usuarios.update', $usuario) : route('usuarios.store') }}" novalidate>
        @csrf
        @if ($editando) @method('PUT') @endif
        <section class="form-seccion">
            <h2 class="form-seccion-titulo"><span>1</span> Datos personales</h2>
            <div class="row g-3">
                @if ($editando)
                    <div class="col-md-4"><label for="curp" class="form-label"><i class="bi bi-lock" aria-hidden="true"></i> CURP</label><input id="curp" class="form-control" value="{{ $persona?->curp }}" disabled><div class="form-text">La CURP no se modifica desde esta pantalla.</div></div>
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
            <h2 class="form-seccion-titulo"><span>2</span> Datos laborales</h2>
            @unless ($laboralesObligatorios)<p class="texto-tenue">Opcionales para esta cuenta. Si capturas uno, completa los tres campos.</p>@endunless
            <div class="row g-3">
                <x-campo name="numero_empleado" label="Número de empleado" :value="$personal?->numero_empleado" maxlength="50" :requerido="$laboralesObligatorios" />
                <x-seleccion name="org_area_id" label="Área" :opciones="$areas" :valor="$personal?->org_area_id" placeholder="Selecciona un área" :requerido="$laboralesObligatorios" />
                <x-campo name="puesto" label="Puesto" :value="$personal?->puesto" maxlength="80" :requerido="$laboralesObligatorios" />
            </div>
        </section>
        <section class="form-seccion" id="roles">
            <h2 class="form-seccion-titulo"><span>3</span> Cuenta y roles</h2>
            <div class="row g-3 mb-4">
                @if ($editando)
                    <x-campo-usuario :value="$usuario->email" :editable="true" nombre="nombre" apellido="primer_apellido"
                        ayuda="Se usa para iniciar sesión. No recibe correos: los enlaces de contraseña llegan al correo personal." />
                @else
                    <x-campo-usuario :automatico="true" nombre="nombre" apellido="primer_apellido"
                        ayuda="Lo genera el sistema con el nombre y el primer apellido. Al crear la cuenta se mostrará con una contraseña temporal para entregarlos." />
                @endif
            </div>
            <p class="form-label">Roles <span class="requerido" aria-hidden="true">*</span></p>
            <div class="roles-grid">
                @foreach ($roles as $rol)
                    <label class="rol-opcion"><input class="form-check-input" type="checkbox" name="roles[]" value="{{ $rol }}" @checked(in_array($rol, $rolesElegidos, true))><span>{{ $rol }}</span></label>
                @endforeach
            </div>
            @error('roles')<div class="texto-peligro small mt-2">{{ $message }}</div>@enderror
            @foreach ($errors->get('roles.*') as $mensajes) @foreach ($mensajes as $mensaje)<div class="texto-peligro small mt-2">{{ $mensaje }}</div>@endforeach @endforeach
            <p class="form-text">Puedes seleccionar varios roles. Alumno y Padre de familia se gestionan desde Control Escolar; si ya los tiene, se conservarán.</p>
        </section>
        @if ($editando)
            <section class="form-seccion">
                <h2 class="form-seccion-titulo"><span>4</span> Motivo del cambio</h2>
                <div class="row g-3"><x-campo name="motivo" label="Motivo del cambio" col="col-12" maxlength="255" :requerido="true" ayuda="Queda registrado en la bitácora junto con los cambios." /></div>
            </section>
        @endif
        <div class="form-acciones"><a href="{{ route('usuarios.index') }}" class="btn btn-outline-secondary">Cancelar</a><button type="submit" class="btn btn-siges">{{ $editando ? 'Guardar cambios' : 'Crear usuario' }}</button></div>
    </form>
    @if ($editando)<x-historial class="mt-4" tabla="users" :registro="$usuario->id" :relacionados="$historial" />@endif
@endsection
