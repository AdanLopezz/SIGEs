<?php

use App\Http\Controllers\Academico\GrupoAsignaturaController;
use App\Http\Controllers\Academico\GrupoController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:horarios.administrar')->group(function () {
    Route::resource('grupos', GrupoController::class)->parameters(['grupos' => 'grupo'])->except(['show']);
    Route::resource('grupos.materias', GrupoAsignaturaController::class)->parameters(['grupos' => 'grupo', 'materias' => 'materia'])
        ->only(['index', 'store', 'edit', 'update', 'destroy'])->scoped();
});
