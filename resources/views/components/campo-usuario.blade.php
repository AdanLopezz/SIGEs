{{-- Usuario generado por el sistema (nombre.apellido + 4 dígitos @dominio) con botón "Regenerar" (usuario-generado.js).
     :editable → se puede escribir (edición de Usuarios); si no, es de solo lectura.
     :automatico → se genera al escribir el nombre y el primer apellido (altas).
     nombre / apellido → id de los campos del formulario de donde se toman. Dentro de un modal, el valor y el nombre
     llegan del botón que lo abre (data-usuario, data-persona-nombre, data-persona-apellido).
     El servidor vuelve a comprobarlo al guardar: si ya no está libre o no corresponde al nombre, genera otro. --}}
@props(['name' => 'email', 'label' => 'Usuario', 'value' => '', 'col' => 'col-md-6', 'ayuda' => null, 'requerido' => true, 'id' => null,
    'editable' => false, 'automatico' => false, 'nombre' => null, 'apellido' => null])
@php
    $id ??= $name;
    $describe = collect([$errors->has($name) ? $id.'-error' : null, $ayuda ? $id.'-ayuda' : null])->filter()->implode(' ');
@endphp
<div class="{{ $col }}">
    <label for="{{ $id }}" class="form-label">{{ $label }} @if ($requerido)<span class="requerido" aria-hidden="true">*</span>@endif</label>
    <div class="input-group has-validation">
        <input {{ $attributes->class(['form-control', 'mono', 'is-invalid' => $errors->has($name)]) }}
               type="text" name="{{ $name }}" id="{{ $id }}" value="{{ is_scalar(old($name, $value)) ? old($name, $value) : '' }}"
               maxlength="255" autocomplete="off" spellcheck="false" @readonly(! $editable)
               placeholder="Se genera con el nombre y el primer apellido"
               data-usuario-generado data-url="{{ route('cuentas.generar-usuario') }}"
               @if ($nombre) data-nombre="{{ $nombre }}" @endif @if ($apellido) data-apellido="{{ $apellido }}" @endif
               @if ($automatico) data-automatico @endif
               @if ($describe !== '') aria-describedby="{{ $describe }}" @endif @if ($errors->has($name)) aria-invalid="true" @endif>
        <button type="button" class="btn btn-outline-siges" data-regenerar-usuario="{{ $id }}"><i class="bi bi-arrow-repeat" aria-hidden="true"></i> Regenerar</button>
        @error($name)<div class="invalid-feedback" id="{{ $id }}-error">{{ $message }}</div>@enderror
    </div>
    @if ($ayuda)<div class="form-text" id="{{ $id }}-ayuda">{{ $ayuda }}</div>@endif
</div>
