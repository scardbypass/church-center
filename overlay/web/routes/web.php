<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\ChurchController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\SupportTicketController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AutomationController;
use App\Http\Controllers\LicenseController;
use App\Http\Controllers\AdminPaymentsController;
use App\Http\Controllers\WhatsAppController;
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
    Route::get('/churches/{churchId}/contacts', [ContactController::class, 'index'])->whereNumber('churchId')->name('churches.contacts.index');
    Route::post('/churches/{churchId}/contacts', [ContactController::class, 'store'])->whereNumber('churchId')->middleware('throttle:30,1')->name('churches.contacts.store');
    Route::delete('/churches/{churchId}/contacts/{contactId}', [ContactController::class, 'destroy'])->whereNumber('churchId')->whereNumber('contactId')->name('churches.contacts.destroy');
    Route::get('/churches/{churchId}/tickets', [SupportTicketController::class, 'index'])->whereNumber('churchId')->name('churches.tickets.index');
    Route::post('/churches/{churchId}/tickets', [SupportTicketController::class, 'store'])->whereNumber('churchId')->middleware('throttle:5,1')->name('churches.tickets.store');
    Route::get('/admin', [AdminController::class, 'index'])->name('admin.index');
    Route::patch('/admin/tickets/{ticketId}/close', [AdminController::class, 'closeTicket'])->whereNumber('ticketId')->name('admin.tickets.close');
    Route::get('/churches/{churchId}/automations', [AutomationController::class, 'index'])->whereNumber('churchId')->name('churches.automations.index');
    Route::post('/churches/{churchId}/automations', [AutomationController::class, 'store'])->whereNumber('churchId')->middleware('throttle:10,1')->name('churches.automations.store');
    Route::delete('/churches/{churchId}/automations/{jobId}', [AutomationController::class, 'destroy'])->whereNumber('churchId')->whereNumber('jobId')->name('churches.automations.destroy');
    Route::get('/churches/{churchId}/licenses', [LicenseController::class, 'index'])->whereNumber('churchId')->name('churches.licenses.index');
    Route::post('/churches/{churchId}/licenses/request', [LicenseController::class, 'request'])->whereNumber('churchId')->middleware('throttle:3,1')->name('churches.licenses.request');
    Route::get('/admin/payments', [AdminPaymentsController::class, 'index'])->name('admin.payments.index');
    Route::post('/admin/payments/{paymentId}/approve', [AdminPaymentsController::class, 'approve'])->whereNumber('paymentId')->name('admin.payments.approve');
    Route::post('/admin/payments/{paymentId}/reject', [AdminPaymentsController::class, 'reject'])->whereNumber('paymentId')->name('admin.payments.reject');
    Route::get('/churches/{churchId}/whatsapp', [WhatsAppController::class, 'index'])->whereNumber('churchId')->name('churches.whatsapp.index');
    Route::post('/churches/{churchId}/whatsapp/connect', [WhatsAppController::class, 'connect'])->whereNumber('churchId')->middleware('throttle:5,1')->name('churches.whatsapp.connect');
    Route::post('/churches/{churchId}/whatsapp/disconnect', [WhatsAppController::class, 'disconnect'])->whereNumber('churchId')->middleware('throttle:5,1')->name('churches.whatsapp.disconnect');
    Route::post('/logout', [GoogleAuthController::class, 'logout'])->name('logout');
});
