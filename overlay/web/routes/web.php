<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\ChurchController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'church-center.home')->name('home');
Route::view('/features', 'church-center.features')->name('features');
Route::view('/pricing', 'church-center.pricing')->name('pricing');
Route::get('/health', fn () => response()->json([
    'status' => 'ok',
    'app' => 'Church Center',
    'time' => now()->toIso8601String(),
]));

Route::middleware('guest')->group(function () {
    Route::view('/login', 'church-center.login')->name('login');
    Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('auth.google');
    Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/churches', [ChurchController::class, 'store'])->middleware('throttle:5,1')->name('churches.store');
    Route::post('/logout', [GoogleAuthController::class, 'logout'])->name('logout');
});
