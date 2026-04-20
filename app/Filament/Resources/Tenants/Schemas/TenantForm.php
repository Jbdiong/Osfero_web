<?php

namespace App\Filament\Resources\Tenants\Schemas;

use Filament\Forms;
use Filament\Forms\Form;

class TenantForm
{
    public static function configure(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('code')
                    ->disabled()
                    ->maxLength(255),
                Forms\Components\DateTimePicker::make('code_expiring')
                    ->label('Code Expires At')
                    ->disabled(),
            ]);
    }
}







