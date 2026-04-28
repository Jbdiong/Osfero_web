<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Todolist extends Model
{
    use HasFactory;

    public function usageLogs(): HasMany
    {
        return $this->hasMany(UsageLog::class);
    }

    protected static function booted()
    {
        static::updated(function (Todolist $todolist) {
            // Check if status changed to 'Completed' (ID 51)
            $statusParent = \App\Models\Lookup::where('name', 'Todolist Status')->first();
            $completedStatus = \App\Models\Lookup::where('name', 'Completed')
                ->where('parent_id', $statusParent?->id)
                ->first()?->id ?? 51;

            if ($todolist->isDirty('status_id') && $todolist->status_id == $completedStatus) {
                // Only trigger if this task is linked to an order item
                if (!$todolist->order_item_id) {
                    return;
                }

                // If no UsageLog exists, create one and a commission
                if (!$todolist->usageLogs()->exists()) {
                    $usageLog = \App\Models\UsageLog::create([
                        'tenant_id' => $todolist->tenant_id,
                        'order_item_id' => $todolist->order_item_id,
                        'todolist_id' => $todolist->id,
                        'user_id' => auth()->id() ?? $todolist->pics->first()?->id,
                        'qty_deducted' => $todolist->quantity ?? 1,
                        'date_delivered' => now(),
                        'notes' => 'Generated automatically on status update.',
                        'created_by' => auth()->id(),
                        'updated_by' => auth()->id(),
                    ]);

                    // Deduct from order item remaining
                    if ($todolist->orderItem) {
                        $todolist->orderItem->decrement('qty_remaining', $todolist->quantity ?? 1);
                    }

                    // Create Commission Entry
                    if ($todolist->assigned_type) {
                        $pics = $todolist->pics;
                        $mainPicId = $pics->first()?->id ?? auth()->id();

                        $entry = \App\Models\CommissionEntry::create([
                            'tenant_id' => $todolist->tenant_id,
                            'customer_id' => $todolist->orderItem?->order?->customer_id ?? $todolist->lead?->customer_id,
                            'usage_log_id' => $usageLog->id,
                            'user_id' => $mainPicId,
                            'type' => $todolist->assigned_type,
                            'entry_date' => now(),
                            'year' => now()->year,
                            'month' => now()->month,
                            'name' => $todolist->Title,
                            'quantity' => $todolist->quantity ?? 1,
                            'remarks' => "Auto-Commission from Status Update: " . $todolist->Title,
                            'status' => 'Pending',
                        ]);

                        if ($pics->count() > 0) {
                            $percent = 100 / $pics->count();
                            $syncData = [];
                            foreach ($pics as $pic) {
                                $syncData[$pic->id] = [
                                    'tenant_id' => $todolist->tenant_id,
                                    'split_percentage' => $percent,
                                ];
                            }
                            $entry->users()->sync($syncData);
                        } else {
                            $entry->users()->sync([
                                (auth()->id() ?? 1) => [
                                    'tenant_id' => $todolist->tenant_id,
                                    'split_percentage' => 100,
                                ]
                            ]);
                        }
                    }
                }
            }
        });
    }

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

    public function todolistPICs(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Picable::class, 'picable');
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function pics(): \Illuminate\Database\Eloquent\Relations\MorphToMany
    {
        return $this->morphToMany(User::class, 'picable', 'picables', 'picable_id', 'user_id')
            ->withPivot('tenant_id');
    }
}
