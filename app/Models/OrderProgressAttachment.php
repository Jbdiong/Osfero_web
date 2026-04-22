<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderProgressAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_progress_track_id',
        'file_path',
        'file_name',
    ];

    public function progressTrack(): BelongsTo
    {
        return $this->belongsTo(OrderProgressTrack::class, 'order_progress_track_id');
    }
}
