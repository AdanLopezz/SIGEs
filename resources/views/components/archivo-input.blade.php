{{-- El <form> que lo use debe llevar enctype="multipart/form-data". --}}
@props(['name', 'label', 'col' => 'col-md-4', 'ayuda' => 'PDF o imagen, máximo 10 MB.', 'requerido' => false])
<div class="{{ $col }}">
    <label for="{{ $name }}" class="form-label">{{ $label }} @if ($requerido)<span class="requerido" aria-hidden="true">*</span>@endif</label>
    <input {{ $attributes->class(['form-control', 'is-invalid' => $errors->has($name)]) }}
           type="file" name="{{ $name }}" id="{{ $name }}" accept=".pdf,.jpg,.jpeg,.png" @required($requerido)
           @if ($errors->has($name)) aria-invalid="true" aria-describedby="{{ $name }}-error" @elseif ($ayuda) aria-describedby="{{ $name }}-ayuda" @endif>
    @error($name)<div class="invalid-feedback" id="{{ $name }}-error">{{ $message }}</div>@enderror
    @if ($ayuda)<div class="form-text" id="{{ $name }}-ayuda">{{ $ayuda }}</div>@endif
    @if ($errors->any())<div class="form-text texto-peligro"><i class="bi bi-exclamation-circle" aria-hidden="true"></i> Vuelve a seleccionar el archivo; el navegador no lo conserva tras un error.</div>@endif
</div>
