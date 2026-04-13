<?php

namespace App\Filament\Resources\Inventory\Tables;

use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('attachments.path')
                    ->label('Thumbnail')
                    ->circular()
                    ->limit(1)
                    ->exists('attachments'),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('brand.name')
                    ->sortable(),
                TextColumn::make('category.name')
                    ->sortable(),
                TextColumn::make('base_sku')
                    ->label('SKU')
                    ->searchable(),
                TextColumn::make('variant_supplier_price')
                    ->label('Cost')
                    ->money('MYR')
                    ->getStateUsing(fn ($record) => $record->variants->first()?->supplier_price)
                    ->sortable(),
                TextColumn::make('variant_sales_price')
                    ->label('Price')
                    ->money('MYR')
                    ->getStateUsing(fn ($record) => $record->variants->first()?->sales_price)
                    ->sortable(),
                TextColumn::make('variants_count')
                    ->label('Variants')
                    ->counts('variants'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                \Filament\Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                \Filament\Tables\Actions\BulkActionGroup::make([
                    \Filament\Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
