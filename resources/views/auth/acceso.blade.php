<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>SIGEs | Acceso</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="auth-body">

<div class="auth-wrapper">

    <div class="auth-container
        {{ $errors->has('name') || $errors->has('password_confirmation') ? 'register-active' : '' }}"
         id="authContainer">

        {{-- ===================================================== --}}
        {{-- REGISTRO --}}
        {{-- ===================================================== --}}

        <div class="form-container register-container">

            <form
                action="{{ route('register') }}"
                method="POST"
                class="auth-form">

                @csrf

                <div class="form-header">
                    <span class="form-badge">
                        SIGEs
                    </span>

                    <h1>Crear cuenta</h1>

                    <p>
                        Registra tus datos para ingresar al
                        Sistema Integral de Gestión Escolar.
                    </p>
                </div>

                <div class="form-floating mb-3">

                    <input
                        type="text"
                        name="name"
                        id="registerName"
                        class="form-control @error('name') is-invalid @enderror"
                        placeholder="Nombre completo"
                        value="{{ old('name') }}"
                        required
                        autofocus>

                    <label for="registerName">
                        Nombre completo
                    </label>

                    @error('name')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror

                </div>


                <div class="form-floating mb-3">

                    <input
                        type="email"
                        name="email"
                        id="registerEmail"
                        class="form-control @error('email') is-invalid @enderror"
                        placeholder="correo@ejemplo.com"
                        value="{{ old('email') }}"
                        required>

                    <label for="registerEmail">
                        Correo electrónico
                    </label>

                    @error('email')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror

                </div>


                <div class="form-floating mb-3 password-field">

                    <input
                        type="password"
                        name="password"
                        id="registerPassword"
                        class="form-control @error('password') is-invalid @enderror"
                        placeholder="Contraseña"
                        required>

                    <label for="registerPassword">
                        Contraseña
                    </label>

                    <button
                        type="button"
                        class="password-toggle"
                        data-password-target="registerPassword">

                        <span class="eye-icon">◉</span>

                    </button>

                    @error('password')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror

                </div>


                <div class="form-floating mb-4 password-field">

                    <input
                        type="password"
                        name="password_confirmation"
                        id="registerPasswordConfirmation"
                        class="form-control"
                        placeholder="Confirmar contraseña"
                        required>

                    <label for="registerPasswordConfirmation">
                        Confirmar contraseña
                    </label>

                    <button
                        type="button"
                        class="password-toggle"
                        data-password-target="registerPasswordConfirmation">

                        <span class="eye-icon">◉</span>

                    </button>

                </div>


                <button
                    type="submit"
                    class="btn auth-primary-btn">

                    Crear cuenta

                </button>


                <div class="mobile-switch">

                    <span>
                        ¿Ya tienes cuenta?
                    </span>

                    <button
                        type="button"
                        id="mobileSignIn">

                        Iniciar sesión

                    </button>

                </div>

            </form>

        </div>


        {{-- ===================================================== --}}
        {{-- LOGIN --}}
        {{-- ===================================================== --}}

        <div class="form-container login-container">

            <form
                action="{{ route('login') }}"
                method="POST"
                class="auth-form">

                @csrf

                <div class="form-header">

                    <span class="form-badge">
                        SIGEs
                    </span>

                    <h1>Bienvenido</h1>

                    <p>
                        Ingresa tus credenciales para continuar.
                    </p>

                </div>


                <div class="form-floating mb-3">

                    <input
                        type="email"
                        name="email"
                        id="loginEmail"
                        class="form-control @error('email') is-invalid @enderror"
                        placeholder="correo@ejemplo.com"
                        value="{{ old('email') }}"
                        required>

                    <label for="loginEmail">
                        Correo electrónico
                    </label>

                </div>


                <div class="form-floating mb-3 password-field">

                    <input
                        type="password"
                        name="password"
                        id="loginPassword"
                        class="form-control"
                        placeholder="Contraseña"
                        required>

                    <label for="loginPassword">
                        Contraseña
                    </label>

                    <button
                        type="button"
                        class="password-toggle"
                        data-password-target="loginPassword">

                        <span class="eye-icon">◉</span>

                    </button>

                </div>


                <div class="login-options">

                    <div class="form-check">

                        <input
                            class="form-check-input"
                            type="checkbox"
                            name="remember"
                            id="remember">

                        <label
                            class="form-check-label"
                            for="remember">

                            Recordarme

                        </label>

                    </div>


                    @if (Route::has('password.request'))

                        <a
                            href="{{ route('password.request') }}"
                            class="forgot-link">

                            ¿Olvidaste tu contraseña?

                        </a>

                    @endif

                </div>


                @if ($errors->has('email'))

                    <div class="alert alert-danger auth-alert">

                        {{ $errors->first('email') }}

                    </div>

                @endif


                <button
                    type="submit"
                    class="btn auth-primary-btn">

                    Iniciar sesión

                </button>


                <div class="mobile-switch">

                    <span>
                        ¿No tienes cuenta?
                    </span>

                    <button
                        type="button"
                        id="mobileSignUp">

                        Crear cuenta

                    </button>

                </div>

            </form>

        </div>


        {{-- ===================================================== --}}
        {{-- PANEL DESLIZANTE --}}
        {{-- ===================================================== --}}

        <div class="overlay-container">

            <div class="overlay">


                {{-- PANEL IZQUIERDO --}}

                <div class="overlay-panel overlay-left">

                    <div class="overlay-decoration decoration-one"></div>
                    <div class="overlay-decoration decoration-two"></div>

                    <div class="overlay-content">

                        <div class="system-icon">
                            <span>SG</span>
                        </div>

                        <span class="overlay-small-title">
                            SISTEMA DE GESTIÓN ESCOLAR
                        </span>

                        <h2>
                            ¡Bienvenido de nuevo!
                        </h2>

                        <p>
                            Accede a tu cuenta para consultar y administrar
                            la información académica.
                        </p>

                        <button
                            type="button"
                            class="btn overlay-btn"
                            id="signInBtn">

                            Iniciar sesión

                        </button>

                    </div>

                </div>


                {{-- PANEL DERECHO --}}

                <div class="overlay-panel overlay-right">

                    <div class="overlay-decoration decoration-three"></div>
                    <div class="overlay-decoration decoration-four"></div>

                    <div class="overlay-content">

                        <div class="system-icon">
                            <span>SG</span>
                        </div>

                        <span class="overlay-small-title">
                            SISTEMA DE GESTIÓN ESCOLAR
                        </span>

                        <h2>
                            Forma parte de SIGEs
                        </h2>

                        <p>
                            Crea una cuenta para acceder a los servicios
                            y herramientas de gestión académica.
                        </p>

                        <button
                            type="button"
                            class="btn overlay-btn"
                            id="signUpBtn">

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
