<?php
namespace App\Filament\Resources\Orders\RelationManagers;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('tenant_id')
                    ->default(fn () => auth()->user()->last_active_tenant_id),
                Forms\Components\TextInput::make('service_type')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('total_qty_purchased')
                    ->label('Total Qty Purchased')
                    ->numeric()
                    ->required()
                    ->default(1),
                Forms\Components\TextInput::make('qty_remaining')
                    ->label('Qty Remaining')
                    ->numeric()
                    ->required()
                    ->default(1)
                    ->hiddenOn('create'),
            ]);
    }
    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('service_type')
            ->columns([
                Tables\Columns\TextColumn::make('service_type'),
                Tables\Columns\TextColumn::make('total_qty_purchased')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('qty_remaining')
                    ->numeric()
                    ->sortable()
                    ->color(fn ($state) => $state == 0 ? 'success' : 'warning'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['qty_remaining'] = $data['total_qty_purchased'];
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('submitCompletion')
                    ->label('Submit Completion')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->form([
                        \Filament\Forms\Components\Grid::make(2)->schema([
                            \Filament\Forms\Components\TextInput::make('qty_deducted')
                                ->label('Qty Completed')
                                ->numeric()
                                ->default(1)
                                ->minValue(1)
                                ->maxValue(function (Forms\Get $get, ?\Illuminate\Database\Eloquent\Model $record) {
                                    return $record ? $record->qty_remaining : 1;
                                })
                                ->required()
                                ->live()
                                ->afterStateUpdated(fn ($state, Forms\Set $set) => $set('commission_qty', $state)),
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
                            ->schema([
                                \Filament\Forms\Components\Toggle::make('log_commission')
                                    ->label('Log to Commission?')
                                    ->default(function (?\Illuminate\Database\Eloquent\Model $record) {
                                        if (!$record) return false;
                                        $st = strtolower($record->service_type);
                                        return str_contains($st, 'design') || str_contains($st, 'video') || str_contains($st, 'ads') || str_contains($st, 'management');
                                    })
                                    ->live(),
                                \Filament\Forms\Components\Group::make()
                                    ->visible(fn (Forms\Get $get) => $get('log_commission'))
                                    ->schema([
                                        \Filament\Forms\Components\Select::make('commission_type')
                                            ->label('Type')
                                            ->options([
                                                'design' => '🎨 Design',
                                                'video' => '🎬 Video',
                                                'ads_management' => '📢 Ads Management',
                                            ])
                                            ->default(function (?\Illuminate\Database\Eloquent\Model $record) {
                                                if (!$record) return null;
                                                $st = strtolower($record->service_type);
                                                if (str_contains($st, 'design')) return 'design';
                                                if (str_contains($st, 'video')) return 'video';
                                                if (str_contains($st, 'ads') || str_contains($st, 'management')) return 'ads_management';
                                                return null;
                                            })
                                            ->required()
                                            ->live(),
                                        \Filament\Forms\Components\Grid::make(2)->schema([
                                            \Filament\Forms\Components\TextInput::make('commission_name')
                                                ->label('Entry Name')
                                                ->default(fn (?\Illuminate\Database\Eloquent\Model $record) => ($record->order->customer?->name ?? 'Customer') . ' - ' . $record->service_type)
                                                ->required(),
                                            \Filament\Forms\Components\TextInput::make('commission_qty')
                                                ->label('Qty')
                                                ->numeric()
                                                ->default(1)
                                                ->required(),
                                        ]),

                                        // PIC Selection Group (New Advanced PIC logic)
                                        \Filament\Forms\Components\Group::make()
                                            ->schema([
                                                \Filament\Forms\Components\Group::make()
                                                    ->hidden(fn (Forms\Get $get) => $get('commission_type') === 'ads_management')
                                                    ->schema([
                                                        \Filament\Forms\Components\Select::make('primary_pic')
                                                            ->label(fn (Forms\Get $get) => $get('commission_type') === 'video' ? 'Primary PIC' : 'Staff Member (PIC)')
                                                            ->options(\App\Models\User::whereHas('tenants', fn ($q) => $q->where('tenants.id', auth()->user()->tenant_id))->pluck('name', 'id'))
                                                            ->default(auth()->id())
                                                            ->required(),
                                                        \Filament\Forms\Components\Toggle::make('is_2_pics')
                                                            ->label('Divide among 2 PICs?')
                                                            ->visible(fn (Forms\Get $get) => $get('commission_type') === 'video')
                                                            ->live(),
                                                        \Filament\Forms\Components\Select::make('secondary_pic')
                                                            ->label('Secondary PIC')
                                                            ->options(\App\Models\User::whereHas('tenants', fn ($q) => $q->where('tenants.id', auth()->user()->tenant_id))->pluck('name', 'id'))
                                                            ->required(fn (Forms\Get $get) => $get('is_2_pics'))
                                                            ->visible(fn (Forms\Get $get) => $get('is_2_pics')),
                                                    ]),
                                                
                                                \Filament\Forms\Components\Select::make('users_multi')
                                                    ->label('Staff Members (PICs)')
                                                    ->multiple()
                                                    ->options(\App\Models\User::whereHas('tenants', fn ($q) => $q->where('tenants.id', auth()->user()->tenant_id))->pluck('name', 'id'))
                                                    ->default([auth()->id()])
                                                    ->required()
                                                    ->visible(fn (Forms\Get $get) => $get('commission_type') === 'ads_management'),
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
                    ->action(function (array $data, ?\Illuminate\Database\Eloquent\Model $record) {
                        $usageLog = \App\Models\UsageLog::create([
                            'tenant_id' => auth()->user()->last_active_tenant_id,
                            'order_item_id' => $record->id,
                            'user_id' => auth()->id(),
                            'qty_deducted' => $data['qty_deducted'],
                            'date_delivered' => $data['date_delivered'],
                            'notes' => $data['notes'],
                        ]);
                        
                        $record->qty_remaining -= $data['qty_deducted'];
                        $record->save();

                        // Create Commission Entry if confirmed
                        if (!empty($data['log_commission'])) {
                            $date = \Carbon\Carbon::parse($data['date_delivered']);
                            
                            $mainUserId = ($data['commission_type'] === 'ads_management') 
                                ? (is_array($data['users_multi'] ?? []) ? reset($data['users_multi']) : auth()->id())
                                : ($data['primary_pic'] ?? auth()->id());

                            $entry = \App\Models\CommissionEntry::create([
                                'tenant_id' => $usageLog->tenant_id,
                                'customer_id' => $record->order->customer_id,
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
                    ->visible(function (?\Illuminate\Database\Eloquent\Model $record) {
                        return $record && $record->qty_remaining > 0;
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}