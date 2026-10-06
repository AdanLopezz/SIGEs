<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Middleware\NormalizeAuthEmail;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use Laravel\Fortify\Http\Controllers\RegisteredUserController;

Route::middleware('guest:web')->group(function () {
    Route::get('/', [AuthController::class, 'showAccess'])->name('inicio');
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');

    Route::middleware(NormalizeAuthEmail::class)->group(function () {
        Route::post('/login', [AuthenticatedSessionController::class, 'store'])
            ->middleware('throttle:login')->name('login.store');
        Route::post('/register', [RegisteredUserController::class, 'store'])->name('register.store');
    });
});

Route::middleware('auth:web')->group(function () {
    Route::view('/dashboard', 'dashboard')->name('dashboard');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
