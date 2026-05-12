<?php
namespace App\Filament\Resources\Packages;
use App\Filament\Resources\Packages\Pages;
use App\Filament\Resources\Packages\RelationManagers;
use App\Models\Package;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Filament\Resources\Packages\Schemas\PackageForm;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PackageResource extends Resource
{
    protected static ?string $model = Package::class;
    protected static ?string $navigationGroup = 'Customers';
    protected static ?string $navigationIcon = null;
    public static function form(Form $form): Form
    {
        return PackageForm::configure($form);
    }
    public static function table(Table $table): Table
    {
        return \App\Filament\Resources\Packages\Tables\PackagesTable::configure($table);
    }
    public static function getRelations(): array
    {
        return [
            RelationManagers\TemplateItemsRelationManager::class,
        ];
    }
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPackages::route('/'),
            'create' => Pages\CreatePackage::route('/create'),
            'edit' => Pages\EditPackage::route('/{record}/edit'),
        ];
    }
}
