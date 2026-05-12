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
        'position',
        'phone_number',
        'is_main',
        'priority_id',
        'tenant_id',
    ];

    protected $casts = [
        'is_main' => 'boolean',
    ];

    protected static function booted()
    {
        static::saving(function ($phone) {
            // Sync lead_id if added from Customer side
            if ($phone->customer_id && !$phone->lead_id) {
                $customer = Customer::find($phone->customer_id);
                if ($customer && $customer->lead_id) {
                    $phone->lead_id = $customer->lead_id;
                }
            }
            
            // Sync customer_id if added from Lead side
            if ($phone->lead_id && !$phone->customer_id) {
                $lead = Lead::find($phone->lead_id);
                if ($lead && $lead->customer) {
                    $phone->customer_id = $lead->customer->id;
                }
            }
        });
    }

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
