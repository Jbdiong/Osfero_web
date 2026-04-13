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
        'supplier_price',
        'sales_price',
        'min_stock_level',
        'variant_specs',
        'initial_location_id', // Virtual
        'initial_quantity',     // Virtual
    ];

    protected static function booted()
    {
        static::created(function ($variant) {
            if ($variant->initial_location_id && $variant->initial_quantity > 0) {
                \App\Models\Stock::create([
                    'variant_id' => $variant->id,
                    'location_id' => $variant->initial_location_id,
                    'quantity' => $variant->initial_quantity,
                ]);
            }
        });
    }

    protected $casts = [
        'variant_specs' => 'array',
        'min_stock_level' => 'decimal:2',
        'supplier_price' => 'decimal:2',
        'sales_price' => 'decimal:2',
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
