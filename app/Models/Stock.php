<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Stock extends Model
{
    use HasFactory;

    protected $fillable = [
        'variant_id',
        'location_id',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
    ];

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ItemVariant::class, 'variant_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
