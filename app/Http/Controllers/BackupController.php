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
     * Import database from SQL file
     */
    public function importDatabase(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized'
                ], 401);
            }

            // Only super admin can import database
            if ($user->role !== 'super admin') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized - Only super admin can import database'
                ], 403);
            }

            $request->validate([
                'sql_file' => 'required|file|mimes:sql,txt|max:102400', // 100MB max
            ]);

            $file = $request->file('sql_file');
            $filename = $file->getClientOriginalName();
            $tempPath = $file->getRealPath();

            // Create import history record
            $importHistory = BackupHistory::create([
                'filename' => $filename,
                'file_type' => 'sql_import',
                'file_size' => $file->getSize(),
                'backup_type' => 'import',
                'user_id' => $user->id_user,
                'user_role' => $user->role,
                'success' => false,
            ]);

            // Try command line method first, fallback to PHP method
            try {
                $result = $this->importUsingCommandLine($tempPath, $importHistory);
            } catch (\Exception $e) {
                Log::warning('Command line import failed, trying PHP method: ' . $e->getMessage());
                $result = $this->importUsingPhp($tempPath, $importHistory);
            }

            if ($result['success']) {
                $importHistory->update([
                    'success' => true
                ]);

                Log::info('Database import completed successfully', [
                    'filename' => $filename,
                    'user_id' => $user->id_user,
                    'method' => $result['method']
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Database berhasil diimport! (Method: ' . $result['method'] . ')'
                ]);
            } else {
                throw new \Exception($result['error']);
            }

        } catch (\Exception $e) {
            Log::error('Database import failed: ' . $e->getMessage());

            if (isset($importHistory)) {
                $importHistory->update([
                    'success' => false,
                    'error_message' => $e->getMessage()
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Import failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Import using command line mysql tool
     */
    private function importUsingCommandLine($filePath, $importHistory)
    {
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');
        $host = config('database.connections.mysql.host');

        // Try to find mysql executable in common paths
        $mysqlPaths = [
            'mysql', // If it's in PATH
            'C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe',
            'C:\Program Files\MySQL\MySQL Server 5.7\bin\mysql.exe',
            'C:\xampp\mysql\bin\mysql.exe',
            'C:\wamp\bin\mysql\mysql8.0.xx\bin\mysql.exe',
            'C:\wamp64\bin\mysql\mysql8.0.xx\bin\mysql.exe',
        ];

        $mysqlCommand = null;
        foreach ($mysqlPaths as $path) {
            if ($this->commandExists($path)) {
                $mysqlCommand = $path;
                break;
            }
        }

        if (!$mysqlCommand) {
            throw new \Exception('MySQL client not found. Please install MySQL command line tools.');
        }

        // Windows command
        $command = "\"{$mysqlCommand}\" --user={$username} --password={$password} --host={$host} {$database} < \"{$filePath}\"";

        // Execute import command
        $process = Process::fromShellCommandline($command);
        $process->setTimeout(300); // 5 minutes timeout
        $process->run();

        // Check if process was successful
        if (!$process->isSuccessful()) {
            throw new \Exception($process->getErrorOutput());
        }

        return ['success' => true, 'method' => 'command_line'];
    }

    /**
     * Import using pure PHP (fallback method)
     */
    private function importUsingPhp($filePath, $importHistory)
    {
        Log::info('Starting PHP-based SQL import');
        
        // Read SQL file
        $sqlContent = file_get_contents($filePath);
        
        if (empty($sqlContent)) {
            throw new \Exception('SQL file is empty');
        }

        // Remove UTF-8 BOM if present
        $sqlContent = preg_replace('/^\xEF\xBB\xBF/', '', $sqlContent);

        // Split into individual queries
        $queries = $this->splitSqlQueries($sqlContent);
        
        $successfulQueries = 0;
        $totalQueries = count($queries);
        $errors = [];
        
        Log::info("Found {$totalQueries} queries to execute");

        // Disable foreign key checks temporarily
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // Don't use transactions for entire import because DDL statements (CREATE, DROP, ALTER)
        // cause implicit commits in MySQL and break transactions

        try {
            foreach ($queries as $index => $query) {
                $query = trim($query);
                
                // Skip empty queries and comments
                if (empty($query) || 
                    strpos($query, '--') === 0 || 
                    strpos($query, '/*') === 0 ||
                    strpos($query, '#') === 0) {
                    continue;
                }

                try {
                    // Execute query without transaction
                    DB::statement($query);
                    $successfulQueries++;
                    
                    // Log progress for large files
                    if ($index % 50 === 0) {
                        Log::info("Import progress: {$successfulQueries}/{$totalQueries} queries");
                    }
                    
                } catch (\Exception $e) {
                    $errorMessage = $e->getMessage();
                    Log::warning("Query failed [{$index}]: " . $errorMessage . " | Query: " . substr($query, 0, 200));
                    
                    // Classify errors - continue for some, stop for others
                    if ($this->isNonCriticalError($errorMessage)) {
                        // Non-critical error, continue with next query
                        $errors[] = "Query {$index}: " . $errorMessage;
                        continue;
                    } else {
                        // Critical error, stop import
                        throw new \Exception("Critical error at query {$index}: " . $errorMessage);
                    }
                }
            }
            
            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            
            Log::info("PHP import completed: {$successfulQueries}/{$totalQueries} queries successful");
            
            $result = [
                'success' => true, 
                'method' => 'php',
                'stats' => [
                    'successful' => $successfulQueries,
                    'total' => $totalQueries,
                    'errors' => $errors
                ]
            ];
            
            if (!empty($errors)) {
                $result['warning'] = 'Import completed with ' . count($errors) . ' non-critical errors';
            }
            
            return $result;
            
        } catch (\Exception $e) {
            // Re-enable foreign key checks on failure
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            
            $errorMsg = "PHP import failed at query {$successfulQueries}: " . $e->getMessage();
            if (!empty($errors)) {
                $errorMsg .= " (Plus " . count($errors) . " non-critical errors)";
            }
            
            throw new \Exception($errorMsg);
        }
    }

    private function isNonCriticalError($errorMessage)
    {
        $nonCriticalPatterns = [
            '/table.*already exists/i',
            '/table.*doesn\'t exist/i',
            '/unknown table/i',
            '/duplicate entry/i',
            '/base table or view not found/i',
            '/cannot add foreign key constraint/i',
            '/key constraint fails/i',
            '/integrity constraint violation/i'
        ];
        
        foreach ($nonCriticalPatterns as $pattern) {
            if (preg_match($pattern, $errorMessage)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Split SQL file into individual queries
     */
    private function splitSqlQueries($sql)
    {
        // Remove MySQL comments
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
        $sql = preg_replace('/--.*?[\r\n]/', '', $sql);
        $sql = preg_replace('/#.*?[\r\n]/', '', $sql);
        
        // Split by semicolon, but respect semicolons in strings and delimited identifiers
        $queries = [];
        $currentQuery = '';
        $inString = false;
        $inBacktick = false;
        $stringChar = '';
        $escapeNext = false;
        
        for ($i = 0; $i < strlen($sql); $i++) {
            $char = $sql[$i];
            
            if ($escapeNext) {
                $currentQuery .= $char;
                $escapeNext = false;
                continue;
            }
            
            if ($char === '\\') {
                $escapeNext = true;
                $currentQuery .= $char;
                continue;
            }
            
            // Handle backticks for identifiers
            if ($char === '`' && !$inString) {
                $inBacktick = !$inBacktick;
                $currentQuery .= $char;
                continue;
            }
            
            // Handle strings
            if (($char === "'" || $char === '"') && !$inBacktick && !$inString) {
                $inString = true;
                $stringChar = $char;
                $currentQuery .= $char;
            } elseif ($char === $stringChar && $inString && !$inBacktick) {
                $inString = false;
                $stringChar = '';
                $currentQuery .= $char;
            } elseif ($char === ';' && !$inString && !$inBacktick) {
                $query = trim($currentQuery);
                if (!empty($query)) {
                    $queries[] = $query;
                }
                $currentQuery = '';
            } else {
                $currentQuery .= $char;
            }
        }
        
        // Add the last query if any
        $lastQuery = trim($currentQuery);
        if (!empty($lastQuery)) {
            $queries[] = $lastQuery;
        }
        
        return array_filter($queries, function($query) {
            // Filter out empty queries and common non-query patterns
            return !empty(trim($query)) && 
                   !preg_match('/^\s*(SET|USE|DELIMITER|--|#|\/\*)/i', $query);
        });
    }

    /**
     * Check if a command exists
     */
    private function commandExists($command)
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $command = "where \"{$command}\" >nul 2>nul";
        } else {
            $command = "command -v {$command} >/dev/null 2>&1";
        }
        
        $process = Process::fromShellCommandline($command);
        $process->run();
        
        return $process->isSuccessful();
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