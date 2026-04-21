<?php

namespace App\Filament\Resources\Orders\Tables;

use Filament\Tables;
use Filament\Tables\Table;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->where('tenant_id', auth()->user()->tenant_id))
            ->columns([
                Tables\Columns\TextColumn::make('invoice_no')
                    ->searchable(),
                Tables\Columns\TextColumn::make('customer.name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('purchase_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->money('MYR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->formatStateUsing(function (\App\Models\Order $record) {
                        $total = $record->items()->sum('total_qty_purchased');
                        $left = $record->items()->sum('qty_remaining');
                        
                        $html = "
                            <div class='flex flex-col gap-1'>
                                <div class='text-xs text-gray-500 font-medium whitespace-nowrap'>Items Progression: " . ($total - $left) . " / {$total}</div>
                                <div class='w-full bg-gray-200 rounded-full h-1 '>
                                    <div class='bg-primary-600 h-1 rounded-full' style='width: " . ($total > 0 ? (($total - $left) / $total * 100) : 0) . "%'></div>
                                </div>
                            </div>
                        ";
                        return new \Illuminate\Support\HtmlString($html);
                    }),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('generateTasks')
                    ->label('Generate Tasks')
                    ->icon('heroicon-m-plus-circle')
                    ->color('info')
                    ->form([
                        \Filament\Forms\Components\Select::make('order_item_id')
                            ->label('Select Item to Task')
                            ->options(function (\App\Models\Order $record) {
                                return $record->items()
                                    ->where('qty_remaining', '>', 0)
                                    ->pluck('service_type', 'id')
                                    ->map(fn ($name, $id) => "$name (Available: " . \App\Models\OrderItem::find($id)->qty_remaining . ")");
                            })
                            ->required()
                            ->live(),
                        \Filament\Forms\Components\Select::make('assigned_type')
                            ->label('Commission Category')
                            ->options([
                                'design' => '🎨 Design',
                                'video' => '🎬 Video',
                                'ads_management' => '📢 Ads Management',
                            ])
                            ->default(fn (\Filament\Forms\Get $get) => strtolower(str_replace(' ', '_', \App\Models\OrderItem::find($get('order_item_id'))?->service_type ?? '')))
                            ->required()
                            ->live(),
                        \Filament\Forms\Components\TextInput::make('qty_to_task')
                            ->label('How many units?')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->maxValue(fn (\Filament\Forms\Get $get) => \App\Models\OrderItem::find($get('order_item_id'))?->qty_remaining ?? 1)
                            ->required(),
                        \Filament\Forms\Components\Select::make('primary_pic')
                            ->label(fn (\Filament\Forms\Get $get) => $get('assigned_type') === 'video' ? 'Primary PIC' : 'Staff PIC')
                            ->options(\App\Models\User::whereHas('tenants', fn ($q) => $q->where('tenants.id', auth()->user()->tenant_id))->pluck('name', 'id'))
                            ->required()
                            ->visible(fn (\Filament\Forms\Get $get) => in_array($get('assigned_type'), ['design', 'video'])),
                        \Filament\Forms\Components\Toggle::make('is_2_pics')
                            ->label('Divide among 2 PICs?')
                            ->visible(fn (\Filament\Forms\Get $get) => $get('assigned_type') === 'video')
                            ->live(),
                        \Filament\Forms\Components\Select::make('secondary_pic')
                            ->label('Secondary PIC')
                            ->options(\App\Models\User::whereHas('tenants', fn ($q) => $q->where('tenants.id', auth()->user()->tenant_id))->pluck('name', 'id'))
                            ->required(fn (\Filament\Forms\Get $get) => $get('is_2_pics'))
                            ->visible(fn (\Filament\Forms\Get $get) => $get('is_2_pics')),
                        \Filament\Forms\Components\Select::make('pics_multi')
                            ->label('Staff Members (PICs)')
                            ->multiple()
                            ->options(\App\Models\User::whereHas('tenants', fn ($q) => $q->where('tenants.id', auth()->user()->tenant_id))->pluck('name', 'id'))
                            ->required()
                            ->maxItems(3)
                            ->visible(fn (\Filament\Forms\Get $get) => $get('assigned_type') === 'ads_management'),
                    ])
                    ->action(function (array $data, \App\Models\Order $record) {
                        $item = \App\Models\OrderItem::find($data['order_item_id']);
                        $qty = (int) $data['qty_to_task'];
                        
                        $statusId = \App\Models\Lookup::where('name', 'To do')->first()?->id ?? 48;
                        $priorityId = \App\Models\Lookup::where('name', 'Low')->first()?->id ?? 16;

                        // Determine which PICs to sync
                        $picsToSync = [];
                        if ($data['assigned_type'] === 'ads_management') {
                            $picsToSync = $data['pics_multi'];
                        } else {
                            $picsToSync[] = $data['primary_pic'];
                            if ($data['assigned_type'] === 'video' && !empty($data['is_2_pics']) && !empty($data['secondary_pic'])) {
                                $picsToSync[] = $data['secondary_pic'];
                            }
                        }

                        $todo = \App\Models\Todolist::create([
                            'tenant_id' => $record->tenant_id,
                            'order_item_id' => $item->id,
                            'quantity' => $qty,
                            'assigned_type' => $data['assigned_type'],
                            'Title' => ($record->customer?->name ?? 'Customer') . " - $qty " . $item->service_type,
                            'Description' => "Tasks generated from Order #" . ($record->invoice_no ?? $record->id),
                            'status_id' => $statusId,
                            'priority_id' => $priorityId,
                        ]);

                        // Assign PICs
                        $todo->pics()->syncWithPivotValues($picsToSync, ['tenant_id' => $record->tenant_id]);

                        // Deduct from remaining
                        $item->decrement('qty_remaining', $qty);
                        
                        \Filament\Notifications\Notification::make()
                            ->title("Task for $qty Units Generated Successfully")
                            ->success()
                            ->send();
                    })
                    ->visible(fn (\App\Models\Order $record) => $record->items()->where('qty_remaining', '>', 0)->exists()),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
