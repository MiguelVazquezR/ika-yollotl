<?php

use App\Http\Controllers\Admin\AppointmentController;
use App\Http\Controllers\AppointmentBookingController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('citas/disponibilidad', [AppointmentBookingController::class, 'availability'])
    ->middleware('throttle:60,1')
    ->name('appointments.availability');

Route::post('citas', [AppointmentBookingController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('appointments.store');

Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::redirect('dashboard', '/admin/citas')->name('dashboard');
    Route::get('citas', [AppointmentController::class, 'index'])->name('admin.appointments.index');
    Route::patch('citas/{appointment}/estado', [AppointmentController::class, 'updateStatus'])->name('admin.appointments.status');
    Route::patch('citas/{appointment}/reagendar', [AppointmentController::class, 'reschedule'])->name('admin.appointments.reschedule');
});

require __DIR__.'/settings.php';
