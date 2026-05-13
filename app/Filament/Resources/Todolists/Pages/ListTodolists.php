<?php


namespace App\Filament\Resources\Todolists\Pages;

use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use App\Models\Lookup;
use App\Models\HiddenTenantLookup;
use App\Filament\Resources\Todolists\TodolistResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTodolists extends ListRecords
{
    protected static string $resource = TodolistResource::class;

    public bool $viewEveryone = false;

    public function toggleEveryone()
    {
        $this->viewEveryone = !$this->viewEveryone;
    }

    public function getView(): string
    {
        return 'filament.resources.todolists.pages.kanban';
    }

    protected function getViewData(): array
    {
        $tenantId = \Filament\Facades\Filament::getTenant()?->id;
        $isSuperadmin = optional(auth()->user()->role)->role === 'Superadmin';

        // 1. Find the parent "Todolist Status" category
        $statusParent = \App\Models\Lookup::where('name', 'Todolist Status')->first();

        if (!$statusParent) {
            $statuses = collect();
        } else {
            // Get all global + tenant-specific statuses for this parent
            $query = \App\Models\Lookup::where('parent_id', $statusParent->id)
                ->where(function ($q) use ($tenantId) {
                    $q->whereNull('tenant_id')->orWhere('tenant_id', $tenantId);
                });

            // Exclude hidden lookups for non-superadmins
            if (!$isSuperadmin) {
                $hiddenIds = \App\Models\HiddenTenantLookup::where('tenant_id', $tenantId)
                    ->pluck('lookup_id')->toArray();
                $query->whereNotIn('id', $hiddenIds);
            }

            $allStatuses = $query->get();

            // Sort by sort_order (then by name as fallback for items with no order)
            $statuses = $allStatuses->sortBy([
                ['sort_order', 'asc'],
                ['name', 'asc'],
            ])->values();
        }

        // 2. Base Query
        $isManager = in_array(auth()->user()?->role?->role, ['Superadmin', 'Tenant admin', 'Manager']);

        $baseQuery = static::getResource()::getEloquentQuery()
            ->with(['priority', 'status', 'children']);

        if ($isManager && !$this->viewEveryone) {
            $baseQuery->whereHas('pics', function ($q) {
                $q->where('user_id', auth()->id());
            });
        }

        // 3. Get Active Tasks (Not Completed)
        $activeTasks = (clone $baseQuery)
            ->whereHas('status', fn ($q) => $q->where('name', '!=', 'Completed'))
            ->whereNull('parent_id')
            ->orderBy('end_date', 'asc')
            ->get();

        // 4. Get Recent Completed Tasks (Max 10)
        $recentCompletedTasks = (clone $baseQuery)
            ->whereHas('status', fn ($q) => $q->where('name', 'Completed'))
            ->whereNull('parent_id')
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get();

        // 5. Merge them
        $todolists = $activeTasks->merge($recentCompletedTasks);

        return [
            'statuses' => $statuses,
            'todolists' => $todolists,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->mountUsing(fn (Form $form, array $arguments) => $form->fill([
                    'tenant_id' => auth()->user()?->tenant_id,
                ])),
        ];
    }

    protected function getActions(): array
    {
        return [
            ...$this->getHeaderActions(),
            $this->editLookupAction(),
        ];
    }

    public function editLookupAction(): Action
    {
        return Action::make('editLookup')
            ->form([
                \Filament\Forms\Components\Placeholder::make('usage_info')
                    ->label('Currently In Use')
                    ->content(fn ($state) => $state ?? '—'),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                \Filament\Forms\Components\ColorPicker::make('color')
                    ->label('Color'),
                TextInput::make('sort_order')
                    ->label('Display Order')
                    ->numeric()
                    ->minValue(1)
                    ->helperText('Lower number = appears first in the Kanban board.'),
                \Filament\Forms\Components\Placeholder::make('order_preview')
                    ->label('Column Order Preview')
                    ->content(fn ($state) => $state ? new \Illuminate\Support\HtmlString($state) : ''),
            ])
            ->fillForm(function (array $arguments) {
                $lookup = Lookup::find($arguments['id']);
                if (!$lookup) return ['name' => '', 'color' => '', 'usage_info' => '—', 'sort_order' => 0, 'order_preview' => ''];

                $tenantId = Filament::getTenant()?->id;
                $todolistStatusParent = Lookup::where('name', 'Todolist Status')->first();

                // Build rich visual breadcrumb
                $orderPreview = '';
                if ($todolistStatusParent) {
                    $hiddenIds = \App\Models\HiddenTenantLookup::where('tenant_id', $tenantId)->pluck('lookup_id')->toArray();
                    $all = \App\Models\Lookup::where('parent_id', $todolistStatusParent->id)
                        ->where(function($q) use ($tenantId) {
                            $q->whereNull('tenant_id')->orWhere('tenant_id', $tenantId);
                        })
                        ->whereNotIn('id', $hiddenIds)
                        ->orderBy('sort_order')->orderBy('name')
                        ->get();

                    $parts = $all->map(function($s) use ($lookup) {
                        $isCurrent = $s->id == $lookup->id;
                        $dot = "<span style='width:8px;height:8px;border-radius:50%;background:{$s->color};display:inline-block;margin-right:4px;'></span>";
                        $label = $isCurrent
                            ? "<strong style='color:inherit'>{$s->name}</strong> <span style='background:#e0e7ff;color:#4338ca;font-size:11px;padding:1px 6px;border-radius:999px;'>{$s->sort_order}</span>"
                            : "{$s->name} <span style='color:#9ca3af;font-size:11px;'>{$s->sort_order}</span>";
                        return $dot . $label;
                    })->implode(' &nbsp;›&nbsp; ');

                    $orderPreview = "<div style='font-size:13px;color:#374151;display:flex;align-items:center;flex-wrap:wrap;gap:4px;'>{$parts}</div>";
                }

                // Usage count
                $count = \App\Models\Todolist::where('tenant_id', $tenantId)
                    ->where('status_id', $lookup->id)->count();
                $usageInfo = $count > 0
                    ? "{$count} todolist(s) using this status. They will be migrated automatically."
                    : 'No todolists currently using this status.';

                return [
                    'name'          => $lookup->name,
                    'color'         => $lookup->color,
                    'sort_order'    => $lookup->sort_order,
                    'usage_info'    => $usageInfo,
                    'order_preview' => $orderPreview,
                ];
            })
            ->action(function (array $data, array $arguments) {
                $tenantId = Filament::getTenant()?->id;
                $isSuperadmin = optional(auth()->user()->role)->role === 'Superadmin';
                $lookup = Lookup::find($arguments['id']);
                if (!$lookup) return;

                $newSortOrder = (int) ($data['sort_order'] ?? $lookup->sort_order);
                $oldSortOrder = $lookup->sort_order;

                // Implement swapping logic if sort_order changed
                if ($newSortOrder != $oldSortOrder) {
                    $conflictingLookup = Lookup::where('parent_id', $lookup->parent_id)
                        ->where('tenant_id', $lookup->tenant_id)
                        ->where('sort_order', $newSortOrder)
                        ->where('id', '!=', $lookup->id)
                        ->first();

                    if ($conflictingLookup) {
                        $conflictingLookup->update(['sort_order' => $oldSortOrder]);
                    }
                }

                if ($lookup->tenant_id === null && !$isSuperadmin) {
                    $newLookup = Lookup::create([
                        'tenant_id'  => $tenantId,
                        'name'       => $data['name'],
                        'label'      => $data['name'],
                        'color'      => $data['color'] ?? $lookup->color,
                        'sort_order' => $newSortOrder,
                        'parent_id'  => $lookup->parent_id,
                    ]);
                    HiddenTenantLookup::firstOrCreate(['lookup_id' => $lookup->id, 'tenant_id' => $tenantId]);

                    $todolistStatusParent = Lookup::where('name', 'Todolist Status')->first();
                    if ($todolistStatusParent && $lookup->parent_id == $todolistStatusParent->id) {
                        \App\Models\Todolist::where('tenant_id', $tenantId)
                            ->where('status_id', $lookup->id)
                            ->update(['status_id' => $newLookup->id]);
                    }
                } else {
                    $lookup->update([
                        'name'       => $data['name'],
                        'label'      => $data['name'],
                        'color'      => $data['color'] ?? $lookup->color,
                        'sort_order' => $newSortOrder,
                    ]);
                }
            });
    }

    public function openCreateTask(int $statusId)
    {
        return $this->redirect(TodolistResource::getUrl('create', ['status_id' => $statusId]), navigate: true);
    }

    public function openTask(int $id)
    {
        return $this->redirect(TodolistResource::getUrl('edit', ['record' => $id]), navigate: true);
    }

    public function updateTaskStatus(int $id, int $statusId)
    {
        $task = \App\Models\Todolist::find($id);
        
        if ($task && $task->tenant_id == auth()->user()->tenant_id) {
            $task->update(['status_id' => $statusId]);
        }

        \Filament\Notifications\Notification::make()
            ->title('Todolist updated')
            ->success()
            ->send();
    }
}







