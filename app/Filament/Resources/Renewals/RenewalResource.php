<?php

namespace App\Filament\Resources\Renewals;

use App\Filament\Resources\Renewals\Pages;
use App\Filament\Resources\Renewals\RelationManagers;
use App\Models\Renewal;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RenewalResource extends Resource
{
    protected static ?string $model = Renewal::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?string $slug = 'renewals';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereDate('Renew_Date', '<=', now())
            ->where('tenant_id', auth()->user()->tenant_id)
            ->where(function ($query) {
                $query->whereNull('status_id')
                    ->orWhereHas('status', function ($q) {
                        $q->where('name', '!=', 'Followed Up');
                    });
            })
            ->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return (int) static::getNavigationBadge() > 0 ? 'danger' : null;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('label')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\DatePicker::make('start_date')
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

                        $set('Renew_Date', $startDate->addMonths($state)->format('Y-m-d'));
                    }),
                Forms\Components\DatePicker::make('Renew_Date')
                    ->required()
                    ->label('Renew Date')
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
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('label')
                    ->searchable()
                    ->label('Label')
                    ->description(fn (Renewal $record) => $record->lead?->Shop_Name),
                Tables\Columns\TextColumn::make('start_date')
                    ->date('d M Y')
                    ->sortable()
                    ->label('Start Date'),
                Tables\Columns\TextColumn::make('Renew_Date')
                    ->date('d M Y')
                    ->sortable()
                    ->label('Renew Date')
                    ->color(fn (Renewal $record) => $record->Renew_Date < now()->startOfDay() ? 'danger' : null),
                Tables\Columns\TextColumn::make('status.name')
                    ->badge()
                    ->sortable()
                    ->label('Status'),
            ])
            ->defaultSort('Renew_Date', 'asc')
            ->recordClasses(fn (Renewal $record) => match (true) {
                $record->Renew_Date < now()->startOfDay() => 'bg-red-50 dark:bg-red-900/10',
                default => null,
            })
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
    
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('tenant_id', auth()->user()->tenant_id);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRenewals::route('/'),
            'create' => Pages\CreateRenewal::route('/create'),
            'edit' => Pages\EditRenewal::route('/{record}/edit'),
        ];
    }
}
