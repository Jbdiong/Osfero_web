<?php
namespace App\Filament\Resources\Packages\Schemas;
use Filament\Forms;
use Filament\Forms\Form;
class PackageForm
{
    public static function configure(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('tenant_id')
                    ->default(fn () => auth()->user()->last_active_tenant_id),
                Forms\Components\TextInput::make('package_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('base_price')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\Repeater::make('templateItems')
                    ->relationship()
                    ->schema([
                        Forms\Components\Hidden::make('tenant_id')
                            ->default(fn () => auth()->user()->last_active_tenant_id),
                        Forms\Components\Select::make('service_type')
                            ->options(function () {
                                $tenantId = auth()->user()->last_active_tenant_id ?? auth()->user()->tenant_id;
                                $orderTypes = \App\Models\OrderItem::when(auth()->check() && $tenantId, fn($q) => $q->where('tenant_id', $tenantId))
                                    ->whereNotNull('service_type')
                                    ->distinct()
                                    ->pluck('service_type', 'service_type')
                                    ->toArray();
                                $packageTypes = \App\Models\PackageTemplateItem::when(auth()->check() && $tenantId, fn($q) => $q->where('tenant_id', $tenantId))
                                    ->whereNotNull('service_type')
                                    ->distinct()
                                    ->pluck('service_type', 'service_type')
                                    ->toArray();
                                return array_merge($orderTypes, $packageTypes);
                            })
                            ->searchable()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('service_type')
                                    ->label('Custom Service Type')
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->createOptionUsing(function (array $data) {
                                return $data['service_type'];
                            })
                            ->required(),
                        Forms\Components\TextInput::make('default_qty')
                            ->label('Default Qty')
                            ->required()
                            ->numeric()
                            ->default(1),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}