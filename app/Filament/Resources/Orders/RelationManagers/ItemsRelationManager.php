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
                        \Filament\Forms\Components\TextInput::make('qty_deducted')
                            ->label('Qty Deducted')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->maxValue(function (Forms\Get $get, ?\Illuminate\Database\Eloquent\Model $record) {
                                return $record ? $record->qty_remaining : 1;
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