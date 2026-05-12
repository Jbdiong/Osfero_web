<?php

namespace App\Filament\Resources\Leads\Tables;

use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use App\Models\Customer;

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
                TextColumn::make('last_modified')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('Source')
                    ->searchable(),
                TextColumn::make('Language')
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
                Action::make('convert_to_customer')
                    ->label('Convert to Customer')
                    ->icon('heroicon-o-user-plus')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Convert Lead to Customer')
                    ->modalDescription('Are you sure you want to convert this lead into a customer? This will create a new Customer record and transfer all contacts.')
                    ->action(function ($record) {
                        $customer = Customer::create([
                            'tenant_id' => $record->tenant_id,
                            'lead_id' => $record->id,
                            'name' => $record->Shop_Name,
                            'company' => $record->Shop_Name,
                        ]);
                        
                        $record->phones()->update(['customer_id' => $customer->id]);
                    })
                    ->hidden(fn ($record) => $record->customer !== null),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}







