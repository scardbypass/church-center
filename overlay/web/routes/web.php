<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'church-center.home')->name('home');
Route::get('/health', fn () => response()->json([
    'status' => 'ok',
    'app' => 'Church Center',
    'time' => now()->toIso8601String(),
]));

Route::view('/features', 'church-center.features')->name('features');
Route::view('/pricing', 'church-center.pricing')->name('pricing');
