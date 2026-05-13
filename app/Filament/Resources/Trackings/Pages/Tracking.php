<?php

namespace App\Filament\Resources\Trackings\Pages;

use Filament\Pages\Page;
use App\Models\User;
use App\Models\Lead;
use App\Models\Todolist;
use App\Models\Lookup;
use App\Models\Customer;
use Illuminate\Support\Facades\Auth;

class Tracking extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-map'; // Or preferred icon

    protected static ?string $slug = 'tracking';
    
    protected static ?string $navigationLabel = 'Tracking';

    protected static string $view = 'filament.pages.tracking';

    public $columns = [];
    public $activeTab = 'tasks';

    public function getHeading(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return new \Illuminate\Support\HtmlString('
            <div class="flex items-center gap-4">
                <span>Tracking</span>
                <select wire:model.live="activeTab" class="text-sm font-normal rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 py-1.5 pl-3 pr-8 focus:ring-blue-500 focus:border-blue-500">
                    <option value="tasks">Todolist</option>
                    <option value="customers">Customers</option>
                </select>
            </div>
        ');
    }

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
            ->where('status', '!=', User::STATUS_DELETED)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        // Initialize user columns
        $itemsByUser = [];
        foreach ($users as $userItem) {
            $itemsByUser[$userItem->id] = [
                'user' => $userItem,
                'todolists' => [],
                'customers' => [],
            ];
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

        // Fetch Customers
        $customers = Customer::where('tenant_id', $tenantId)
            ->with(['pics'])
            ->get();

        foreach ($customers as $customer) {
            foreach ($customer->pics as $picUser) {
                if (isset($itemsByUser[$picUser->id])) {
                    $itemsByUser[$picUser->id]['customers'][] = $customer;
                }
            }
        }

        $this->columns = $itemsByUser;
    }
}







