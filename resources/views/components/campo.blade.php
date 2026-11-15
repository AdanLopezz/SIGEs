@props(['name', 'label', 'value' => '', 'type' => 'text', 'col' => 'col-md-4', 'ayuda' => null, 'requerido' => false, 'id' => null])
@php $id ??= $name; @endphp
<div class="{{ $col }}">
    <label for="{{ $id }}" class="form-label">{{ $label }} @if ($requerido)<span class="requerido" aria-hidden="true">*</span>@endif</label>
    <input {{ $attributes->class(['form-control', 'is-invalid' => $errors->has($name)]) }}
           type="{{ $type }}" name="{{ $name }}" id="{{ $id }}"
           value="{{ is_scalar(old($name, $value)) ? old($name, $value) : '' }}" @required($requerido)
           @if ($errors->has($name)) aria-invalid="true" aria-describedby="{{ $id }}-error" @elseif ($ayuda) aria-describedby="{{ $id }}-ayuda" @endif>
    @error($name)<div class="invalid-feedback" id="{{ $id }}-error">{{ $message }}</div>@enderror
    @if ($ayuda)<div class="form-text" id="{{ $id }}-ayuda">{{ $ayuda }}</div>@endif
</div>
