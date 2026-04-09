<?php

namespace App\Filament\Resources\Commissions\Pages;

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

        // Ensure user_id is gracefully filled for DB constraints when forms hide it (e.g. Sales, Ads)
        if (empty($data['user_id'])) {
            $data['user_id'] = Auth::id();
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

    protected function afterCreate(): void
    {
        $record = $this->getRecord();
        
        if (in_array($record->type, ['design', 'video'])) {
            $syncData = [];
            $state = $this->form->getRawState();

            // Add primary pic
            $primaryId = $state['primary_pic'] ?? $record->user_id;

            $syncData[$primaryId] = [
                'tenant_id' => $record->tenant_id,
                'split_percentage' => 100,
            ];

            // If video and checked 2 pics 
            if ($record->type === 'video' && ($state['is_2_pics'] ?? false) && !empty($state['secondary_user_id'])) {
                $secondaryId = $state['secondary_user_id'];
                
                // If they picked the same person twice somehow, fallback to 100%
                if ($secondaryId != $primaryId) {
                    $syncData[$primaryId]['split_percentage'] = 50;
                    $syncData[$secondaryId] = [
                        'tenant_id' => $record->tenant_id,
                        'split_percentage' => 50,
                    ];
                }
            }

            $record->users()->sync($syncData);
        } elseif ($record->users()->count() === 0 && $record->user_id) {
            $record->users()->sync([
                $record->user_id => [
                    'tenant_id' => $record->tenant_id,
                    'split_percentage' => 100,
                ]
            ]);
        }
    }
}
