<?php

namespace App\Filament\Resources\Renewals\Pages;

use App\Filament\Resources\Renewals\RenewalResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRenewal extends EditRecord
{
    protected static string $resource = RenewalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}







