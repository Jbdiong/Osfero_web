<?php

namespace App\Http\Controllers;

use App\Models\Renewal;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_leads' => 150,
            'total_leads_change' => '+10%',
            'relevant_this_month' => '50/150',
            'relevant_change' => '+10%',
            'cost' => '$5',
            'cost_change' => '+10%',
            'conversion' => '5 out of 50',
            'conversion_change' => '-0.89%',
            'close_deal' => 15,
            'close_deal_change' => '+10%',
            'paid_to_date' => '$16,000',
            'paid_to_date_change' => '+10%',
            'leads_by_category' => [
                'total' => 100,
                'relevant' => 75,
                'irrelevant' => 25,
            ]
        ];

        // Fetch calendar data (upcoming deadlines and overdue renewals)
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        $overdueRenewals = [];
        if ($tenantId) {
            // Get the "Renewal Status" parent lookup
            $renewalStatusParent = \App\Models\Lookup::whereNull('tenant_id')
                ->whereNull('parent_id')
                ->where('name', 'Renewal Status')
                ->first();
            
            // Get the "Pending Renewal" status lookup ID
            $pendingRenewalStatus = null;
            if ($renewalStatusParent) {
                $pendingRenewalStatus = \App\Models\Lookup::whereNull('tenant_id')
                    ->where('parent_id', $renewalStatusParent->id)
                    ->where('name', 'Pending Renewal')
                    ->first();
            }
            
            // Calculate dates
            $today = now()->startOfDay();
            $sevenDaysFromNow = now()->addDays(7)->endOfDay();
            
            $query = Renewal::where('tenant_id', $tenantId)
                ->where(function ($q) use ($today, $sevenDaysFromNow) {
                    // Overdue: Renew_Date < today
                    // Due within 7 days: Renew_Date between today and 7 days from now
                    $q->where('Renew_Date', '<', $today)
                      ->orWhereBetween('Renew_Date', [$today, $sevenDaysFromNow]);
                })
                ->with(['lead', 'status']);
            
            // Only show renewals with "Pending Renewal" status
            if ($pendingRenewalStatus) {
                $overdueRenewals = $query->where('status_id', $pendingRenewalStatus->id)
                    ->orderBy('Renew_Date', 'asc')
                    ->limit(10)
                    ->get()
                    ->map(function ($renewal) use ($today) {
                        $renewDate = $renewal->Renew_Date->startOfDay();
                        $isOverdue = $renewDate < $today;
                        
                        return [
                            'name' => $renewal->label ?: ($renewal->lead ? $renewal->lead->Shop_Name : 'Unnamed Renewal'),
                            'date' => $renewal->Renew_Date->format('d M Y'),
                            'is_overdue' => $isOverdue,
                            'days_until' => $isOverdue ? null : $today->diffInDays($renewDate),
                        ];
                    })
                    ->toArray();
            }
        }

        $calendar = [
            'upcoming_deadline_countdown' => '10:35:29',
            'upcoming_deadline_title' => 'XHS posting',
            'upcoming_deadline_more' => '+ 6 more due this week',
            'overdue_renewals' => $overdueRenewals,
        ];

        return Inertia::render('Dashboard/Index', [
            'stats' => $stats,
            'calendar' => $calendar,
        ]);
    }
}





