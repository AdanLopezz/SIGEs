<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('titulo', 'Impresión') | SIGEs</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
{{-- Página para imprimir: sin menú ni barra superior. --}}
<body class="impresion-body">
    @yield('contenido')
    @stack('modales')
</body>
</html>
