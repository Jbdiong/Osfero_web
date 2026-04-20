<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsageAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'usage_log_id',
        'file_name',
        'file_url',
        'upload_date',
    ];

    protected $casts = [
        'upload_date' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function usageLog(): BelongsTo
    {
        return $this->belongsTo(UsageLog::class);
    }
}
