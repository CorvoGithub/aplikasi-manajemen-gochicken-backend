<?php
// app/Services/AuditLogService.php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditLogService
{
    public static function log($tableName, $action, $recordId, $oldData = null, $newData = null, $description = null)
    {
        $user = Auth::user();
        
        if (!$user) {
            return;
        }

        $request = app(Request::class);

        // Ensure record_id is always a string
        $recordId = (string)$recordId;

        AuditLog::create([
            'table_name' => $tableName,
            'action' => $action,
            'old_data' => $oldData ? json_encode($oldData) : null,
            'new_data' => $newData ? json_encode($newData) : null,
            'record_id' => $recordId,
            'user_id' => $user->id_user,
            'user_role' => $user->role,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'description' => $description,
        ]);
    }

    public static function logCreate($tableName, $recordId, $newData, $description = null)
    {
        self::log($tableName, 'CREATE', $recordId, null, $newData, $description);
    }

    public static function logUpdate($tableName, $recordId, $oldData, $newData, $description = null)
    {
        self::log($tableName, 'UPDATE', $recordId, $oldData, $newData, $description);
    }

    public static function logDelete($tableName, $recordId, $oldData, $description = null)
    {
        self::log($tableName, 'DELETE', $recordId, $oldData, null, $description);
    }
}