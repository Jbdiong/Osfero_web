<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\LeadPIC;
use App\Models\Todolist;
use App\Models\TodolistPIC;
use App\Models\Lookup;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TrackingController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        // Get all users in the tenant
        $users = User::where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        // Get all leads in the tenant with their PICs
        // Filter PICs by tenant_id and ensure users belong to the current tenant
        $leads = Lead::where('tenant_id', $tenantId)
            ->with([
                'leadPICs' => function($query) use ($tenantId) {
                    $query->where('tenant_id', $tenantId)
                        ->with(['user' => function($userQuery) use ($tenantId) {
                            $userQuery->where('tenant_id', $tenantId);
                        }]);
                },
                'status'
            ])
            ->get();

        // Get "Completed" status ID for todolists
        $todolistStatusParent = Lookup::where('name', 'Todolist Status')
            ->whereNull('parent_id')
            ->whereNull('tenant_id')
            ->first();
        
        $completedStatusId = null;
        if ($todolistStatusParent) {
            $completedStatus = Lookup::where('parent_id', $todolistStatusParent->id)
                ->where('name', 'Completed')
                ->whereNull('tenant_id')
                ->first();
            $completedStatusId = $completedStatus ? $completedStatus->id : null;
        }

        // Get all todolists in the tenant that are not completed, with their PICs
        // Filter PICs by tenant_id and ensure users belong to the current tenant
        $todolistsQuery = Todolist::where('tenant_id', $tenantId)
            ->with([
                'todolistPICs' => function($query) use ($tenantId) {
                    $query->where('tenant_id', $tenantId)
                        ->with(['user' => function($userQuery) use ($tenantId) {
                            $userQuery->where('tenant_id', $tenantId);
                        }]);
                },
                'status',
                'priority',
                'lead'
            ]);
        
        if ($completedStatusId) {
            $todolistsQuery->where(function($query) use ($completedStatusId) {
                $query->where('status_id', '!=', $completedStatusId)
                    ->orWhereNull('status_id');
            });
        }
        
        $todolists = $todolistsQuery->get();

        // Group leads and todolists by user (PIC)
        $itemsByUser = [];
        
        // Initialize with all users
        foreach ($users as $userItem) {
            $itemsByUser[$userItem->id] = [
                'user' => $userItem,
                'leads' => [],
                'todolists' => []
            ];
        }

        // Distribute leads to their assigned users (only if they have PICs)
        foreach ($leads as $lead) {
            $leadPICs = $lead->leadPICs;
            
            if (!$leadPICs->isEmpty()) {
                // Assign to each PIC
                foreach ($leadPICs as $leadPIC) {
                    $userId = $leadPIC->user_id;
                    if (isset($itemsByUser[$userId])) {
                        $itemsByUser[$userId]['leads'][] = $this->transformLead($lead);
                    }
                }
            }
        }

        // Distribute todolists to their assigned users (PICs) (only if they have PICs)
        foreach ($todolists as $todolist) {
            $todolistPICs = $todolist->todolistPICs;
            
            if (!$todolistPICs->isEmpty()) {
                // Assign to each PIC
                foreach ($todolistPICs as $todolistPIC) {
                    $userId = $todolistPIC->user_id;
                    if (isset($itemsByUser[$userId])) {
                        $itemsByUser[$userId]['todolists'][] = $this->transformTodolist($todolist);
                    }
                }
            }
        }

        // Transform to array format for frontend
        $columns = [];
        foreach ($itemsByUser as $key => $data) {
            $columns[$key] = [
                'id' => $data['user']->id,
                'name' => $data['user']->name,
                'email' => $data['user']->email,
                'leads' => $data['leads'],
                'todolists' => $data['todolists']
            ];
        }

        return Inertia::render('Tracking/Index', [
            'columns' => $columns,
            'users' => $users,
        ]);
    }

    private function transformLead($lead)
    {
        return [
            'id' => $lead->id,
            'shop_name' => $lead->Shop_Name,
            'industry' => $lead->Industry,
            'city' => $lead->City,
            'state' => $lead->State,
            'country' => $lead->Country,
            'status' => $lead->status ? $lead->status->name : null,
            'relevant' => $lead->relevant,
            'last_modified' => $lead->last_modified ? $lead->last_modified->format('Y-m-d H:i:s') : null,
            'type' => 'lead',
        ];
    }

    private function transformTodolist($todolist)
    {
        return [
            'id' => $todolist->id,
            'title' => $todolist->Title,
            'description' => $todolist->Description,
            'status' => $todolist->status ? $todolist->status->name : null,
            'priority' => $todolist->priority ? $todolist->priority->name : null,
            'start_date' => $todolist->start_date ? $todolist->start_date->format('Y-m-d') : null,
            'end_date' => $todolist->end_date ? $todolist->end_date->format('Y-m-d') : null,
            'lead' => $todolist->lead ? [
                'id' => $todolist->lead->id,
                'shop_name' => $todolist->lead->Shop_Name,
            ] : null,
            'type' => 'todolist',
        ];
    }
}



