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

Route::middleware(['web', 'auth'])->patch('/calendar/events/{id}', [
    \App\Filament\Resources\Events\Pages\ListEvents::class,
    'updateQuickEvent',
])->name('calendar.events.update');

Route::middleware(['web', 'auth'])->delete('/calendar/events/{id}', [
    \App\Filament\Resources\Events\Pages\ListEvents::class,
    'deleteQuickEvent',
])->name('calendar.events.delete');

Route::middleware(['web', 'auth'])->get('/calendar/customers', [
    \App\Filament\Resources\Events\Pages\ListEvents::class,
    'getCustomers',
])->name('calendar.customers');

Route::get('/legal/privacy-policy', function () {
    return view('privacy-policy');
})->name('privacy-policy');

// Public invite link - stores code in session and redirects to register
Route::get('/invite/{code}', function (string $code) {
    $tenant = \App\Models\Tenant::findByInvitationCode($code);

    if (!$tenant) {
        return redirect('/')->with('error', 'This invite link is invalid or has expired.');
    }

    session(['invite_code' => $code]);

    return redirect(route('filament.admin.auth.register'));
})->name('invite.register');

// Helper: "Copy Invite Link" from tenant menu - generates code if needed, then copies
Route::middleware(['web', 'auth'])->get('/tenant/{tenant}/invite-link', function (string $tenant) {
    $tenantModel = \App\Models\Tenant::where('slug', $tenant)->firstOrFail();

    // Auto-generate a fresh code if missing or expired
    if (empty($tenantModel->code) || ($tenantModel->code_expiring && $tenantModel->code_expiring->isPast())) {
        $tenantModel->generateInvitationCode();
    }

    $inviteUrl = url('/invite/' . $tenantModel->code);

    // Redirect back to the panel with a flash notification containing the URL
    return redirect(\Filament\Facades\Filament::getPanel()->getUrl($tenantModel))
        ->with('invite_link_flash', $inviteUrl);
})->name('tenant.invite.copy');


Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/auth/reactivate', function () {
        if (\Illuminate\Support\Facades\Auth::user()->status != 2) {
            return redirect('/');
        }
        return view('auth.reactivate');
    })->name('reactivate.prompt');

    Route::post('/auth/reactivate', function () {
        $user = \Illuminate\Support\Facades\Auth::user();
        if ($user->status != 2) {
            return redirect('/');
        }
        $user->update(['status' => 1]);
        return redirect('/');
    })->name('reactivate.process');

    Route::post('/auth/reactivate/cancel', function () {
        \Illuminate\Support\Facades\Auth::logout();
        session()->invalidate();
        session()->regenerateToken();
        return redirect('/');
    })->name('reactivate.cancel');
});