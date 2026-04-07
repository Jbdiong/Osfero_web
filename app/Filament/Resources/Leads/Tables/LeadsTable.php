<?php

namespace App\Filament\Resources\Leads\Tables;

use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LeadsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('Shop_Name')
                    ->searchable(),
                TextColumn::make('Industry')
                    ->searchable(),
                TextColumn::make('Manual_Industry')
                    ->searchable(),
                TextColumn::make('last_modified')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('Source')
                    ->searchable(),
                TextColumn::make('Manual_Source')
                    ->searchable(),
                TextColumn::make('Language')
                    ->searchable(),
                TextColumn::make('Manual_Language')
                    ->searchable(),
                TextColumn::make('City')
                    ->searchable(),
                TextColumn::make('State')
                    ->searchable(),
                TextColumn::make('Country')
                    ->searchable(),
                IconColumn::make('relevant')
                    ->boolean(),
                TextColumn::make('status.name')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
