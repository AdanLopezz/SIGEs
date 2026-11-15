<?php

use App\Http\Controllers\ControlEscolar\AlumnoController;
use App\Http\Controllers\ControlEscolar\BajaController;
use App\Http\Controllers\ControlEscolar\CambioPeriodoController;
use App\Http\Controllers\ControlEscolar\ConsentimientoController;
use App\Http\Controllers\ControlEscolar\CredencialController;
use App\Http\Controllers\ControlEscolar\CuentaAccesoController;
use App\Http\Controllers\ControlEscolar\InscripcionController;
use App\Http\Controllers\ControlEscolar\RegularizacionController;
use App\Http\Controllers\ControlEscolar\TutorAlumnoController;
use Illuminate\Support\Facades\Route;

// Va antes que alumnos.ver para que "alumnos/create" no lo capture "alumnos/{alumno}".
Route::middleware('permission:alumnos.editar')->group(function () {
    // El alta exige salud: NSS y tipo de sangre son obligatorios.
    Route::get('alumnos/create', [AlumnoController::class, 'create'])->middleware('permission:alumnos.salud.ver')->name('alumnos.create');
    Route::post('alumnos', [AlumnoController::class, 'store'])->middleware('permission:alumnos.salud.ver')->name('alumnos.store');

    Route::middleware('can:editar,alumno')->group(function () {
        Route::get('alumnos/{alumno}/edit', [AlumnoController::class, 'edit'])->name('alumnos.edit');
        Route::put('alumnos/{alumno}', [AlumnoController::class, 'update'])->name('alumnos.update');
        Route::post('alumnos/{alumno}/crear-cuenta', [AlumnoController::class, 'crearCuenta'])
            ->middleware('throttle:10,1')->name('alumnos.crear-cuenta');
        Route::resource('alumnos.tutores', TutorAlumnoController::class)->parameters(['alumnos' => 'alumno', 'tutores' => 'tutor'])
            ->only(['index', 'store', 'edit', 'update', 'destroy'])->scoped();
        Route::post('alumnos/{alumno}/tutores/{tutor}/crear-cuenta', [TutorAlumnoController::class, 'crearCuenta'])
            ->scopeBindings()->middleware('throttle:10,1')->name('alumnos.tutores.crear-cuenta');
        // Datos de acceso de las cuentas del alumno y de sus tutores (solo cuentas únicamente de Alumno o Padre de familia).
        Route::get('alumnos/{alumno}/cuenta', [CuentaAccesoController::class, 'alumno'])->name('alumnos.cuenta');
        Route::get('alumnos/{alumno}/cuenta/imprimir', [CuentaAccesoController::class, 'imprimirAlumno'])->name('alumnos.cuenta.imprimir');
        Route::patch('alumnos/{alumno}/cuenta/nueva-contrasena', [CuentaAccesoController::class, 'nuevaAlumno'])
            ->middleware('throttle:10,1')->name('alumnos.cuenta.nueva-contrasena');
        Route::get('alumnos/{alumno}/tutores/{tutor}/cuenta', [CuentaAccesoController::class, 'tutor'])->scopeBindings()->name('alumnos.tutores.cuenta');
        Route::get('alumnos/{alumno}/tutores/{tutor}/cuenta/imprimir', [CuentaAccesoController::class, 'imprimirTutor'])->scopeBindings()->name('alumnos.tutores.cuenta.imprimir');
        Route::patch('alumnos/{alumno}/tutores/{tutor}/cuenta/nueva-contrasena', [CuentaAccesoController::class, 'nuevaTutor'])
            ->scopeBindings()->middleware('throttle:10,1')->name('alumnos.tutores.cuenta.nueva-contrasena');
        // Con historial el tutor no se borra: se desactiva (con motivo) o se reactiva.
        Route::patch('alumnos/{alumno}/tutores/{tutor}/desactivar', [TutorAlumnoController::class, 'desactivar'])->scopeBindings()->name('alumnos.tutores.desactivar');
        Route::patch('alumnos/{alumno}/tutores/{tutor}/reactivar', [TutorAlumnoController::class, 'reactivar'])->scopeBindings()->name('alumnos.tutores.reactivar');
        Route::post('alumnos/{alumno}/consentimientos', [ConsentimientoController::class, 'store'])->name('alumnos.consentimientos.store');
        // Credencial: emitir (POST) redirige a imprimir (GET), que usa el token una sola vez.
        Route::post('alumnos/{alumno}/credencial', [CredencialController::class, 'emitir'])->middleware('throttle:10,1')->name('alumnos.credencial.emitir');
        Route::get('alumnos/{alumno}/credencial', [CredencialController::class, 'imprimir'])->name('alumnos.credencial.imprimir');
    });
});

Route::middleware(['permission:inscripciones.administrar', 'can:ver,alumno'])->group(function () {
    Route::get('alumnos/{alumno}/inscripcion', [InscripcionController::class, 'edit'])->name('alumnos.inscripcion');
    Route::post('alumnos/{alumno}/inscripcion', [InscripcionController::class, 'inscribir'])->name('alumnos.inscribir');
    Route::put('alumnos/{alumno}/inscripcion', [InscripcionController::class, 'cambiarGrupo'])->name('alumnos.cambiar-grupo');
});

// Asistente "Cambio de periodo y reinscripción" (crear grupos exige además horarios.administrar, en el modelo).
Route::middleware('permission:inscripciones.administrar')->prefix('cambio-periodo')->name('cambio-periodo.')->controller(CambioPeriodoController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('grupos', 'grupos')->name('grupos');
    Route::post('grupos', 'guardarGrupos')->name('grupos.guardar');
    Route::get('alumnos', 'alumnos')->name('alumnos');
    Route::get('alumnos/{grupo}', 'revisar')->name('alumnos.revisar');
    Route::put('alumnos/{grupo}', 'guardarRevision')->name('alumnos.guardar');
    Route::post('confirmar', 'confirmar')->middleware('throttle:10,1')->name('confirmar');
    Route::get('resumen', 'resumen')->name('resumen');
    Route::get('resumen/imprimir', 'imprimir')->name('resumen.imprimir');
});

Route::middleware('permission:bajas.registrar')->group(function () {
    Route::get('bajas', [BajaController::class, 'index'])->name('bajas.index');
    Route::middleware('can:ver,alumno')->group(function () {
        Route::post('alumnos/{alumno}/bajas', [BajaController::class, 'store'])->name('alumnos.bajas.store');
        Route::get('alumnos/{alumno}/reingreso', [BajaController::class, 'reingreso'])->name('alumnos.reingreso');
        Route::post('alumnos/{alumno}/reingreso', [BajaController::class, 'reingresar'])->name('alumnos.reingresar');
    });
});

// Prefectura (acceso.registrar) o quien ve alumnos. No aplica AlumnoPolicy: se verifica a cualquier alumno.
Route::get('credenciales/verificar/{token}', [CredencialController::class, 'verificar'])
    ->middleware(['permission:acceso.registrar|alumnos.ver', 'throttle:60,1'])->where('token', '[A-Za-z0-9]{40}')->name('credenciales.verificar');

Route::middleware('permission:alumnos.ver')->group(function () {
    Route::get('alumnos', [AlumnoController::class, 'index'])->name('alumnos.index');
    Route::get('alumnos/{alumno}', [AlumnoController::class, 'show'])->middleware('can:ver,alumno')->name('alumnos.show');
    Route::get('alumnos/{alumno}/consentimientos', [ConsentimientoController::class, 'index'])->middleware('can:ver,alumno')->name('alumnos.consentimientos.index');
});

// Regularización: extraordinarios y recursamientos (las reglas las aplica TrxResultadoAsignatura).
Route::middleware('permission:recursamientos.administrar')->group(function () {
    Route::get('regularizacion', [RegularizacionController::class, 'index'])->name('regularizacion.index');
    Route::post('regularizacion/{resultado}/extraordinario', [RegularizacionController::class, 'extraordinario'])->name('regularizacion.extraordinario');
    Route::put('regularizacion/{resultado}/extraordinario', [RegularizacionController::class, 'corregirExtraordinario'])->name('regularizacion.corregir-extraordinario');
    Route::get('regularizacion/{resultado}/recursamiento', [RegularizacionController::class, 'recursamiento'])->name('regularizacion.recursamiento');
    Route::post('regularizacion/{resultado}/recursamiento', [RegularizacionController::class, 'asignar'])->name('regularizacion.asignar');
    Route::delete('regularizacion/recursamientos/{recursamiento}', [RegularizacionController::class, 'quitar'])->name('regularizacion.quitar');
});
