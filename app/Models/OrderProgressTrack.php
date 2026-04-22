<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderProgressTrack extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'status_id',
        'title',
        'notes',
        'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'date',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(Lookup::class, 'status_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(OrderProgressAttachment::class, 'order_progress_track_id');
    }
}
