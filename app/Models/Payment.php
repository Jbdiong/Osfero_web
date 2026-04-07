<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'Amount',
        'currency_id',
        'status_id',
        'tenant_id',
    ];

    protected $casts = [
        'Amount' => 'decimal:2',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(Lookup::class, 'status_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function todolists(): HasMany
    {
        return $this->hasMany(Todolist::class);
    }
}
