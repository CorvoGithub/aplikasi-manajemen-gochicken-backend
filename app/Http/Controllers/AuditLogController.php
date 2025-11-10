<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AuditLogController extends Controller
{
    public function getAuditLogs(Request $request)
    {
        try {
            $user = $request->user();
            
            // Only super admin can view audit logs
            if (!$user || $user->role !== 'super admin') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized - Only super admin can view audit logs'
                ], 403);
            }

            $query = AuditLog::with(['user:id_user,nama,email,role,id_cabang', 'user.cabang:id_cabang,nama_cabang'])
                ->orderBy('created_at', 'desc');

            // Apply filters
            if ($request->has('start_date') && $request->start_date) {
                $query->whereDate('created_at', '>=', $request->start_date);
            }

            if ($request->has('end_date') && $request->end_date) {
                $query->whereDate('created_at', '<=', $request->end_date);
            }

            if ($request->has('cabang') && $request->cabang !== 'all') {
                $query->whereHas('user', function ($q) use ($request) {
                    $q->where('id_cabang', $request->cabang);
                });
            }

            $logs = $query->paginate(20);

            return response()->json([
                'status' => 'success',
                'data' => $logs
            ]);

        } catch (\Exception $e) {
            Log::error('Get audit logs failed: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get audit logs: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getAuditLogsByTable(Request $request, $tableName)
    {
        try {
            $user = $request->user();
            
            if (!$user || $user->role !== 'super admin') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized'
                ], 403);
            }

            $logs = AuditLog::with(['user:id_user,nama,email,role,id_cabang', 'user.cabang:id_cabang,nama_cabang'])
                ->where('table_name', $tableName)
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            return response()->json([
                'status' => 'success',
                'data' => $logs
            ]);

        } catch (\Exception $e) {
            Log::error('Get audit logs by table failed: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get audit logs'
            ], 500);
        }
    }

    public function clearAuditLogs(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user || $user->role !== 'super admin') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized - Only super admin can clear audit logs'
                ], 403);
            }

            $deleted = AuditLog::truncate();

            Log::info('Audit logs cleared by user: ' . $user->id_user);

            return response()->json([
                'status' => 'success',
                'message' => 'All audit logs have been cleared successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Clear audit logs failed: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to clear audit logs: ' . $e->getMessage()
            ], 500);
        }
    }

    public function exportAuditLogs(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user || $user->role !== 'super admin') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized'
                ], 403);
            }

            $query = AuditLog::with(['user:id_user,nama,email,role,id_cabang', 'user.cabang:id_cabang,nama_cabang'])
                ->orderBy('created_at', 'desc');

            // Apply filters
            if ($request->has('start_date') && $request->start_date) {
                $query->whereDate('created_at', '>=', $request->start_date);
            }

            if ($request->has('end_date') && $request->end_date) {
                $query->whereDate('created_at', '<=', $request->end_date);
            }

            if ($request->has('table') && $request->table !== 'all') {
                $query->where('table_name', $request->table);
            }

            if ($request->has('action') && $request->action !== 'all') {
                $query->where('action', $request->action);
            }

            if ($request->has('cabang') && $request->cabang !== 'all') {
                $query->whereHas('user', function ($q) use ($request) {
                    $q->where('id_cabang', $request->cabang);
                });
            }

            $logs = $query->get();

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="audit-logs-' . date('Y-m-d') . '.csv"',
            ];

            $callback = function() use ($logs) {
                $file = fopen('php://output', 'w');
                fputcsv($file, ['ID', 'Table', 'Action', 'Record ID', 'User', 'Role', 'Cabang', 'Description', 'Timestamp']);

                foreach ($logs as $log) {
                    $cabangName = $log->user->cabang->nama_cabang ?? '-';
                    fputcsv($file, [
                        $log->id,
                        $log->table_name,
                        $log->action,
                        $log->record_id,
                        $log->user->nama,
                        $log->user_role,
                        $cabangName,
                        $log->description,
                        $log->created_at
                    ]);
                }
                fclose($file);
            };

            return response()->stream($callback, 200, $headers);

        } catch (\Exception $e) {
            Log::error('Export audit logs failed: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to export audit logs'
            ], 500);
        }
    }

    public function getCabangList()
    {
        try {
            $cabang = DB::table('cabang')
                ->select('id_cabang', 'nama_cabang')
                ->orderBy('nama_cabang')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $cabang
            ]);

        } catch (\Exception $e) {
            Log::error('Get cabang list failed: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get cabang list'
            ], 500);
        }
    }
}