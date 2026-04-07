<?php

namespace App\Filament\Pages;

use App\Models\CommissionEntry;
use App\Models\CommissionSetting;
use App\Models\User;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class CommissionSummary extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon  = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Commission Summary';
    protected static ?string $navigationGroup = 'Commission';
    protected static ?int    $navigationSort  = 2;
    protected static string  $view            = 'filament.pages.commission-summary';

    public static function canAccess(): bool
    {
        $user = Auth::user();
        if (! $user || ! $user->role) return false;
        $role = strtolower($user->role->role ?? '');
        return in_array($role, ['manager', 'admin', 'superadmin', 'super admin', 'tenantadmin', 'tenant admin']);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public ?array $filterData = [];

    public function mount(): void
    {
        $this->form->fill([
            'filterYear'  => now()->year,
            'filterMonth' => now()->month,
            'filterUser'  => null,
        ]);
    }

    protected function getFormSchema(): array
    {
        return [
            \Filament\Forms\Components\Section::make()
                ->schema([
                    \Filament\Forms\Components\Grid::make(3)
                        ->schema([
                            \Filament\Forms\Components\Select::make('filterYear')
                                ->label('Year')
                                ->options(fn () => $this->getYearOptions())
                                ->placeholder('All Years')
                                ->live()
                                ->native(true),

                            \Filament\Forms\Components\Select::make('filterMonth')
                                ->label('Month')
                                ->options(fn () => $this->getMonthOptions())
                                ->placeholder('All Months')
                                ->live()
                                ->native(true),

                            \Filament\Forms\Components\Select::make('filterUser')
                                ->label('Staff')
                                ->options(fn () => $this->getUserOptions())
                                ->placeholder('All Staff')
                                ->live()
                                ->native(true),
                        ]),
                ])
                ->compact(),
        ];
    }

    public function form(\Filament\Forms\Form $form): \Filament\Forms\Form
    {
        return $form
            ->schema($this->getFormSchema())
            ->statePath('filterData');
    }

    public function getSummaryData(): array
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id;
        $settings = CommissionSetting::forTenant($tenantId);

        $state = $this->filterData;
        $fYear  = $state['filterYear'] ?? null;
        $fMonth = $state['filterMonth'] ?? null;
        $fUser  = $state['filterUser'] ?? null;

        // Get all staff in this tenant (show all, even if no entries)
        $staffQuery = User::where('tenant_id', $tenantId);
        if ($fUser) {
            $staffQuery->where('id', $fUser);
        }
        $staffList = $staffQuery->get();

        $rows = $staffList->map(function (User $staff) use ($tenantId, $settings, $fYear, $fMonth) {
            $baseQuery = CommissionEntry::where('tenant_id', $tenantId)
                ->where('user_id', $staff->id);

            if ($fYear) {
                $baseQuery->where('year', $fYear);
            }
            if ($fMonth) {
                $baseQuery->where('month', $fMonth);
            }

            $entries = $baseQuery->get();
            $approved = $entries->where('is_approved', true);

            if ($entries->isEmpty()) {
                return [
                    'name'   => $staff->name,
                    'design' => 0,
                    'ads'    => 0,
                    'sales'  => 0,
                    'total'  => 0,
                    'design_qty'   => 0,
                    'ads_clients'  => 0,
                    'sales_value'  => 0,
                    'pendingCount' => 0,
                    'has_entries'  => false,
                ];
            }

            // -- Design: sum approved quantities → apply bonus on total
            $designQtyApproved = (int) $approved->where('type', 'design')->sum('quantity');
            $designComm = $settings->designCommission($designQtyApproved);

            // -- Ads: each logged approved client = one ads_fee
            $adsClientsApproved = $approved->where('type', 'ads_management')->count();
            $adsComm    = $adsClientsApproved * $settings->adsCommissionPerClient();

            // -- Sales: sum approved package values × rate
            $salesValueApproved = (float) $approved->where('type', 'sales')->sum('package_value');
            $salesComm  = $settings->salesCommission($salesValueApproved);

            $total = $designComm + $adsComm + $salesComm;
            $pendingCount = $entries->where('is_approved', false)->count();

            return [
                'name'         => $staff->name,
                'design'       => $designComm,
                'ads'          => $adsComm,
                'sales'        => $salesComm,
                'total'        => $total,
                'design_qty'   => $designQtyApproved,
                'ads_clients'  => $adsClientsApproved,
                'sales_value'  => $salesValueApproved,
                'pendingCount' => $pendingCount,
                'has_entries'  => true,
            ];
        })
        ->filter(fn ($r) => $r['total'] > 0 || $r['has_entries'])
        ->sortByDesc('total')
        ->values();

        return [
            'rows'          => $rows,
            'grandTotal'    => $rows->sum('total'),
            'totalDesign'   => $rows->sum('design'),
            'totalAds'      => $rows->sum('ads'),
            'totalSales'    => $rows->sum('sales'),
            'settings'      => $settings,
            'fYear'         => $fYear,
            'fMonth'        => $fMonth,
        ];
    }

    public function getYearOptions(): array
    {
        $year = now()->year;
        return array_combine(range($year - 2, $year + 1), range($year - 2, $year + 1));
    }

    public function getMonthOptions(): array
    {
        return [
            1 => 'January',   2 => 'February',  3 => 'March',
            4 => 'April',     5 => 'May',        6 => 'June',
            7 => 'July',      8 => 'August',     9 => 'September',
            10 => 'October', 11 => 'November',  12 => 'December',
        ];
    }

    public function getUserOptions(): array
    {
        $tenantId = Auth::user()->tenant_id;
        return User::where('tenant_id', $tenantId)->pluck('name', 'id')->toArray();
    }

    public function getSelectedMonthLabel(): string
    {
        $month = $this->filterData['filterMonth'] ?? null;
        if (! $month) return 'All Months';
        return $this->getMonthOptions()[$month] ?? '';
    }
}
