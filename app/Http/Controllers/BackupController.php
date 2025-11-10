<?php
// app/Http/Controllers/BackupController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\BackupHistory;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class BackupController extends Controller
{
    public function createBackup(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized'
                ], 401);
            }

            // Create backup history record first
            $backupHistory = BackupHistory::create([
                'filename' => 'gochicken_backup_' . now()->format('Y-m-d_H-i-s') . '.sql',
                'file_type' => 'sql',
                'file_size' => 0,
                'backup_type' => 'manual',
                'user_id' => $user->id_user,
                'user_role' => $user->role,
                'success' => false,
            ]);

            $database = config('database.connections.mysql.database');
            $username = config('database.connections.mysql.username');
            $password = config('database.connections.mysql.password');
            $host = config('database.connections.mysql.host');
            
            $timestamp = now()->format('Y-m-d_H-i-s');
            $filename = "gochicken_backup_{$timestamp}.sql";
            $filePath = storage_path("app/backups/{$filename}");
            
            // Ensure backups directory exists
            if (!file_exists(storage_path('app/backups'))) {
                mkdir(storage_path('app/backups'), 0755, true);
            }
            
            // Create mysqldump command (handle Windows)
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                // Windows command
                $command = "mysqldump --user={$username} --password={$password} --host={$host} {$database} > \"{$filePath}\"";
            } else {
                // Linux/Mac command
                $command = "mysqldump --user={$username} --password={$password} --host={$host} {$database} > {$filePath}";
            }
            
            // Execute backup command
            $process = Process::fromShellCommandline($command);
            $process->setTimeout(300); // 5 minutes timeout
            $process->run();
            
            // Check if process was successful
            if (!$process->isSuccessful()) {
                Log::error('Backup process failed: ' . $process->getErrorOutput());
                
                // Fallback to manual SQL generation
                return $this->createManualSqlBackup($request, $backupHistory);
            }
            
            // Check if file was created and has content
            if (!file_exists($filePath) || filesize($filePath) === 0) {
                Log::error('Backup file is empty or not created');
                return $this->createManualSqlBackup($request, $backupHistory);
            }
            
            $fileSize = filesize($filePath);
            
            // Update backup history with success
            $backupHistory->update([
                'filename' => $filename,
                'file_size' => $fileSize,
                'file_path' => $filePath,
                'success' => true
            ]);

            Log::info('SQL backup created successfully', [
                'filename' => $filename,
                'size' => $fileSize
            ]);

            // Return file as download
            return response()->download($filePath, $filename, [
                'Content-Type' => 'application/sql',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ])->deleteFileAfterSend(true);
            
        } catch (\Exception $e) {
            Log::error('Backup failed: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Backup failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Fallback method to generate SQL manually if mysqldump fails
     */
    private function createManualSqlBackup(Request $request, BackupHistory $backupHistory)
    {
        try {
            Log::info('Using manual SQL backup method');
            
            $tables = [
                'users', 'cabang', 'karyawan', 'produk', 'bahan_baku', 
                'transaksi', 'detail_transaksi', 'pengeluaran', 'jenis_pengeluaran',
                'stok_cabang', 'bahan_baku_harian', 'detail_pengeluaran'
            ];
            
            $sqlContent = "-- GoChicken Database Backup\n";
            $sqlContent .= "-- Generated: " . now()->toISOString() . "\n";
            $sqlContent .= "-- Database: " . config('database.connections.mysql.database') . "\n\n";
            
            foreach ($tables as $table) {
                try {
                    // Get table structure
                    $structure = DB::select("SHOW CREATE TABLE `{$table}`");
                    $sqlContent .= "--\n-- Table structure for table `{$table}`\n--\n";
                    $sqlContent .= "DROP TABLE IF EXISTS `{$table}`;\n";
                    $sqlContent .= $structure[0]->{'Create Table'} . ";\n\n";
                    
                    // Get table data
                    $data = DB::table($table)->get();
                    if ($data->count() > 0) {
                        $sqlContent .= "--\n-- Dumping data for table `{$table}`\n--\n";
                        
                        foreach ($data as $row) {
                            $columns = [];
                            $values = [];
                            
                            foreach ((array)$row as $column => $value) {
                                $columns[] = "`{$column}`";
                                $values[] = is_null($value) ? 'NULL' : "'" . addslashes($value) . "'";
                            }
                            
                            $sqlContent .= "INSERT INTO `{$table}` (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $values) . ");\n";
                        }
                        $sqlContent .= "\n";
                    }
                    
                } catch (\Exception $e) {
                    Log::error("Failed to backup table {$table}: " . $e->getMessage());
                    $sqlContent .= "-- Error backing up table {$table}: " . $e->getMessage() . "\n";
                }
            }
            
            $timestamp = now()->format('Y-m-d_H-i-s');
            $filename = "gochicken_backup_manual_{$timestamp}.sql";
            $filePath = storage_path("app/backups/{$filename}");
            
            // Ensure backups directory exists
            if (!file_exists(storage_path('app/backups'))) {
                mkdir(storage_path('app/backups'), 0755, true);
            }
            
            file_put_contents($filePath, $sqlContent);
            $fileSize = filesize($filePath);
            
            // Update backup history
            $backupHistory->update([
                'filename' => $filename,
                'file_size' => $fileSize,
                'file_path' => $filePath,
                'success' => true
            ]);

            Log::info('Manual SQL backup completed', [
                'filename' => $filename,
                'size' => $fileSize
            ]);

            return response()->download($filePath, $filename, [
                'Content-Type' => 'application/sql',
                'Content-Dposition' => 'attachment; filename="' . $filename . '"',
            ])->deleteFileAfterSend(true);
            
        } catch (\Exception $e) {
            Log::error('Manual SQL backup also failed: ' . $e->getMessage());
            
            $backupHistory->update([
                'success' => false,
                'error_message' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    public function getBackupHistory(Request $request)
    {
        try {
            Log::info('Backup history endpoint hit');
            
            $user = $request->user();
            if (!$user) {
                Log::warning('Unauthorized backup history access');
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized'
                ], 401);
            }

            Log::info('User authenticated', ['user_id' => $user->id_user, 'role' => $user->role]);

            // Simple query without any relationships
            $query = BackupHistory::orderBy('created_at', 'desc')->limit(50);

            if ($user->role !== 'super admin') {
                $query->where('user_id', $user->id_user);
            }

            $history = $query->get();

            Log::info('Backup history retrieved', ['count' => $history->count()]);

            return response()->json([
                'status' => 'success',
                'data' => $history
            ]);

        } catch (\Exception $e) {
            Log::error('Get backup history failed: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get backup history: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Keep JSON backup as alternative
     */
    public function createBackupSimple(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized'
                ], 401);
            }

            $tables = [
                'users', 'cabang', 'karyawan', 'produk', 'bahan_baku', 
                'transaksi', 'detail_transaksi', 'pengeluaran', 'jenis_pengeluaran',
                'stok_cabang', 'bahan_baku_harian', 'detail_pengeluaran'
            ];
            
            $backupData = ['metadata' => [
                'backup_type' => 'json',
                'timestamp' => now()->toISOString(),
                'database' => config('database.connections.mysql.database'),
                'tables_backed_up' => $tables,
                'created_by' => [
                    'user_id' => $user->id_user,
                    'user_name' => $user->name,
                    'role' => $user->role
                ]
            ]];
            
            foreach ($tables as $table) {
                try {
                    $backupData[$table] = DB::table($table)->get()->toArray();
                } catch (\Exception $e) {
                    $backupData[$table] = ['error' => $e->getMessage()];
                }
            }
            
            $timestamp = now()->format('Y-m-d_H-i-s');
            $filename = "gochicken_backup_{$timestamp}.json";
            $content = json_encode($backupData, JSON_PRETTY_PRINT);

            return response($content, 200, [
                'Content-Type' => 'application/json',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
            
        } catch (\Exception $e) {
            Log::error('JSON backup failed: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Backup failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function clearBackupHistory(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized'
                ], 401);
            }

            // Only super admin can clear history
            if ($user->role !== 'super admin') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized - Only super admin can clear backup history'
                ], 403);
            }

            $deletedCount = BackupHistory::query()->delete();

            Log::info('Backup history cleared', [
                'user_id' => $user->id_user,
                'deleted_count' => $deletedCount
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Riwayat backup berhasil dihapus',
                'deleted_count' => $deletedCount
            ]);

        } catch (\Exception $e) {
            Log::error('Clear backup history failed: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghapus riwayat backup: ' . $e->getMessage()
            ], 500);
        }
    }
}