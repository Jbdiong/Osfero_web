<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = null;
    
    protected static ?string $navigationGroup = 'Settings';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user && $user->role?->role === 'Staff') {
            $query->where('users.id', $user->id);
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tenant_status')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(function (User $record) {
                        // Priority 1: If globally deleted or suspended, show that global status
                        if ($record->status !== User::STATUS_ACTIVE) {
                            return $record->status;
                        }

                        // Priority 2: Fallback to tenant-specific pivot status
                        $tenant = \Filament\Facades\Filament::getTenant();
                        if (!$tenant) return $record->status;
                        $pivot = $record->tenants()->where('tenants.id', $tenant->id)->first()?->pivot;
                        return $pivot ? $pivot->status : 1;
                    })
                    ->color(fn ($state): string => match ($state) {
                        1 => 'success',
                        2 => 'warning',
                        3 => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        1 => 'Active',
                        2 => 'Suspended',
                        3 => 'Deleted',
                        default => 'Unknown',
                    }),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(function (User $record): bool {
                        if ($record->status === User::STATUS_DELETED) return false;
                        $currentUser = auth()->user();
                        return $currentUser && $currentUser->id === $record->id;
                    }),
                Tables\Actions\Action::make('resetPassword')
                    ->label('Reset Password')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->form([
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->required()
                            ->maxLength(255)
                            ->label('New Password'),
                    ])
                    ->action(function (User $record, array $data) {
                        $record->update([
                            'password' => $data['password'],
                        ]);
                    })
                    ->visible(function (User $record): bool {
                        if ($record->status === User::STATUS_DELETED) return false;
                        $currentUser = auth()->user();
                        return $currentUser && $currentUser->id === $record->id;
                    }),
                Tables\Actions\Action::make('suspend')
                    ->label('Suspend')
                    ->icon('heroicon-o-pause-circle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(function (User $record): bool {
                        if ($record->status === User::STATUS_DELETED) return false;
                        
                        $tenant = \Filament\Facades\Filament::getTenant();
                        $pivotStatus = $tenant ? ($record->tenants()->where('tenants.id', $tenant->id)->first()?->pivot->status ?? 1) : 1;
                        if ($pivotStatus !== 1) return false;
                        
                        $currentUser = auth()->user();
                        if (!$currentUser) return false;
                        $role = $currentUser->role?->role;
                        
                        return in_array($role, ['Superadmin', 'Tenant admin']);
                    })
                    ->action(function (User $record) {
                        $tenant = \Filament\Facades\Filament::getTenant();
                        if ($tenant) {
                            $record->tenants()->updateExistingPivot($tenant->id, ['status' => 2]);
                        }
                    }),
                Tables\Actions\Action::make('activate')
                    ->label('Activate')
                    ->icon('heroicon-o-play-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(function (User $record): bool {
                        if ($record->status === User::STATUS_DELETED) return false;

                        $tenant = \Filament\Facades\Filament::getTenant();
                        $pivotStatus = $tenant ? ($record->tenants()->where('tenants.id', $tenant->id)->first()?->pivot->status ?? 1) : 1;
                        if ($pivotStatus === 1) return false;
                        
                        $currentUser = auth()->user();
                        if (!$currentUser) return false;
                        $role = $currentUser->role?->role;
                        
                        return in_array($role, ['Superadmin', 'Tenant admin']);
                    })
                    ->action(function (User $record) {
                        $tenant = \Filament\Facades\Filament::getTenant();
                        if ($tenant) {
                            $record->tenants()->updateExistingPivot($tenant->id, ['status' => 1]);
                        }
                    }),
                Tables\Actions\Action::make('delete')
                    ->label('Delete')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(function (User $record): bool {
                        if ($record->status === User::STATUS_DELETED) return false;

                        $tenant = \Filament\Facades\Filament::getTenant();
                        $pivotStatus = $tenant ? ($record->tenants()->where('tenants.id', $tenant->id)->first()?->pivot->status ?? 1) : 1;
                        if ($pivotStatus === 3) return false;
                        
                        $currentUser = auth()->user();
                        if (!$currentUser) return false;
                        $role = $currentUser->role?->role;
                        
                        return in_array($role, ['Superadmin', 'Tenant admin']);
                    })
                    ->action(function (User $record) {
                        $tenant = \Filament\Facades\Filament::getTenant();
                        if ($tenant) {
                            $record->tenants()->updateExistingPivot($tenant->id, ['status' => 3]);
                        }
                    }),
            ])
            ->bulkActions([
                // Removed bulk delete to prevent global user deletion
            ])
            ->recordUrl(function (User $record): ?string {
                if ($record->status === User::STATUS_DELETED) return null;
                $currentUser = auth()->user();
                if ($currentUser && $currentUser->id === $record->id) {
                    return static::getUrl('edit', ['record' => $record]);
                }
                return null;
            });
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
