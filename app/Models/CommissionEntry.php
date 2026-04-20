<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommissionEntry extends Model
{
    use HasFactory;

    protected $table = 'commission_entries';

    protected $fillable = [
        'tenant_id',
        'customer_id',
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
            'video'          => 'Video',
            'ads_management' => 'Ads Management',
            'sales'          => 'Sales',
            default          => ucfirst($this->type),
        };
    }

    public function pics(): HasMany
    {
        return $this->hasMany(CommissionPic::class, 'commission_entry_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'commission_pics', 'commission_entry_id', 'user_id')
                    ->withPivot(['split_percentage', 'tenant_id'])
                    ->withTimestamps();
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
