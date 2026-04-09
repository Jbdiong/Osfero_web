<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Todolist;
use Illuminate\Http\Request;
use Carbon\Carbon;

class TodolistController extends Controller
{
    public function index(Request $request)
    {
        // "mean just overdue by 1 day only"
        // Overdue by 1 day means the end_date was yesterday.
        $targetDate = Carbon::yesterday()->toDateString();

        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $query = Todolist::query()
            ->where('tenant_id', $user->tenant_id)
            ->whereNull('parent_id'); // Only top-level tasks

        $canViewEveryone = in_array(optional($user->role)->role, ['Superadmin', 'Tenant admin', 'Manager']);
        $wantsToViewEveryone = $request->query('view_everyone') == '1';

        if (!$canViewEveryone || !$wantsToViewEveryone) {
            $query->whereHas('todolistPICs', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        $todolists = $query->with(['children' => function ($q) {
                // Subtasks - we only need the title for the "point form"
                $q->select('id', 'parent_id', 'Title', 'status_id'); 
            }, 'status', 'priority', 'todolistPICs.user'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $todolists->map(function ($todo) {
                return [
                    'id' => $todo->id,
                    'Title' => $todo->Title,
                    'Description' => $todo->Description,
                    'end_date' => $todo->end_date ? $todo->end_date->toDateString() : null,
                    'start_date' => $todo->start_date ? $todo->start_date->toDateString() : null,
                    'status_id' => $todo->status_id,
                    'status' => $todo->status ? $todo->status->name : null,
                    'priority_id' => $todo->priority_id,
                    'priority' => $todo->priority ? $todo->priority->name : null,
                    'subtasks' => $todo->children->pluck('Title'),
                    'pics' => $todo->todolistPICs->map(function ($pic) { // Changed pics to todolistPICs
                        return [
                            'id' => $pic->id,
                            'todolist_id' => $pic->todolist_id,
                            'user_id' => $pic->user_id,
                            'tenant_id' => $pic->tenant_id,
                            'user' => $pic->user ? ['name' => $pic->user->name] : null,
                        ];
                    }),
                ];
            }),
        ]);
    }
    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user) return response()->json(['message' => 'Unauthorized'], 401);

        $validated = $request->validate([
            'Title' => 'required|string|max:255',
            'Description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'status_id' => 'required|exists:lookups,id',
            'priority_id' => 'nullable|exists:lookups,id',
            'pics' => 'nullable|array',
            'pics.*' => 'exists:users,id',
            'subtasks' => 'nullable|array',
            'subtasks.*' => 'string',
        ]);

        $todo = \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $user) {
            $todo = Todolist::create([
                'Title' => $validated['Title'],
                'Description' => $validated['Description'] ?? null,
                'start_date' => $validated['start_date'] ?? null,
                'end_date' => $validated['end_date'] ?? null,
                'status_id' => $validated['status_id'],
                'priority_id' => $validated['priority_id'] ?? null,
                'tenant_id' => $user->tenant_id,
                // Defaults
                'position' => 0,
            ]);

            // Handle PICs
            if (!empty($validated['pics'])) {
                foreach ($validated['pics'] as $userId) {
                    \App\Models\TodolistPIC::create([
                        'todolist_id' => $todo->id,
                        'user_id' => $userId,
                        'tenant_id' => $user->tenant_id,
                    ]);
                }
            }

            // Handle Subtasks (as child Todolists)
            if (!empty($validated['subtasks'])) {
                foreach ($validated['subtasks'] as $subtaskTitle) {
                    Todolist::create([
                        'Title' => $subtaskTitle,
                        'start_date' => $todo->start_date,
                        'end_date' => $todo->end_date,
                        'status_id' => $todo->status_id, // Inherit status? Or default to To Do?
                        'priority_id' => $todo->priority_id,
                        'parent_id' => $todo->id,
                        'tenant_id' => $user->tenant_id,
                    ]);
                }
            }

            return $todo;
        });

        // Load relationships for broadcasting
        $todo->load(['children', 'todolistPICs.user', 'status', 'priority']);

        // Broadcast Event
        // \App\Events\TodolistCreated::dispatch($todo);

        return response()->json(['success' => true, 'data' => $todo], 201);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();
        if (!$user) return response()->json(['message' => 'Unauthorized'], 401);

        $todo = Todolist::where('id', $id)->where('tenant_id', $user->tenant_id)->first();
        if (!$todo) return response()->json(['message' => 'Not found'], 404);

        $validated = $request->validate([
            'Title' => 'sometimes|required|string|max:255',
            'Description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'status_id' => 'sometimes|required|exists:lookups,id',
            'priority_id' => 'nullable|exists:lookups,id',
            'pics' => 'nullable|array',
            'pics.*' => 'exists:users,id',
            'subtasks' => 'nullable|array',
            'subtasks.*' => 'string',
        ]);

        $todo = \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $todo, $user) {
            $todo->update([
                'Title' => $validated['Title'] ?? $todo->Title,
                'Description' => $validated['Description'] ?? $todo->Description,
                'start_date' => $validated['start_date'] ?? $todo->start_date,
                'end_date' => $validated['end_date'] ?? $todo->end_date,
                'status_id' => $validated['status_id'] ?? $todo->status_id,
                'priority_id' => array_key_exists('priority_id', $validated) ? $validated['priority_id'] : $todo->priority_id,
            ]);

            // Sync PICs
            if (isset($validated['pics'])) {
                // Delete existing
                \App\Models\TodolistPIC::where('todolist_id', $todo->id)->delete();
                // Add new
                foreach ($validated['pics'] as $userId) {
                    \App\Models\TodolistPIC::create([
                        'todolist_id' => $todo->id,
                        'user_id' => $userId,
                        'tenant_id' => $user->tenant_id,
                    ]);
                }
            }

            // Sync Subtasks
            if (isset($validated['subtasks'])) {
                Todolist::where('parent_id', $todo->id)->delete();
                foreach ($validated['subtasks'] as $subtaskTitle) {
                    Todolist::create([
                        'Title' => $subtaskTitle,
                        'start_date' => $todo->start_date,
                        'end_date' => $todo->end_date,
                        'status_id' => $todo->status_id,
                        'priority_id' => $todo->priority_id,
                        'parent_id' => $todo->id,
                        'tenant_id' => $user->tenant_id,
                    ]);
                }
            }

            return $todo->refresh();
        });

        // Load relationships for broadcasting
        $todo->load(['children', 'todolistPICs.user', 'status', 'priority']);

        // Broadcast Event
        // \App\Events\TodolistUpdated::dispatch($todo);

        return response()->json(['success' => true, 'data' => $todo]);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $todo = Todolist::where('id', $id)->where('tenant_id', $user->tenant_id)->first();
        if (!$todo) return response()->json(['message' => 'Not found'], 404);

        $todolistId = $todo->id;
        $tenantId = $todo->tenant_id;

        $todo->delete();

        // Broadcast Event
        // \App\Events\TodolistDeleted::dispatch($todolistId, $tenantId);

        return response()->json(['success' => true, 'message' => 'Deleted']);
    }
}
