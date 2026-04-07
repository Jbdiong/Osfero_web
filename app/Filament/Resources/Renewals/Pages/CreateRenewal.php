<?php

namespace App\Filament\Resources\Renewals\Pages;

use App\Filament\Resources\Renewals\RenewalResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateRenewal extends CreateRecord
{
    protected static string $resource = RenewalResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tenant_id'] = auth()->user()->tenant_id;
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
