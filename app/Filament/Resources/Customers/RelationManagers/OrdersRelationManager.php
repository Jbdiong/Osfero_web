<?php
namespace App\Filament\Resources\Customers\RelationManagers;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
class OrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('id')
                    ->required()
                    ->maxLength(255),
            ]);
    }
    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->recordTitleAttribute('invoice_no')
            ->recordUrl(
                fn (\App\Models\Order $record): string => \App\Filament\Resources\Orders\OrderResource::getUrl('edit', ['record' => $record]),
            )
            ->columns([
                Tables\Columns\TextColumn::make('id'),
                Tables\Columns\TextColumn::make('purchase_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('invoice_no')
                    ->searchable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->numeric()
                    ->sortable()
                    ->money('MYR', true), // Assuming MYR, change if different 
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->url(fn (\Filament\Resources\RelationManagers\RelationManager $livewire): string => \App\Filament\Resources\Orders\OrderResource::getUrl('create', ['customer_id' => $livewire->getOwnerRecord()->id])),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\EditAction::make()
                    ->url(fn (\App\Models\Order $record): string => \App\Filament\Resources\Orders\OrderResource::getUrl('edit', ['record' => $record])),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]); 
    }
}