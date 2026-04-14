<?php

namespace App\Filament\Resources\Commissions;

use App\Filament\Resources\Commissions\Pages;
use App\Filament\Resources\Commissions\Schemas\CommissionForm;
use App\Filament\Resources\Commissions\Tables\CommissionTable;
use App\Models\CommissionEntry;
use App\Models\CommissionSetting;
use App\Models\User;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class CommissionResource extends Resource
{
    protected static ?string $model = CommissionEntry::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'My Entries';

    protected static ?string $navigationGroup = 'Commission';

    protected static ?int $navigationSort = 1;
    
    protected static ?string $recordTitleAttribute = 'name';

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        if (! $user) {
            return parent::getEloquentQuery()->whereRaw('1=0');
        }

        $query = parent::getEloquentQuery()->where('tenant_id', $user->tenant_id);

        if (static::isStaffOnly()) {
            $query = $query->where('user_id', $user->id);
        }

        return $query;
    }

    public static function isStaffOnly(): bool
    {
        $user = Auth::user();
        if (! $user) return true;
        
        $roleState = $user->role;
        if (! $roleState) return true;
        
        $roleName = strtolower($roleState->role ?? '');
        $managerRoles = ['manager', 'admin', 'superadmin', 'super admin', 'tenantadmin', 'tenant admin'];
        
        return ! in_array($roleName, $managerRoles);
    }

    public static function form(Form $form): Form
    {
        return CommissionForm::configure($form);
    }

    public static function table(Table $table): Table
    {
        return CommissionTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCommissions::route('/'),
            'create' => Pages\CreateCommission::route('/create'),
            'edit'   => Pages\EditCommission::route('/{record}/edit'),
        ];
    }

    // ── Helpers ─────────────────────────────────────────────────────

    protected static ?CommissionSetting $cachedSettings = null;

    public static function getSettings(): CommissionSetting
    {
        if (static::$cachedSettings) return static::$cachedSettings;
        $tenantId = Auth::user()?->tenant_id;
        return static::$cachedSettings = CommissionSetting::forTenant($tenantId);
    }

    public static function designHintFromSettings(): string
    {
        $s = self::getSettings();
        return sprintf(
            'RM %.2f/design | Bonus tiers: %s',
            (float) $s->design_rate,
            $s->tierSummary()
        );
    }
}
