<?php

namespace App\Filament\Resources\Customers\Tables;

use Filament\Tables;
use Filament\Tables\Table;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([

                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('company')
                    ->searchable(),
                Tables\Columns\TextColumn::make('pics')
                    ->label('Staff / PICs')
                    ->getStateUsing(fn (\App\Models\Customer $record) => $record->pics->pluck('name'))
                    ->badge()
                    ->color('gray')
                    ->searchable(query: function ($query, string $search) {
                        return $query->whereHas('pics', fn($q) => $q->where('users.name', 'like', "%{$search}%"));
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('addOrder')
                    ->label('Add Order')
                    ->icon('heroicon-m-plus-circle')
                    ->color('success')
                    ->url(fn (\App\Models\Customer $record): string => \App\Filament\Resources\Orders\OrderResource::getUrl('create', ['customer_id' => $record->id])),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}







