<?php

namespace App\Filament\Resources\LookupResource\Pages;

use App\Filament\Resources\LookupResource;
use Filament\Actions;
use Filament\Resources\Pages\Page;

use App\Models\Lookup;
use App\Models\HiddenTenantLookup;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\TextInput;
use Filament\Facades\Filament;
use Illuminate\Support\Collection;

class ManageLookups extends Page
{
    protected static string $resource = LookupResource::class;

    protected static string $view = 'filament.resources.lookup-resource.pages.manage-lookups';

    public $selectedGroupId = null;
    public $selectedParentId = null;

    public function mount()
    {
        $firstGroup = Lookup::whereNull('tenant_id')
            ->whereNull('parent_id')
            ->orderBy('name')
            ->first();
        if ($firstGroup) {
            $this->selectedGroupId = $firstGroup->id;
            $parentQuery = Lookup::whereNull('tenant_id')
                ->where('parent_id', $firstGroup->id);
            
            if (optional(auth()->user()->role)->role !== 'Superadmin') {
                $parentQuery->where('name', '!=', 'Audit Type');
            }

            $firstParent = $parentQuery->orderBy('name')->first();
            if ($firstParent) {
                $this->selectedParentId = $firstParent->id;
            }
        }
    }

    public function selectGroup($id)
    {
        $this->selectedGroupId = $id;
        $this->selectedParentId = null;
        // Auto-select first category in this group
        $parentQuery = Lookup::whereNull('tenant_id')
            ->where('parent_id', $id);
        
        if (optional(auth()->user()->role)->role !== 'Superadmin') {
            $parentQuery->where('name', '!=', 'Audit Type');
        }

        $first = $parentQuery->orderBy('name')->first();
        if ($first) {
            $this->selectedParentId = $first->id;
        }
    }

    public function selectParent($id)
    {
        $this->selectedParentId = $id;
    }

    public function getGroups(): Collection
    {
        return Lookup::whereNull('tenant_id')
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();
    }

    public function getParents(): Collection
    {
        if (!$this->selectedGroupId) return collect();
        $query = Lookup::whereNull('tenant_id')
            ->where('parent_id', $this->selectedGroupId);

        if (optional(auth()->user()->role)->role !== 'Superadmin') {
            $query->where('name', '!=', 'Audit Type');
        }

        return $query->orderBy('name')->get();
    }

    public function getChildren(): Collection
    {
        if (!$this->selectedParentId) return collect();
        $tenantId = Filament::getTenant()?->id;
        $isSuperadmin = optional(auth()->user()->role)->role === 'Superadmin';

        $query = Lookup::where(function ($query) use ($tenantId) {
                // Global/Tenant lookups
                $query->where(function($sq) use ($tenantId) {
                    $sq->where(fn($ssq) => $ssq->whereNull('tenant_id')->orWhere('tenant_id', $tenantId))
                       ->whereNull('user_id');
                })
                // Private user lookups
                ->orWhere('user_id', auth()->id());
            })
            ->where('parent_id', $this->selectedParentId);

        // Superadmins always see everything, including what tenants have hidden
        if (!$isSuperadmin) {
            $hiddenIds = HiddenTenantLookup::where('tenant_id', $tenantId)
                ->pluck('lookup_id')
                ->toArray();
            $query->whereNotIn('id', $hiddenIds);
        }

        return $query->orderBy('name')->get();
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function addParentAction(): Action
    {
        return Action::make('addParent')
            ->label('New Parent')
            ->color('gray')
            ->link()
            ->icon('heroicon-m-plus')
            ->visible(fn() => optional(auth()->user()->role)->role === 'Superadmin')
            ->form([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
            ])
            ->action(function (array $data) {
                $record = Lookup::create([
                    'tenant_id' => null, // Categories are always global
                    'name' => $data['name'],
                    'label' => $data['name'],
                    'parent_id' => $this->selectedGroupId,
                ]);
                $this->selectedParentId = $record->id;
            });
    }

    public function addSubCategoryAction(): Action
    {
        return Action::make('addSubCategory')
            ->label('New Sub-category')
            ->color('gray')
            ->link()
            ->icon('heroicon-m-plus')
            ->form([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                \Filament\Forms\Components\ColorPicker::make('color')
                    ->label('Color'),
                \Filament\Forms\Components\ViewField::make('is_global')
                    ->view('filament.forms.components.custom-toggle')
                    ->label('Global')
                    ->default(false)
                    ->visible(fn() => optional(auth()->user()->role)->role === 'Superadmin'),
                \Filament\Forms\Components\ViewField::make('is_private')
                    ->view('filament.forms.components.custom-toggle')
                    ->label('Private (Only for me)')
                    ->default(false)
                    ->hidden(fn($get) => $get('is_global')),
            ])
            ->action(function (array $data) {
                $isGlobal = $data['is_global'] ?? false;
                $isPrivate = $data['is_private'] ?? false;
                Lookup::create([
                    'tenant_id' => $isGlobal ? null : Filament::getTenant()->id,
                    'user_id'   => ($isPrivate && !$isGlobal) ? auth()->id() : null,
                    'name'      => $data['name'],
                    'label'     => $data['name'],
                    'color'     => $data['color'] ?? null,
                    'parent_id' => $this->selectedParentId,
                ]);
            });
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
                \Filament\Forms\Components\ViewField::make('is_private')
                    ->view('filament.forms.components.custom-toggle')
                    ->label('Private (Only for me)')
                    ->visible(fn($get) => !$get('is_global')),
                TextInput::make('sort_order')
                    ->label('Display Order')
                    ->numeric()
                    ->minValue(1)
                    ->helperText('Lower number = appears first in the Kanban board.')
                    ->visible(fn ($get, $livewire) => (
                        $livewire->selectedParentId &&
                        \App\Models\Lookup::find($livewire->selectedParentId)?->name === 'Todolist Status'
                    )),
                \Filament\Forms\Components\Placeholder::make('order_preview')
                    ->label('Column Order Preview')
                    ->content(fn ($state) => $state ? new \Illuminate\Support\HtmlString($state) : '')
                    ->visible(fn ($get, $livewire) => (
                        $livewire->selectedParentId &&
                        \App\Models\Lookup::find($livewire->selectedParentId)?->name === 'Todolist Status'
                    )),
            ])
            ->fillForm(function (array $arguments) {
                $lookup = Lookup::find($arguments['id']);
                if (!$lookup) return ['name' => '', 'color' => '', 'usage_info' => '—', 'sort_order' => 0, 'order_preview' => ''];

                $tenantId = Filament::getTenant()?->id;
                $todolistStatusParent = Lookup::where('name', 'Todolist Status')->first();
                $isStatus = $todolistStatusParent && $lookup->parent_id == $todolistStatusParent->id;

                $usageInfo = '—';
                $orderPreview = '';
                if ($isStatus) {
                    $count = \App\Models\Todolist::where('tenant_id', $tenantId)
                        ->where('status_id', $lookup->id)
                        ->count();
                    $usageInfo = $count > 0
                        ? "{$count} todolist(s) are using this status. They will be automatically migrated."
                        : 'No todolists are currently using this status.';

                    // Build visual breadcrumb
                    $hiddenIds = \App\Models\HiddenTenantLookup::where('tenant_id', $tenantId)->pluck('lookup_id')->toArray();
                    $all = Lookup::where('parent_id', $todolistStatusParent->id)
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

                return [
                    'name'          => $lookup->name,
                    'color'         => $lookup->color,
                    'is_global'     => $lookup->tenant_id === null,
                    'is_private'    => $lookup->user_id !== null,
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
                    // Global lookup: create a tenant-specific override and hide the original
                    $newLookup = Lookup::create([
                        'tenant_id'  => $tenantId,
                        'name'       => $data['name'],
                        'label'      => $data['name'],
                        'color'      => $data['color'] ?? $lookup->color,
                        'sort_order' => $newSortOrder,
                        'parent_id'  => $lookup->parent_id,
                    ]);

                    HiddenTenantLookup::firstOrCreate([
                        'lookup_id' => $lookup->id,
                        'tenant_id' => $tenantId,
                    ]);

                    // Migrate existing todolists from the old global status to the new tenant override
                    $todolistStatusParent = Lookup::where('name', 'Todolist Status')->first();
                    if ($todolistStatusParent && $lookup->parent_id == $todolistStatusParent->id) {
                        \App\Models\Todolist::where('tenant_id', $tenantId)
                            ->where('status_id', $lookup->id)
                            ->update(['status_id' => $newLookup->id]);
                    }
                } else {
                    // Tenant's own lookup OR superadmin editing global: update directly (id unchanged, no migration needed)
                    $lookup->update([
                        'name'       => $data['name'],
                        'label'      => $data['name'],
                        'color'      => $data['color'] ?? $lookup->color,
                        'user_id'    => ($data['is_private'] ?? false) ? auth()->id() : null,
                        'sort_order' => $newSortOrder,
                    ]);
                }
            });
    }

    public function deleteLookupAction(): Action
    {
        return Action::make('deleteLookup')
            ->requiresConfirmation()
            ->modalDescription(function (array $arguments) {
                $lookup = Lookup::find($arguments['id'] ?? 0);
                if (!$lookup) return 'Are you sure you want to delete this item?';

                $tenantId = Filament::getTenant()?->id;
                $todolistStatusParent = Lookup::where('name', 'Todolist Status')->first();
                $isStatus = $todolistStatusParent && $lookup->parent_id == $todolistStatusParent->id;

                if ($isStatus) {
                    $count = \App\Models\Todolist::where('tenant_id', $tenantId)
                        ->where('status_id', $lookup->id)
                        ->count();

                    if ($count > 0) {
                        return new \Illuminate\Support\HtmlString(
                            "<span class='text-red-600 font-medium'>⚠️ {$count} todolist(s) are currently using this status.</span><br>"
                            . "Deleting or hiding it may cause those todolists to show no status. Are you sure you want to continue?"
                        );
                    }
                }

                return 'Are you sure you want to delete this item? This action cannot be undone.';
            })
            ->action(function (array $arguments) {
                $tenantId = Filament::getTenant()?->id;
                $lookup = Lookup::find($arguments['id']);

                if (!$lookup) return;

                $isSuperadmin = optional(auth()->user()->role)->role === 'Superadmin';

                if ($lookup->tenant_id === null && !$isSuperadmin) {
                    // Global lookup for tenant: hide it instead of deleting
                    HiddenTenantLookup::firstOrCreate([
                        'lookup_id' => $lookup->id,
                        'tenant_id' => $tenantId,
                    ]);
                    return;
                }

                if ($lookup->tenant_id !== null && $lookup->tenant_id != $tenantId && !$isSuperadmin) {
                    return; // Security: cannot delete other tenant's records
                }

                $lookup->delete();
                if ($this->selectedParentId == $arguments['id']) {
                    $this->selectedParentId = $this->getParents()->first()?->id;
                }
            });
    }
}

