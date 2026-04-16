<?php

namespace App\Filament\Resources\Renewals\Schemas;

use Filament\Forms;
use Filament\Forms\Form;

class RenewalForm
{
    public static function configure(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('label')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\DatePicker::make('start_date')
                    ->displayFormat('d/m/Y')
                    ->native(false)
                    ->required()
                    ->live(),
                Forms\Components\Select::make('duration')
                    ->options([
                        1 => '1 Month',
                        2 => '2 Months',
                        3 => '3 Months',
                        4 => '4 Months',
                        5 => '5 Months',
                        6 => '6 Months',
                        7 => '7 Months',
                        12 => '1 Year',
                    ])
                    ->label('Duration')
                    ->placeholder('Select Duration')
                    ->dehydrated(false)
                    ->live()
                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, ?int $state) {
                        if (! $state) {
                            return;
                        }

                        $startDate = $get('start_date');
                        if (! $startDate) {
                            $startDate = now();
                            $set('start_date', $startDate->format('Y-m-d'));
                        } else {
                            $startDate = \Carbon\Carbon::parse($startDate);
                        }

                        $set('Renew_Date', $startDate->addMonths($state)->subDay()->format('Y-m-d'));
                    }),
                Forms\Components\DatePicker::make('Renew_Date')
                    ->required()
                    ->displayFormat('d/m/Y')
                    ->native(false)
                    ->label('Expired Date')
                    ->after('start_date'),
                Forms\Components\Select::make('status_id')
                    ->relationship('status', 'name', fn ($query) => $query->whereHas('parent', fn ($q) => $q->where('name', 'Renewal Status')))
                    ->default(null),
                Forms\Components\Select::make('lead_id')
                    ->relationship('lead', 'Shop_Name')
                    ->searchable()
                    ->preload()
                    ->default(null),
                Forms\Components\Select::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->required()
                    ->default(fn () => auth()->user()->tenant_id)
                    ->hidden(fn () => auth()->user()->tenant_id !== null)
                    ->dehydrated(true),
                Forms\Components\Textarea::make('remarks')
                    ->columnSpanFull(),
            ]);
    }
}
