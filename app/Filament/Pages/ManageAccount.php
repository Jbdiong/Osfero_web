<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Auth;

class ManageAccount extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-user';

    protected static string $view = 'filament.pages.manage-account';

    protected static bool $shouldRegisterNavigation = false;

    public function getTitle(): string
    {
        return 'Manage Account';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('deactivate')
                ->label('Deactivate Account')
                ->color('warning')
                ->icon('heroicon-o-pause-circle')
                ->requiresConfirmation()
                ->modalHeading('Deactivate Global Account')
                ->modalDescription('Are you sure you want to deactivate your global account? You will be logged out immediately.')
                ->action(function () {
                    $user = Auth::user();
                    $user->update(['status' => 2]); // 2 is Suspended
                    Auth::logout();
                    session()->invalidate();
                    session()->regenerateToken();
                    return redirect('/');
                }),
            Action::make('delete')
                ->label('Delete Account')
                ->color('danger')
                ->icon('heroicon-o-trash')
                ->requiresConfirmation()
                ->modalHeading('Delete Global Account')
                ->modalDescription('Are you sure you want to permanently delete your global account? This action cannot be undone.')
                ->action(function () {
                    $user = Auth::user();
                    $user->delete(); // Calls the overridden delete method
                    Auth::logout();
                    session()->invalidate();
                    session()->regenerateToken();
                    return redirect('/');
                }),
        ];
    }
}
