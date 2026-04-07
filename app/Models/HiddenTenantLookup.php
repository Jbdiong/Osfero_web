<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HiddenTenantLookup extends Model
{
    use HasFactory;

    protected $fillable = [
        'lookup_id',
        'tenant_id',
    ];

    public function lookup(): BelongsTo
    {
        return $this->belongsTo(Lookup::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
