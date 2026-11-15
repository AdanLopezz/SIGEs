<?php

use App\Http\Controllers\Configuracion\AreaController;
use App\Http\Controllers\Configuracion\AsignaturaController;
use App\Http\Controllers\Configuracion\CarreraController;
use App\Http\Controllers\Configuracion\DiaInhabilController;
use App\Http\Controllers\Configuracion\EspacioController;
use App\Http\Controllers\Configuracion\PeriodoController;
use App\Http\Controllers\Configuracion\PlantelController;
use App\Http\Controllers\Configuracion\TipoIncidenciaController;
use App\Http\Controllers\Configuracion\TurnoController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:configuracion.administrar')->prefix('configuracion')->name('configuracion.')->group(function () {
    Route::get('/', [PlantelController::class, 'edit'])->name('edit');
    Route::put('/', [PlantelController::class, 'update'])->name('update');
    Route::resource('periodos', PeriodoController::class)->parameters(['periodos' => 'periodo'])->except(['show', 'create']);
    Route::resource('dias-inhabiles', DiaInhabilController::class)->parameters(['dias-inhabiles' => 'dia'])->except(['show', 'create']);
    Route::resource('turnos', TurnoController::class)->parameters(['turnos' => 'turno'])->except(['show', 'create']);
    Route::resource('carreras', CarreraController::class)->parameters(['carreras' => 'carrera'])->except(['show', 'create', 'destroy']);
    Route::patch('carreras/{carrera}/estado', [CarreraController::class, 'cambiarEstado'])->name('carreras.estado');
    Route::resource('asignaturas', AsignaturaController::class)->parameters(['asignaturas' => 'asignatura'])->except(['show', 'create', 'destroy']);
    Route::patch('asignaturas/{asignatura}/estado', [AsignaturaController::class, 'cambiarEstado'])->name('asignaturas.estado');
    Route::resource('areas', AreaController::class)->parameters(['areas' => 'area'])->except(['show', 'create']);
    Route::resource('espacios', EspacioController::class)->parameters(['espacios' => 'espacio'])->except(['show', 'create', 'destroy']);
    Route::patch('espacios/{espacio}/estado', [EspacioController::class, 'cambiarEstado'])->name('espacios.estado');
    Route::resource('tipos-incidencia', TipoIncidenciaController::class)->parameters(['tipos-incidencia' => 'tipo'])->except(['show', 'create']);
    Route::patch('periodos/{periodo}/activar', [PeriodoController::class, 'activar'])->name('periodos.activar');
});
