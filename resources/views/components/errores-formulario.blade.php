{{-- Alerta general de un formulario con errores: el aviso y la lista de todos los errores. --}}
@if ($errors->any())
    <x-aviso tipo="peligro">
        Revisa los campos señalados antes de guardar:
        <ul>
            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </x-aviso>
@endif
