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
            $baseQuery->whereHas('users', fn($q) => $q->where('user_id', $user->id));
        } elseif ($staffId) {
            $baseQuery->whereHas('users', fn($q) => $q->where('user_id', $staffId));
        }

        // We load the users relation to calculate specific percentages if needed
        $allEntries = $baseQuery->with('users')->get();
        
        // Target user for calculating personal totals (if not manager, or if specifically filtering for a staff)
        $targetUserId = (! $isManager) ? $user->id : ($staffId ? $staffId : null);

        $calculateTypeTotal = function($type, $isApproved) use ($allEntries, $targetUserId) {
            $total = 0;
            $entries = $allEntries->whereIn('type', (array) $type);
            if ($isApproved) {
                $entries = $entries->where('is_approved', true);
            } else {
                $entries = $entries->where('is_rejected', false);
            }

            foreach ($entries as $e) {
                $multiplier = ($e->type === 'video') ? 2 : 1;
                $value = in_array($e->type, ['design', 'video']) ? ($e->quantity * $multiplier) : 
                        ($e->type === 'ads_management' ? 1 : $e->package_value);

                if ($targetUserId) {
                    $pivot = $e->users->firstWhere('id', $targetUserId);
                    if ($pivot) {
                        $split = $pivot->pivot->split_percentage ?? 100;
                        $total += $value * ($split / 100);
                    }
                } else {
                    // For manager viewing the whole team, we just sum up the full value since it represents the team total
                    $total += $value;
                }
            }
            return $total;
        };

        // --- Aggregates (Personal if staff, Tenant/Filtered if manager) ---
        $designQtyApproved = (int) $calculateTypeTotal(['design', 'video'], true);
        $designQtyTotal    = (int) $calculateTypeTotal(['design', 'video'], false);
        $designComm = $settings->designCommission($designQtyApproved);
        
        $adsClientsApproved = (int) $calculateTypeTotal('ads_management', true);
        $adsClientsTotal    = (int) $calculateTypeTotal('ads_management', false);
        $adsComm    = $adsClientsApproved * $settings->adsCommissionPerClient();

        $salesValueApproved = (float) $calculateTypeTotal('sales', true);
        $salesValueTotal    = (float) $calculateTypeTotal('sales', false);
        $salesComm  = $settings->salesCommission($salesValueApproved);

        $totalApproved = $designComm + $adsComm + $salesComm;

        // --- Recent Logs (For Managers: Own + Pending/Rejected in Tenant) ---
        $recentLogsQuery = CommissionEntry::with('users')
            ->where('tenant_id', $tenantId)
            ->where('year', $year)
            ->where('month', $month);

        if (! $isManager) {
            $recentLogsQuery->whereHas('users', fn($q) => $q->where('user_id', $user->id));
        } else {
             // For manager: logs they are a PIC of, OR any pending log
             $recentLogsQuery->where(function($q) use ($user) {
                 $q->whereHas('users', fn($sq) => $sq->where('user_id', $user->id))
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
                'name'          => $e->name,
                'pic_names'     => $e->users->pluck('name')->toArray(),
                'qty'           => $e->quantity,
                'value'         => $e->package_value,
                'is_approved'   => $e->is_approved,
                'is_rejected'   => $e->is_rejected,
                'rejection_reason' => $e->rejection_reason,
                'remarks'       => $e->remarks,
                'entry_date'    => $e->entry_date ? $e->entry_date->format('Y-m-d') : null,
                'date'          => $e->created_at->toIso8601String(),
            ]),
        ];

        if ($isManager) {
             $response['staff_summary'] = User::where('tenant_id', $tenantId)
                ->where('role_id', '!=', 1) // Non-superadmins
                ->get()
                ->map(function($staff) use ($tenantId, $year, $month, $settings) {
                    $staffEntries = CommissionEntry::with('users')
                        ->whereHas('users', fn($q) => $q->where('user_id', $staff->id))
                        ->where('year', $year)
                        ->where('month', $month)
                        ->where('is_approved', true)
                        ->get();
                    
                    $dQty = 0;
                    $aQty = 0;
                    $sVal = 0;

                    foreach ($staffEntries as $e) {
                         $pivot = $e->users->firstWhere('id', $staff->id);
                         $split = ($pivot && isset($pivot->pivot->split_percentage)) ? $pivot->pivot->split_percentage : 100;
                         $pct = $split / 100;

                         if (in_array($e->type, ['design', 'video'])) {
                             $multiplier = ($e->type === 'video') ? 2 : 1;
                             $dQty += ($e->quantity * $multiplier * $pct);
                         } elseif ($e->type === 'ads_management') {
                             $aQty += (1 * $pct);
                         } elseif ($e->type === 'sales') {
                             $sVal += ($e->package_value * $pct);
                         }
                    }

                    $earned = $settings->designCommission((int) $dQty) + 
                             ((int) $aQty * $settings->adsCommissionPerClient()) + 
                             $settings->salesCommission((float) $sVal);

                    return [
                        'id' => $staff->id,
                        'name' => $staff->name,
                        'role' => $staff->role?->role ?? 'Staff',
                        'total_earned' => $earned,
                        'pending_count' => CommissionEntry::whereHas('users', fn($q) => $q->where('user_id', $staff->id))
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
            'type'          => 'required|in:design,video,ads_management,sales',
            'name'          => 'required|string|max:255',
            'quantity'      => 'nullable|integer|min:1',
            'package_value' => 'nullable|numeric|min:0',
            'month'         => 'required|integer|between:1,12',
            'year'          => 'required|integer',
            'entry_date'    => 'nullable|date',
            'remarks'       => 'nullable|string',
            'pics'          => 'nullable|array',
            'pics.*'        => 'integer|exists:users,id',
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

        $picIds = $validated['pics'] ?? [];
        if (!empty($picIds)) {
            $split = 100 / count($picIds);
            
            // Video strictly follows 2x quantity logic handled elsewhere, but for sync, the split is what matters.
            $syncData = [];
            foreach ($picIds as $picId) {
                $syncData[$picId] = [
                    'tenant_id' => $user->tenant_id,
                    'split_percentage' => $split,
                ];
            }
            $entry->users()->sync($syncData);
        } else {
            // Legacy compatibility: automatically assign the creator as the 100% PIC
            $entry->users()->sync([
                $user->id => [
                    'tenant_id' => $user->tenant_id,
                    'split_percentage' => 100,
                ]
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Commission entry logged successfully.',
            'entry'   => $entry
        ], 201);
    }

    /**
     * PUT /api/v1/commission/{id}
     * Update an existing commission entry.
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $entry = CommissionEntry::where('tenant_id', $user->tenant_id)
            ->where('id', $id)
            ->firstOrFail();

        // Ensure user can only edit their own tickets unless they are a manager
        if ($this->isStaffOnly($user) && $entry->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'type'          => 'required|in:design,video,ads_management,sales',
            'name'          => 'required|string|max:255',
            'quantity'      => 'nullable|integer|min:1',
            'package_value' => 'nullable|numeric|min:0',
            'month'         => 'required|integer|between:1,12',
            'year'          => 'required|integer',
            'entry_date'    => 'nullable|date',
            'remarks'       => 'nullable|string',
            'pics'          => 'nullable|array',
            'pics.*'        => 'integer|exists:users,id',
        ]);

        $entry->update([
            'type'          => $validated['type'],
            'name'          => $validated['name'],
            'quantity'      => $validated['quantity'] ?? 1,
            'package_value' => $validated['package_value'] ?? null,
            'month'         => $validated['month'],
            'year'          => $validated['year'],
            'entry_date'    => $validated['entry_date'],
            'remarks'       => $validated['remarks'],
        ]);

        $picIds = $validated['pics'] ?? [];
        if (!empty($picIds)) {
            $split = 100 / count($picIds);
            
            $syncData = [];
            foreach ($picIds as $picId) {
                $syncData[$picId] = [
                    'tenant_id' => $user->tenant_id,
                    'split_percentage' => $split,
                ];
            }
            $entry->users()->sync($syncData);
        }

        return response()->json([
            'success' => true,
            'message' => 'Commission entry updated successfully.',
            'entry'   => $entry
        ]);
    }

    /**
     * DELETE /api/v1/commission/{id}
     */
    public function destroy($id)
    {
        $user = Auth::user();
        $entry = CommissionEntry::where('tenant_id', $user->tenant_id)
            ->where('id', $id)
            ->firstOrFail();

        if ($this->isStaffOnly($user) && $entry->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $entry->delete();

        return response()->json(['success' => true, 'message' => 'Deleted successfully.']);
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
