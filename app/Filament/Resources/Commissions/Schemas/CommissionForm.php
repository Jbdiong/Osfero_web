<?php

namespace App\Filament\Resources\Commissions\Schemas;

use App\Filament\Resources\Commissions\CommissionResource;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;

class CommissionForm
{
    public static function configure(Form $form): Form
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
                            'video'          => '🎬  Video',
                            'ads_management' => '📢  Ads Management',
                            'sales'          => '💼  Sales',
                        ])
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (Set $set) {
                            $set('users', [Auth::id()]);
                        })
                        ->native(false),

                    Forms\Components\DatePicker::make('entry_date')
                        ->native(false)
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

                    // Single Select for Design & Primary for Video
                    Forms\Components\Select::make('primary_pic')
                        ->label(fn (Get $get) => $get('type') === 'video' ? 'Primary PIC' : 'Staff Member (PIC)')
                        ->options(function () {
                            $tenantId = Auth::user()->tenant_id;
                            return User::where('tenant_id', $tenantId)->pluck('name', 'id');
                        })
                        ->default(fn () => Auth::id())
                        ->afterStateHydrated(function (Forms\Components\Select $component, ?Model $record) {
                            if (! $record) return;
                            if (in_array($record->type, ['design', 'video'])) {
                                $pic = $record->users->first();
                                $component->state($pic ? $pic->id : clone $record->user_id);
                            }
                        })
                        ->hidden(function (Get $get) {
                            return !in_array($get('type'), ['design', 'video']);
                        })
                        ->required(fn (Get $get) => in_array($get('type'), ['design', 'video']))
                        ->dehydrated(false)
                        ->native(false),

                    // Checkbox for 2 PICs for Video
                    Forms\Components\Checkbox::make('is_2_pics')
                        ->label('Divide among 2 PICs?')
                        ->live()
                        ->afterStateHydrated(function (Forms\Components\Checkbox $component, ?Model $record) {
                            if (! $record) return;
                            if ($record->type !== 'video') return;
                            $picCount = $record->users()->count();
                            $component->state($picCount > 1);
                        })
                        ->afterStateUpdated(function (Set $set, $state) {
                            if (!$state) $set('secondary_user_id', null);
                        })
                        ->hidden(function (Get $get) {
                            return $get('type') !== 'video';
                        })
                        ->dehydrated(false),

                    // Secondary PIC for Video
                    Forms\Components\Select::make('secondary_user_id')
                        ->label('Secondary PIC')
                        ->options(function () {
                            $tenantId = Auth::user()->tenant_id;
                            return User::where('tenant_id', $tenantId)->pluck('name', 'id');
                        })
                        ->afterStateHydrated(function (Forms\Components\Select $component, ?Model $record) {
                            if (! $record) return;
                            if ($record->type !== 'video') return;
                            $pics = $record->users;
                            if ($pics->count() > 1) {
                                // Get the one that is NOT the primary user_id
                                $secondary = $pics->firstWhere('id', '!==', $record->user_id);
                                if ($secondary) {
                                    $component->state($secondary->id);
                                }
                            }
                        })
                        ->hidden(function (Get $get) {
                            return $get('type') !== 'video' || ! $get('is_2_pics');
                        })
                        ->required(fn (Get $get) => $get('type') === 'video' && $get('is_2_pics'))
                        ->dehydrated(false)
                        ->native(false),

                    // Multiple Staff Members for Other Types
                    Forms\Components\Select::make('users_multi')
                        ->label('Staff Members (PICs)')
                        ->multiple()
                        ->relationship('users', 'name', function ($query) {
                            $tenantId = Auth::user()->tenant_id;
                            return $query->where('users.tenant_id', $tenantId);
                        })
                        ->preload()
                        ->searchable()
                        ->default(fn () => [Auth::id()])
                        ->saveRelationshipsUsing(function (Model $record, $state) {
                            $count = is_array($state) ? count($state) : 0;
                            $percentage = $count > 0 ? 100 / $count : 100;
                            
                            $syncData = [];
                            if (is_array($state)) {
                                foreach ($state as $userId) {
                                    $syncData[$userId] = [
                                        'tenant_id' => $record->tenant_id,
                                        'split_percentage' => $percentage,
                                    ];
                                }
                            }
                            $record->users()->sync($syncData);
                        })
                        ->hidden(function (Get $get) {
                            // Hidden for design AND video
                            return in_array($get('type'), ['design', 'video']);
                        })
                        ->required(fn (Get $get) => !in_array($get('type'), ['design', 'video']))
                        ->native(false),
                ]),

            // ── DESIGN & VIDEO ──────────────────────────────────────
            Forms\Components\Section::make(fn (Get $get) => $get('type') === 'video' ? 'Video Entry' : 'Design Entry')
                ->description(fn (Get $get) => $get('type') === 'video' ? '1 Video counts as 2x Design.' : CommissionResource::designHintFromSettings())
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Project Name')
                        ->placeholder('e.g. Company ABC Banner Set')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('quantity')
                        ->label(fn (Get $get) => $get('type') === 'video' ? 'Number of Videos' : 'Number of Designs')
                        ->numeric()
                        ->minValue(1)
                        ->default(1)
                        ->required()
                        ->suffix(fn (Get $get) => $get('type') === 'video' ? 'videos' : 'pcs')
                        ->helperText('Each entry accumulates to your monthly total — bonus tier is applied on the total.'),
                ])
                ->visible(fn (Get $get) => in_array($get('type'), ['design', 'video'])),

            // ── ADS MANAGEMENT ──────────────────────────────────────
            Forms\Components\Section::make('Ads Management Entry')
                ->description(fn () => 'Fee per client is fixed at RM ' . number_format((float) CommissionResource::getSettings()->ads_fee, 2) . '/month.')
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
                ->description(fn () => 'Commission rate: ' . number_format((float) CommissionResource::getSettings()->sales_rate, 2) . '% of package value.')
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
                            $rate = (float) CommissionResource::getSettings()->sales_rate;
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
}
