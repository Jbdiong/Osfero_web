<?php

namespace App\Http\Controllers;

use App\Models\Todolist;
use App\Models\TodolistPIC;
use App\Models\Lead;
use App\Models\Payment;
use App\Models\Lookup;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TodoController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        // Get status lookup IDs
        $statusParent = Lookup::where('name', 'Todolist Status')
            ->whereNull('parent_id')
            ->whereNull('tenant_id')
            ->first();

        $statusMap = [];
        if ($statusParent) {
            $statuses = Lookup::where('parent_id', $statusParent->id)
                ->whereNull('tenant_id')
                ->get();
            
            foreach ($statuses as $status) {
                $statusMap[$status->name] = $status->id;
            }
        }

        // Fetch all todolists for the tenant, ordered by position then created_at
        $todolists = Todolist::with(['lead', 'payment', 'priority', 'status', 'todolistPICs.user'])
            ->where('tenant_id', $tenantId)
            ->orderBy('position')
            ->orderBy('created_at')
            ->get();

        // Group todolists by status
        $groupedTodos = [
            'todo' => [],
            'in_progress' => [],
            'pending' => [],
            'completed' => []
        ];

        foreach ($todolists as $todolist) {
            $statusName = $todolist->status ? strtolower($todolist->status->name) : null;
            $priorityName = $todolist->priority ? strtolower($todolist->priority->name) : null;
            
            // Determine type color based on priority
            $typeColor = 'blue'; // default
            if ($priorityName === 'urgent') {
                $typeColor = 'red';
            } elseif ($priorityName === 'high') {
                $typeColor = 'orange';
            } elseif ($priorityName === 'low') {
                $typeColor = 'gray';
            }

            // Get company name from lead or use title
            $company = $todolist->lead ? ($todolist->lead->Shop_Name ?? $todolist->Title) : $todolist->Title;
            
            // Get type (priority name or status name)
            $type = $todolist->priority ? $todolist->priority->name : ($todolist->status ? $todolist->status->name : 'Task');

            $taskData = [
                'id' => $todolist->id,
                'type' => $type,
                'type_color' => $typeColor,
                'company' => $company,
                'checklist' => [], // No checklist in the model, keeping empty for compatibility
                'todolist' => $todolist // Include full todolist data for reference
            ];

            // Group by status
            if ($statusName === 'to do' || $statusName === 'todo') {
                $groupedTodos['todo'][] = $taskData;
            } elseif ($statusName === 'in progress') {
                $groupedTodos['in_progress'][] = $taskData;
            } elseif ($statusName === 'pending') {
                $groupedTodos['pending'][] = $taskData;
            } elseif ($statusName === 'done' || $statusName === 'completed') {
                $groupedTodos['completed'][] = $taskData;
            } else {
                // If no status or unknown status, default to 'todo'
                $groupedTodos['todo'][] = $taskData;
            }
        }

        // Sort each group by position
        foreach ($groupedTodos as $key => $group) {
            usort($groupedTodos[$key], function ($a, $b) {
                $posA = $a['todolist']->position ?? 0;
                $posB = $b['todolist']->position ?? 0;
                return $posA <=> $posB;
            });
        }

        return Inertia::render('Todo/Index', [
            'todos' => $groupedTodos,
            'statusMap' => $statusMap, // Pass status IDs for drag and drop
        ]);
    }

    /**
     * API: Get all todolists
     */
    public function apiIndex(Request $request): JsonResponse
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        $query = Todolist::with(['lead', 'payment', 'priority', 'status', 'todolistPICs.user', 'parent', 'children'])
            ->where('tenant_id', $tenantId);

        // Filter by status if provided
        if ($request->has('status_id')) {
            $query->where('status_id', $request->status_id);
        }

        // Filter by lead if provided
        if ($request->has('lead_id')) {
            $query->where('lead_id', $request->lead_id);
        }

        // Filter by payment if provided
        if ($request->has('payment_id')) {
            $query->where('payment_id', $request->payment_id);
        }

        $todolists = $query->orderBy('position')->orderBy('created_at')->get();

        return response()->json(['data' => $todolists]);
    }

    /**
     * API: Get a single todolist
     */
    public function apiShow($id): JsonResponse
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        $todolist = Todolist::with(['lead', 'payment', 'priority', 'status', 'todolistPICs.user', 'parent', 'children'])
            ->where('tenant_id', $tenantId)
            ->findOrFail($id);

        return response()->json(['data' => $todolist]);
    }

    /**
     * API: Create a new todolist
     */
    public function apiStore(Request $request): JsonResponse
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        $validated = $request->validate([
            'lead_id' => 'nullable|exists:leads,id',
            'payment_id' => 'nullable|exists:payments,id',
            'Title' => 'required|string|max:255',
            'Description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'priority_id' => 'required|exists:lookups,id',
            'parent_id' => 'nullable|exists:todolists,id',
            'status_id' => 'required|exists:lookups,id',
            'position' => 'nullable|integer|min:0',
            'pic_user_ids' => 'required|array|min:1',
            'pic_user_ids.*' => 'exists:users,id',
        ]);

        // Verify lead and payment belong to the same tenant
        if (isset($validated['lead_id'])) {
            $lead = Lead::where('id', $validated['lead_id'])
                ->where('tenant_id', $tenantId)
                ->firstOrFail();
        }

        if (isset($validated['payment_id'])) {
            $payment = Payment::where('id', $validated['payment_id'])
                ->where('tenant_id', $tenantId)
                ->firstOrFail();
        }

        $validated['tenant_id'] = $tenantId;
        
        // If position is not provided, set it to the max position + 1 for the same status
        if (!isset($validated['position'])) {
            $maxPosition = Todolist::where('tenant_id', $tenantId)
                ->where('status_id', $validated['status_id'] ?? null)
                ->max('position') ?? -1;
            $validated['position'] = $maxPosition + 1;
        }
        
        $picUserIds = $validated['pic_user_ids'] ?? [];
        unset($validated['pic_user_ids']);

        $todolist = Todolist::create($validated);

        Log::info('Creating Todolist PICs', ['todolist_id' => $todolist->id, 'pic_user_ids' => $picUserIds]);

        // Create PICs
        foreach ($picUserIds as $userId) {
            TodolistPIC::create([
                'todolist_id' => $todolist->id,
                'user_id' => $userId,
                'tenant_id' => $tenantId,
            ]);
        }

        $todolist->load(['lead', 'payment', 'priority', 'status', 'todolistPICs.user', 'parent', 'children']);

        return response()->json(['data' => $todolist], 201);
    }

    /**
     * API: Update a todolist
     */
    public function apiUpdate(Request $request, $id): JsonResponse
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        $todolist = Todolist::where('tenant_id', $tenantId)->findOrFail($id);

        $validated = $request->validate([
            'lead_id' => 'nullable|exists:leads,id',
            'payment_id' => 'nullable|exists:payments,id',
            'Title' => 'sometimes|required|string|max:255',
            'Description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'priority_id' => 'required|exists:lookups,id',
            'parent_id' => 'nullable|exists:todolists,id',
            'status_id' => 'required|exists:lookups,id',
            'position' => 'nullable|integer|min:0',
            'pic_user_ids' => 'required|array|min:1',
            'pic_user_ids.*' => 'exists:users,id',
        ]);

        // Verify lead and payment belong to the same tenant
        if (isset($validated['lead_id'])) {
            $lead = Lead::where('id', $validated['lead_id'])
                ->where('tenant_id', $tenantId)
                ->firstOrFail();
        }

        if (isset($validated['payment_id'])) {
            $payment = Payment::where('id', $validated['payment_id'])
                ->where('tenant_id', $tenantId)
                ->firstOrFail();
        }

        $picUserIds = $validated['pic_user_ids'] ?? null;
        unset($validated['pic_user_ids']);

        $todolist->update($validated);

        // Update PICs if provided
        if ($picUserIds !== null) {
            Log::info('Updating Todolist PICs', ['todolist_id' => $todolist->id, 'pic_user_ids' => $picUserIds]);
            // Delete existing PICs
            TodolistPIC::where('todolist_id', $todolist->id)->delete();

            // Create new PICs
            foreach ($picUserIds as $userId) {
                TodolistPIC::create([
                    'todolist_id' => $todolist->id,
                    'user_id' => $userId,
                    'tenant_id' => $tenantId,
                ]);
            }
        }

        $todolist->load(['lead', 'payment', 'priority', 'status', 'todolistPICs.user', 'parent', 'children']);

        return response()->json(['data' => $todolist]);
    }

    /**
     * API: Delete a todolist
     */
    public function apiDestroy($id): JsonResponse
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        $todolist = Todolist::where('tenant_id', $tenantId)->findOrFail($id);
        $todolist->delete();

        return response()->json(['message' => 'Todolist deleted successfully']);
    }

    /**
     * API: Get payments for a lead
     */
    public function apiGetPaymentsByLead($leadId): JsonResponse
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        $lead = Lead::where('id', $leadId)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        $payments = Payment::where('lead_id', $leadId)
            ->where('tenant_id', $tenantId)
            ->with(['status', 'currency'])
            ->get();

        return response()->json(['data' => $payments]);
    }

    /**
     * API: Update positions for drag and drop
     */
    public function apiUpdatePositions(Request $request): JsonResponse
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        $validated = $request->validate([
            'updates' => 'required|array',
            'updates.*.id' => 'required|exists:todolists,id',
            'updates.*.position' => 'required|integer|min:0',
            'updates.*.status_id' => 'nullable|exists:lookups,id',
        ]);

        foreach ($validated['updates'] as $update) {
            $todolist = Todolist::where('id', $update['id'])
                ->where('tenant_id', $tenantId)
                ->firstOrFail();

            $updateData = ['position' => $update['position']];
            
            // Update status if provided (when moving between columns)
            if (isset($update['status_id'])) {
                $updateData['status_id'] = $update['status_id'];
            }

            $todolist->update($updateData);
        }

        return response()->json(['message' => 'Positions updated successfully']);
    }

    /**
     * Get available staff (users) for the current tenant.
     */
    public function getStaff(): JsonResponse
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        if (!$tenantId) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant ID is required.',
                'data' => []
            ], 400);
        }

        $staff = User::whereHas('tenants', fn ($q) => $q->where('tenants.id', $tenantId))
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $staff
        ]);
    }
}







