<?php

namespace App\Filament\Resources\Inventory;

use App\Filament\Clusters\Inventory;
use App\Filament\Resources\Inventory\Pages;
use App\Filament\Resources\Inventory\Schemas\ItemForm;
use App\Filament\Resources\Inventory\Tables\ItemTable;
use App\Models\Item;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ItemResource extends Resource
{
    protected static ?string $model = Item::class;

    protected static ?string $cluster = Inventory::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', Auth::user()->tenant_id);
    }

    public static function form(Form $form): Form
    {
        return ItemForm::configure($form);
    }

    public static function table(Table $table): Table
    {
        return ItemTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListItem::route('/'),
            'create' => Pages\CreateItem::route('/create'),
            'edit' => Pages\EditItem::route('/{record}/edit'),
        ];
    }
}







