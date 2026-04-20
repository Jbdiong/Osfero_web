<?php

namespace App\Filament\Resources\Inventory\Categories\Categories;

use App\Models\Category;
use App\Filament\Resources\Inventory\Category\RelationManagers\SubfoldersRelationManager;
use App\Filament\Resources\Inventory\Category\RelationManagers\ItemsRelationManager;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;
    protected static ?string $slug = 'folders';
    protected static ?string $navigationLabel = 'Folders';
    protected static ?string $modelLabel = 'Folder';
    protected static ?string $pluralModelLabel = 'Folders';
    protected static ?string $navigationIcon = 'heroicon-o-folder';
    protected static ?string $navigationGroup = 'Inventory';
    protected static ?int $navigationSort = 3;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('tenant_id', Auth::user()->tenant_id);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')->required()->maxLength(255),
            Select::make('parent_id')
                ->label('Parent Category')
                ->options(fn () => Category::where('tenant_id', Auth::user()->tenant_id)->pluck('name', 'id'))
                ->searchable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Folder Name')
                    ->icon('heroicon-o-folder')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('children_count')
                    ->counts('children')
                    ->label('Sub-folders'),
                TextColumn::make('items_count')
                    ->counts('items')
                    ->label('Items'),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->actions([
                ViewAction::make()->label('Open Folder'),
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('parent_id'));
    }

    public static function getRelations(): array
    {
        return [
            SubfoldersRelationManager::class,
            ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\Inventory\Category\Pages\ListCategory::route('/'),
            'view' => \App\Filament\Resources\Inventory\Category\Pages\ViewCategory::route('/{record}'),
        ];
    }
}







