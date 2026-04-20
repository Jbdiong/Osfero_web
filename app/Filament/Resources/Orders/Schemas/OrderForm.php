<?php

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Forms;
use Filament\Forms\Form;

class OrderForm
{
    public static function configure(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('tenant_id')
                    ->default(fn () => auth()->user()->last_active_tenant_id),
                Forms\Components\Select::make('customer_id')
                    ->relationship('customer', 'name')
                    ->required()
                    ->default(request()->query('customer_id')),
                Forms\Components\Select::make('apply_package_id')
                    ->label('Apply a Package Template (Optional)')
                    ->options(fn () => \App\Models\Package::when(auth()->user()->tenant_id, fn($q) => $q->where('tenant_id', auth()->user()->tenant_id))->pluck('package_name', 'id'))
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                        if (! $state) return;
                        
                        $package = \App\Models\Package::with('templateItems')->find($state);
                        if (! $package) return;
                        
                        $items = $get('items') ?? [];
                        
                        // Clean up any empty initial items automatically added by Filament
                        $items = array_filter($items, function ($item) {
                            return !empty($item['service_type']);
                        });
                        
                        foreach ($package->templateItems as $templateItem) {
                            // Using uniqid() instead of str()->uuid() to avoid string-cast issues in some filament setups, but both work. str()->uuid() is fine.
                            $items[(string) \Illuminate\Support\Str::uuid()] = [
                                'tenant_id' => auth()->user()->tenant_id ?? $package->tenant_id,
                                'service_type' => $templateItem->service_type,
                                'total_qty_purchased' => $templateItem->default_qty,
                                'qty_remaining' => $templateItem->default_qty,
                            ];
                        }
                        $set('items', $items);
                        
                        // Auto-fill total amount if it's 0 or empty
                        if (! $get('total_amount') || $get('total_amount') == 0) {
                            $set('total_amount', $package->base_price);
                        }
                        
                        // Reset the select so they can add multiple packages if they want
                        $set('apply_package_id', null);
                    })
                    ->dehydrated(false)
                    ->helperText('Selecting a package will automatically populate the items below.'),
                Forms\Components\DatePicker::make('purchase_date'),
                Forms\Components\TextInput::make('total_amount')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\TextInput::make('invoice_no')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\FileUpload::make('invoice_file')
                    ->label('Invoice (PDF)')
                    ->acceptedFileTypes(['application/pdf'])
                    ->directory('invoices')
                    ->downloadable()
                    ->openable()
                    ->default(null)
                    ->live(), // Auto-refresh the form state when this changes
                Forms\Components\Placeholder::make('invoice_preview')
                    ->label('Invoice Preview')
                    ->content(function (Forms\Get $get) {
                        $file = $get('invoice_file');
                        if (!$file) return '-';

                        // Filament sometimes wraps existing single uploads in an array locally
                        $path = is_array($file) ? reset($file) : $file;

                        // Check if the current state is an actively uploading temporary file
                        if ($path instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                            return new \Illuminate\Support\HtmlString('<div style="padding: 1rem; color: #6b7280; font-style: italic; border: 1px dashed #d1d5db; border-radius: 0.5rem; margin-top: 0.5rem;">Preview will be completely available after you save the form.</div>');
                        }

                        // Otherwise it's a recognized string path to the storage folder
                        if (is_string($path)) {
                            $url = asset('storage/' . $path);
                            return new \Illuminate\Support\HtmlString('<iframe src="'.$url.'" style="width: 100%; height: 600px; border: 1px solid #e5e7eb; border-radius: 0.5rem; margin-top: 0.5rem;"></iframe>');
                        }

                        return '-';
                    })
                    ->visible(fn (Forms\Get $get) => filled($get('invoice_file')))
                    ->columnSpanFull(),
                Forms\Components\Repeater::make('items')
                    ->relationship()
                    ->visibleOn('create')
                    ->schema([
                        Forms\Components\Hidden::make('tenant_id')
                            ->default(fn () => auth()->user()->last_active_tenant_id),
                        Forms\Components\TextInput::make('service_type')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('total_qty_purchased')
                            ->label('Total Qty Purchased')
                            ->required()
                            ->numeric()
                            ->default(1),
                        Forms\Components\TextInput::make('qty_remaining')
                            ->label('Qty Remaining')
                            ->required()
                            ->numeric()
                            ->default(1)
                            ->hiddenOn('create'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}







