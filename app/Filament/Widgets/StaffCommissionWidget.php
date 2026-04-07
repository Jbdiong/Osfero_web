<?php

namespace App\Filament\Widgets;

use App\Models\CommissionEntry;
use App\Models\CommissionSetting;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class StaffCommissionWidget extends BaseWidget
{
    public static function canView(): bool
    {
        $user = Auth::user();
        if (! $user || ! $user->role) return false;
        $role = strtolower($user->role->role ?? '');
        
        // Superadmins might not need to see this on their personal dashboard
        return ! in_array($role, ['superadmin', 'super admin']);
    }

    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id;
        $settings = CommissionSetting::forTenant($tenantId);

        $month = now()->month;
        $year  = now()->year;

        // Current user's entries for THIS month
        $entries = CommissionEntry::where('tenant_id', $tenantId)
            ->where('user_id', $user->id)
            ->where('month', $month)
            ->where('year', $year)
            ->get();

        // 1. Calculate Design Total + Progress
        $designQty  = (int) $entries->where('type', 'design')->sum('quantity');
        $designComm = $settings->designCommission($designQty);
        $currentPct = $settings->designBonusPercent($designQty);
        $nextTier   = $settings->nextDesignTier($designQty);

        $designProgressLabel = "Current Bonus: " . number_format($currentPct, 0) . "%";
        $designProgressColor = 'primary';
        $designProgressIcon  = 'heroicon-m-paint-brush';

        if ($nextTier) {
            $needed = $nextTier['min_qty'] - $designQty;
            $designProgressLabel = "{$needed} more designs to hit " . number_format((float)$nextTier['bonus_percent'], 0) . "% bonus!";
            $designProgressColor = 'warning';
        } elseif ($currentPct > 0) {
            $designProgressLabel = "Max bonus tier (" . number_format($currentPct, 0) . "%) achieved! 🚀";
            $designProgressColor = 'success';
        }

        // 2. Ads
        $adsClients = $entries->where('type', 'ads_management')->count();
        $adsComm    = $adsClients * $settings->adsCommissionPerClient();

        // 3. Sales
        $salesValue = (float) $entries->where('type', 'sales')->sum('package_value');
        $salesComm  = $settings->salesCommission($salesValue);

        $grandTotal = $designComm + $adsComm + $salesComm;

        return [
            Stat::make('Total Commissions (' . now()->format('F') . ')', 'RM ' . number_format($grandTotal, 2))
                ->description('Accumulated from Design, Ads, and Sales.')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Design Progress', $designQty . ' Designs')
                ->description($designProgressLabel)
                ->descriptionIcon($designProgressIcon)
                ->color($designProgressColor),

            Stat::make('Ads & Sales', 'RM ' . number_format($adsComm + $salesComm, 2))
                ->description($adsClients . ' Ads clients | RM ' . number_format($salesValue, 2) . ' Sales')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('info'),
        ];
    }
}
