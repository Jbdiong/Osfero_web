<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\CommissionEntry;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class User extends Authenticatable implements FilamentUser, HasTenants, HasName
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'last_active_tenant_id',
        'name',
        'email',
        'password',
        'verification_code',
    ];

    protected $appends = [
        'tenant_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    
    public function getTenantIdAttribute()
    {
        return \Filament\Facades\Filament::getTenant()?->id ?? $this->last_active_tenant_id;
    }

    public function setTenantIdAttribute($value)
    {
        $this->attributes['last_active_tenant_id'] = $value;
    }

    public function tenants()
    {
        return $this->belongsToMany(Tenant::class)
            ->withPivot(['role_id', 'display_name'])
            ->withTimestamps();
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'last_active_tenant_id');
    }

    public function roles()
    {
        return $this->belongsToMany(SystemRole::class, 'tenant_user', 'user_id', 'role_id')
            ->withPivot(['tenant_id', 'display_name'])
            ->withTimestamps();
    }

    public function getRoleAttribute()
    {
        $tenantId = Filament::getTenant()?->id ?? $this->last_active_tenant_id;
        
        if (!$tenantId) return null;

        // Try to find the role in the pivot table for the active tenant
        $tenant = $this->tenants()->where('tenants.id', $tenantId)->first();
        
        if ($tenant && $tenant->pivot && $tenant->pivot->role_id) {
            return SystemRole::find($tenant->pivot->role_id);
        }

        return null;
    }

    public function leadPICs()
    {
        return $this->hasMany(LeadPIC::class);
    }

    public function eventPICs()
    {
        return $this->hasMany(EventPIC::class);
    }

    public function todolistPICs()
    {
        return $this->hasMany(TodolistPIC::class);
    }

    public function commissionEntries()
    {
        return $this->hasMany(CommissionEntry::class);
    }

    public function getTenants(Panel $panel): Collection
    {
        return $this->tenants;
    }

    public function canAccessTenant(Model $tenant): bool
    {
        return $this->tenants->contains($tenant);
    }

    public function getFilamentName(): string
    {
        $tenant = Filament::getTenant();
        if ($tenant) {
            $pivot = $this->tenants()->where('tenants.id', $tenant->id)->first()?->pivot;
            return $pivot?->display_name ?: $this->name;
        }
        return $this->name;
    }
}
