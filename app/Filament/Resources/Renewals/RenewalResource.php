<?php

namespace App\Filament\Resources\Renewals;

use App\Filament\Resources\Renewals\Pages;
use App\Models\Renewal;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\Renewals\Schemas\RenewalForm;
use App\Filament\Resources\Renewals\Tables\RenewalsTable;

class RenewalResource extends Resource
{
    protected static ?string $model = Renewal::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?string $slug = 'renewals';

    protected static ?string $recordTitleAttribute = 'label';

    public static function getNavigationBadge(): ?string
    {
        $tenantId = \Filament\Facades\Filament::getTenant()?->id;
        
        if (! $tenantId) return null;

        return static::getModel()::whereDate('Renew_Date', '<=', now())
            ->where('tenant_id', $tenantId)
            ->where(function ($query) {
                $query->whereNull('status_id')
                    ->orWhereHas('status', function ($q) {
                        $q->where('name', '!=', 'Followed Up');
                    });
            })
            ->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return (int) static::getNavigationBadge() > 0 ? 'danger' : null;
    }

    public static function form(Form $form): Form
    {
        return RenewalForm::configure($form);
    }

    public static function table(Table $table): Table
    {
        return RenewalsTable::configure($table);
    }
    
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('tenant_id', auth()->user()->tenant_id);
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
            'index' => Pages\ListRenewals::route('/'),
            'create' => Pages\CreateRenewal::route('/create'),
            'edit' => Pages\EditRenewal::route('/{record}/edit'),
        ];
    }
}
