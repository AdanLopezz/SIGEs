<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIGEs</title>
    @vite(['resources/css/app.css'])
</head>
<body class="dashboard-body">
<main class="dashboard-rainbow">
    <img src="{{ asset('img/siges.png') }}" alt="Logo SIGEs" class="dashboard-rainbow-logo">

    <form action="{{ route('logout') }}" method="POST">
        @csrf
        <button type="submit" class="dashboard-logout-icon" aria-label="Cerrar sesión" title="Cerrar sesión">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M9 8V5a2 2 0 0 1 2-2h7a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-7a2 2 0 0 1-2-2v-3"/>
                <path d="M14 12H3"/>
                <path d="m7 8-4 4 4 4"/>
            </svg>
        </button>
    </form>
</main>
</body>
</html>
