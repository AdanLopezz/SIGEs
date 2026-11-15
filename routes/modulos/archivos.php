<?php

use App\Http\Controllers\Archivos\ArchivoController;
use Illuminate\Support\Facades\Route;

// Sin permission:<permiso>: la autorización la decide ArchivoPolicy@ver (quien lo subió también puede verlo).
Route::get('archivos/{archivo}', [ArchivoController::class, 'ver'])->middleware('can:ver,archivo')->name('archivos.ver');
