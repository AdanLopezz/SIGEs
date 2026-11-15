<?php

use App\Http\Controllers\Portal\AvisoController;
use App\Http\Controllers\Portal\HijoController;
use App\Http\Controllers\Portal\PermisoPortalController;
use App\Http\Controllers\Portal\PortalController;
use Illuminate\Support\Facades\Route;

// Portal de Alumno ("Mi escuela") y Padre de familia ("Mis hijos"). Sin permisos de Spatie: rol + PortalPolicy.
// El alumno nunca va en la URL: sale de la cuenta o del hijo elegido en la sesión (ResuelveAlumnoDelPortal).
Route::middleware('role:Padre de familia')->prefix('mis-hijos')->name('mis-hijos.')->group(function () {
    Route::get('/', [HijoController::class, 'index'])->name('seleccion.index');
    Route::post('{alumno}/elegir', [HijoController::class, 'elegir'])->name('seleccion.elegir');
    Route::post('permisos/{permiso}/confirmar', [PermisoPortalController::class, 'confirmar'])->name('permisos.confirmar');
});

foreach (['mi-escuela' => 'Alumno', 'mis-hijos' => 'Padre de familia'] as $prefijo => $rol) {
    Route::middleware("role:{$rol}")->prefix($prefijo)->name("{$prefijo}.")->group(function () {
        Route::get('calificaciones', [PortalController::class, 'calificaciones'])->name('calificaciones.index');
        Route::get('horario', [PortalController::class, 'horario'])->name('horario.index');
        Route::get('asistencia', [PortalController::class, 'asistencia'])->name('asistencia.index');
        Route::get('incidencias', [PortalController::class, 'incidencias'])->name('incidencias.index');
        Route::get('permisos', [PortalController::class, 'permisos'])->name('permisos.index');
        Route::get('credencial', [PortalController::class, 'credencial'])->name('credencial.index');
        Route::get('datos', [PortalController::class, 'datos'])->name('datos.index');
    });
}

Route::middleware('role:Alumno|Padre de familia')->group(function () {
    Route::get('avisos', [AvisoController::class, 'index'])->name('avisos.index');
    Route::get('avisos/{aviso}', [AvisoController::class, 'show'])->name('avisos.show');
});
