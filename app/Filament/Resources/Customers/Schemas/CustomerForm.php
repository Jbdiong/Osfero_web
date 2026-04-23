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
                Forms\Components\Select::make('pics')
                    ->relationship('pics', 'name', fn ($query) => $query->whereHas('tenants', fn ($q) => $q->where('tenants.id', auth()->user()->last_active_tenant_id)))
                    ->multiple()
                    ->preload()
                    ->default(fn () => [auth()->id()])
                    ->label('PICs (Person In Charge)'),
                Forms\Components\Repeater::make('phones')
                    ->relationship()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Contact Name')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone_number')
                            ->label('Phone Number')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Toggle::make('is_main')
                            ->label('Main Contact')
                            ->default(false),
                        Forms\Components\Hidden::make('tenant_id')
                            ->default(fn () => auth()->user()->last_active_tenant_id),
                    ])
                    ->columns(3)
                    ->defaultItems(0)
                    ->addActionLabel('Add Phone Number')
                    ->columnSpanFull(),
            ]);
    }
}







