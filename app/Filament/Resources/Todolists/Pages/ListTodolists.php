<?php



namespace App\Filament\Resources\Todolists\Pages;

use Filament\Forms\Form;

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
        // 1. Get all statuses
        $statusParent = \App\Models\Lookup::where('name', 'Todolist Status')->first();
        $statuses = $statusParent ? \App\Models\Lookup::where('parent_id', $statusParent->id)->get() : collect();
        
        // 2. Base Query using the resource's Eloquent Query
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
            ->orderBy('updated_at', 'desc') // Show most recently completed first
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
        \App\Models\Todolist::where('id', $id)
            ->where('tenant_id', auth()->user()->tenant_id)
            ->update([
                'status_id' => $statusId,
            ]);

        \Filament\Notifications\Notification::make()
            ->title('Todolist updated')
            ->success()
            ->send();
    }
}







