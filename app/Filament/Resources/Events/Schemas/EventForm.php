<?php

namespace App\Filament\Resources\Events\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;

class EventForm
{
    public static function configure(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('lead_id')
                    ->relationship('lead', 'id')
                    ->default(null),
                TextInput::make('title')
                    ->required(),
                Textarea::make('description')
                    ->default(null)
                    ->columnSpanFull(),
                DateTimePicker::make('start_time')
                    ->required(),
                DateTimePicker::make('end_time')
                    ->required(),
                Select::make('status_id')
                    ->relationship('status', 'name', fn ($query) => $query->whereHas('parent', fn ($q) => $q->where('name', 'Event Status'))->orderBy('id'))
                    ->default(request()->query('status_id'))
                    ->required(),
                Select::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->required(),
            ]);
    }
}







