<?php

namespace App\Filament\Resources\Trackings\Pages;

use Filament\Pages\Page;
use App\Models\User;
use App\Models\Lead;
use App\Models\Todolist;
use App\Models\Lookup;
use Illuminate\Support\Facades\Auth;

class Tracking extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-map'; // Or preferred icon

    protected static ?string $slug = 'tracking';
    
    protected static ?string $navigationLabel = 'Tracking';

    protected static string $view = 'filament.pages.tracking';

    public $columns = [];

    public function mount()
    {
        $this->loadTrackingData();
    }

    public function loadTrackingData()
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        // Get all users in the tenant via the pivot table
        $users = User::whereHas('tenants', fn ($q) => $q->where('tenants.id', $tenantId))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        // Initialize user columns
        $itemsByUser = [];
        foreach ($users as $userItem) {
            $itemsByUser[$userItem->id] = [
                'user' => $userItem,
                'leads' => [],
                'todolists' => []
            ];
        }

        // Fetch Leads
        $leads = Lead::where('tenant_id', $tenantId)
            ->with(['leadPICs', 'status'])
            ->get();

        foreach ($leads as $lead) {
            foreach ($lead->leadPICs as $pic) {
                if (isset($itemsByUser[$pic->user_id])) {
                    $itemsByUser[$pic->user_id]['leads'][] = $lead;
                }
            }
        }

        // Fetch Todolists
        // Get "Completed" status ID to filter out
        $todolistStatusParent = Lookup::where('name', 'Todolist Status')->first(); // Adjust lookups as needed
        $completedStatusId = null;
        if ($todolistStatusParent) {
            $completedStatus = Lookup::where('parent_id', $todolistStatusParent->id)
                ->where('name', 'Completed')
                ->first();
            $completedStatusId = $completedStatus?->id;
        }

        $todolistsQuery = Todolist::where('tenant_id', $tenantId)
            ->with(['todolistPICs', 'status', 'priority', 'lead']);
        
        if ($completedStatusId) {
            $todolistsQuery->where(function($q) use ($completedStatusId) {
                $q->where('status_id', '!=', $completedStatusId)
                  ->orWhereNull('status_id');
            });
        }

        $todolists = $todolistsQuery->get();

        foreach ($todolists as $todo) {
            foreach ($todo->todolistPICs as $pic) {
                if (isset($itemsByUser[$pic->user_id])) {
                    $itemsByUser[$pic->user_id]['todolists'][] = $todo;
                }
            }
        }

        $this->columns = $itemsByUser;
    }
}







