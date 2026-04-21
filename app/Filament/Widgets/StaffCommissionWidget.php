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

        // Current user's entries for THIS month, based on PIC status and only APPROVED
        $entries = CommissionEntry::where('tenant_id', $tenantId)
            ->where('month', $month)
            ->where('year', $year)
            ->where('is_approved', true) // Only approved commissions can be counted
            ->whereHas('pics', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->with(['pics' => function ($query) use ($user) {
                $query->where('user_id', $user->id);
            }])
            ->get();

        $designQty         = 0.0;
        $adsClientsPortion = 0.0;
        $salesValuePortion = 0.0;

        foreach ($entries as $entry) {
            $myPic = $entry->pics->first();
            $splitFactor = $myPic ? ((float) $myPic->split_percentage / 100) : 1.0;

            if ($entry->type === 'design' || $entry->type === 'video') {
                // Fix: Video counts as 2x Design according to form description
                $multiplier = $entry->type === 'video' ? 2.0 : 1.0;
                $portion    = (float) $entry->quantity * $multiplier * $splitFactor;
                
                $designQty += $portion;
            } elseif ($entry->type === 'ads_management') {
                $adsClientsPortion += (1.0 * $splitFactor);
            } elseif ($entry->type === 'sales') {
                $salesValuePortion += ((float) $entry->package_value * $splitFactor);
            }
        }

        // 1. Calculate Design Total + Progress
        $designQtyInt = (int) floor($designQty);
        $designComm   = $settings->designCommission($designQtyInt);
        $currentPct   = $settings->designBonusPercent($designQtyInt);
        $nextTier     = $settings->nextDesignTier($designQtyInt);

        $designProgressLabel = "Current Bonus: " . number_format($currentPct, 0) . "%";
        $designProgressColor = 'primary';
        $designProgressIcon  = 'heroicon-m-paint-brush';

        if ($nextTier) {
            $needed = $nextTier['min_qty'] - $designQtyInt;
            $designProgressLabel = "{$needed} more designs to hit " . number_format((float)$nextTier['bonus_percent'], 0) . "% bonus!";
            $designProgressColor = 'warning';
        } elseif ($currentPct > 0) {
            $designProgressLabel = "Max bonus tier (" . number_format($currentPct, 0) . "%) achieved! \ud83d\ude80";
            $designProgressColor = 'success';
        }

        // 2. Ads
        $adsComm = $adsClientsPortion * $settings->adsCommissionPerClient();

        // 3. Sales
        $salesComm = $settings->salesCommission($salesValuePortion);

        $grandTotal = $designComm + $adsComm + $salesComm;

        return [
            Stat::make('Total Commissions (' . now()->format('F') . ')', 'RM ' . number_format($grandTotal, 2))
                ->description('Accumulated from Design, Ads, and Sales.')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Design Progress', $designQtyInt . ' Designs')
                ->description($designProgressLabel)
                ->descriptionIcon($designProgressIcon)
                ->color($designProgressColor),

            Stat::make('Ads & Sales', 'RM ' . number_format($adsComm + $salesComm, 2))
                ->description(number_format($adsClientsPortion, 1) . ' Ads clients | RM ' . number_format($salesValuePortion, 2) . ' Sales')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('info'),
        ];
    }
}





