<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\RecuperarContrasenaController;
use App\Http\Controllers\Panel\ContrasenaController;
use App\Http\Controllers\Panel\DashboardController;
use App\Http\Middleware\NormalizeAuthEmail;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use Laravel\Fortify\Http\Controllers\NewPasswordController;
use Laravel\Fortify\Http\Controllers\PasswordResetLinkController;

/*
Visitantes (sin sesión)
*/
Route::middleware('guest:web')->group(function () {
    Route::get('/', [AuthController::class, 'showAccess'])->name('inicio');
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');

    // Pantallas para recuperar o crear contraseña
    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');

    // Formularios que reciben un correo: se normaliza (minúsculas y sin espacios)
    Route::middleware(NormalizeAuthEmail::class)->group(function () {
        Route::post('/login', [AuthenticatedSessionController::class, 'store'])
            ->middleware('throttle:login')->name('login.store');

        // Propio (no el de Fortify): acepta usuario o correo personal y el enlace llega al correo personal.
        Route::post('/forgot-password', [RecuperarContrasenaController::class, 'store'])
            ->middleware('throttle:6,1')->name('password.email');

        Route::post('/reset-password', [NewPasswordController::class, 'store'])
            ->middleware('throttle:6,1')->name('password.update');
    });
});

/*
 Usuarios con sesión
*/
Route::middleware('auth:web')->group(function () {
    // Cerrar sesión: siempre disponible, aunque la cuenta no tenga rol
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    // Todo el sistema: requiere al menos un rol
    Route::middleware('con.rol')->group(function () {
        // Cambiar mi contraseña: disponible aunque la cuenta tenga que cambiar su contraseña temporal.
        Route::get('/mi-cuenta/contrasena', [ContrasenaController::class, 'edit'])->name('mi-cuenta.contrasena.edit');
        Route::put('/mi-cuenta/contrasena', [ContrasenaController::class, 'update'])->middleware('throttle:6,1')->name('mi-cuenta.contrasena.update');
    });

    // Todo lo demás: además, sin contraseña temporal pendiente de cambiar
    Route::middleware(['con.rol', 'cambio.contrasena'])->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
        require __DIR__.'/modulos/periodo.php';
        require __DIR__.'/modulos/cuentas.php';
        require __DIR__.'/modulos/administracion.php';
        require __DIR__.'/modulos/configuracion.php';
        require __DIR__.'/modulos/academico.php';
        require __DIR__.'/modulos/calificaciones.php';
        require __DIR__.'/modulos/horarios.php';
        require __DIR__.'/modulos/archivos.php';
        require __DIR__.'/modulos/control-escolar.php';
        require __DIR__.'/modulos/auditoria.php';
        require __DIR__.'/modulos/prefectura.php';
        require __DIR__.'/modulos/orientacion.php';
        require __DIR__.'/modulos/vinculacion.php';
        require __DIR__.'/modulos/reportes.php';
        require __DIR__.'/modulos/portal.php';
    });
});
