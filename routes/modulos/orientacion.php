<?php

use App\Http\Controllers\Orientacion\IncidenciaController;
use App\Http\Controllers\Orientacion\SeguimientoController;
use Illuminate\Support\Facades\Route;

// La visibilidad la decide IncidenciaPolicy (TrxIncidencia::scopeVisiblesPara).
Route::middleware('permission:incidencias.registrar|incidencias.cerrar')->group(function () {
    Route::get('incidencias', [IncidenciaController::class, 'index'])->name('incidencias.index');
    // Va antes que incidencias/{incidencia} para que "create" no se tome como id.
    Route::middleware('permission:incidencias.registrar')->group(function () {
        Route::get('incidencias/create', [IncidenciaController::class, 'create'])->name('incidencias.create');
        Route::post('incidencias', [IncidenciaController::class, 'store'])->name('incidencias.store');
    });
    Route::get('incidencias/{incidencia}', [IncidenciaController::class, 'show'])->middleware('can:ver,incidencia')->name('incidencias.show');
    Route::get('incidencias/{incidencia}/edit', [IncidenciaController::class, 'edit'])->middleware('can:editar,incidencia')->name('incidencias.edit');
    Route::put('incidencias/{incidencia}', [IncidenciaController::class, 'update'])->middleware('can:editar,incidencia')->name('incidencias.update');
    Route::patch('incidencias/{incidencia}/seguimiento', [IncidenciaController::class, 'seguimiento'])->middleware('can:seguimiento,incidencia')->name('incidencias.seguimiento');
    Route::patch('incidencias/{incidencia}/cerrar', [IncidenciaController::class, 'cerrar'])->middleware('can:cerrar,incidencia')->name('incidencias.cerrar');
});

// Seguimiento del alumno: visibilidad con SeguimientoPolicy (TrxSeguimientoAlumno::scopeVisiblesPara). Nunca en el portal.
Route::middleware('permission:seguimiento.administrar')->group(function () {
    Route::get('seguimiento', [SeguimientoController::class, 'index'])->name('seguimiento.index');
    // Van antes que seguimiento/{seguimiento} para que no se tomen como id.
    Route::get('seguimiento/create', [SeguimientoController::class, 'create'])->name('seguimiento.create');
    Route::get('seguimiento/alumnos-a-atender', [SeguimientoController::class, 'alumnos'])->name('seguimiento.alumnos');
    Route::get('seguimiento/resumen', [SeguimientoController::class, 'resumen'])->name('seguimiento.resumen');
    Route::post('seguimiento', [SeguimientoController::class, 'store'])->name('seguimiento.store');
    Route::get('seguimiento/{seguimiento}', [SeguimientoController::class, 'show'])->middleware('can:ver,seguimiento')->name('seguimiento.show');
    Route::post('seguimiento/{seguimiento}/notas', [SeguimientoController::class, 'nota'])->name('seguimiento.nota');
    Route::patch('seguimiento/{seguimiento}/cerrar', [SeguimientoController::class, 'cerrar'])->name('seguimiento.cerrar');
    Route::post('seguimiento/{seguimiento}/continuar', [SeguimientoController::class, 'continuar'])->name('seguimiento.continuar');
    Route::patch('seguimiento/{seguimiento}/reabrir', [SeguimientoController::class, 'reabrir'])->name('seguimiento.reabrir');
    Route::patch('seguimiento/{seguimiento}/riesgo', [SeguimientoController::class, 'riesgo'])->name('seguimiento.riesgo');
});
