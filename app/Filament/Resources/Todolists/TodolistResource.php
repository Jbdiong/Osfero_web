<?php

namespace App\Filament\Resources\Todolists;

use App\Filament\Resources\Todolists\Pages\CreateTodolist;
use App\Filament\Resources\Todolists\Pages\EditTodolist;
use App\Filament\Resources\Todolists\Pages\ListTodolists;
use App\Filament\Resources\Todolists\Schemas\TodolistForm;
use App\Filament\Resources\Todolists\Tables\TodolistsTable;
use App\Models\Todolist;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Forms\Form;

use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TodolistResource extends Resource
{
    protected static ?string $model = Todolist::class;

    public static function getSubmitCompletionAction($actionClass = \Filament\Tables\Actions\Action::class)
    {
        return $actionClass::make('submitCompletion')
            ->label('Submit Completion')
            ->icon('heroicon-m-check-circle')
            ->color('success')
            ->hidden(fn (Todolist $record) => $record->status?->name === 'Completed')
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
                                    ->default(fn (Todolist $record) => $record->Title)
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
            ->action(function (array $data, Todolist $record) {
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
                    $record->orderItem->decrement('qty_remaining', $data['qty_deducted']);
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
                
                \Filament\Notifications\Notification::make()
                    ->title('Task Completed & Commission Logged')
                    ->success()
                    ->send();
            })
            ->requiresConfirmation();
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->where('tenant_id', auth()->user()?->tenant_id);

        $isManager = in_array(auth()->user()?->role?->role, ['Superadmin', 'Tenant admin', 'Manager']);

        if (!$isManager) {
            $query->whereHas('pics', function ($q) {
                $q->where('user_id', auth()->id());
            });
        }

        return $query;
    }

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $recordTitleAttribute = 'Title';

    protected static ?string $slug = 'todolists';
    
    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();
        $isManager = in_array($user?->role?->role, ['Superadmin', 'Tenant admin', 'Manager']);

        $query = static::getModel()::query()
            ->where('tenant_id', $user?->tenant_id);

        if (!$isManager) {
            $query->whereHas('pics', function ($q) {
                $q->where('user_id', auth()->id());
            });
        }

        return $query->whereDate('end_date', '<=', now())
            ->whereHas('status', function ($query) {
                $query->where('name', '!=', 'Completed');
            })
            ->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return (int) static::getNavigationBadge() > 0 ? 'danger' : null;
    }

    public static function form(Form $form): Form
    {
        return TodolistForm::configure($form);
    }

    public static function table(Table $table): Table
    {
        return TodolistsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTodolists::route('/'),
            'create' => CreateTodolist::route('/create'),
            'edit' => EditTodolist::route('/{record}/edit'),
            'archived' => \App\Filament\Resources\Todolists\Pages\ArchivedTodolists::route('/archived'),
        ];
    }
}
