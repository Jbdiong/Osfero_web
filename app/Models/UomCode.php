<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UomCode extends Model
{
    use HasFactory;

    protected $fillable = ['tenant_id', 'code', 'name', 'precision'];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function itemVariants(): HasMany
    {
        return $this->hasMany(ItemVariant::class, 'uom_id');
    }
}
