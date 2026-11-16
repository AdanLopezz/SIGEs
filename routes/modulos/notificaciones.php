<?php

use App\Http\Controllers\Administracion\NotificacionController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:usuarios.administrar')->prefix('notificaciones')->name('notificaciones.')->group(function () {
    Route::get('/', [NotificacionController::class, 'index'])->name('index');
    Route::get('{notificacion}', [NotificacionController::class, 'show'])->name('show');
    Route::post('{notificacion}/reintentar', [NotificacionController::class, 'reintentar'])->middleware('throttle:10,1')->name('reintentar');
});
