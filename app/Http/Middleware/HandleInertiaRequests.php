<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'ziggy' => fn () => [
                ...(new \Tighten\Ziggy\Ziggy)->toArray(),
            ],
            'csrf_token' => fn () => csrf_token(),
            'overdue_renewals_count' => fn () => $this->getOverdueRenewalsCount($request),
        ];
    }

    /**
     * Get the count of overdue renewals and renewals due within 7 days for the authenticated user
     */
    protected function getOverdueRenewalsCount(Request $request): int
    {
        if (!$request->user()) {
            return 0;
        }

        $user = $request->user();
        $tenantId = $user->tenant_id;

        if (!$tenantId) {
            return 0;
        }

        // Get the "Renewal Status" parent lookup
        $renewalStatusParent = \App\Models\Lookup::whereNull('tenant_id')
            ->whereNull('parent_id')
            ->where('name', 'Renewal Status')
            ->first();
        
        if (!$renewalStatusParent) {
            return 0;
        }

        // Get the "Pending Renewal" status lookup ID
        $pendingRenewalStatus = \App\Models\Lookup::whereNull('tenant_id')
            ->where('parent_id', $renewalStatusParent->id)
            ->where('name', 'Pending Renewal')
            ->first();
        
        if (!$pendingRenewalStatus) {
            return 0;
        }

        // Count renewals due within 7 days (including overdue: Renew_Date <= today + 7 days)
        $today = now()->startOfDay();
        $sevenDaysFromNow = now()->addDays(7)->endOfDay();
        
        return \App\Models\Renewal::where('tenant_id', $tenantId)
            ->where('status_id', $pendingRenewalStatus->id)
            ->where('Renew_Date', '<=', $sevenDaysFromNow)
            ->count();
    }
}
