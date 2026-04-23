<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'company',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function commissionEntries(): HasMany
    {
        return $this->hasMany(CommissionEntry::class);
    }

    public function renewals(): HasMany
    {
        return $this->hasMany(Renewal::class);
    }

    public function pics(): \Illuminate\Database\Eloquent\Relations\MorphToMany
    {
        return $this->morphToMany(\App\Models\User::class, 'picable', 'picables', 'picable_id', 'user_id')
            ->withPivot('tenant_id');
    }

    public function phones(): HasMany
    {
        return $this->hasMany(Phone::class);
    }
}
