<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TodolistPIC extends Model
{
    use HasFactory;

    protected $fillable = [
        'todolist_id',
        'user_id',
        'tenant_id',
    ];

    public function todolist(): BelongsTo
    {
        return $this->belongsTo(Todolist::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
