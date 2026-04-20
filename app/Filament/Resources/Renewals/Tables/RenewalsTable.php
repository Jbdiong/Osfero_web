<?php

namespace App\Filament\Resources\Renewals\Tables;

use App\Models\Renewal;
use Filament\Tables;
use Filament\Forms;
use Filament\Tables\Table;

class RenewalsTable
{
    public static function configure(Table $table): Table
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
                    ->label('Expired Date')
                    ->color(fn (Renewal $record) => $record->Renew_Date < now()->startOfDay() ? 'danger' : null),
                Tables\Columns\TextColumn::make('status.name')
                    ->badge()
                    ->sortable()
                    ->label('Status'),
                Tables\Columns\TextColumn::make('remarks')
                    ->limit(50)
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                Tables\Actions\Action::make('renew')
                    ->label('Renew')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->form([
                        Forms\Components\DatePicker::make('start_date')
                            ->displayFormat('d/m/Y')
                            ->native(false)
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                $startDate = $get('start_date');
                                $duration = $get('duration');
                                if (! $duration) return;
                                $startDate = $startDate ? \Carbon\Carbon::parse($startDate) : now();
                                $set('start_date', $startDate->format('Y-m-d'));
                                $set('Renew_Date', $startDate->addMonths((int) $duration)->subDay()->format('Y-m-d'));
                            }),
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
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                $startDate = $get('start_date');
                                $duration = $get('duration');
                                if (! $duration) return;
                                $startDate = $startDate ? \Carbon\Carbon::parse($startDate) : now();
                                $set('start_date', $startDate->format('Y-m-d'));
                                $set('Renew_Date', $startDate->addMonths((int) $duration)->subDay()->format('Y-m-d'));
                            }),
                        Forms\Components\DatePicker::make('Renew_Date')
                            ->required()
                            ->displayFormat('d/m/Y')
                            ->native(false)
                            ->label('Expired Date')
                            ->after('start_date'),
                        Forms\Components\Select::make('status_id')
                            ->relationship('status', 'name', fn ($query) => $query->whereHas('parent', fn ($q) => $q->where('name', 'Renewal Status')))
                            ->label('Status')
                            ->default(null),
                    ])
                    ->action(function (Renewal $record, array $data): void {
                        $record->update([
                            'start_date' => $data['start_date'],
                            'Renew_Date' => $data['Renew_Date'],
                            'status_id' => $data['status_id'] ?? $record->status_id,
                        ]);
                    })
                    ->fillForm(fn (Renewal $record): array => [
                        'start_date' => $record->start_date,
                        'Renew_Date' => $record->Renew_Date,
                        'status_id' => $record->status_id,
                    ]),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
