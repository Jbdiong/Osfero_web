<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UpdateLastActiveTenant
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        $tenant = \Filament\Facades\Filament::getTenant();

        if ($user && $tenant && $user->last_active_tenant_id !== $tenant->id) {
            $user->update([
                'last_active_tenant_id' => $tenant->id,
            ]);
        }

        return $next($request);
    }
}
