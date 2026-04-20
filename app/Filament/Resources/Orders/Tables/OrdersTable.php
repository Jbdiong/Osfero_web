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
                            ->live(),
                        \Filament\Forms\Components\TextInput::make('qty_deducted')
                            ->label('Qty Deducted')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->maxValue(function (\Filament\Forms\Get $get, \App\Models\Order $record) {
                                if (!$get('order_item_id')) return 1;
                                return $record->items()->find($get('order_item_id'))?->qty_remaining ?? 1;
                            })
                            ->required(),
                        \Filament\Forms\Components\DatePicker::make('date_delivered')
                            ->label('Date Delivered')
                            ->default(now())
                            ->required(),
                        \Filament\Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->nullable(),
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







