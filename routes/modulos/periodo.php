<?php

use App\Http\Controllers\Panel\PeriodoConsultaController;
use Illuminate\Support\Facades\Route;

// Periodo de consulta (barra superior): cualquier usuario con sesión; el portal solo elige periodos de su alumno.
Route::put('periodo-consulta', [PeriodoConsultaController::class, 'update'])->name('periodo-consulta.update');
Route::delete('periodo-consulta', [PeriodoConsultaController::class, 'destroy'])->name('periodo-consulta.destroy');
