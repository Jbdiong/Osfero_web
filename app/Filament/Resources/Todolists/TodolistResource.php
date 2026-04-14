<?php

namespace App\Filament\Resources\Todolists;

use App\Filament\Resources\Todolists\Pages\CreateTodolist;
use App\Filament\Resources\Todolists\Pages\EditTodolist;
use App\Filament\Resources\Todolists\Pages\ListTodolists;
use App\Filament\Resources\Todolists\Schemas\TodolistForm;
use App\Filament\Resources\Todolists\Tables\TodolistsTable;
use App\Models\Todolist;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Forms\Form;

use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TodolistResource extends Resource
{
    protected static ?string $model = Todolist::class;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->where('tenant_id', auth()->user()?->tenant_id);

        $isManager = in_array(auth()->user()?->role?->role, ['Superadmin', 'Tenant admin', 'Manager']);

        if (!$isManager) {
            $query->whereHas('pics', function ($q) {
                $q->where('user_id', auth()->id());
            });
        }

        return $query;
    }

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $recordTitleAttribute = 'Title';

    protected static ?string $slug = 'todolists';
    
    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();
        $isManager = in_array($user?->role?->role, ['Superadmin', 'Tenant admin', 'Manager']);

        $query = static::getModel()::query()
            ->where('tenant_id', $user?->tenant_id);

        if (!$isManager) {
            $query->whereHas('pics', function ($q) {
                $q->where('user_id', auth()->id());
            });
        }

        return $query->whereDate('end_date', '<=', now())
            ->whereHas('status', function ($query) {
                $query->where('name', '!=', 'Completed');
            })
            ->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return (int) static::getNavigationBadge() > 0 ? 'danger' : null;
    }

    public static function form(Form $form): Form
    {
        return TodolistForm::configure($form);
    }

    public static function table(Table $table): Table
    {
        return TodolistsTable::configure($table);
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
            'index' => ListTodolists::route('/'),
            'create' => CreateTodolist::route('/create'),
            'edit' => EditTodolist::route('/{record}/edit'),
            'archived' => \App\Filament\Resources\Todolists\Pages\ArchivedTodolists::route('/archived'),
        ];
    }
}
