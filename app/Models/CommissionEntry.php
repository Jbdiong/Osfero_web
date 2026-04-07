<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissionEntry extends Model
{
    use HasFactory;

    protected $table = 'commission_entries';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'type',
        'entry_date',
        'year',
        'month',
        'name',
        'quantity',
        'package_value',
        'remarks',
        'is_approved',
        'approved_by',
        'approved_at',
        'is_rejected',
        'rejection_reason',
        'rejected_by',
        'rejected_at',
    ];

    protected $casts = [
        'year'          => 'integer',
        'month'         => 'integer',
        'entry_date'    => 'date',
        'quantity'      => 'integer',
        'package_value' => 'decimal:2',
        'is_approved'   => 'boolean',
        'approved_at'   => 'datetime',
        'is_rejected'   => 'boolean',
        'rejected_at'   => 'datetime',
    ];

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getMonthNameAttribute(): string
    {
        return \Carbon\Carbon::create()->month($this->month)->format('F');
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
