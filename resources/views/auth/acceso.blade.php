<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIGEs | Acceso</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="auth-body">
<div class="auth-wrapper">
    <div class="auth-container" id="authContainer">

        {{-- Inicio de sesión --}}
        <div class="form-container login-container">
            <form action="{{ route('login.store') }}" method="POST" class="auth-form">
                @csrf

                <div class="form-header">
                    <h1>Bienvenido</h1>
                    <p>Ingresa tus credenciales para continuar.</p>
                </div>

                @if (session('status'))
                    <div class="alert alert-success auth-alert">
                        {{ session('status') }}
                    </div>
                @endif

                <div class="form-floating mb-3">
                    <input type="email" name="email" id="loginEmail"
                           class="form-control @error('email', 'login') is-invalid @enderror"
                           placeholder="Usuario"
                           value="{{ $loginEmail ?? ($errors->getBag('login')->any() ? old('email') : '') }}"
                           autocomplete="username" required autofocus>
                    <label for="loginEmail">Usuario</label>
                </div>

                <div class="form-floating mb-3 password-field">
                    <input type="password" name="password" id="loginPassword"
                           class="form-control" placeholder="Contraseña"
                           autocomplete="current-password" required>
                    <label for="loginPassword">Contraseña</label>
                    <button type="button" class="password-toggle" data-password-target="loginPassword"
                            aria-label="Mostrar contraseña">
                        <span class="eye-icon">◉</span>
                    </button>
                </div>

                <div class="login-options">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember" value="1"
                            @checked($rememberLogin ?? ($errors->getBag('login')->any() && old('remember')))>
                        <label class="form-check-label" for="remember">Recordarme</label>
                    </div>

                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="forgot-link">¿Olvidaste tu contraseña?</a>
                    @endif
                </div>

                @if ($errors->getBag('login')->any())
                    <div class="alert alert-danger auth-alert">
                        @foreach ($errors->getBag('login')->all() as $message)
                            <div>{{ $message }}</div>
                        @endforeach
                    </div>
                @endif

                <button type="submit" class="btn auth-primary-btn">Iniciar sesión</button>

                {{-- En celular el panel azul se oculta: se muestra este aviso --}}
                <div class="mobile-switch">
                    <span>¿No tienes cuenta? Solicítala al administrador o a Control Escolar.</span>
                </div>
            </form>
        </div>

        {{-- Panel informativo --}}
        <div class="overlay-container">
            <div class="overlay">
                <div class="overlay-panel overlay-right">
                    <div class="overlay-decoration decoration-three"></div>
                    <div class="overlay-decoration decoration-four"></div>

                    <div class="overlay-content">
                        <div class="system-icon">
                            <img src="{{ asset('img/logo.png') }}" alt="Logo SIGEs">
                        </div>

                        <h2 class="overlay-title">Bienvenido a SIGEs</h2>
                        <p>Sistema Integral de Gestión Escolar.</p>
                        <p class="overlay-note">
                            ¿Aún no tienes cuenta? El personal la solicita al administrador del sistema;
                            alumnos y padres de familia la reciben de Control Escolar al inscribirse.
                        </p>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
</body>
</html>
