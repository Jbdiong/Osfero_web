<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Phone extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'customer_id',
        'name',
        'phone_number',
        'is_main',
        'priority_id',
        'tenant_id',
    ];

    protected $casts = [
        'is_main' => 'boolean',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function priority(): BelongsTo
    {
        return $this->belongsTo(Lookup::class, 'priority_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
