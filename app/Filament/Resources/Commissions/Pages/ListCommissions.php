<?php

namespace App\Filament\Resources\Commissions\Pages;

use App\Filament\Resources\Commissions\CommissionResource;
use App\Models\CommissionEntry;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

use App\Filament\Widgets\StaffCommissionWidget;

class ListCommissions extends ListRecords
{
    protected static string $resource = CommissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('New Commission'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            StaffCommissionWidget::class,
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All'),
            'design' => Tab::make('Design')
                ->modifyQueryUsing(function (Builder $query) {
                    return $query->where('type', 'design');
                })
                ->icon('heroicon-m-paint-brush'),
            'ads_management' => Tab::make('Ads Management')
                ->modifyQueryUsing(function (Builder $query) {
                    return $query->where('type', 'ads_management');
                })
                ->icon('heroicon-m-megaphone'),
            'sales' => Tab::make('Sales')
                ->modifyQueryUsing(function (Builder $query) {
                    return $query->where('type', 'sales');
                })
                ->icon('heroicon-m-briefcase'),
        ];
    }
}
