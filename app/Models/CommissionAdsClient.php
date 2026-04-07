<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissionAdsClient extends Model
{
    use HasFactory;

    protected $fillable = [
        'commission_id',
        'tenant_id',
        'client_name',
        'monthly_fee',
    ];

    protected $casts = [
        'monthly_fee' => 'decimal:2',
    ];

    public function commission(): BelongsTo
    {
        return $this->belongsTo(Commission::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
