<?php

namespace App\Filament\Resources\Inventory\Schemas;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Location;
use App\Models\UomCode;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Illuminate\Support\Facades\Auth;

class ItemForm
{
    public static function configure(Form $form): Form
    {
        return $form->schema([
            Section::make('Basic Information')
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('base_sku')
                        ->label('Base SKU / Product Code')
                        ->unique(ignoreRecord: true)
                        ->maxLength(255),
                    Grid::make(2)->schema([
                        Select::make('brand_id')
                            ->label('Brand')
                            ->options(fn () => Brand::where('tenant_id', Auth::user()->tenant_id)->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('name')->required(),
                            ])
                            ->createOptionUsing(fn (array $data) => Brand::create([...$data, 'tenant_id' => Auth::user()->tenant_id])->id),
                        Select::make('category_id')
                            ->label('Category')
                            ->options(fn () => Category::where('tenant_id', Auth::user()->tenant_id)->pluck('name', 'id'))
                            ->default(request()->query('category_id'))
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('name')->required(),
                            ])
                            ->createOptionUsing(fn (array $data) => Category::create([...$data, 'tenant_id' => Auth::user()->tenant_id])->id),
                    ]),
                    Textarea::make('description')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),

            Section::make('Variants (SKUs)')
                ->description('Manage different versions of this product (e.g. Sizes, Colors)')
                ->schema([
                    Repeater::make('variants')
                        ->relationship()
                        ->schema([
                            Grid::make(3)->schema([
                                TextInput::make('sku')
                                    ->label('SKU')
                                    ->required()
                                    ->unique(ignoreRecord: true),
                                TextInput::make('barcode')
                                    ->label('Barcode'),
                                Select::make('uom_id')
                                    ->label('UOM')
                                    ->options(fn () => UomCode::where('tenant_id', Auth::user()->tenant_id)->orWhere('tenant_id', 0)->pluck('name', 'id'))
                                    ->default(fn () => UomCode::where('code', 'PCS')->first()?->id)
                                    ->searchable()
                                    ->preload(),
                                TextInput::make('supplier_price')
                                    ->label('Cost Price')
                                    ->numeric()
                                    ->prefix('RM')
                                    ->default(0),
                                TextInput::make('sales_price')
                                    ->label('Sales Price')
                                    ->numeric()
                                    ->prefix('RM')
                                    ->default(0),
                                TextInput::make('min_stock_level')
                                    ->label('Min Stock')
                                    ->numeric()
                                    ->default(0),
                            ]),
                            Grid::make(2)->schema([
                                Select::make('initial_location_id')
                                    ->label('Initial Location')
                                    ->options(fn () => Location::where('tenant_id', Auth::user()->tenant_id)->pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('Choose a location to add stock')
                                    ->required(fn ($get) => (float)$get('initial_quantity') > 0),
                                TextInput::make('initial_quantity')
                                    ->label('Initial Stock')
                                    ->numeric()
                                    ->default(0)
                                    ->helperText('Type a number to set the starting stock level.'),
                            ]),
                            KeyValue::make('variant_specs')
                                ->label('Variant Detail (e.g. Color: Blue, Size: XL)')
                                ->keyLabel('Detail Name (e.g. Color)')
                                ->valueLabel('Value (e.g. Blue)')
                                ->helperText('Describe what makes this variant unique (Size, Color, Material, etc.).'),
                        ])
                        ->itemLabel(fn (array $state): ?string => $state['sku'] ?? null)
                        ->collapsible()
                        ->defaultItems(1),
                ]),

            Section::make('Pictures')
                ->schema([
                    Repeater::make('attachments')
                        ->relationship('attachments')
                        ->schema([
                            FileUpload::make('path')
                                ->label('Image')
                                ->image()
                                ->directory('item-attachments')
                                ->required()
                                ->columnSpanFull(),
                            Toggle::make('is_main')
                                ->label('Primary Image')
                                ->default(false),
                            TextInput::make('sort_order')
                                ->numeric()
                                ->default(0),
                        ])
                        ->grid(2)
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => is_string($state['path'] ?? null) ? basename($state['path']) : null)
                        ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                            $data['tenant_id'] = Auth::user()->tenant_id;
                            return $data;
                        })
                        ->mutateRelationshipDataBeforeSaveUsing(function (array $data): array {
                            $data['tenant_id'] = Auth::user()->tenant_id;
                            return $data;
                        }),
                ]),
        ]);
    }
}







