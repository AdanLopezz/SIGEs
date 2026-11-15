<?php

use App\Http\Controllers\Academico\AsistenciaMateriaController;
use App\Http\Controllers\Academico\HorarioController;
use App\Http\Controllers\Academico\PaseListaController;
use App\Http\Controllers\Orientacion\AsistenciaClaseController;
use Illuminate\Support\Facades\Route;

// Horarios: captura por grupo, copia y consultas por docente y por aula (Control Escolar). Las reglas están en AcaHorario.
Route::middleware('permission:horarios.administrar')->prefix('horarios')->name('horarios.')->group(function () {
    Route::get('/', [HorarioController::class, 'index'])->name('index');
    Route::post('grupos/{grupo}/clases', [HorarioController::class, 'store'])->name('store');
    Route::post('grupos/{grupo}/copiar', [HorarioController::class, 'copiar'])->name('copiar');
    Route::put('clases/{horario}', [HorarioController::class, 'update'])->name('update');
    Route::delete('clases/{horario}', [HorarioController::class, 'destroy'])->name('destroy');
    Route::get('docentes', [HorarioController::class, 'docentes'])->name('docentes');
    Route::get('aulas', [HorarioController::class, 'aulas'])->name('aulas');
});

// Horario de un grupo (consulta e impresión): también Tutor, Orientador, Docente y Prefectura en sus grupos (el controlador lo revisa).
Route::middleware('permission:horarios.administrar|asistencia.ver')->group(function () {
    Route::get('horarios/grupos/{grupo}', [HorarioController::class, 'grupo'])->name('horarios.grupo');
    Route::get('horarios/grupos/{grupo}/imprimir', [HorarioController::class, 'imprimirGrupo'])->name('horarios.grupo.imprimir');
});

// Horario de un docente para imprimir: Control Escolar el de cualquiera; el docente, el suyo.
Route::get('horarios/docentes/{docente}/imprimir', [HorarioController::class, 'imprimirDocente'])
    ->middleware('permission:horarios.administrar|asistencia_clase.registrar')->name('horarios.docente.imprimir');

// Docente: "Mi horario" y "Mis clases de hoy".
Route::middleware('permission:asistencia_clase.registrar')->group(function () {
    Route::get('mi-horario', [HorarioController::class, 'miHorario'])->name('mi-horario.index');
    Route::get('mis-clases', [PaseListaController::class, 'index'])->name('pase-lista.index');
});

// Lista de una materia y asistencia por materia: su docente o Control Escolar (GrupoAsignaturaPolicy).
Route::middleware('permission:asistencia_clase.registrar|asistencia_clase.corregir')->group(function () {
    Route::get('mis-clases/materias/{materia}', [PaseListaController::class, 'lista'])->middleware('can:verAsistencia,materia')->name('pase-lista.lista');
    Route::put('mis-clases/materias/{materia}', [PaseListaController::class, 'guardar'])->middleware('can:pasarLista,materia')->name('pase-lista.guardar');
    Route::get('asistencia-materias', [AsistenciaMateriaController::class, 'index'])->name('asistencia-materia.index');
    Route::get('asistencia-materias/{materia}', [AsistenciaMateriaController::class, 'show'])->middleware('can:verAsistencia,materia')->name('asistencia-materia.show');
});

// Orientación → Asistencia por clase de los alumnos de mis grupos.
Route::get('asistencia-clase', [AsistenciaClaseController::class, 'index'])->middleware('permission:asistencia.ver')->name('asistencia-clase.index');
