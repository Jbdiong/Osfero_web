<?php

namespace App\Http\Controllers;

use App\Models\Renewal;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CalendarController extends Controller
{
    /**
     * Display the renewals page
     */
    public function renewals()
    {
        return Inertia::render('Renewals/Index');
    }

    public function index()
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        // Fetch overdue renewals and renewals due within 7 days (renewals where Renew_Date is in the past or within 7 days) with status "Pending Renewal"
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
                $query->where('status_id', $pendingRenewalStatus->id);
            } else {
                // If status doesn't exist, return empty array
                $overdueRenewals = [];
            }
            
            if ($pendingRenewalStatus) {
                $overdueRenewals = $query->orderBy('Renew_Date', 'asc')
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

        $hardcodedCalendar = [
            'overdue_tasks' => [
                [
                    'status' => 'OVERDUE by 1w',
                    'type' => 'Renewal',
                    'name' => 'May Sing',
                    'task' => 'In task Collect $$$',
                    'task_color' => 'red'
                ],
                [
                    'status' => 'OVERDUE by 1w',
                    'type' => null,
                    'name' => 'Lily cosmetic',
                    'task' => 'In task Follow up',
                    'task_color' => 'green'
                ]
            ],
            'upcoming_deadlines' => [
                [
                    'status' => 'KOL',
                    'type' => null,
                    'name' => 'TFC',
                    'task' => 'DUE in 10:35:29 Design',
                    'task_color' => 'blue',
                    'subtask' => 'In task Finalise design'
                ]
            ],
            'events' => [
                
               
            ],
            'upcoming_deadline_countdown' => '10:35:29',
            'upcoming_deadline_title' => 'XHS posting',
            'upcoming_deadline_more' => '+ 6 more due this week',
            'overdue_renewals' => $overdueRenewals,
            'selected_date' => '20',
            'selected_month' => 'December 2025',
            'current_time' => '14:25'
        ];

        return Inertia::render('Calendar/Index', [
            'calendar' => $hardcodedCalendar,
        ]);
    }

    /**
     * API: Store a new renewal
     */
    public function apiStoreRenewal(Request $request): JsonResponse
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        // Superadmin can have null tenant_id, so we need to handle that
        if (!$tenantId) {
            return response()->json([
                'message' => 'Tenant ID is required. Superadmin cannot create renewals.',
            ], 403);
        }

        $validated = $request->validate([
            'label' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'Renew_Date' => 'required|date',
            'lead_id' => 'nullable|exists:leads,id',
        ]);

        // Ensure lead belongs to the same tenant if provided
        if ($validated['lead_id'] ?? null) {
            $lead = \App\Models\Lead::where('tenant_id', $tenantId)->find($validated['lead_id']);
            if (!$lead) {
                return response()->json([
                    'message' => 'Lead not found or does not belong to your tenant.',
                ], 404);
            }
        }

        // Auto-assign "Pending Renewal" status
        $renewalStatusParent = \App\Models\Lookup::whereNull('tenant_id')
            ->whereNull('parent_id')
            ->where('name', 'Renewal Status')
            ->first();
        
        $pendingRenewalStatus = null;
        if ($renewalStatusParent) {
            $pendingRenewalStatus = \App\Models\Lookup::whereNull('tenant_id')
                ->where('parent_id', $renewalStatusParent->id)
                ->where('name', 'Pending Renewal')
                ->first();
        }
        
        if ($pendingRenewalStatus) {
            $validated['status_id'] = $pendingRenewalStatus->id;
        } else {
            return response()->json([
                'message' => 'Pending Renewal status not found in lookups.',
            ], 500);
        }

        $validated['tenant_id'] = $tenantId;
        $renewal = Renewal::create($validated);

        $renewal->load(['lead', 'status', 'tenant']);

        // Log audit (keep JSON format for filtering)
        $auditService = new AuditService();
        $auditService->logCreate('renewals', $renewal->id, json_encode($validated), $tenantId, $user->id);

        return response()->json([
            'data' => $renewal,
            'message' => 'Renewal created successfully',
        ], 201);
    }

    /**
     * API: Get all renewals for the current tenant
     */
    public function apiIndexRenewals(Request $request): JsonResponse
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$tenantId) {
            return response()->json([
                'data' => [],
                'message' => 'Tenant ID is required.',
            ]);
        }

        $renewals = Renewal::where('tenant_id', $tenantId)
            ->with(['lead', 'status', 'tenant'])
            ->orderBy('Renew_Date', 'asc')
            ->get();

        return response()->json([
            'data' => $renewals,
        ]);
    }


    /**
     * API: Update a renewal
     */
    public function apiUpdateRenewal(Request $request, $id): JsonResponse
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$tenantId) {
            return response()->json([
                'message' => 'Tenant ID is required.',
            ], 403);
        }

        $renewal = Renewal::where('tenant_id', $tenantId)->findOrFail($id);

        $validated = $request->validate([
            'label' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'Renew_Date' => 'sometimes|required|date',
            'status_id' => 'nullable|exists:lookups,id',
            'lead_id' => 'nullable|exists:leads,id',
        ]);

        // Ensure lead belongs to the same tenant if provided
        if (isset($validated['lead_id']) && $validated['lead_id']) {
            $lead = \App\Models\Lead::where('tenant_id', $tenantId)->find($validated['lead_id']);
            if (!$lead) {
                return response()->json([
                    'message' => 'Lead not found or does not belong to your tenant.',
                ], 404);
            }
        }

        // Get old values for audit logging
        $oldValues = $renewal->toArray();
        
        // Get old status name if status_id is being changed
        $oldStatusName = null;
        if (isset($validated['status_id']) && $renewal->status_id) {
            $oldStatus = \App\Models\Lookup::find($renewal->status_id);
            $oldStatusName = $oldStatus ? $oldStatus->name : null;
        }
        
        $renewal->update($validated);
        $renewal->load(['lead', 'status', 'tenant']);

        // Log audit for each changed field
        $auditService = new AuditService();
        foreach ($validated as $field => $newValue) {
            $oldValue = $oldValues[$field] ?? null;
            if ($oldValue != $newValue) {
                // For status_id, use status names instead of IDs
                if ($field === 'status_id') {
                    $oldStatusValue = $oldStatusName ?? ($oldValue ? 'ID: ' . $oldValue : null);
                    $newStatus = \App\Models\Lookup::find($newValue);
                    $newStatusValue = $newStatus ? $newStatus->name : ($newValue ? 'ID: ' . $newValue : null);
                    
                    $auditService->logUpdate(
                        'renewals',
                        $renewal->id,
                        'status',
                        $oldStatusValue,
                        $newStatusValue,
                        $tenantId,
                        $user->id
                    );
                } else {
                    $auditService->logUpdate(
                        'renewals',
                        $renewal->id,
                        $field,
                        $oldValue,
                        $newValue,
                        $tenantId,
                        $user->id
                    );
                }
            }
        }

        return response()->json([
            'data' => $renewal,
            'message' => 'Renewal updated successfully',
        ]);
    }

    /**
     * API: Get audit logs for renewals
     */
    public function apiIndexRenewalAudits(Request $request): JsonResponse
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$tenantId) {
            return response()->json([
                'data' => [],
                'message' => 'Tenant ID is required.',
            ]);
        }

        $audits = \App\Models\Audit::where('table_name', 'renewals')
            ->where('tenant_id', $tenantId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($audit) {
                // Load the renewal to get label
                $renewal = Renewal::find($audit->record_id);
                
                // Load user if performed_by is set
                $user = null;
                if ($audit->performed_by) {
                    $user = \App\Models\User::find($audit->performed_by);
                }
                
                return [
                    'id' => $audit->id,
                    'table_name' => $audit->table_name,
                    'record_id' => $audit->record_id,
                    'column_name' => $audit->column_name,
                    'old_value' => $audit->old_value,
                    'new_value' => $audit->new_value,
                    'audit_type' => $audit->audit_type,
                    'performed_by' => $audit->performed_by,
                    'performed_by_user' => $user ? [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                    ] : null,
                    'created_at' => $audit->created_at,
                    'renewal' => $renewal ? [
                        'id' => $renewal->id,
                        'label' => $renewal->label,
                        'lead' => $renewal->lead ? [
                            'id' => $renewal->lead->id,
                            'Shop_Name' => $renewal->lead->Shop_Name,
                        ] : null,
                    ] : null,
                ];
            });

        return response()->json([
            'data' => $audits,
        ]);
    }
}





