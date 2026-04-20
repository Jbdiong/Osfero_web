<?php
namespace App\Filament\Resources\Orders\RelationManagers;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
class UsageLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'usageLogs';
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('order_item_id')
                    ->label('Service/Task')
                    ->options(function (\Filament\Resources\RelationManagers\RelationManager $livewire, $record) {
                        return $livewire->getOwnerRecord()->items()
                            ->get()
                            ->mapWithKeys(function ($item) use ($record) {
                                // Add back the original qty to the calculation so they see what it WILL be if they move away from it
                                $additional = ($record && $record->order_item_id === $item->id) ? " (Active)" : " (Left: {$item->qty_remaining})";
                                return [$item->id => "{$item->service_type} {$additional}"];
                            });
                    })
                    ->disabledOn('create') // we don't have create here anyway, but just keeping it safe
                    ->required()
                    ->live(),
                Forms\Components\TextInput::make('qty_deducted')
                    ->label('Qty Used')
                    ->numeric()
                    ->required()
                    ->minValue(1)
                    ->maxValue(function (Forms\Get $get, ?\Illuminate\Database\Eloquent\Model $record) {
                        if (!$record) return 1;
                        
                        // If they pick a DIFFERENT task while editing, the max is just the new task's remaining qty.
                        if ($get('order_item_id') && $get('order_item_id') != $record->order_item_id) {
                            $newItem = \App\Models\OrderItem::find($get('order_item_id'));
                            return $newItem ? $newItem->qty_remaining : 1;
                        }
                        // Otherwise, it's the current task's remaining PLUS the originally deducted amount.
                        // (Because they are just modifying the existing deduction)
                        if ($record->orderItem) {
                            return $record->orderItem->qty_remaining + $record->getOriginal('qty_deducted');
                        }
                        
                        return 1;
                    }),
                Forms\Components\DatePicker::make('date_delivered')
                    ->required(),
                Forms\Components\Textarea::make('notes'),
                Forms\Components\Repeater::make('attachments')
                    ->relationship('attachments')
                    ->schema([
                        Forms\Components\TextInput::make('file_name'),
                        Forms\Components\FileUpload::make('file_url')
                            ->directory('usage_attachments')
                            ->downloadable()
                            ->openable(),
                    ])
            ]);
    }
    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('date_delivered')
            ->columns([
                Tables\Columns\TextColumn::make('orderItem.service_type')
                    ->label('Service/Task')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('qty_deducted')
                    ->label('Qty Used')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('date_delivered')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Submitted By'),
                Tables\Columns\TextColumn::make('notes')
                    ->limit(50),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Intentionally empty: creation happens through the Order table action
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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