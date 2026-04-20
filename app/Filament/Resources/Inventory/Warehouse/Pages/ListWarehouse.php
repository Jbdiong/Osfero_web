<?php

namespace App\Filament\Resources\Inventory\Warehouse\Pages;

use App\Filament\Resources\Inventory\Warehouses\Warehouses\WarehouseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListWarehouse extends ListRecords
{
    protected static string $resource = WarehouseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->mutateFormDataUsing(function (array $data): array {
                    $data['tenant_id'] = Auth::user()->tenant_id;
                    return $data;
                }),
        ];
    }
}







