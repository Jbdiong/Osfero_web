<?php

namespace App\Http\Controllers;

use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLoginForm()
    {
        // Redirect authenticated users to dashboard
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        
        return view('auth');
    }

    /**
     * Handle a login request to the application.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($request->only('email', 'password'), $remember)) {
            $request->session()->regenerate();

            // Log login audit
            $user = Auth::user();
            if ($user) {
                $auditService = new AuditService();
                $auditService->logLogin($user->id, $user->tenant_id);
            }

            return redirect()->intended(route('dashboard'));
        }

        throw ValidationException::withMessages([
            'email' => ['The provided credentials do not match our records.'],
        ]);
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request)
    {
        // Get user before logging out
        $user = Auth::user();
        $tenantId = $user?->tenant_id;
        $userId = $user?->id;

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Log logout audit
        if ($userId) {
            $auditService = new AuditService();
            $auditService->logLogout($userId, $tenantId);
        }

        return redirect()->route('login');
    }
}

