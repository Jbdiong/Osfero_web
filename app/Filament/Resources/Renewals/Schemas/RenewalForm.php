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
                    ->live()
                    ->afterStateUpdated(fn (Forms\Set $set, Forms\Get $get) => self::updateRenewDate($set, $get)),
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
                    ->afterStateUpdated(fn (Forms\Set $set, Forms\Get $get) => self::updateRenewDate($set, $get)),
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
                Forms\Components\Select::make('customer_id')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable(),
                Forms\Components\Hidden::make('tenant_id')
                    ->default(fn () => auth()->user()->last_active_tenant_id),
                Forms\Components\Textarea::make('remarks')
                    ->columnSpanFull(),
            ]);
    }

    private static function updateRenewDate(Forms\Set $set, Forms\Get $get): void
    {
        $startDate = $get('start_date');
        $duration = $get('duration');

        if (! $duration) {
            return;
        }

        if (! $startDate) {
            $startDate = now();
            $set('start_date', $startDate->format('Y-m-d'));
        } else {
            $startDate = \Carbon\Carbon::parse($startDate);
        }

        $set('Renew_Date', $startDate->addMonths((int) $duration)->subDay()->format('Y-m-d'));
    }
}







