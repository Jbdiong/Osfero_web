<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderItem extends Model
{
    use HasFactory;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($item) {
            if (empty($item->qty_remaining) || $item->qty_remaining === 1) { // 1 is default from filament form, 0 from DB
                $item->qty_remaining = $item->total_qty_purchased;
            }
        });
    }

    protected $fillable = [
        'tenant_id',
        'order_id',
        'service_type',
        'total_qty_purchased',
        'qty_remaining',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function usageLogs(): HasMany
    {
        return $this->hasMany(UsageLog::class);
    }
}
