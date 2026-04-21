<?php

namespace App\Filament\Resources\Packages\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class TemplateItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'templateItems';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('tenant_id')
                    ->default(fn () => auth()->user()->last_active_tenant_id),
                Forms\Components\TextInput::make('service_type')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('default_qty')
                    ->required()
                    ->numeric()
                    ->default(1),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('service_type')
            ->columns([
                Tables\Columns\TextColumn::make('service_type'),
                Tables\Columns\TextColumn::make('default_qty')
                    ->numeric(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
