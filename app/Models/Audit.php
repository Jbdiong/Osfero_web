<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Audit extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'table_name',
        'record_id',
        'column_name',
        'old_value',
        'new_value',
        'audit_type',
        'performed_by',
        'tenant_id',
        'created_at',
    ];

    public function performedByUser()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
