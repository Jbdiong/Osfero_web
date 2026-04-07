<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CommissionEntry;
use App\Models\CommissionSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CommissionController extends Controller
{
    /**
     * GET /api/v1/commission/summary
     * Returns the current user's commission data for a given month/year.
     * Defaults to the current month/year.
     */
    public function summary(Request $request)
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id;
        $settings = CommissionSetting::forTenant($tenantId);
        $isManager = ! $this->isStaffOnly($user);

        $year   = (int) ($request->query('year',  now()->year));
        $month  = (int) ($request->query('month', now()->month));
        $staffId = $request->query('staff_id');

        $baseQuery = CommissionEntry::where('tenant_id', $tenantId)
            ->where('year', $year)
            ->where('month', $month);

        if (! $isManager) {
            $baseQuery->where('user_id', $user->id);
        } elseif ($staffId) {
            $baseQuery->where('user_id', $staffId);
        }

        $allEntries = $baseQuery->get();
        $approved   = $allEntries->where('is_approved', true);

        // --- Aggregates (Personal if staff, Tenant/Filtered if manager) ---
        $designQtyApproved = (int) $approved->where('type', 'design')->sum('quantity');
        $designQtyTotal    = (int) $allEntries->where('type', 'design')->where('is_rejected', false)->sum('quantity');
        $designComm = $settings->designCommission($designQtyApproved);
        
        $adsClientsApproved = $approved->where('type', 'ads_management')->count();
        $adsClientsTotal    = $allEntries->where('type', 'ads_management')->where('is_rejected', false)->count();
        $adsComm    = $adsClientsApproved * $settings->adsCommissionPerClient();

        $salesValueApproved = (float) $approved->where('type', 'sales')->sum('package_value');
        $salesValueTotal    = (float) $allEntries->where('type', 'sales')->where('is_rejected', false)->sum('package_value');
        $salesComm  = $settings->salesCommission($salesValueApproved);

        $totalApproved = $designComm + $adsComm + $salesComm;

        // --- Recent Logs (For Managers: Own + Pending/Rejected in Tenant) ---
        $recentLogsQuery = CommissionEntry::with('user')
            ->where('tenant_id', $tenantId)
            ->where('year', $year)
            ->where('month', $month);

        if (! $isManager) {
            $recentLogsQuery->where('user_id', $user->id);
        } else {
             $recentLogsQuery->where(function($q) use ($user) {
                 $q->where('user_id', $user->id)
                   ->orWhere('is_approved', false);
             });
        }

        $logsData = $recentLogsQuery->orderBy('created_at', 'desc')->take(20)->get();

        $response = [
            'year'  => $year,
            'month' => $month,
            'is_manager' => $isManager,
            'design' => [
                'qty'            => $designQtyTotal,
                'qty_approved'   => $designQtyApproved,
                'commission'     => $designComm,
                'bonus_percent'  => $settings->designBonusPercent($designQtyApproved),
                'rate_per_unit'  => (float) $settings->design_rate,
                'next_tier'      => $settings->nextDesignTier($designQtyApproved),
            ],
            'ads' => [
                'clients'        => $adsClientsTotal,
                'clients_approved' => $adsClientsApproved,
                'commission'     => $adsComm,
                'fee_per_client' => $settings->adsCommissionPerClient(),
            ],
            'sales' => [
                'total_value'    => $salesValueTotal,
                'total_value_approved' => $salesValueApproved,
                'commission'     => $salesComm,
                'rate_percent'   => (float) $settings->sales_rate,
            ],
            'total_commission' => $totalApproved,
            'recent_logs'      => $logsData->map(fn($e) => [
                'id'            => $e->id,
                'type'          => $e->type,
                'name'          => ($e->user_id !== $user->id) ? "{$e->user->name}: {$e->name}" : $e->name,
                'qty'           => $e->quantity,
                'value'         => $e->package_value,
                'is_approved'   => $e->is_approved,
                'is_rejected'   => $e->is_rejected,
                'rejection_reason' => $e->rejection_reason,
                'entry_date'    => $e->entry_date ? $e->entry_date->format('Y-m-d') : null,
                'date'          => $e->created_at->toIso8601String(),
            ]),
        ];

        if ($isManager) {
             $response['staff_summary'] = User::where('tenant_id', $tenantId)
                ->where('role_id', '!=', 1) // Non-superadmins
                ->get()
                ->map(function($staff) use ($tenantId, $year, $month, $settings) {
                    $staffEntries = CommissionEntry::where('user_id', $staff->id)
                        ->where('year', $year)
                        ->where('month', $month)
                        ->where('is_approved', true)
                        ->get();
                    
                    $dQty = $staffEntries->where('type', 'design')->sum('quantity');
                    $aQty = $staffEntries->where('type', 'ads_management')->count();
                    $sVal = $staffEntries->where('type', 'sales')->sum('package_value');

                    $earned = $settings->designCommission($dQty) + 
                             ($aQty * $settings->adsCommissionPerClient()) + 
                             $settings->salesCommission($sVal);

                    return [
                        'id' => $staff->id,
                        'name' => $staff->name,
                        'role' => $staff->role?->role ?? 'Staff',
                        'total_earned' => $earned,
                        'pending_count' => CommissionEntry::where('user_id', $staff->id)
                            ->where('year', $year)->where('month', $month)
                            ->where('is_approved', false)->where('is_rejected', false)->count(),
                    ];
                })->filter(fn($s) => $s['total_earned'] > 0 || $s['pending_count'] > 0)->values();
        }

        return response()->json($response);
    }

    /**
     * POST /api/v1/commission/store
     * Log a new commission entry.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        
        $validated = $request->validate([
            'type'          => 'required|in:design,ads_management,sales',
            'name'          => 'required|string|max:255',
            'quantity'      => 'nullable|integer|min:1',
            'package_value' => 'nullable|numeric|min:0',
            'month'         => 'required|integer|between:1,12',
            'year'          => 'required|integer',
            'entry_date'    => 'nullable|date',
            'remarks'       => 'nullable|string',
        ]);

        $entry = CommissionEntry::create([
            'tenant_id'     => $user->tenant_id,
            'user_id'       => $user->id,
            'type'          => $validated['type'],
            'name'          => $validated['name'],
            'quantity'      => $validated['quantity'] ?? 1,
            'package_value' => $validated['package_value'] ?? null,
            'month'         => $validated['month'],
            'year'          => $validated['year'],
            'entry_date'    => $validated['entry_date'],
            'remarks'       => $validated['remarks'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Commission entry logged successfully.',
            'entry'   => $entry
        ], 201);
    }

    public function approve(Request $request, $id)
    {
        $user = Auth::user();
        if ($this->isStaffOnly($user)) {
             return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $entry = CommissionEntry::where('tenant_id', $user->tenant_id)->findOrFail($id);
        
        $entry->update([
            'is_approved' => true,
            'is_rejected' => false,
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }

    public function reject(Request $request, $id)
    {
        $user = Auth::user();
        if ($this->isStaffOnly($user)) {
             return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $entry = CommissionEntry::where('tenant_id', $user->tenant_id)->findOrFail($id);
        
        $entry->update([
            'is_approved'      => false,
            'is_rejected'      => true,
            'rejection_reason' => $request->input('reason', 'Entry rejected by manager.'),
            'rejected_by'      => $user->id,
            'rejected_at'      => now(),
        ]);

        return response()->json(['success' => true]);
    }

    private function isStaffOnly($user): bool
    {
        if (! $user) return true;
        
        $roleState = $user->role;
        if (! $roleState) return true;
        
        $roleName = strtolower($roleState->role ?? '');
        $managerRoles = ['manager', 'admin', 'superadmin', 'super admin', 'tenantadmin', 'tenant admin'];
        
        return ! in_array($roleName, $managerRoles);
    }
}
