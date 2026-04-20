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
        // Logic: Renewals due within next 7 days OR already overdue
        $renewals = \App\Models\Renewal::where('tenant_id', $tenantId)
            ->where('Renew_Date', '<=', now()->addDays(7))
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

        return [
            'events' => \App\Models\Event::where('tenant_id', $tenantId)->get()->map(function ($event) {
                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'start' => $event->start_time,
                    'end' => $event->end_time,
                    'color' => 'blue', // required by calendar.js colorMap
                ];
            }),
            'upcoming_deadline' => $upcomingDeadlineData ?: null,
            'overdue_renewals' => $renewals,
            'customers' => \App\Models\Customer::where('tenant_id', $tenantId)
                ->orderBy('name')
                ->get(['id', 'name', 'company'])
                ->map(fn($c) => ['id' => $c->id, 'label' => $c->name . ($c->company ? ' (' . $c->company . ')' : '')])
                ->values(),
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
        ]);
        $user = auth()->user();
        $tenantId = $user->tenant_id;
        $event = \App\Models\Event::create([
            'title'       => $data['title'],
            'description' => $data['description'] ?? null,
            'start_time'  => $data['start_time'],
            'end_time'    => $data['end_time'],
            'customer_id' => $data['customer_id'] ?? null,
            'tenant_id'   => $tenantId,
        ]);
        return response()->json([
            'success' => true,
            'event' => [
                'id'    => $event->id,
                'title' => $event->title,
                'start' => $event->start_time,
                'end'   => $event->end_time,
            ],
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
