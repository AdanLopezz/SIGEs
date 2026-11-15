{{-- Buscador de alumno (GET ?buscar=…): campo y lupa en un input-group. Lo usan Control de acceso, Permisos de salida e Incidencias. --}}
@props(['accion', 'buscar' => null])
<form method="GET" action="{{ $accion }}" {{ $attributes->class(['mb-3']) }} role="search">
    <label for="buscar" class="form-label">Buscar alumno</label>
    <div class="input-group">
        <input id="buscar" name="buscar" class="form-control @error('buscar') is-invalid @enderror" value="{{ $buscar }}" maxlength="100" placeholder="Nombre, matrícula o CURP"
            @error('buscar') aria-invalid="true" aria-describedby="buscar-error" @enderror>
        <button class="btn btn-outline-siges" type="submit" aria-label="Buscar"><i class="bi bi-search" aria-hidden="true"></i></button>
        @error('buscar')<div class="invalid-feedback" id="buscar-error">{{ $message }}</div>@enderror
    </div>
</form>
