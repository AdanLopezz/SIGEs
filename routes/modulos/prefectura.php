<?php

use App\Http\Controllers\Prefectura\AccesoController;
use App\Http\Controllers\Prefectura\AsistenciaController;
use App\Http\Controllers\Prefectura\PermisoSalidaController;
use Illuminate\Support\Facades\Route;

Route::prefix('prefectura')->group(function () {
    Route::middleware('permission:acceso.registrar')->group(function () {
        Route::get('acceso', [AccesoController::class, 'index'])->name('acceso.index');
        // Un lector en la puerta puede leer muchas credenciales seguidas.
        Route::post('acceso', [AccesoController::class, 'registrar'])->middleware('throttle:120,1')->name('acceso.registrar');
        Route::post('acceso/manual', [AccesoController::class, 'manual'])->name('acceso.manual');
        Route::post('asistencia/cerrar', [AsistenciaController::class, 'cerrar'])->name('asistencia.cerrar');
    });

    // Consulta (asistencia.ver) o quien puede justificar (incidencias.cerrar o alumnos.editar); cada Request afina la regla.
    Route::middleware('permission:asistencia.ver|incidencias.cerrar|alumnos.editar')->group(function () {
        Route::get('asistencia', [AsistenciaController::class, 'index'])->name('asistencia.index');
        Route::patch('asistencia/{asistencia}/justificar', [AsistenciaController::class, 'justificar'])->name('asistencia.justificar');
    });

    Route::middleware('permission:permisos_salida.administrar')->group(function () {
        Route::get('permisos-salida', [PermisoSalidaController::class, 'index'])->name('permisos-salida.index');
        Route::post('permisos-salida', [PermisoSalidaController::class, 'store'])->name('permisos-salida.store');
        Route::post('permisos-salida/{permiso}/confirmar', [PermisoSalidaController::class, 'confirmar'])->name('permisos-salida.confirmar');
        Route::patch('permisos-salida/{permiso}/cancelar', [PermisoSalidaController::class, 'cancelar'])->name('permisos-salida.cancelar');
    });
});
