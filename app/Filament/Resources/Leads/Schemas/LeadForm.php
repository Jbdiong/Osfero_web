<?php

namespace App\Filament\Resources\Leads\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;

class LeadForm
{
    public static function configure(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('Shop_Name')
                    ->required(),
                TextInput::make('Industry')
                    ->default(null),
                TextInput::make('Manual_Industry')
                    ->default(null),
                DateTimePicker::make('last_modified'),
                TextInput::make('Source')
                    ->default(null),
                TextInput::make('Manual_Source')
                    ->default(null),
                TextInput::make('Language')
                    ->default(null),
                TextInput::make('Manual_Language')
                    ->default(null),
                TextInput::make('City')
                    ->default(null),
                TextInput::make('State')
                    ->default(null),
                TextInput::make('Country')
                    ->default(null),
                Textarea::make('address_1')
                    ->default(null)
                    ->columnSpanFull(),
                Textarea::make('address_2')
                    ->default(null)
                    ->columnSpanFull(),
                Textarea::make('address_3')
                    ->default(null)
                    ->columnSpanFull(),
                Toggle::make('relevant')
                    ->required(),
                Textarea::make('Irrelevant_reason')
                    ->default(null)
                    ->columnSpanFull(),
                Textarea::make('remarks')
                    ->default(null)
                    ->columnSpanFull(),
                Select::make('status_id')
                    ->relationship('status', 'name')
                    ->default(null),
                Select::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->required(),
            ]);
    }
}
