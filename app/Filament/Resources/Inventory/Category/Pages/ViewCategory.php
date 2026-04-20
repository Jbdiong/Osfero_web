<?php

namespace App\Filament\Resources\Inventory\Category\Pages;

use App\Filament\Resources\Inventory\Categories\Categories\CategoryResource;
use Filament\Resources\Pages\ViewRecord;

class ViewCategory extends ViewRecord
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\EditAction::make(),
        ];
    }
}







