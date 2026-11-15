<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIGEs | Nueva contraseña</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="auth-body">
<div class="auth-wrapper">
    <div class="auth-container">

        <div class="form-container">
            <form action="{{ route('password.update') }}" method="POST" class="auth-form">
                @csrf
                <input type="hidden" name="token" value="{{ $request->route('token') }}">

                <div class="form-header">
                    <h1>Nueva contraseña</h1>
                    <p>Escribe la contraseña que usarás para entrar a SIGEs.</p>
                </div>

                <div class="form-floating mb-3">
                    <input type="email" name="email" id="email" value="{{ old('email', $request->email) }}"
                           class="form-control @error('email') is-invalid @enderror"
                           placeholder="Usuario" autocomplete="username" required readonly>
                    <label for="email">Usuario</label>
                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="form-floating mb-3 password-field">

                    <input type="password" name="password" id="password"
                           class="form-control @error('password') is-invalid @enderror"
                           placeholder="Nueva contraseña" autocomplete="new-password" required autofocus>
                    <label for="password">Nueva contraseña</label>
                    <button type="button" class="password-toggle" data-password-target="password" aria-label="Mostrar contraseña">
                        <span class="eye-icon">◉</span>
                    </button>
                    @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="form-floating mb-4 password-field">
                    <input type="password" name="password_confirmation" id="password_confirmation"
                           class="form-control" placeholder="Confirmar contraseña" autocomplete="new-password" required>
                    <label for="password_confirmation">Confirmar contraseña</label>
                    <button type="button" class="password-toggle" data-password-target="password_confirmation" aria-label="Mostrar contraseña">
                        <span class="eye-icon">◉</span>
                    </button>
                </div>

                <button type="submit" class="btn auth-primary-btn">Guardar contraseña</button>
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
                        <h2 class="overlay-title">Protege tu cuenta</h2>

                        <ol class="overlay-steps">
                            <li><span>✓</span>Mínimo 8 caracteres.</li>
                            <li><span>✓</span>Mayúsculas y minúsculas.</li>
                            <li><span>✓</span>Al menos un número.</li>
                        </ol>

                        <p class="overlay-note">No compartas tu contraseña. El personal de SIGEs nunca te la pedirá.</p>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
</body>
</html>
