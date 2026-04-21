<?php

namespace App\Filament\Resources\Inventory\Pages;

use App\Filament\Resources\Inventory\ItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListItem extends ListRecords
{
    protected static string $resource = ItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}







