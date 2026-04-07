<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ItemVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_id',
        'sku',
        'barcode',
        'uom_id',
        'min_stock_level',
        'variant_specs',
    ];

    protected $casts = [
        'variant_specs' => 'array',
        'min_stock_level' => 'decimal:2',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(UomCode::class, 'uom_id');
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class, 'variant_id');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'variant_id');
    }
}
