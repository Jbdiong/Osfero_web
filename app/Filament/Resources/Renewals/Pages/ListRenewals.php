<?php

namespace App\Filament\Resources\Renewals\Pages;

use App\Filament\Resources\Renewals\RenewalResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRenewals extends ListRecords
{
    protected static string $resource = RenewalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}







