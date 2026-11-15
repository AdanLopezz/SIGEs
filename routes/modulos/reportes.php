<?php

use App\Http\Controllers\Reportes\ReporteController;
use App\Support\Reportes\Catalogo;
use Illuminate\Support\Facades\Route;

// Reportes (reportes.ver). Cada reporte decide además quién lo ve (FiltrarReporteRequest → Reporte::puedeVer).
Route::middleware('permission:reportes.ver')->group(function () {
    Route::get('reportes', [ReporteController::class, 'index'])->name('reportes.index');
    Route::get('reportes/{reporte}', [ReporteController::class, 'show'])->whereIn('reporte', array_keys(Catalogo::REPORTES))->name('reportes.show');
    Route::get('reportes/{reporte}/csv', [ReporteController::class, 'csv'])->whereIn('reporte', array_keys(Catalogo::REPORTES))->name('reportes.csv');
    Route::get('reportes/{reporte}/imprimir', [ReporteController::class, 'imprimir'])->whereIn('reporte', array_keys(Catalogo::REPORTES))->name('reportes.imprimir');
});
