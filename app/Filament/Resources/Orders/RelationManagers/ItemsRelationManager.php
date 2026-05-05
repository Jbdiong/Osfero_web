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
                Forms\Components\Select::make('service_type')
                    ->options(function () {
                        $existing = \App\Models\OrderItem::when(auth()->check() && auth()->user()->tenant_id, fn($q) => $q->where('tenant_id', auth()->user()->tenant_id))
                            ->whereNotNull('service_type')
                            ->distinct()
                            ->pluck('service_type', 'service_type')
                            ->toArray();
                        return array_merge($existing, [
                            'Design' => '🎨 Design',
                            'Video' => '🎬 Video',
                            'Ads Management' => '📢 Ads Management',
                        ]);
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
            ])
            ->actions([
                Tables\Actions\Action::make('generateTasks')
                    ->label('Generate Tasks')
                    ->icon('heroicon-m-plus-circle')
                    ->color('info')
                    ->form([
                        \Filament\Forms\Components\Select::make('assigned_type')
                            ->label('Commission Category')
                            ->options([
                                'design' => '🎨 Design',
                                'video' => '🎬 Video',
                                'ads_management' => '📢 Ads Management',
                            ])
                            ->default(fn ($record) => strtolower(str_replace(' ', '_', $record->service_type ?? '')))
                            ->required()
                            ->live(),
                        \Filament\Forms\Components\TextInput::make('qty_to_task')
                            ->label('How many units to task?')
                            ->numeric()
                            ->default(fn ($record) => $record->qty_remaining)
                            ->minValue(1)
                            ->maxValue(fn ($record) => $record->qty_remaining)
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
                    ->action(function (array $data, \App\Models\OrderItem $record) {
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
                            'order_item_id' => $record->id,
                            'quantity' => $qty,
                            'assigned_type' => $data['assigned_type'],
                            'Title' => ($record->order->customer?->name ?? 'Customer') . " - $qty " . $record->service_type,
                            'Description' => "Task for " . $record->service_type,
                            'status_id' => $statusId,
                            'priority_id' => $priorityId,
                        ]);

                        $todo->pics()->syncWithPivotValues($picsToSync, ['tenant_id' => $record->tenant_id]);

                        $record->decrement('qty_remaining', $qty);

                        \Filament\Notifications\Notification::make()
                            ->title("Task for $qty Units Created")
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record) => $record->qty_remaining > 0),
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