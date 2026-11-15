<?php

use App\Http\Controllers\Administracion\AccesoUsuarioController;
use App\Http\Controllers\Administracion\UsuarioController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:usuarios.administrar')->group(function () {
    Route::resource('usuarios', UsuarioController::class)->parameters(['usuarios' => 'usuario'])->except(['show', 'destroy']);
    // Datos de acceso (usuario y contraseña temporal); cada consulta queda en la bitácora.
    Route::get('usuarios/{usuario}/acceso', [AccesoUsuarioController::class, 'show'])->name('usuarios.acceso');
    Route::get('usuarios/{usuario}/acceso/imprimir', [AccesoUsuarioController::class, 'imprimir'])->name('usuarios.acceso.imprimir');
    Route::patch('usuarios/{usuario}/acceso/nueva-contrasena', [AccesoUsuarioController::class, 'nuevaContrasena'])
        ->middleware('throttle:10,1')->name('usuarios.acceso.nueva-contrasena');
    Route::patch('usuarios/{usuario}/quitar-acceso', [UsuarioController::class, 'quitarAcceso'])->name('usuarios.quitar-acceso');
});
