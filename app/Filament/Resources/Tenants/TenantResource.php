<?php

namespace App\Filament\Resources\Tenants;

use App\Filament\Resources\Tenants\Pages;
use App\Models\Tenant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

use App\Filament\Resources\Tenants\Schemas\TenantForm;
use App\Filament\Resources\Tenants\Tables\TenantsTable;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationLabel = 'Generate Code';
    protected static ?string $pluralModelLabel = 'Generate Code';
    protected static ?string $modelLabel = 'Generate Code';

    // Prevent Filament from trying to scope Tenants by themselves
    protected static bool $isScopedToTenant = false;

    public static function canViewAny(): bool
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        return $user && in_array(optional($user->role)->role, ['Superadmin', 'Tenant admin', 'Manager']);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (\Illuminate\Support\Facades\Auth::check()) {
            $user = \Illuminate\Support\Facades\Auth::user();
            if ($user->tenant_id) {
                $query->where('id', $user->tenant_id);
            }
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return TenantForm::configure($form);
    }

    public static function table(Table $table): Table
    {
        return TenantsTable::configure($table);
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
            'index' => Pages\ListTenants::route('/'),
            'create' => Pages\CreateTenant::route('/create'),
            'edit' => Pages\EditTenant::route('/{record}/edit'),
        ];
    }
}
