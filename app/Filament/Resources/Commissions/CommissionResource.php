<?php

namespace App\Filament\Resources\Commissions;

use App\Filament\Resources\Commissions\CommissionResource\Pages;
use App\Models\CommissionEntry;
use App\Models\CommissionSetting;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class CommissionResource extends Resource
{
    protected static ?string $model = CommissionEntry::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'My Entries';

    protected static ?string $navigationGroup = 'Commission';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        if (! $user) {
            return parent::getEloquentQuery()->whereRaw('1=0');
        }

        $query = parent::getEloquentQuery()->where('tenant_id', $user->tenant_id);

        if (static::isStaffOnly()) {
            $query = $query->where('user_id', $user->id);
        }

        return $query;
    }

    public static function isStaffOnly(): bool
    {
        $user = Auth::user();
        if (! $user) return true;
        
        $roleState = $user->role;
        if (! $roleState) return true;
        
        $roleName = strtolower($roleState->role ?? '');
        $managerRoles = ['manager', 'admin', 'superadmin', 'super admin', 'tenantadmin', 'tenant admin'];
        
        return ! in_array($roleName, $managerRoles);
    }

    public static function form(Form $form): Form
    {
        $currentYear  = now()->year;
        $currentMonth = now()->month;

        $years  = array_combine(
            range($currentYear - 2, $currentYear + 1),
            range($currentYear - 2, $currentYear + 1)
        );
        $months = [
            1 => 'January',   2 => 'February',  3 => 'March',
            4 => 'April',     5 => 'May',        6 => 'June',
            7 => 'July',      8 => 'August',     9 => 'September',
            10 => 'October', 11 => 'November',  12 => 'December',
        ];

        return $form->schema([

            // ── Entry header ────────────────────────────────────────
            Forms\Components\Section::make()
                ->schema([
                    Forms\Components\Select::make('type')
                        ->label('Commission Type')
                        ->options([
                            'design'         => '🎨  Design',
                            'ads_management' => '📢  Ads Management',
                            'sales'          => '💼  Sales',
                        ])
                        ->required()
                        ->live()
                        ->native(false),

                    Forms\Components\DatePicker::make('entry_date')
                        ->label(fn (Get $get) => $get('type') === 'design' ? 'Design Date' : 'Entry Date')
                        ->default(now())
                        ->required(),

                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\Select::make('month')
                            ->options($months)
                            ->default($currentMonth)
                            ->required()
                            ->native(false),

                        Forms\Components\Select::make('year')
                            ->options($years)
                            ->default($currentYear)
                            ->required()
                            ->native(false),
                    ]),

                    // Manager/admin can choose any staff member
                    Forms\Components\Select::make('user_id')
                        ->label('Staff Member')
                        ->options(function () {
                            $tenantId = Auth::user()->tenant_id;
                            return User::where('tenant_id', $tenantId)->pluck('name', 'id');
                        })
                        ->default(fn () => Auth::id())
                        ->hidden(fn () => self::isStaffOnly())
                        ->dehydrated()
                        ->required()
                        ->native(false),
                ]),

            // ── DESIGN ──────────────────────────────────────────────
            Forms\Components\Section::make('Design Entry')
                ->description(fn () => self::designHintFromSettings())
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Project Name')
                        ->placeholder('e.g. Company ABC Banner Set')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('quantity')
                        ->label('Number of Designs')
                        ->numeric()
                        ->minValue(1)
                        ->default(1)
                        ->required()
                        ->suffix('pcs')
                        ->helperText('Each entry accumulates to your monthly total — bonus tier is applied on the total.'),
                ])
                ->visible(fn (Get $get) => $get('type') === 'design'),

            // ── ADS MANAGEMENT ──────────────────────────────────────
            Forms\Components\Section::make('Ads Management Entry')
                ->description(fn () => 'Fee per client is fixed at RM ' . number_format((float) self::getSettings()->ads_fee, 2) . '/month.')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Client Name')
                        ->placeholder('e.g. Kedai Runcit Sdn Bhd')
                        ->required()
                        ->maxLength(255),
                ])
                ->visible(fn (Get $get) => $get('type') === 'ads_management'),

            // ── SALES ───────────────────────────────────────────────
            Forms\Components\Section::make('Sales Entry')
                ->description(fn () => 'Commission rate: ' . number_format((float) self::getSettings()->sales_rate, 2) . '% of package value.')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Client Name')
                        ->placeholder('e.g. Syarikat XYZ')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('package_value')
                        ->label('Package Value (RM)')
                        ->numeric()
                        ->minValue(0)
                        ->required()
                        ->prefix('RM')
                        ->live(debounce: 600)
                        ->helperText(function (Get $get): string {
                            $v    = (float) ($get('package_value') ?? 0);
                            $rate = (float) self::getSettings()->sales_rate;
                            $comm = round($v * $rate / 100, 2);
                            return $comm > 0
                                ? 'Commission earned: RM ' . number_format($comm, 2)
                                : 'Enter amount to see commission preview';
                        }),
                ])
                ->visible(fn (Get $get) => $get('type') === 'sales'),

            // ── Remarks ─────────────────────────────────────────────
            Forms\Components\Section::make()
                ->schema([
                    Forms\Components\Textarea::make('remarks')
                        ->label('Remarks (optional)')
                        ->rows(2),
                ])
                ->visible(fn (Get $get) => filled($get('type'))),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Staff')
                    ->sortable()
                    ->searchable()
                    ->hidden(fn () => self::isStaffOnly()),

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'design'         => 'primary',
                        'ads_management' => 'success',
                        'sales'          => 'warning',
                        default          => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'design'         => '🎨 Design',
                        'ads_management' => '📢 Ads Management',
                        'sales'          => '💼 Sales',
                        default          => ucfirst($state),
                    }),

                Tables\Columns\TextColumn::make('name')
                    ->label('Project / Client')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('entry_date')
                    ->label('Entry Date')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Qty')
                    ->placeholder('—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('package_value')
                    ->label('Package (RM)')
                    ->money('MYR')
                    ->placeholder('—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('month')
                    ->label('Month')
                    ->formatStateUsing(fn (int $state): string => \Carbon\Carbon::create()->month($state)->format('F'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('year')
                    ->label('Year')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_approved')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('warning')
                    ->sortable(),

                Tables\Columns\TextColumn::make('approvedBy.name')
                    ->label('Approved By')
                    ->placeholder('Pending')
                    ->hidden(fn () => self::isStaffOnly())
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Logged At')
                    ->dateTime('d M Y, h:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->groups([
                Tables\Grouping\Group::make('month')
                    ->label('Month')
                    ->getTitleFromRecordUsing(
                        fn (CommissionEntry $r) => \Carbon\Carbon::create()->month($r->month)->format('F') . ' ' . $r->year
                    ),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'design'         => 'Design',
                        'ads_management' => 'Ads Management',
                        'sales'          => 'Sales',
                    ]),

                Tables\Filters\SelectFilter::make('month')
                    ->options([
                        1 => 'January',   2 => 'February',  3 => 'March',
                        4 => 'April',     5 => 'May',        6 => 'June',
                        7 => 'July',      8 => 'August',     9 => 'September',
                        10 => 'October', 11 => 'November',  12 => 'December',
                    ]),

                Tables\Filters\SelectFilter::make('year')
                    ->options(fn () => array_combine(
                        range(now()->year - 2, now()->year + 1),
                        range(now()->year - 2, now()->year + 1)
                    )),

                Tables\Filters\TernaryFilter::make('is_approved')
                    ->label('Approval Status')
                    ->placeholder('All')
                    ->trueLabel('Approved Only')
                    ->falseLabel('Pending Only'),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (CommissionEntry $r) => ! self::isStaffOnly() && ! $r->is_approved)
                    ->action(fn (CommissionEntry $r) => $r->update([
                        'is_approved' => true,
                        'approved_by' => Auth::id(),
                        'approved_at' => now(),
                    ]))
                    ->requiresConfirmation(),

                Tables\Actions\Action::make('unapprove')
                    ->label('Recall')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (CommissionEntry $r) => ! self::isStaffOnly() && $r->is_approved)
                    ->action(fn (CommissionEntry $r) => $r->update([
                        'is_approved' => false,
                        'approved_by' => null,
                        'approved_at' => null,
                    ]))
                    ->requiresConfirmation(),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCommissions::route('/'),
            'create' => Pages\CreateCommission::route('/create'),
            'edit'   => Pages\EditCommission::route('/{record}/edit'),
        ];
    }

    // ── Helpers ─────────────────────────────────────────────────────

    protected static ?CommissionSetting $cachedSettings = null;

    public static function getSettings(): CommissionSetting
    {
        if (static::$cachedSettings) return static::$cachedSettings;
        $tenantId = Auth::user()?->tenant_id;
        return static::$cachedSettings = CommissionSetting::forTenant($tenantId);
    }

    protected static function designHintFromSettings(): string
    {
        $s = self::getSettings();
        return sprintf(
            'RM %.2f/design | Bonus tiers: %s',
            (float) $s->design_rate,
            $s->tierSummary()
        );
    }
}
