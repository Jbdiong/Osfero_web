<?php

namespace App\Filament\Resources\Todolists\Pages;

use App\Filament\Resources\Todolists\TodolistResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTodolist extends EditRecord
{
    protected static string $resource = TodolistResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
