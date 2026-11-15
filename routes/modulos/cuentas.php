<?php

use App\Http\Controllers\Cuentas\GenerarUsuarioController;
use Illuminate\Support\Facades\Route;

// Usuario generado (nombre.apellido + 4 dígitos @dominio) para los formularios de Usuarios y de Control Escolar.
Route::get('cuentas/generar-usuario', GenerarUsuarioController::class)
    ->middleware(['permission:usuarios.administrar|alumnos.editar', 'throttle:60,1'])->name('cuentas.generar-usuario');
