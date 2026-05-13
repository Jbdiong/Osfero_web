<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lookup extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'label',
        'lookup_id',
        'parent_id',
        'tenant_id',
        'user_id',
        'color',
        'sort_order',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Lookup::class, 'parent_id');
    }
}
