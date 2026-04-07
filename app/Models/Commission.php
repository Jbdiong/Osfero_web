<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Commission extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'type',
        'year',
        'month',
        'design_quantity',
        'design_rate',
        'ads_amount',
        'sales_package_value',
        'sales_commission_rate',
        'commission_amount',
        'remarks',
    ];

    protected $casts = [
        'design_quantity'       => 'integer',
        'design_rate'           => 'decimal:2',
        'ads_amount'            => 'decimal:2',
        'sales_package_value'   => 'decimal:2',
        'sales_commission_rate' => 'decimal:2',
        'commission_amount'     => 'decimal:2',
        'year'                  => 'integer',
        'month'                 => 'integer',
    ];

    // --- Bonus tiers for Design commissions ---
    //  quantity >= 70 → 20%
    //  quantity >= 50 → 15%
    //  quantity >= 40 → 10%
    //  quantity >= 30 →  5%
    //  otherwise      →  0%
    public static function designBonusRate(int $quantity): float
    {
        if ($quantity >= 70) return 0.20;
        if ($quantity >= 50) return 0.15;
        if ($quantity >= 40) return 0.10;
        if ($quantity >= 30) return 0.05;
        return 0.00;
    }

    // --- Calculate commission for any type ---
    public static function calculateAmount(array $data): float
    {
        $type = $data['type'] ?? '';

        switch ($type) {
            case 'design':
                // Commission = bonus portion ONLY (qty × rate × bonus%)
                // e.g. 30 designs → 30 × 140 × 5% = RM 210
                $qty   = (int) ($data['design_quantity'] ?? 0);
                $rate  = (float) ($data['design_rate'] ?? 140);
                $bonus = self::designBonusRate($qty);
                return round($qty * $rate * $bonus, 2);

            case 'ads_management':
                return round((float) ($data['ads_amount'] ?? 0), 2);

            case 'sales':
                $value      = (float) ($data['sales_package_value'] ?? 0);
                $rate       = (float) ($data['sales_commission_rate'] ?? 10) / 100;
                return round($value * $rate, 2);

            default:
                return 0.00;
        }
    }

    // --- Relationships ---
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function adsClients(): HasMany
    {
        return $this->hasMany(CommissionAdsClient::class);
    }

    // --- Helpers ---
    public function getMonthNameAttribute(): string
    {
        return \Carbon\Carbon::create()->month($this->month)->format('F');
    }

    public function getBonusRateAttribute(): float
    {
        if ($this->type !== 'design') return 0.0;
        return self::designBonusRate((int) $this->design_quantity) * 100;
    }

    public function getTypeFormattedAttribute(): string
    {
        return match ($this->type) {
            'design'         => 'Design',
            'ads_management' => 'Ads Management',
            'sales'          => 'Sales',
            default          => ucfirst($this->type),
        };
    }
}
