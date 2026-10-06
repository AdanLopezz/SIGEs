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
    <div class="auth-container {{ ($panel ?? 'login') === 'register' ? 'register-active' : '' }}" id="authContainer">

        <div class="form-container register-container">
            <form action="{{ route('register.store') }}" method="POST" class="auth-form">
                @csrf

                <div class="form-header">
                    <h1>Crear cuenta</h1>
                    <p>Registra tus datos para ingresar al Sistema Integral de Gestión Escolar.</p>
                </div>

                <div class="form-floating mb-3">
                    <input type="text" name="name" id="registerName"
                           class="form-control @error('name', 'register') is-invalid @enderror"
                           placeholder="Nombre completo"
                           value="{{ $errors->getBag('register')->any() ? old('name') : '' }}"
                           autocomplete="name" required
                           @if (($panel ?? 'login') === 'register') autofocus @endif>
                    <label for="registerName">Nombre completo</label>
                    @error('name', 'register')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-floating mb-3">
                    <input type="email" name="email" id="registerEmail"
                           class="form-control @error('email', 'register') is-invalid @enderror"
                           placeholder="correo@ejemplo.com"
                           value="{{ $errors->getBag('register')->any() ? old('email') : '' }}"
                           autocomplete="email" required>
                    <label for="registerEmail">Correo electrónico</label>
                    @error('email', 'register')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-floating mb-3 password-field">
                    <input type="password" name="password" id="registerPassword"
                           class="form-control @error('password', 'register') is-invalid @enderror"
                           placeholder="Contraseña" autocomplete="new-password" required>
                    <label for="registerPassword">Contraseña</label>
                    <button type="button" class="password-toggle" data-password-target="registerPassword"
                            aria-label="Mostrar contraseña">
                        <span class="eye-icon">◉</span>
                    </button>
                    @error('password', 'register')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-floating mb-4 password-field">
                    <input type="password" name="password_confirmation" id="registerPasswordConfirmation"
                           class="form-control" placeholder="Confirmar contraseña"
                           autocomplete="new-password" required>
                    <label for="registerPasswordConfirmation">Confirmar contraseña</label>
                    <button type="button" class="password-toggle"
                            data-password-target="registerPasswordConfirmation"
                            aria-label="Mostrar contraseña">
                        <span class="eye-icon">◉</span>
                    </button>
                </div>

                <button type="submit" class="btn auth-primary-btn">Crear cuenta</button>

                <div class="mobile-switch">
                    <span>¿Ya tienes cuenta?</span>
                    <button type="button" id="mobileSignIn">Iniciar sesión</button>
                </div>
            </form>
        </div>

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
                           placeholder="correo@ejemplo.com"
                           value="{{ $loginEmail ?? ($errors->getBag('login')->any() ? old('email') : '') }}"
                           autocomplete="email" required>
                    <label for="loginEmail">Correo electrónico</label>
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

                <div class="mobile-switch">
                    <span>¿No tienes cuenta?</span>
                    <button type="button" id="mobileSignUp">Crear cuenta</button>
                </div>
            </form>
        </div>

        <div class="overlay-container">
            <div class="overlay">

                <div class="overlay-panel overlay-left">
                    <div class="overlay-decoration decoration-one"></div>
                    <div class="overlay-decoration decoration-two"></div>

                    <div class="overlay-content">
                        <div class="system-icon">
                            <img src="{{ asset('img/logo.png') }}" alt="Logo SIGEs">
                        </div>


                        <h2 class="overlay-title">¡Bienvenido de nuevo!</h2>
                        <p>Accede a tu cuenta para consultar y administrar la información académica.</p>

                        <button type="button" class="btn overlay-btn" id="signInBtn">
                            Iniciar sesión
                        </button>
                    </div>
                </div>

                <div class="overlay-panel overlay-right">
                    <div class="overlay-decoration decoration-three"></div>
                    <div class="overlay-decoration decoration-four"></div>

                    <div class="overlay-content">
                        <div class="system-icon">
                            <img src="{{ asset('img/logo.png') }}" alt="Logo SIGEs">
                        </div>

                        <h2 class="overlay-title">Forma parte de SIGEs</h2>
                        <p>Crea una cuenta para acceder a los servicios y herramientas de gestión académica.</p>

                        <button type="button" class="btn overlay-btn" id="signUpBtn">
                            Crear cuenta
                        </button>
                    </div>
                </div>

            </div>
        </div>

    </div>
</div>
</body>
</html>
