<?php

use App\Http\Controllers\Administracion\AuditoriaController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:auditoria.ver')->group(function () {
    Route::get('auditoria', [AuditoriaController::class, 'index'])->name('auditoria.index');
});
