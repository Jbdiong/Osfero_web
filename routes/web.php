<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\TodoController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\TrackingController;
use Illuminate\Support\Facades\Route;

// Authentication routes (public)
// Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
// Route::post('/login', [AuthController::class, 'login']);
// Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Protected routes (require authentication via Sanctum)
// Route::middleware(['auth:sanctum'])->group(function () {
//     Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
//     Route::get('/leads', [LeadController::class, 'index'])->name('leads.index');
//     Route::get('/todo', [TodoController::class, 'index'])->name('todo.index');
//     Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');
//     Route::get('/renewals', [CalendarController::class, 'renewals'])->name('renewals.index');
//     Route::get('/tracking', [TrackingController::class, 'index'])->name('tracking.index');
// });

// Quick event save from calendar modal (Filament session auth)
Route::middleware(['web', 'auth'])->post('/calendar/events/quick-store', [
    \App\Filament\Resources\Events\Pages\ListEvents::class,
    'storeQuickEvent',
])->name('calendar.events.quick-store');