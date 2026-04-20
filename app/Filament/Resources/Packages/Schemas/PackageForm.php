<?php

namespace App\Filament\Resources\Packages\Schemas;

use Filament\Forms;
use Filament\Forms\Form;

class PackageForm
{
    public static function configure(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('tenant_id')
                    ->default(fn () => auth()->user()->last_active_tenant_id),
                Forms\Components\TextInput::make('package_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('base_price')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\Repeater::make('templateItems')
                    ->relationship()
                    ->schema([
                        Forms\Components\Hidden::make('tenant_id')
                            ->default(fn () => auth()->user()->last_active_tenant_id),
                        Forms\Components\TextInput::make('service_type')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('default_qty')
                            ->label('Default Qty')
                            ->required()
                            ->numeric()
                            ->default(1),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}







