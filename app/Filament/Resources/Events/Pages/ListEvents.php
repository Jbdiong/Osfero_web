<?php

namespace App\Filament\Resources\Events\Pages;

use App\Filament\Resources\Events\EventResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEvents extends ListRecords
{
    protected static string $resource = EventResource::class;

    protected static string $view = 'filament.resources.events.pages.list-events';

    protected function getViewData(): array
    {
        $user = auth()->user();
        $tenantId = $user->tenant_id;

        // 1. Upcoming Deadline (Todolist)
        // Find the most urgent pending task for this user
        // We need to check TodolistPICs to see if user is assigned
        $upcomingTask = \App\Models\Todolist::where('tenant_id', $tenantId)
            ->whereHas('todolistPICs', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->whereHas('status', function ($q) {
                $q->where('name', '!=', 'Completed'); // Assuming 'Completed' is the logic
            })
            ->whereNotNull('end_date')
            ->orderBy('end_date', 'asc')
            ->first();

        $upcomingDeadlineData = [];
        if ($upcomingTask) {
            $days = now()->diffInDays($upcomingTask->end_date, false);
            $countdownText = $days < 0 ? abs((int)$days) . ' days overdue' : ((int)$days == 0 ? 'Today' : (int)$days . ' days');
            
            // Count other tasks due this week
            $startOfWeek = now()->startOfWeek();
            $endOfWeek = now()->endOfWeek();
            
            $otherTasksCount = \App\Models\Todolist::where('tenant_id', $tenantId)
                ->where('id', '!=', $upcomingTask->id)
                ->whereHas('todolistPICs', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                })
                ->whereHas('status', function ($q) {
                    $q->where('name', '!=', 'Completed');
                })
                ->whereBetween('end_date', [$startOfWeek, $endOfWeek])
                ->count();

            $upcomingDeadlineData = [
                'title' => $upcomingTask->Title,
                'countdown' => $countdownText,
                'is_overdue' => $days < 0,
                'more_count' => $otherTasksCount,
                'id' => $upcomingTask->id,
            ];
        }

        // 2. Overdue/Upcoming Renewals
        // Sidebar logic: Renewals due within next 7 days OR already overdue
        $renewals = \App\Models\Renewal::where('tenant_id', $tenantId)
            ->where('Renew_Date', '<=', now()->addDays(7))
            ->whereHas('status', function ($q) {
                $q->where('name', '!=', 'Ended');
            })
            ->orderBy('Renew_Date', 'asc')
            ->get()
            ->map(function ($renewal) {
                 return [
                    'id' => $renewal->id,
                    'label' => $renewal->label,
                    'Renew_Date' => $renewal->Renew_Date,
                    'start_date' => $renewal->start_date,
                    'status_id' => $renewal->status_id,
                 ];
            });

        // Calendar logic: ALL renewals where status is not 'Ended'
        $calendarRenewals = \App\Models\Renewal::where('tenant_id', $tenantId)
            ->whereHas('status', function ($q) {
                $q->where('name', '!=', 'Ended');
            })
            ->get()
            ->map(function ($renewal) {
                 return [
                    'id' => 'renewal-' . $renewal->id,
                    'title' => $renewal->label . ' (Renewal)',
                    'start' => $renewal->Renew_Date->format('Y-m-d'),
                    'allDay' => true,
                    'type' => 'renewal',
                 ];
            });

        // 3. Incomplete Todolists (show on calendar if start_date or end_date is set)
        // Only show tasks where the current user is a PIC
        $todolists = \App\Models\Todolist::where('tenant_id', $tenantId)
            ->whereHas('todolistPICs', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->whereHas('status', function ($q) {
                $q->where('name', '!=', 'Completed');
            })
            ->where(function ($q) {
                $q->whereNotNull('start_date')
                  ->orWhereNotNull('end_date');
            })
            ->get()
            ->map(function ($task) {
                // Use start_date as start; fall back to end_date if no start_date
                $start = $task->start_date
                    ? $task->start_date->format('Y-m-d')
                    : $task->end_date->format('Y-m-d');

                // end is exclusive in FullCalendar all-day events, so add 1 day
                $end = $task->end_date
                    ? $task->end_date->copy()->addDay()->format('Y-m-d')
                    : null;

                return [
                    'id'    => 'todo-' . $task->id,
                    'title' => $task->Title,
                    'start' => $start,
                    'end'   => $end,
                    'type'  => 'todolist',
                ];
            });

        return [
            'events' => \App\Models\Event::where('tenant_id', $tenantId)
                ->whereHas('eventPICs', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                })
                ->get()
                ->map(function ($event) {
                return [
                    'id'            => $event->id,
                    'title'         => $event->title,
                    'start'         => $event->start_time,
                    'end'           => $event->end_time,
                    'allDay'        => (bool)$event->all_day,
                    'color'         => 'blue',
                    'customer_id'   => $event->customer_id,
                    'event_type_id' => $event->event_type_id,
                    'description'   => $event->description,
                ];
            }),
            'event_types' => $this->getEventTypes($tenantId),
            'upcoming_deadline' => $upcomingDeadlineData ?: null,
            'overdue_renewals' => $renewals,
            'calendar_renewals' => $calendarRenewals,
            'todolists' => $todolists,
            'customers' => \App\Models\Customer::where('tenant_id', $tenantId)
                ->orderBy('name')
                ->get(['id', 'name', 'company'])
                ->map(fn($c) => ['id' => $c->id, 'label' => $c->name . ($c->company ? ' (' . $c->company . ')' : '')])
                ->values(),
            'category_colors' => [
                'events' => \App\Models\Lookup::where('name', 'Event')->whereNull('parent_id')->value('color') ?? '#ff6700',
                'todolist' => \App\Models\Lookup::where('name', 'Todolist')->whereNull('parent_id')->value('color') ?? '#3b82f6',
                'renewals' => \App\Models\Lookup::where('name', 'Renewal')->whereNull('parent_id')->value('color') ?? '#ef4444',
                'holidays' => '#008002',
            ],
            'tenant_id' => $tenantId,
        ];
    }

    // POST handler for quick-save from the calendar modal
    public function storeQuickEvent(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_time'  => 'required|date',
            'end_time'    => 'required|date|after_or_equal:start_time',
            'customer_id' => 'nullable|exists:customers,id',
            'all_day'     => 'nullable|boolean',
        ]);
        $user = auth()->user();
        $tenantId = $user->tenant_id;
        $event = \App\Models\Event::create([
            'title'         => $data['title'],
            'description'   => $data['description'] ?? null,
            'start_time'    => $data['start_time'],
            'end_time'      => $data['end_time'],
            'customer_id'   => $data['customer_id'] ?? null,
            'event_type_id' => $request->event_type_id,
            'all_day'       => $data['all_day'] ?? false,
            'tenant_id'     => $tenantId,
        ]);

        // Assign current user as PIC
        $event->eventPICs()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenantId,
        ]);
        return response()->json([
            'success' => true,
            'event' => [
                'id'          => $event->id,
                'title'       => $event->title,
                'description' => $event->description,
                'start'       => $event->start_time->toDateTimeString(),
                'end'         => $event->end_time->toDateTimeString(),
                'allDay'      => (bool)$event->all_day,
                'customer_id' => $event->customer_id,
                'event_type_id' => $event->event_type_id,
            ],
        ]);
    }

    // PATCH handler — edit an existing event from the calendar modal
    public function updateQuickEvent(\Illuminate\Http\Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        $user    = auth()->user();
        $event   = \App\Models\Event::where('tenant_id', $user->tenant_id)
            ->whereHas('eventPICs', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->findOrFail($id);

        $data = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_time'  => 'required|date',
            'end_time'    => 'required|date|after_or_equal:start_time',
            'customer_id' => 'nullable|exists:customers,id',
            'all_day'     => 'nullable|boolean',
        ]);

        $event->update([
            'title'         => $data['title'],
            'description'   => $data['description'] ?? null,
            'start_time'    => $data['start_time'],
            'end_time'      => $data['end_time'],
            'customer_id'   => $data['customer_id'] ?? null,
            'event_type_id' => $request->event_type_id,
            'all_day'       => $data['all_day'] ?? false,
        ]);

        return response()->json([
            'success' => true,
            'event'   => [
                'id'          => $event->id,
                'title'       => $event->title,
                'description' => $event->description,
                'start'       => $event->start_time->toDateTimeString(),
                'end'         => $event->end_time->toDateTimeString(),
                'allDay'      => (bool)$event->all_day,
                'customer_id' => $event->customer_id,
                'event_type_id' => $event->event_type_id,
            ],
        ]);
    }

    protected function getEventTypes($tenantId)
    {
        $eventTypeParent = \App\Models\Lookup::where('name', 'Event Type')->first();
        if (!$eventTypeParent) return collect();

        $hiddenIds = \App\Models\HiddenTenantLookup::where('tenant_id', $tenantId)->pluck('lookup_id')->toArray();
        return \App\Models\Lookup::where('parent_id', $eventTypeParent->id)
            ->where(function($q) use ($tenantId) {
                // Global/Tenant lookups (public)
                $q->where(function($sq) use ($tenantId) {
                    $sq->where(fn($ssq) => $ssq->whereNull('tenant_id')->orWhere('tenant_id', $tenantId))
                        ->whereNull('user_id');
                })
                // User-specific lookups (private)
                ->orWhere('user_id', auth()->id());
            })
            ->whereNotIn('id', $hiddenIds)
            ->orderBy('sort_order')->orderBy('name')
            ->get(['id', 'name', 'color']);
    }

    public function fetchEventTypes(): array
    {
        return $this->getEventTypes(auth()->user()->tenant_id)->toArray();
    }

    // DELETE handler — remove an event from the calendar modal
    public function deleteQuickEvent(int $id): \Illuminate\Http\JsonResponse
    {
        $user  = auth()->user();
        $event = \App\Models\Event::where('tenant_id', $user->tenant_id)
            ->whereHas('eventPICs', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->findOrFail($id);
        $event->delete();

        return response()->json(['success' => true]);
    }

    // GET handler — returns fresh customer list for the modal dropdown
    public function getCustomers(): \Illuminate\Http\JsonResponse
    {
        $user = auth()->user();
        $customers = \App\Models\Customer::where('tenant_id', $user->tenant_id)
            ->orderBy('name')
            ->get(['id', 'name', 'company'])
            ->map(fn($c) => [
                'id'    => $c->id,
                'label' => $c->name . ($c->company ? ' (' . $c->company . ')' : ''),
            ])
            ->values();

        return response()->json($customers);
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            \Filament\Actions\Action::make('createEventType')
                ->label('New Event Type')
                ->color('gray')
                ->icon('heroicon-m-plus')
                ->form([
                    \Filament\Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    \Filament\Forms\Components\ColorPicker::make('color')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $tenantId = auth()->user()->tenant_id;
                    $parent = \App\Models\Lookup::where('name', 'Event Type')->first();
                    
                    if (!$parent) {
                        // Create parent if missing (shouldn't happen but safe)
                        $eventGroup = \App\Models\Lookup::where('name', 'Event')->first();
                        $parent = \App\Models\Lookup::create([
                            'name' => 'Event Type',
                            'label' => 'Event Type',
                            'parent_id' => $eventGroup?->id,
                        ]);
                    }

                    \App\Models\Lookup::create([
                        'tenant_id' => $tenantId,
                        'user_id' => auth()->id(),
                        'name' => $data['name'],
                        'label' => $data['name'],
                        'color' => $data['color'],
                        'parent_id' => $parent->id,
                    ]);

                    \Filament\Notifications\Notification::make()
                        ->title('Event type created')
                        ->success()
                        ->send();
                        
                    $this->dispatch('refresh-event-types');
                }),
        ];
    }
}
