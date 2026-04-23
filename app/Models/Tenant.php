<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use App\Models\CommissionEntry;
use App\Models\CommissionSetting;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'code',
        'code_expiring',
    ];

    protected $casts = [
        'code_expiring' => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $tenant) {
            if (empty($tenant->slug)) {
                $tenant->slug = Str::slug($tenant->name);
            }
        });

        static::updating(function (self $tenant) {
            if ($tenant->isDirty('name') && empty($tenant->slug)) {
                $tenant->slug = Str::slug($tenant->name);
            }
        });
    }

    public function generateInvitationCode()
    {
        $this->code = strtoupper(\Illuminate\Support\Str::random(8));
        $this->code_expiring = now()->addHours(24);
        $this->save();

        return $this->code;
    }

    public static function findByInvitationCode($code)
    {
        return self::where('code', $code)
            ->where('code_expiring', '>', now())
            ->first();
    }

    public function users(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['role_id', 'display_name'])
            ->withTimestamps();
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function todolists(): HasMany
    {
        return $this->hasMany(Todolist::class);
    }

    public function phones(): HasMany
    {
        return $this->hasMany(Phone::class);
    }

    public function renewals(): HasMany
    {
        return $this->hasMany(Renewal::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    public function audits(): HasMany
    {
        return $this->hasMany(Audit::class);
    }

    public function lookups(): HasMany
    {
        return $this->hasMany(Lookup::class);
    }

    public function pics(): HasMany
    {
        return $this->hasMany(Picable::class);
    }

    public function eventPICs(): HasMany
    {
        return $this->hasMany(Picable::class)->where('picable_type', Event::class);
    }

    public function todolistPICs(): HasMany
    {
        return $this->hasMany(Picable::class)->where('picable_type', Todolist::class);
    }

    public function leadPICs(): HasMany
    {
        return $this->hasMany(Picable::class)->where('picable_type', Lead::class);
    }

    public function hiddenTenantLookups(): HasMany
    {
        return $this->hasMany(HiddenTenantLookup::class);
    }

    public function commissionEntries(): HasMany
    {
        return $this->hasMany(CommissionEntry::class);
    }

    public function commissionSetting(): HasOne
    {
        return $this->hasOne(CommissionSetting::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function packages(): HasMany
    {
        return $this->hasMany(Package::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function packageTemplateItems(): HasMany
    {
        return $this->hasMany(PackageTemplateItem::class);
    }

    public function usageLogs(): HasMany
    {
        return $this->hasMany(UsageLog::class);
    }
}
