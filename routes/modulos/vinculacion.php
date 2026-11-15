<?php

use App\Http\Controllers\Vinculacion\AspiranteController;
use App\Http\Controllers\Vinculacion\ConvenioController;
use App\Http\Controllers\Vinculacion\EmpresaController;
use Illuminate\Support\Facades\Route;

// Empresas y convenios: dual.administrar administra; dual.revisar (Subdirector) consulta.
Route::middleware('permission:dual.administrar')->group(function () {
    // Van antes que {empresa}/{convenio} para que "create" no se tome como id.
    Route::get('empresas/create', [EmpresaController::class, 'create'])->name('empresas.create');
    Route::post('empresas', [EmpresaController::class, 'store'])->name('empresas.store');
    Route::get('empresas/{empresa}/edit', [EmpresaController::class, 'edit'])->name('empresas.edit');
    Route::put('empresas/{empresa}', [EmpresaController::class, 'update'])->name('empresas.update');
    Route::delete('empresas/{empresa}', [EmpresaController::class, 'destroy'])->name('empresas.destroy');
    Route::get('convenios/create', [ConvenioController::class, 'create'])->name('convenios.create');
    Route::post('convenios', [ConvenioController::class, 'store'])->name('convenios.store');
    Route::get('convenios/{convenio}/edit', [ConvenioController::class, 'edit'])->name('convenios.edit');
    Route::put('convenios/{convenio}', [ConvenioController::class, 'update'])->name('convenios.update');
    Route::patch('convenios/{convenio}/cancelar', [ConvenioController::class, 'cancelar'])->name('convenios.cancelar');
});
Route::middleware('permission:dual.administrar|dual.revisar')->group(function () {
    Route::get('empresas', [EmpresaController::class, 'index'])->name('empresas.index');
    Route::get('empresas/{empresa}', [EmpresaController::class, 'show'])->name('empresas.show');
    Route::get('convenios', [ConvenioController::class, 'index'])->name('convenios.index');
    Route::get('convenios/{convenio}', [ConvenioController::class, 'show'])->name('convenios.show');
});

// Aspirantes: aspirantes.administrar (Vinculación); Control Escolar (alumnos.editar) consulta y convierte en alumno (alumnos.create?aspirante=).
Route::middleware('permission:aspirantes.administrar')->group(function () {
    Route::get('aspirantes/create', [AspiranteController::class, 'create'])->name('aspirantes.create');
    Route::post('aspirantes', [AspiranteController::class, 'store'])->name('aspirantes.store');
    Route::get('aspirantes/{aspirante}/edit', [AspiranteController::class, 'edit'])->name('aspirantes.edit');
    Route::put('aspirantes/{aspirante}', [AspiranteController::class, 'update'])->name('aspirantes.update');
    Route::patch('aspirantes/{aspirante}/estado', [AspiranteController::class, 'estado'])->name('aspirantes.estado');
});
Route::middleware('permission:aspirantes.administrar|alumnos.editar')->group(function () {
    Route::get('aspirantes', [AspiranteController::class, 'index'])->name('aspirantes.index');
    Route::get('aspirantes/{aspirante}', [AspiranteController::class, 'show'])->name('aspirantes.show');
});
