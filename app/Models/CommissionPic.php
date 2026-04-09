<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommissionPic extends Model
{
    protected $fillable = [
        'tenant_id',
        'commission_entry_id',
        'user_id',
        'split_percentage',
    ];

    protected $casts = [
        'split_percentage' => 'decimal:2',
    ];

    public function commissionEntry()
    {
        return $this->belongsTo(CommissionEntry::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
