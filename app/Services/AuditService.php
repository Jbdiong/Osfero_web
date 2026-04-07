<?php

namespace App\Services;

use App\Models\Audit;
use App\Models\Lookup;
use Illuminate\Support\Facades\Auth;

class AuditService
{
    /**
     * Get audit type lookup ID by name
     */
    protected function getAuditTypeId(string $auditTypeName): ?int
    {
        // Get the "Audit Type" parent lookup
        $auditTypeParent = Lookup::whereNull('tenant_id')
            ->whereNull('parent_id')
            ->where('name', 'Audit Type')
            ->first();

        if (!$auditTypeParent) {
            return null;
        }

        // Get the specific audit type lookup
        $auditType = Lookup::whereNull('tenant_id')
            ->where('parent_id', $auditTypeParent->id)
            ->where('name', $auditTypeName)
            ->first();

        return $auditType ? $auditType->id : null;
    }

    /**
     * Log an audit entry
     *
     * @param string $tableName The name of the table
     * @param int $recordId The ID of the record
     * @param string $auditTypeName One of: Login, LogOut, Create, Update, Completed, Delete
     * @param string|null $columnName The column that was changed (for updates)
     * @param mixed $oldValue The old value (for updates)
     * @param mixed $newValue The new value (for updates/creates)
     * @param int|null $tenantId The tenant ID (if null, will use authenticated user's tenant_id)
     * @param int|null $performedBy The user ID who performed the action (if null, will use authenticated user)
     * @return Audit
     */
    public function log(
        string $tableName,
        int $recordId,
        string $auditTypeName,
        ?string $columnName = null,
        $oldValue = null,
        $newValue = null,
        ?int $tenantId = null,
        ?int $performedBy = null
    ): Audit {
        $user = Auth::user();
        
        // Get tenant ID
        if ($tenantId === null) {
            $tenantId = $user?->tenant_id;
        }

        // Get user ID who performed the action
        if ($performedBy === null) {
            $performedBy = $user?->id;
        }

        // Get audit type lookup ID
        $auditTypeId = $this->getAuditTypeId($auditTypeName);

        // Convert values to string for storage
        $oldValueStr = $oldValue !== null ? (is_string($oldValue) ? $oldValue : json_encode($oldValue)) : null;
        $newValueStr = $newValue !== null ? (is_string($newValue) ? $newValue : json_encode($newValue)) : null;

        return Audit::create([
            'table_name' => $tableName,
            'record_id' => $recordId,
            'column_name' => $columnName,
            'old_value' => $oldValueStr,
            'new_value' => $newValueStr,
            'audit_type' => $auditTypeName, // Store the name for easy reference
            'performed_by' => $performedBy ? (string) $performedBy : null,
            'tenant_id' => $tenantId,
            'created_at' => now(),
        ]);
    }

    /**
     * Log a Create action
     */
    public function logCreate(string $tableName, int $recordId, $newValue = null, ?int $tenantId = null, ?int $performedBy = null): Audit
    {
        return $this->log($tableName, $recordId, 'Create', null, null, $newValue, $tenantId, $performedBy);
    }

    /**
     * Log an Update action
     */
    public function logUpdate(
        string $tableName,
        int $recordId,
        string $columnName,
        $oldValue,
        $newValue,
        ?int $tenantId = null,
        ?int $performedBy = null
    ): Audit {
        return $this->log($tableName, $recordId, 'Update', $columnName, $oldValue, $newValue, $tenantId, $performedBy);
    }

    /**
     * Log a Delete action
     */
    public function logDelete(string $tableName, int $recordId, $oldValue = null, ?int $tenantId = null, ?int $performedBy = null): Audit
    {
        return $this->log($tableName, $recordId, 'Delete', null, $oldValue, null, $tenantId, $performedBy);
    }

    /**
     * Log a Login action
     */
    public function logLogin(int $userId, ?int $tenantId = null): Audit
    {
        return $this->log('users', $userId, 'Login', null, null, null, $tenantId, $userId);
    }

    /**
     * Log a LogOut action
     */
    public function logLogout(int $userId, ?int $tenantId = null): Audit
    {
        return $this->log('users', $userId, 'LogOut', null, null, null, $tenantId, $userId);
    }

    /**
     * Log a Completed action
     */
    public function logCompleted(string $tableName, int $recordId, ?int $tenantId = null, ?int $performedBy = null): Audit
    {
        return $this->log($tableName, $recordId, 'Completed', null, null, null, $tenantId, $performedBy);
    }
}