<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIGEs | Recuperar contraseña</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="auth-body">
<div class="auth-wrapper">
    <div class="auth-container">

        <div class="form-container">
            <form action="{{ route('password.email') }}" method="POST" class="auth-form">
                @csrf

                <div class="form-header">
                    <h1>Recuperar contraseña</h1>
                    <p>Escribe tu usuario o tu correo personal. Te enviaremos a tu correo personal un enlace para crear una nueva.</p>
                </div>

                @if (session('status'))
                    <div class="alert alert-success auth-alert">{{ session('status') }}</div>
                @endif

                <div class="form-floating mb-4">
                    <input type="text" name="email" id="email" value="{{ old('email') }}"
                           class="form-control @error('email') is-invalid @enderror"
                           placeholder="Usuario o correo personal" autocomplete="username" required autofocus>
                    <label for="email">Usuario o correo personal</label>
                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <button type="submit" class="btn auth-primary-btn">Enviar enlace</button>

                <a href="{{ route('login') }}" class="forgot-link auth-back">← Volver a iniciar sesión</a>
            </form>
        </div>

        <div class="overlay-container">
            <div class="overlay">
                <div class="overlay-panel">
                    <div class="overlay-decoration decoration-three"></div>
                    <div class="overlay-decoration decoration-four"></div>

                    <div class="overlay-content">
                        <div class="system-icon">
                            <img src="{{ asset('img/logo.png') }}" alt="Logo SIGEs">
                        </div>
                        <h2 class="overlay-title">Recupera tu acceso</h2>

                        <ol class="overlay-steps">
                            <li><span>1</span>Escribe tu correo de acceso.</li>
                            <li><span>2</span>Abre el enlace que te enviamos.</li>
                            <li><span>3</span>Crea tu nueva contraseña.</li>
                        </ol>

                        <p class="overlay-note">El enlace caduca en 60 minutos. Si no lo recibes, revisa la carpeta de spam o acude con el administrador del sistema.</p>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
</body>
</html>
