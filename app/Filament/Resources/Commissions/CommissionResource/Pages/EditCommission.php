<?php

namespace App\Filament\Resources\Commissions\CommissionResource\Pages;

use App\Filament\Resources\Commissions\CommissionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCommission extends EditRecord
{
    protected static string $resource = CommissionResource::class;

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

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Null out irrelevant fields when type changes
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
