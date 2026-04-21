<?php

namespace App\Filament\Resources\Inventory\Brand\Pages;

use App\Filament\Resources\Inventory\BrandResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListBrand extends ListRecords
{
    protected static string $resource = BrandResource::class;

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







