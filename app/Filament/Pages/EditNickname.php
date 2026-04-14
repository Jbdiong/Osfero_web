<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Notifications\Notification;
use Filament\Facades\Filament;
use Filament\Pages\Dashboard;
use App\Models\Tenant;

class EditNickname extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static string $view = 'filament.pages.edit-nickname';

    protected static bool $shouldRegisterNavigation = false;

    public ?array $data = [];

    public function mount(): void
    {
        $tenant = Filament::getTenant();
        
        if (!$tenant) {
            abort(404);
        }

        $pivot = auth()->user()->tenants()
            ->where('tenants.id', $tenant->id)
            ->first()
            ?->pivot;

        $this->form->fill([
            'nickname' => $pivot?->display_name ?? auth()->user()->name,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Workplace Nickname')
                    ->description('This name will be visible to others in this specific workplace.')
                    ->schema([
                        TextInput::make('nickname')
                            ->required()
                            ->maxLength(255),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $tenant = Filament::getTenant();
        $data = $this->form->getState();

        auth()->user()->tenants()->updateExistingPivot(
            $tenant->id, 
            ['display_name' => $data['nickname']]
        );

        Notification::make()
            ->title('Nickname updated successfully!')
            ->success()
            ->send();
            
        $this->redirect(Dashboard::getUrl());
    }

    protected function getFormActions(): array
    {
        return [
            \Filament\Actions\Action::make('save')
                ->label('Save Changes')
                ->submit('save'),
        ];
    }
}
