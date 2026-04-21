<?php

namespace App\Filament\Resources\Orders\Tables;

use Filament\Tables;
use Filament\Tables\Table;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([

                Tables\Columns\TextColumn::make('customer.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('purchase_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('invoice_no')
                    ->searchable(),
                Tables\Columns\TextColumn::make('invoice_file')
                    ->searchable(),
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
                Tables\Actions\Action::make('submitCompletion')
                    ->label('Submit Completion')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->form([
                        \Filament\Forms\Components\Placeholder::make('all_leftover')
                            ->label('Leftover Tasks for this Order')
                            ->content(function (\App\Models\Order $record) {
                                $leftovers = $record->items()->where('qty_remaining', '>', 0)->get();
                                if ($leftovers->isEmpty()) {
                                    return '-';
                                }
                                
                                $html = "<ul style='list-style-type: disc; padding-left: 20px; font-weight: 500;'>";
                                foreach ($leftovers as $task) {
                                    $html .= "<li>{$task->service_type} - <span style='color: rgb(22 163 74); font-weight: bold;'>{$task->qty_remaining} Left</span></li>";
                                }
                                $html .= "</ul>";
                                
                                return new \Illuminate\Support\HtmlString($html);
                            }),
                        \Filament\Forms\Components\Select::make('order_item_id')
                            ->label('Task / Service')
                            ->options(function (\App\Models\Order $record) {
                                return $record->items()
                                    ->where('qty_remaining', '>', 0)
                                    ->get()
                                    ->mapWithKeys(function ($item) {
                                        return [$item->id => "{$item->service_type} (Left: {$item->qty_remaining})"];
                                    });
                            })
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, \Filament\Forms\Set $set, \App\Models\Order $record) {
                                if (!$state) return;
                                $item = $record->items()->find($state);
                                if (!$item) return;

                                $st = strtolower($item->service_type);
                                $type = null;
                                if (str_contains($st, 'design')) $type = 'design';
                                elseif (str_contains($st, 'video')) $type = 'video';
                                elseif (str_contains($st, 'ads') || str_contains($st, 'management')) $type = 'ads_management';
                                
                                if ($type) {
                                    $set('log_commission', true);
                                    $set('commission_type', $type);
                                } else {
                                    $set('log_commission', false);
                                }
                                
                                $set('commission_name', ($record->customer?->name ?? 'Customer') . ' - ' . $item->service_type);
                            }),
                        \Filament\Forms\Components\Grid::make(2)->schema([
                            \Filament\Forms\Components\TextInput::make('qty_deducted')
                                ->label('Qty Completed')
                                ->numeric()
                                ->default(1)
                                ->minValue(1)
                                ->maxValue(function (\Filament\Forms\Get $get, \App\Models\Order $record) {
                                    if (!$get('order_item_id')) return 1;
                                    return $record->items()->find($get('order_item_id'))?->qty_remaining ?? 1;
                                })
                                ->required()
                                ->live()
                                ->afterStateUpdated(fn ($state, \Filament\Forms\Set $set) => $set('commission_qty', $state)),
                            \Filament\Forms\Components\DatePicker::make('date_delivered')
                                ->label('Date Delivered')
                                ->default(now())
                                ->required(),
                        ]),
                        \Filament\Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(2)
                            ->nullable(),
                        \Filament\Forms\Components\Section::make('Commission Details')
                            ->description('Confirm if you want to earn commission for this completion.')
                            ->collapsed(fn (\Filament\Forms\Get $get) => !$get('log_commission'))
                            ->schema([
                                \Filament\Forms\Components\Toggle::make('log_commission')
                                    ->label('Log to Commission?')
                                    ->default(false)
                                    ->live(),
                                \Filament\Forms\Components\Group::make()
                                    ->visible(fn (\Filament\Forms\Get $get) => $get('log_commission'))
                                    ->schema([
                                        \Filament\Forms\Components\Select::make('commission_type')
                                            ->label('Type')
                                            ->options([
                                                'design' => '🎨 Design',
                                                'video' => '🎬 Video',
                                                'ads_management' => '📢 Ads Management',
                                            ])
                                            ->required()
                                            ->live(),
                                        \Filament\Forms\Components\Grid::make(2)->schema([
                                            \Filament\Forms\Components\TextInput::make('commission_name')
                                                ->label('Entry Name')
                                                ->required(),
                                            \Filament\Forms\Components\TextInput::make('commission_qty')
                                                ->label('Qty')
                                                ->numeric()
                                                ->default(1)
                                                ->required(),
                                        ]),
                                        
                                        // PIC Selection Group
                                        \Filament\Forms\Components\Group::make()
                                            ->schema([
                                                // Single/Video PICs
                                                \Filament\Forms\Components\Group::make()
                                                    ->hidden(fn (\Filament\Forms\Get $get) => $get('commission_type') === 'ads_management')
                                                    ->schema([
                                                        \Filament\Forms\Components\Select::make('primary_pic')
                                                            ->label(fn (\Filament\Forms\Get $get) => $get('commission_type') === 'video' ? 'Primary PIC' : 'Staff Member (PIC)')
                                                            ->options(\App\Models\User::whereHas('tenants', fn ($q) => $q->where('tenants.id', auth()->user()->tenant_id))->pluck('name', 'id'))
                                                            ->default(auth()->id())
                                                            ->required(),
                                                        \Filament\Forms\Components\Toggle::make('is_2_pics')
                                                            ->label('Divide among 2 PICs?')
                                                            ->visible(fn (\Filament\Forms\Get $get) => $get('commission_type') === 'video')
                                                            ->live(),
                                                        \Filament\Forms\Components\Select::make('secondary_pic')
                                                            ->label('Secondary PIC')
                                                            ->options(\App\Models\User::whereHas('tenants', fn ($q) => $q->where('tenants.id', auth()->user()->tenant_id))->pluck('name', 'id'))
                                                            ->required(fn (\Filament\Forms\Get $get) => $get('is_2_pics'))
                                                            ->visible(fn (\Filament\Forms\Get $get) => $get('is_2_pics')),
                                                    ]),
                                                
                                                // Multi PICs (Ads)
                                                \Filament\Forms\Components\Select::make('users_multi')
                                                    ->label('Staff Members (PICs)')
                                                    ->multiple()
                                                    ->options(\App\Models\User::whereHas('tenants', fn ($q) => $q->where('tenants.id', auth()->user()->tenant_id))->pluck('name', 'id'))
                                                    ->default([auth()->id()])
                                                    ->required()
                                                    ->visible(fn (\Filament\Forms\Get $get) => $get('commission_type') === 'ads_management'),
                                            ])
                                    ]),
                            ]),
                        \Filament\Forms\Components\Repeater::make('attachments')
                            ->label('Attachments (Optional)')
                            ->schema([
                                \Filament\Forms\Components\TextInput::make('file_name')
                                    ->label('Attachment Name')
                                    ->nullable(),
                                \Filament\Forms\Components\FileUpload::make('file_url')
                                    ->label('File')
                                    ->directory('usage_attachments')
                                    ->nullable(),
                            ])
                            ->defaultItems(0)
                            ->collapsed()
                    ])
                    ->action(function (array $data, \App\Models\Order $record) {
                        $usageLog = \App\Models\UsageLog::create([
                            'tenant_id' => auth()->user()->last_active_tenant_id,
                            'order_item_id' => $data['order_item_id'],
                            'user_id' => auth()->id(),
                            'qty_deducted' => $data['qty_deducted'],
                            'date_delivered' => $data['date_delivered'],
                            'notes' => $data['notes'],
                        ]);
                        
                        $item = \App\Models\OrderItem::find($data['order_item_id']);
                        $item->qty_remaining -= $data['qty_deducted'];
                        $item->save();

                        // Create Commission Entry if confirmed
                        if (!empty($data['log_commission'])) {
                            $date = \Carbon\Carbon::parse($data['date_delivered']);
                            
                            // Determine primary user for the main user_id field
                            $mainUserId = ($data['commission_type'] === 'ads_management') 
                                ? (is_array($data['users_multi']) ? reset($data['users_multi']) : auth()->id())
                                : $data['primary_pic'];

                            $entry = \App\Models\CommissionEntry::create([
                                'tenant_id' => $usageLog->tenant_id,
                                'customer_id' => $record->customer_id,
                                'user_id' => $mainUserId,
                                'type' => $data['commission_type'],
                                'entry_date' => $data['date_delivered'],
                                'year' => $date->year,
                                'month' => $date->month,
                                'name' => $data['commission_name'],
                                'quantity' => $data['commission_qty'],
                                'remarks' => $usageLog->notes,
                            ]);
                            
                            // Link commission to the usage log
                            $usageLog->update(['commission_entry_id' => $entry->id]);
                            
                            // Sync PICs logic
                            $syncData = [];
                            if ($data['commission_type'] === 'video' && !empty($data['is_2_pics']) && !empty($data['secondary_pic'])) {
                                $syncData[$data['primary_pic']] = ['tenant_id' => $usageLog->tenant_id, 'split_percentage' => 50];
                                $syncData[$data['secondary_pic']] = ['tenant_id' => $usageLog->tenant_id, 'split_percentage' => 50];
                            } elseif ($data['commission_type'] === 'ads_management' && !empty($data['users_multi'])) {
                                $uids = is_array($data['users_multi']) ? $data['users_multi'] : [$data['users_multi']];
                                $count = count($uids);
                                $percent = $count > 0 ? 100 / $count : 100;
                                foreach ($uids as $uid) {
                                    $syncData[$uid] = ['tenant_id' => $usageLog->tenant_id, 'split_percentage' => $percent];
                                }
                            } else {
                                // Default to single PIC
                                $syncData[$mainUserId] = ['tenant_id' => $usageLog->tenant_id, 'split_percentage' => 100];
                            }
                            
                            $entry->users()->sync($syncData);
                        }
                        
                        if (!empty($data['attachments'])) {
                            foreach ($data['attachments'] as $attachment) {
                                if (!empty($attachment['file_url'])) { 
                                    \App\Models\UsageAttachment::create([
                                        'tenant_id' => auth()->user()->last_active_tenant_id,
                                        'usage_log_id' => $usageLog->id,
                                        'file_name' => $attachment['file_name'] ?? 'Attachment',
                                        'file_url' => $attachment['file_url'],
                                    ]);
                                }
                            }
                        }
                    })
                    ->visible(function (\App\Models\Order $record) {
                        return $record->items()->where('qty_remaining', '>', 0)->exists();
                    }),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}







