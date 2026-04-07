<?php

namespace App\Filament\Resources\Commissions\CommissionResource\Pages;

use App\Filament\Resources\Commissions\CommissionResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateCommission extends CreateRecord
{
    protected static string $resource = CommissionResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();
        $data['tenant_id'] = $user->tenant_id;

        if (CommissionResource::isStaffOnly()) {
            $data['user_id'] = $user->id;
        }

        // Ensure irrelevant fields for each type are nulled out
        if ($data['type'] === 'design') {
            $data['package_value'] = null;
        } elseif ($data['type'] === 'ads_management') {
            $data['quantity']      = null;
            $data['package_value'] = null;
        } elseif ($data['type'] === 'sales') {
            $data['quantity'] = null;
        }

        return $data;
    }
}
