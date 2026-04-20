<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Forms;
use Filament\Forms\Form;

class CustomerForm
{
    public static function configure(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('tenant_id')
                    ->default(fn () => auth()->user()->last_active_tenant_id),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('company')
                    ->maxLength(255)
                    ->default(null),
            ]);
    }
}







