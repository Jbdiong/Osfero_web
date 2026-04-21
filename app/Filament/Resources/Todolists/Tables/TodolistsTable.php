<?php

namespace App\Filament\Resources\Todolists\Tables;

use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TodolistsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->where('tenant_id', auth()->user()->tenant_id))
            ->columns([
                TextColumn::make('lead.id')
                    ->searchable(),
                TextColumn::make('payment.id')
                    ->searchable(),
                TextColumn::make('quantity')
                    ->badge()
                    ->color('info')
                    ->alignCenter(),
                TextColumn::make('Title')
                    ->searchable(),
                TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('end_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('priority.name')
                    ->searchable(),
                TextColumn::make('parent.id')
                    ->searchable(),
                TextColumn::make('status.name')
                    ->searchable(),
                TextColumn::make('position')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('tenant.name')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                \Filament\Tables\Actions\Action::make('submitCompletion')
                    ->label('Submit Completion')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->hidden(fn (\App\Models\Todolist $record) => $record->status?->name === 'Completed')
                    ->form([
                        \Filament\Forms\Components\Grid::make(2)->schema([
                            \Filament\Forms\Components\DatePicker::make('date_delivered')
                                ->label('Date Delivered')
                                ->default(now())
                                ->required(),
                            \Filament\Forms\Components\TextInput::make('qty_deducted')
                                ->label('Units Completed')
                                ->numeric()
                                ->default(fn ($record) => $record->quantity)
                                ->minValue(1)
                                ->required()
                                ->helperText('This will deduct from the remaining quantity of the order item.'),
                        ]),
                        \Filament\Forms\Components\Textarea::make('notes')
                            ->label('Work Notes / Remarks')
                            ->rows(2)
                            ->nullable(),
                        \Filament\Forms\Components\Section::make('Commission Details')
                            ->description('Confirm if you want to generate a commission entry for the assigned PICs.')
                            ->schema([
                                \Filament\Forms\Components\Toggle::make('log_commission')
                                    ->label('Generate Commission?')
                                    ->default(true)
                                    ->live(),
                                \Filament\Forms\Components\Group::make()
                                    ->visible(fn (\Filament\Forms\Get $get) => $get('log_commission'))
                                    ->schema([
                                        \Filament\Forms\Components\Select::make('commission_type')
                                            ->label('Commission Type')
                                            ->options([
                                                'design' => '🎨 Design',
                                                'video' => '🎬 Video',
                                                'ads_management' => '📢 Ads Management',
                                            ])
                                            ->default(fn ($record) => $record->assigned_type)
                                            ->required(),
                                        \Filament\Forms\Components\TextInput::make('commission_name')
                                            ->label('Entry Name')
                                            ->default(fn (\App\Models\Todolist $record) => $record->Title)
                                            ->required(),
                                    ])
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
                    ->action(function (array $data, \App\Models\Todolist $record) {
                        // 1. Create UsageLog
                        $usageLog = \App\Models\UsageLog::create([
                            'tenant_id' => $record->tenant_id,
                            'order_item_id' => $record->order_item_id,
                            'todolist_id' => $record->id,
                            'user_id' => auth()->id(), // Who submitted it
                            'qty_deducted' => $data['qty_deducted'],
                            'date_delivered' => $data['date_delivered'],
                            'notes' => $data['notes'],
                            'created_by' => auth()->id(),
                            'updated_by' => auth()->id(),
                        ]);

                        // 2. Update Order Item remaining qty if link exists
                        if ($record->orderItem) {
                            $record->orderItem->qty_remaining -= $data['qty_deducted'];
                            $record->orderItem->save();
                        }

                        // 3. Mark Todolist as completed
                        $statusParent = \App\Models\Lookup::where('name', 'Todolist Status')->first();
                        $completedStatus = \App\Models\Lookup::where('name', 'Completed')
                            ->where('parent_id', $statusParent?->id)
                            ->first()?->id ?? 51; // Fallback to 51 based on DB check
                        
                        $record->update(['status_id' => $completedStatus]);

                        // 4. Create Commission Entry if confirmed
                        if (!empty($data['log_commission'])) {
                            $date = \Carbon\Carbon::parse($data['date_delivered']);
                            
                            // PICs from Todolist
                            $pics = $record->pics;
                            // If no PICs, use the creator of the log or current user
                            $mainPicId = $pics->first()?->id ?? auth()->id();

                            $entry = \App\Models\CommissionEntry::create([
                                'tenant_id' => $record->tenant_id,
                                'customer_id' => $record->orderItem?->order?->customer_id ?? $record->lead?->customer_id,
                                'usage_log_id' => $usageLog->id,
                                'user_id' => $mainPicId,
                                'type' => $data['commission_type'],
                                'entry_date' => $data['date_delivered'],
                                'year' => $date->year,
                                'month' => $date->month,
                                'name' => $data['commission_name'],
                                'quantity' => $data['qty_deducted'],
                                'remarks' => "Commission from Todolist Completion: " . $record->Title,
                                'status' => 'Pending', // Ensure it has a default status
                            ]);
                            
                            // Sync PICs with Split Percentage
                            if ($pics->count() > 0) {
                                $percent = 100 / $pics->count();
                                $syncData = [];
                                foreach ($pics as $pic) {
                                    $syncData[$pic->id] = [
                                        'tenant_id' => $record->tenant_id,
                                        'split_percentage' => $percent,
                                    ];
                                }
                                $entry->users()->sync($syncData);
                            } else {
                                $entry->users()->sync([
                                    auth()->id() => [
                                        'tenant_id' => $record->tenant_id,
                                        'split_percentage' => 100,
                                    ]
                                ]);
                            }
                        }

                        // 5. Handle Attachments
                        if (!empty($data['attachments'])) {
                            foreach ($data['attachments'] as $attachment) {
                                if (!empty($attachment['file_url'])) { 
                                    \App\Models\UsageAttachment::create([
                                        'tenant_id' => $record->tenant_id,
                                        'usage_log_id' => $usageLog->id,
                                        'file_name' => $attachment['file_name'] ?? 'Attachment',
                                        'file_url' => $attachment['file_url'],
                                    ]);
                                }
                            }
                        }
                    })
                    ->requiresConfirmation(),
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}







