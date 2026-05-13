<?php

namespace App\Filament\Resources\Inventory\Category\RelationManagers;

use App\Models\Category;
use App\Filament\Resources\Inventory\CategoryResource;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class SubfoldersRelationManager extends RelationManager
{
    protected static string $relationship = 'children';
    protected static ?string $title = 'Sub-folders';
    protected static ?string $modelLabel = 'Sub-folder';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Folder Name')
                    ->icon('heroicon-o-folder')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('items_count')
                    ->counts('items')
                    ->label('Items'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->button()
                    ->authorize(true)
                    ->visible(true)
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['tenant_id'] = Auth::user()->tenant_id;
                        return $data;
                    }),
            ])
            ->actions([
                ViewAction::make()
                    ->url(fn (Category $record): string => CategoryResource::getUrl('view', ['record' => $record])),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ])
            ->emptyStateActions([
                CreateAction::make()
                    ->button()
                    ->authorize(true)
                    ->visible(true)
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['tenant_id'] = Auth::user()->tenant_id;
                        return $data;
                    }),
            ]);
    }
}







