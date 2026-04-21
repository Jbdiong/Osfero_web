<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UsageLog extends Model
{
    use HasFactory;

    protected static function boot()
    {
        parent::boot();

        static::updating(function ($log) {
            // Did they re-assign the log to a different task entirely?
            if ($log->isDirty('order_item_id')) {
                $oldItemId = $log->getOriginal('order_item_id');
                $newItemId = $log->order_item_id;
                
                $oldQty = $log->getOriginal('qty_deducted');
                $newQty = $log->qty_deducted;

                // Refund the previous item
                $oldItem = OrderItem::find($oldItemId);
                if ($oldItem) {
                    $oldItem->qty_remaining += $oldQty;
                    $oldItem->save();
                }

                // Deduct from the new item
                $newItem = OrderItem::find($newItemId);
                if ($newItem) {
                    $newItem->qty_remaining -= $newQty;
                    $newItem->save();
                }
                
                return; // Stop here since we already handled the qty deduction logic
            }

            // Case 2: Only changed qty_deducted (but kept the same task)
            if ($log->isDirty('qty_deducted')) {
                $difference = $log->qty_deducted - $log->getOriginal('qty_deducted');
                
                $item = $log->orderItem;
                if ($item) {
                    $item->qty_remaining -= $difference;
                    $item->save();
                }
            }
        });

        static::deleting(function ($log) {
            // Restore the quantity when a log is completely deleted
            $item = $log->orderItem;
            if ($item) {
                $item->qty_remaining += $log->qty_deducted;
                $item->save();
            }
        });
    }

    protected $fillable = [
        'tenant_id',
        'order_item_id',
        'todolist_id',
        'user_id',
        'qty_deducted',
        'date_delivered',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date_delivered' => 'date',
    ];

    public function todolist(): BelongsTo
    {
        return $this->belongsTo(Todolist::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function commissionEntry(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(CommissionEntry::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(UsageAttachment::class);
    }
}
