<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Lead;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $lead = Lead::create([
            'Shop_Name' => $data['company'] ?? $data['name'],
            'relevant' => true,
            'tenant_id' => $data['tenant_id'],
        ]);

        $data['lead_id'] = $lead->id;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}







