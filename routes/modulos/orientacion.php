<?php

use App\Http\Controllers\Orientacion\IncidenciaController;
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
