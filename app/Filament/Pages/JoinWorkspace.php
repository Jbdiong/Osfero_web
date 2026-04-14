<?php

namespace App\Filament\Pages;

use App\Models\Tenant;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Facades\Filament;
use App\Models\SystemRole;

class JoinWorkspace extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-plus-circle';

    protected static string $view = 'filament.pages.join-workspace';

    protected static bool $shouldRegisterNavigation = false;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('invitation_code')
                    ->label('Invitation Code')
                    ->required()
                    ->placeholder('Enter the 8-character workspace code')
                    ->validationAttribute('invitation code')
                    ->rule(fn () => function (string $attribute, $value, \Closure $fail) {
                        $tenant = Tenant::findByInvitationCode($value);
                        if (! $tenant) {
                            $fail('The invitation code is invalid or has expired.');
                        }
                        
                        // Check if already a member
                        if (auth()->user()->tenants()->where('tenants.id', $tenant->id)->exists()) {
                            $fail('You are already a member of this workspace.');
                        }
                    }),
            ])
            ->statePath('data');
    }

    public function join(): void
    {
        $payload = $this->form->getState();
        $tenant = Tenant::findByInvitationCode($payload['invitation_code']);
        $user = auth()->user();

        $staffRole = SystemRole::where('role', 'Staff')->first();

        $user->tenants()->attach($tenant->id, [
            'role_id' => $staffRole ? $staffRole->id : 4,
            'display_name' => $user->name,
        ]);

        Notification::make()
            ->title("Joined {$tenant->name} successfully!")
            ->success()
            ->send();

        // Redirect to the new tenant dashboard
        $this->redirect(Filament::getPanel()->getUrl($tenant));
    }

    protected function getFormActions(): array
    {
        return [
            \Filament\Actions\Action::make('join')
                ->label('Join Workspace')
                ->submit('join'),
        ];
    }
}
