<?php

namespace App\Filament\Resources\Inventory\Category\RelationManagers;

use App\Filament\Resources\Inventory\Items\Items\ItemResource;
use App\Filament\Resources\Inventory\Schemas\ItemForm;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Illuminate\Support\Facades\Auth;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';
    protected static ?string $title = 'Items in this Folder';

    public function form(Form $form): Form
    {
        return ItemForm::configure($form);
    }

    public function table(Table $table): Table
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
            ])
            ->headerActions([
                CreateAction::make()
                    ->button()
                    ->modalWidth('4xl')
                    ->authorize(true)
                    ->visible(true)
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['tenant_id'] = Auth::user()->tenant_id;
                        return $data;
                    }),
            ])
            ->actions([
                EditAction::make()
                    ->modalWidth('4xl'),
                DeleteAction::make(),
            ])
            ->emptyStateActions([
                CreateAction::make()
                    ->button()
                    ->modalWidth('4xl')
                    ->authorize(true)
                    ->visible(true)
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['tenant_id'] = Auth::user()->tenant_id;
                        return $data;
                    }),
            ]);
    }
}







