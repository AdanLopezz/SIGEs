{{-- Campos de una nota (agregar, nota final de Cerrar y de Continuar). $bolsa = bolsa de errores del formulario:
     old() y los errores solo se usan si ese formulario fue el que regresó con errores (los demás comparten los nombres). --}}
@php
    $errores = $errors->getBag($bolsa);
    $propio = $errores->any();
    $valor = fn (string $campo, $omision = '') => $propio ? old($campo, $omision) : $omision;
@endphp
<div class="row g-3">
    @if ($conFecha)
        <div class="col-md-6">
            <label for="{{ $prefijo }}Fecha" class="form-label">Fecha de la atención <span class="requerido" aria-hidden="true">*</span></label>
            <input type="date" id="{{ $prefijo }}Fecha" name="fecha" required max="{{ today()->format('Y-m-d') }}" min="{{ $minimo }}" value="{{ $valor('fecha', today()->format('Y-m-d')) }}"
                class="form-control @if ($errores->has('fecha')) is-invalid @endif">
            @if ($errores->has('fecha'))<div class="invalid-feedback">{{ $errores->first('fecha') }}</div>@endif
        </div>
    @endif
    <div class="{{ $conFecha ? 'col-md-6' : 'col-12' }}">
        <label for="{{ $prefijo }}Tipo" class="form-label">Tipo de atención <span class="requerido" aria-hidden="true">*</span></label>
        <select id="{{ $prefijo }}Tipo" name="tipo_atencion" required class="form-select @if ($errores->has('tipo_atencion')) is-invalid @endif">
            <option value="">Selecciona un tipo</option>
            @foreach ($tipos as $clave => $nombre)
                <option value="{{ $clave }}" @selected($valor('tipo_atencion') === $clave)>{{ $nombre }}</option>
            @endforeach
        </select>
        @if ($errores->has('tipo_atencion'))<div class="invalid-feedback">{{ $errores->first('tipo_atencion') }}</div>@endif
    </div>
    <div class="col-12">
        <label for="{{ $prefijo }}Descripcion" class="form-label">Descripción <span class="requerido" aria-hidden="true">*</span></label>
        <textarea id="{{ $prefijo }}Descripcion" name="descripcion" rows="3" minlength="10" maxlength="4000" required class="form-control @if ($errores->has('descripcion')) is-invalid @endif">{{ $valor('descripcion') }}</textarea>
        @if ($errores->has('descripcion'))<div class="invalid-feedback">{{ $errores->first('descripcion') }}</div>@endif
    </div>
    <div class="col-12">
        <label for="{{ $prefijo }}Acuerdos" class="form-label">Acuerdos <span class="requerido" aria-hidden="true">*</span></label>
        <textarea id="{{ $prefijo }}Acuerdos" name="acuerdos" rows="3" minlength="5" maxlength="4000" required class="form-control @if ($errores->has('acuerdos')) is-invalid @endif">{{ $valor('acuerdos') }}</textarea>
        @if ($errores->has('acuerdos'))<div class="invalid-feedback">{{ $errores->first('acuerdos') }}</div>@endif
    </div>
    @if ($conMotivo)
        <div class="col-12">
            <label for="{{ $prefijo }}Motivo" class="form-label">Motivo (bitácora) <span class="requerido" aria-hidden="true">*</span></label>
            <input type="text" id="{{ $prefijo }}Motivo" name="motivo" maxlength="255" required value="{{ $valor('motivo') }}" class="form-control @if ($errores->has('motivo')) is-invalid @endif">
            @if ($errores->has('motivo'))<div class="invalid-feedback">{{ $errores->first('motivo') }}</div>@endif
        </div>
    @endif
</div>
