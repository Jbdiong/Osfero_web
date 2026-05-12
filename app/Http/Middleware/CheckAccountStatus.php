<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckAccountStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();
            
            // If the user's global status is 2 (Suspended/Deactivated)
            // and they are not already on the reactivate or logout routes
            if ($user->status == 2 && !$request->is('auth/reactivate') && !$request->is('auth/reactivate/*') && !$request->is('*logout*')) {
                return redirect()->route('reactivate.prompt');
            }
        }

        return $next($request);
    }
}
