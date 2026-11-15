<?php

use App\Http\Controllers\Academico\CalificacionController;
use App\Http\Controllers\Academico\EvaluacionController;
use Illuminate\Support\Facades\Route;

// Evaluaciones y calificaciones. La visibilidad y cada acción las decide GrupoAsignaturaPolicy.
Route::middleware('permission:evaluaciones.programar|calificaciones.corregir|resultados.cerrar')->group(function () {
    Route::get('evaluaciones', [EvaluacionController::class, 'index'])->name('evaluaciones.index');
    Route::get('evaluaciones/materias/{materia}/edit', [EvaluacionController::class, 'edit'])->middleware('can:ver,materia')->name('evaluaciones.edit');
    Route::put('evaluaciones/materias/{materia}', [EvaluacionController::class, 'update'])->name('evaluaciones.update');
    Route::post('evaluaciones/materias/{materia}/estandar', [EvaluacionController::class, 'estandar'])->middleware('can:programar,materia')->name('evaluaciones.estandar');
});

// Cerrar una evaluación: el docente que captura o quien corrige. Se usa desde Evaluaciones y desde Calificaciones.
Route::patch('evaluaciones/{evaluacion}/cerrar', [EvaluacionController::class, 'cerrar'])
    ->middleware('permission:calificaciones.capturar|calificaciones.corregir')->name('evaluaciones.cerrar');

Route::middleware('permission:calificaciones.capturar|calificaciones.corregir|resultados.cerrar')->group(function () {
    Route::get('calificaciones', [CalificacionController::class, 'index'])->name('calificaciones.index');
    Route::get('calificaciones/materias/{materia}', [CalificacionController::class, 'materia'])->middleware('can:ver,materia')->name('calificaciones.materia');
    Route::put('calificaciones/materias/{materia}', [CalificacionController::class, 'guardar'])->name('calificaciones.guardar');
    Route::post('calificaciones/materias/{materia}/resultados', [CalificacionController::class, 'cerrarMateria'])
        ->middleware('can:cerrarResultados,materia')->name('calificaciones.cerrar-materia');
    Route::middleware('permission:resultados.cerrar')->group(function () {
        Route::get('calificaciones/grupos/{grupo}', [CalificacionController::class, 'grupo'])->name('calificaciones.grupo');
        Route::post('calificaciones/grupos/{grupo}/resultados', [CalificacionController::class, 'cerrarGrupo'])->name('calificaciones.cerrar-grupo');
    });
});
