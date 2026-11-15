{{-- Select con label, old(), error y ayuda (igual que <x-campo>). :opciones = [valor => texto]; el slot admite opciones especiales.
     :bloqueado → candado en la etiqueta y campo deshabilitado (no se envía). --}}
@props(['name', 'label', 'opciones' => [], 'valor' => null, 'placeholder' => null, 'requerido' => false, 'ayuda' => null, 'col' => 'col-md-4', 'id' => null, 'bloqueado' => false])
@php
    $id ??= $name;
    $actual = old($name, $valor);
@endphp
<div class="{{ $col }}">
    <label for="{{ $id }}" class="form-label">@if ($bloqueado)<i class="bi bi-lock" aria-hidden="true"></i> @endif{{ $label }} @if ($requerido)<span class="requerido" aria-hidden="true">*</span>@endif</label>
    <select {{ $attributes->class(['form-select', 'is-invalid' => $errors->has($name)]) }} name="{{ $name }}" id="{{ $id }}" @required($requerido) @disabled($bloqueado)
            @if ($errors->has($name)) aria-invalid="true" aria-describedby="{{ $id }}-error" @elseif ($ayuda) aria-describedby="{{ $id }}-ayuda" @endif>
        @if ($placeholder !== null)<option value="">{{ $placeholder }}</option>@endif
        @foreach ($opciones as $valorOpcion => $texto)
            <option value="{{ $valorOpcion }}" @selected((string) $actual === (string) $valorOpcion)>{{ $texto }}</option>
        @endforeach
        {{ $slot }}
    </select>
    @error($name)<div class="invalid-feedback" id="{{ $id }}-error">{{ $message }}</div>@enderror
    @if ($ayuda)<div class="form-text" id="{{ $id }}-ayuda">{{ $ayuda }}</div>@endif
</div>
