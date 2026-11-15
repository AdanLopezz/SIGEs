@extends('layouts.app')
@php $editando = $empresa->exists; @endphp
@section('titulo', $editando ? 'Editar empresa' : 'Nueva empresa')
@section('contenido')
    <x-page-header :titulo="$editando ? 'Editar empresa' : 'Nueva empresa'" :subtitulo="$editando ? 'Los cambios quedan en la bitácora.' : 'Registra la empresa y su contacto.'"
        :volver="$editando ? [route('empresas.show', $empresa), 'Volver a la empresa'] : [route('empresas.index'), 'Volver a Empresas']" />
    <x-errores-formulario />

    <form method="POST" action="{{ $editando ? route('empresas.update', $empresa) : route('empresas.store') }}" novalidate>
        @csrf
        @if ($editando) @method('PUT') @endif
        <section class="form-seccion">
            <h2 class="form-seccion-titulo"><span>1</span> Empresa</h2>
            <div class="row g-3">
                <x-campo name="nombre" label="Nombre o razón social" col="col-md-8" :value="$empresa->nombre" maxlength="150" :requerido="true" />
                <x-campo name="rfc" label="RFC" :value="$empresa->rfc" maxlength="13" class="text-uppercase mono" :requerido="true" ayuda="12 caracteres (persona moral) o 13 (persona física)." />
            </div>
        </section>
        <section class="form-seccion">
            <h2 class="form-seccion-titulo"><span>2</span> Contacto</h2>
            <div class="row g-3">
                <x-campo name="contacto_nombre" label="Nombre del contacto" :value="$empresa->contacto_nombre" maxlength="150" :requerido="true" />
                <x-campo name="contacto_telefono" label="Teléfono" type="tel" :value="$empresa->contacto_telefono" maxlength="10" inputmode="numeric" :requerido="true" ayuda="10 dígitos." />
                <x-campo name="contacto_correo" label="Correo" type="email" :value="$empresa->contacto_correo" maxlength="150" :requerido="true" />
            </div>
        </section>
        @if ($editando)
            <section class="form-seccion">
                <h2 class="form-seccion-titulo"><span>3</span> Motivo del cambio</h2>
                <div class="row g-3"><x-campo name="motivo" label="Motivo del cambio" col="col-12" maxlength="255" :requerido="true" ayuda="Queda registrado en la bitácora junto con los cambios." /></div>
            </section>
        @endif
        <div class="form-acciones">
            <a href="{{ $editando ? route('empresas.show', $empresa) : route('empresas.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            <button type="submit" class="btn btn-siges">{{ $editando ? 'Guardar cambios' : 'Registrar empresa' }}</button>
        </div>
    </form>
@endsection
