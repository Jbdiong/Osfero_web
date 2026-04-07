<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissionSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'design_rate',
        'design_tiers',  // JSON: [{min_qty, max_qty, bonus_percent}, ...]
        'sales_rate',
        'ads_fee',
    ];

    protected $casts = [
        'design_rate'  => 'decimal:2',
        'design_tiers' => 'array',
        'sales_rate'   => 'decimal:2',
        'ads_fee'      => 'decimal:2',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get or create the settings for a given tenant with sensible defaults.
     */
    public static function forTenant(int $tenantId): static
    {
        return static::firstOrCreate(
            ['tenant_id' => $tenantId],
            [
                'design_rate'  => 140,
                'design_tiers' => [
                    ['min_qty' => 30, 'max_qty' => 39,   'bonus_percent' => 5],
                    ['min_qty' => 40, 'max_qty' => 49,   'bonus_percent' => 10],
                    ['min_qty' => 50, 'max_qty' => 69,   'bonus_percent' => 15],
                    ['min_qty' => 70, 'max_qty' => null,  'bonus_percent' => 20],
                ],
                'sales_rate'   => 10,
                'ads_fee'      => 149,
            ]
        );
    }

    /**
     * Return the applicable bonus percentage for a given design quantity.
     * Checks each tier: matches when min_qty <= qty <= max_qty (null max = no upper bound).
     * Returns the HIGHEST matching bonus if tiers overlap.
     */
    public function designBonusPercent(int $qty): float
    {
        $tiers  = $this->design_tiers ?? [];
        $best   = 0.0;

        foreach ($tiers as $tier) {
            $min = (int)   ($tier['min_qty']     ?? 0);
            $max = isset($tier['max_qty']) && $tier['max_qty'] !== null && $tier['max_qty'] !== ''
                ? (int) $tier['max_qty']
                : PHP_INT_MAX;

            if ($qty >= $min && $qty <= $max) {
                $pct  = (float) ($tier['bonus_percent'] ?? 0);
                if ($pct > $best) {
                    $best = $pct;
                }
            }
        }

        return $best;
    }

    /**
     * Design commission = total_qty × design_rate × (bonus% / 100)
     */
    public function designCommission(int $totalQty): float
    {
        $bonus = $this->designBonusPercent($totalQty);
        return round($totalQty * (float) $this->design_rate * ($bonus / 100), 2);
    }

    /**
     * Sales commission = package_value × (sales_rate / 100)
     */
    public function salesCommission(float $packageValue): float
    {
        return round($packageValue * ((float) $this->sales_rate / 100), 2);
    }

    /**
     * Ads commission per client = ads_fee
     */
    public function adsCommissionPerClient(): float
    {
        return (float) $this->ads_fee;
    }

    /**
     * Human-readable tier list for hints.
     * e.g. "30–39 → 5% | 40–49 → 10% | 70+ → 20%"
     */
    public function tierSummary(): string
    {
        $tiers = $this->design_tiers ?? [];
        if (empty($tiers)) return 'No bonus tiers configured';

        return collect($tiers)
            ->sortBy('min_qty')
            ->map(function ($tier) {
                $min   = $tier['min_qty'];
                $max   = isset($tier['max_qty']) && $tier['max_qty'] !== null && $tier['max_qty'] !== ''
                    ? $tier['max_qty']
                    : null;
                $pct   = $tier['bonus_percent'];
                $range = $max ? "{$min}–{$max}" : "{$min}+";
                return "{$range} → {$pct}%";
            })
            ->implode(' | ');
    }

    /**
     * Find the next available tier higher than the current qty.
     * Returns ['min_qty' => X, 'bonus_percent' => Y] or null.
     */
    public function nextDesignTier(int $currentQty): ?array
    {
        $tiers = collect($this->design_tiers ?? [])
            ->where('min_qty', '>', $currentQty)
            ->sortBy('min_qty')
            ->first();

        return $tiers ?: null;
    }
}
