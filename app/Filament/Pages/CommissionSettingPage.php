<?php

namespace App\Filament\Pages;

use App\Models\CommissionSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class CommissionSettingPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon    = null;
    protected static ?string $navigationLabel   = 'Commission Settings';
    protected static ?string $navigationGroup   = 'Commission';
    protected static ?int    $navigationSort    = 3;
    protected static string  $view              = 'filament.pages.commission-setting';
    protected static ?string $title             = 'Commission Settings';

    public static function canAccess(): bool
    {
        $user = Auth::user();
        if (! $user || ! $user->role) return false;
        $role = strtolower($user->role->role ?? '');
        return in_array($role, ['tenantadmin', 'tenant admin', 'admin', 'superadmin', 'super admin']);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public ?array $data = [];

    public function mount(): void
    {
        $settings = CommissionSetting::forTenant(Auth::user()->tenant_id);
        $this->form->fill($settings->toArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([

                // ── Design ─────────────────────────────────────────────────
                Forms\Components\Section::make('🎨  Design Commission')
                    ->description('Commission = Total Monthly Designs × Rate × Bonus %')
                    ->schema([

                        Forms\Components\TextInput::make('design_rate')
                            ->label('Rate per Design (RM)')
                            ->numeric()
                            ->minValue(0)
                            ->required()
                            ->prefix('RM')
                            ->columnSpanFull(),

                        Forms\Components\Repeater::make('design_tiers')
                            ->label('Bonus Tiers')
                            ->schema([
                                Forms\Components\Grid::make(3)->schema([
                                    Forms\Components\TextInput::make('min_qty')
                                        ->label('Min Qty')
                                        ->integer()
                                        ->minValue(0)
                                        ->required()
                                        ->suffix('designs'),

                                    Forms\Components\TextInput::make('max_qty')
                                        ->label('Max Qty')
                                        ->integer()
                                        ->minValue(0)
                                        ->nullable()
                                        ->placeholder('No limit (∞)')
                                        ->suffix('designs'),

                                    Forms\Components\TextInput::make('bonus_percent')
                                        ->label('Bonus %')
                                        ->numeric()
                                        ->minValue(0)
                                        ->maxValue(100)
                                        ->required()
                                        ->suffix('%'),
                                ]),
                            ])
                            ->addActionLabel('+ Add Tier')
                            ->reorderable()
                            ->collapsible()
                            ->defaultItems(0)
                            ->itemLabel(function (array $state): string {
                                $min = $state['min_qty'] ?? '?';
                                $max = (isset($state['max_qty']) && $state['max_qty'] !== null && $state['max_qty'] !== '')
                                    ? $state['max_qty']
                                    : '∞';
                                $pct = $state['bonus_percent'] ?? '?';
                                return "{$min} – {$max} designs → {$pct}% bonus";
                            })
                            ->columnSpanFull(),
                    ]),

                // ── Ads Management ──────────────────────────────────────────
                Forms\Components\Section::make('📢  Ads Management Commission')
                    ->description('Each logged client entry counts as one fee unit per month.')
                    ->schema([
                        Forms\Components\TextInput::make('ads_fee')
                            ->label('Fee per Client per Month (RM)')
                            ->numeric()
                            ->minValue(0)
                            ->required()
                            ->prefix('RM'),
                    ]),

                // ── Sales ───────────────────────────────────────────────────
                Forms\Components\Section::make('💼  Sales Commission')
                    ->description('Commission is calculated on each logged package value.')
                    ->schema([
                        Forms\Components\TextInput::make('sales_rate')
                            ->label('Commission Rate (%)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->required()
                            ->suffix('%'),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        // Sanitize tiers: cast types and remove empty max_qty
        $data['design_tiers'] = collect($data['design_tiers'] ?? [])
            ->map(fn ($tier) => [
                'min_qty'       => (int) ($tier['min_qty'] ?? 0),
                'max_qty'       => (isset($tier['max_qty']) && $tier['max_qty'] !== null && $tier['max_qty'] !== '')
                    ? (int) $tier['max_qty']
                    : null,
                'bonus_percent' => (float) ($tier['bonus_percent'] ?? 0),
            ])
            ->sortBy('min_qty')
            ->values()
            ->all();

        CommissionSetting::updateOrCreate(
            ['tenant_id' => Auth::user()->tenant_id],
            $data
        );

        Notification::make()
            ->title('Commission settings saved!')
            ->success()
            ->send();
    }
}
