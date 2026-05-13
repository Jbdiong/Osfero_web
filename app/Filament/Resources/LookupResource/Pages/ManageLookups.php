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
                $query->whereNull('tenant_id')
                    ->orWhere('tenant_id', $tenantId);
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
                    'parent_id' => null,
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
                \Filament\Forms\Components\ViewField::make('is_global')
                    ->view('filament.forms.components.custom-toggle')
                    ->default(false)
                    ->visible(fn() => optional(auth()->user()->role)->role === 'Superadmin'),
            ])
            ->action(function (array $data) {
                $isGlobal = $data['is_global'] ?? false;
                Lookup::create([
                    'tenant_id' => $isGlobal ? null : Filament::getTenant()->id,
                    'name' => $data['name'],
                    'label' => $data['name'],
                    'parent_id' => $this->selectedParentId,
                ]);
            });
    }

    public function editLookupAction(): Action
    {
        return Action::make('editLookup')
            ->form([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
            ])
            ->fillForm(function (array $arguments) {
                $lookup = Lookup::find($arguments['id']);
                return ['name' => $lookup?->name];
            })
            ->action(function (array $data, array $arguments) {
                $tenantId = Filament::getTenant()?->id;
                $isSuperadmin = optional(auth()->user()->role)->role === 'Superadmin';
                $lookup = Lookup::find($arguments['id']);

                if (!$lookup) return;

                if ($lookup->tenant_id === null && !$isSuperadmin) {
                    // Global lookup: create a tenant-specific override and hide the original
                    Lookup::create([
                        'tenant_id' => $tenantId,
                        'name' => $data['name'],
                        'label' => $data['name'],
                        'parent_id' => $lookup->parent_id,
                    ]);

                    HiddenTenantLookup::firstOrCreate([
                        'lookup_id' => $lookup->id,
                        'tenant_id' => $tenantId,
                    ]);
                } else {
                    // Tenant's own lookup: update directly
                    $lookup->update([
                        'name' => $data['name'],
                        'label' => $data['name'],
                    ]);
                }
            });
    }

    public function deleteLookupAction(): Action
    {
        return Action::make('deleteLookup')
            ->requiresConfirmation()
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

