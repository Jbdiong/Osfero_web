<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Todolist extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'payment_id',
        'Title',
        'Description',
        'start_date',
        'end_date',
        'priority_id',
        'parent_id',
        'status_id',
        'position',
        'tenant_id',
        'order_item_id',
        'quantity',
        'assigned_type',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function priority(): BelongsTo
    {
        return $this->belongsTo(Lookup::class, 'priority_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(Lookup::class, 'status_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Todolist::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Todolist::class, 'parent_id');
    }

    public function todolistPICs(): HasMany
    {
        return $this->hasMany(TodolistPIC::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function pics(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'todolist_p_i_c_s', 'todolist_id', 'user_id')
            ->withPivot('tenant_id');
    }
}
