<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LookupResource\Pages;
use App\Filament\Resources\LookupResource\RelationManagers;
use App\Models\Lookup;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LookupResource extends Resource
{
    protected static ?string $model = Lookup::class;

    protected static ?string $navigationIcon = null;
    protected static ?string $navigationLabel = 'System Settings';
    protected static ?string $slug = 'system-settings';

    protected static ?string $navigationGroup = 'Settings';

    public static function getModelLabel(): string
    {
        return 'System Setting';
    }

    public static function getPluralModelLabel(): string
    {
        return 'System Settings';
    }

    public static function canViewAny(): bool
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        $role = optional($user->role)->role;
        return $user && in_array($role, ['Superadmin', 'Tenant admin']);
    }

    // Scope lookups to the current tenant
    protected static bool $isScopedToTenant = true;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('label')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('lookup_id')
                    ->numeric()
                    ->default(null),
                Forms\Components\Select::make('parent_id')
                    ->relationship(
                        'parent', 
                        'name', 
                        fn (Builder $query) => $query->where(fn ($q) => $q->whereNull('tenant_id')->orWhere('tenant_id', \Filament\Facades\Filament::getTenant()?->id))
                    )
                    ->searchable()
                    ->preload()
                    ->default(null),
                Forms\Components\Select::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->searchable()
                    ->preload()
                    ->default(null),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('label')
                    ->searchable(),
                Tables\Columns\TextColumn::make('lookup_id')
                    ->sortable(),
                Tables\Columns\TextColumn::make('parent.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('tenant.name')
                    ->sortable(),
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
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ManageLookups::route('/'),
            'create' => Pages\CreateLookup::route('/create'),
            'edit' => Pages\EditLookup::route('/{record}/edit'),
        ];
    }
}
