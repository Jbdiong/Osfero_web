<?php

namespace App\Filament\Resources\Todolists\Pages;

use App\Filament\Resources\Todolists\TodolistResource;
use Filament\Resources\Pages\CreateRecord;

use Filament\Actions;

class CreateTodolist extends CreateRecord
{
    protected static string $resource = TodolistResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (request()->has('status_id')) {
            $data['status_id'] = request()->query('status_id');
        }
        return $data;
    }



    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
