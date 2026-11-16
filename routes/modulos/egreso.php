<?php

use App\Http\Controllers\ControlEscolar\EgresoController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:egreso.administrar')->prefix('egreso')->name('egreso.')->group(function () {
    Route::get('/', [EgresoController::class, 'index'])->name('index');
    Route::get('csv', [EgresoController::class, 'csv'])->name('csv');
    Route::get('imprimir', [EgresoController::class, 'imprimir'])->name('imprimir');
    Route::get('{alumno}', [EgresoController::class, 'show'])->name('show');
    Route::get('{alumno}/procesos/{tipo}', [EgresoController::class, 'proceso'])->name('proceso');
    Route::post('{alumno}/procesos', [EgresoController::class, 'guardarProceso'])->name('procesos.guardar');
    Route::post('{alumno}/confirmar', [EgresoController::class, 'store'])->middleware('can:egreso-confirmar')->name('store');
    Route::put('{alumno}/documentacion', [EgresoController::class, 'documentacion'])->middleware('can:egreso-confirmar')->name('documentacion');
});
