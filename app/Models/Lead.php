<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Lead extends Model
{
    use HasFactory;

    protected $fillable = [
        'Shop_Name',
        'Industry',
        'last_modified',
        'Source',
        'Language',
        'City',
        'State',
        'Country',
        'address_1',
        'address_2',
        'address_3',
        'relevant',
        'Irrelevant_reason',
        'remarks',
        'status_id',
        'tenant_id',
    ];

    protected $casts = [
        'last_modified' => 'datetime',
        'relevant' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(Lookup::class, 'status_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function todolists(): HasMany
    {
        return $this->hasMany(Todolist::class);
    }

    public function phones(): HasMany
    {
        return $this->hasMany(Phone::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function renewals(): HasMany
    {
        return $this->hasMany(Renewal::class);
    }

    public function leadPICs(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Picable::class, 'picable');
    }

    public function marketer(): HasOneThrough
    {
        return $this->hasOneThrough(
            User::class,
            Picable::class,
            'picable_id', // Foreign key on picables table
            'id',      // Foreign key on users table
            'id',      // Local key on leads table
            'user_id'  // Local key on picables table
        )->where('picables.picable_type', self::class);
    }

    public function customer(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Customer::class);
    }
}
