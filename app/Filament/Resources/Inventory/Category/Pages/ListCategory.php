<?php

namespace App\Filament\Resources\Inventory\Category\Pages;

use App\Filament\Resources\Inventory\Categories\Categories\CategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListCategory extends ListRecords
{
    protected static string $resource = CategoryResource::class;

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







