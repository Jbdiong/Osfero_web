<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'title',
        'description',
        'start_time',
        'end_time',
        'status_id',
        'tenant_id',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(Lookup::class, 'status_id');
    }

    public function eventPICs(): HasMany
    {
        return $this->hasMany(EventPIC::class);
    }
}
