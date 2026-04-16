<?php

namespace App\Filament\Resources\Renewals\Tables;

use App\Models\Renewal;
use Filament\Tables;
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
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
