<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BahanBakuController;
use App\Http\Controllers\CabangController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\JenisPengeluaranController;
use App\Http\Controllers\KaryawanController;
use App\Http\Controllers\KasirController;
use App\Http\Controllers\ManageAdminCabangController;
use App\Http\Controllers\PengeluaranController;
use App\Http\Controllers\ProdukController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TransaksiController;
use App\Http\Controllers\PemesananController;
use App\Http\Controllers\ReportDailyController;
use App\Http\Controllers\BahanBakuPakaiController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AndroidTransaksiController;

// Rute publik
Route::post('/super-admin/login', [AuthController::class, 'loginSuperAdmin']);
Route::post('/admin-cabang/login', [AuthController::class, 'loginAdminCabang']);
Route::get('/cabang', [CabangController::class, 'index']);
Route::post('/kasir/login', [AuthController::class, 'loginKasir']);

// ===================================================================
// --- RUTE KHUSUS UNTUK ANDROID ---
// ===================================================================
Route::get('/android/cabang/{id_cabang}/produk', [ProdukController::class, 'getProdukByCabangForAndroid']);
Route::get('/current-user', [AuthController::class, 'getCurrentUser']); 
Route::post('/transaksi', [App\Http\Controllers\AndroidTransaksiController::class, 'store']);
Route::get('/cabang/{id_cabang}/transaksi', [App\Http\Controllers\AndroidTransaksiController::class, 'getTransaksiByCabang']);


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', fn(Request $request) => $request->user());
    Route::post('/logout', [AuthController::class, 'logout']);

    // ===================================================================
    // --- RUTE UNTUK SUPER ADMIN ---
    // ===================================================================
    Route::middleware('role:super admin')->group(function () {

        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'globalStats']);
        Route::get('/dashboard/chart', [DashboardController::class, 'globalChart']);
        Route::get('/dashboard/branch-summaries', [DashboardController::class, 'dailyBranchSummaries']);
        Route::get('/dashboard/revenue-breakdown', [DashboardController::class, 'revenueBreakdown']);
        Route::get('/dashboard/quick-stats', [DashboardController::class, 'quickStats']);

        // Reports
        Route::get('/reports/all', [ReportController::class, 'allCabangReport']);
        Route::get('/reports/products', [ReportController::class, 'productReportSuperAdmin']);
        Route::get('/reports/sales', [ReportController::class, 'salesReportSuperAdmin']);
        Route::get('/reports/sales/transactions', [ReportController::class, 'salesTransactionsSuperAdmin']);
        Route::get('/reports/sales/expenses', [ReportController::class, 'salesExpensesSuperAdmin']);
        Route::get('/reports/employees', [ReportController::class, 'employeeReportSuperAdmin']);

        //Daily reports
        Route::get('/report/harian', [ReportDailyController::class, 'getDailyReport']);
        Route::put('/report/update-status/{id}', [ReportDailyController::class, 'updateOrderStatus']);
        Route::delete('/pengeluaran-daily/{id}', [ReportDailyController::class, 'deletePengeluaran']);

        //Jenis pengeluaran
        Route::get('/jenis-pengeluaran', [JenisPengeluaranController::class, 'index']);
        Route::post('/jenis-pengeluaran', [JenisPengeluaranController::class, 'store']);
        Route::put('/jenis-pengeluaran/{id_jenis}', [JenisPengeluaranController::class, 'update']);
        Route::delete('/jenis-pengeluaran/{id_jenis}', [JenisPengeluaranController::class, 'destroy']);

        //Backup database
        Route::post('/backup', [BackupController::class, 'createBackup']);
        Route::post('/backup-json', [BackupController::class, 'createBackupSimple']);
        Route::get('/backup-history', [BackupController::class, 'getBackupHistory']);
        Route::delete('/backup-history', [BackupController::class, 'clearBackupHistory']);

        //Audit log
        Route::get('/audit-logs', [AuditLogController::class, 'getAuditLogs']);
        Route::get('/audit-logs/{tableName}', [AuditLogController::class, 'getAuditLogsByTable']);
        Route::delete('/audit-logs/clear', [AuditLogController::class, 'clearAuditLogs']);
        Route::get('/audit-logs/export', [AuditLogController::class, 'exportAuditLogs']);
        Route::get('/audit-logs/cabang-list', [AuditLogController::class, 'getCabangList']);

        //Bahan baku pakai
        Route::get('/bahan-baku-pakai', [BahanBakuPakaiController::class, 'index']);
        Route::post('/bahan-baku-pakai', [BahanBakuPakaiController::class, 'store']);
        Route::put('/bahan-baku-pakai/{id_pemakaian}', [BahanBakuPakaiController::class, 'update']);
        Route::delete('/bahan-baku-pakai/{id_pemakaian}', [BahanBakuPakaiController::class, 'destroy']);

        // Manage Admin Cabang
        Route::get('/admin-cabang', [ManageAdminCabangController::class, 'listAdmin']);
        Route::get('/cabang-without-admin', [ManageAdminCabangController::class, 'getCabangWithoutAdmin']);
        Route::post('/create-admin-cabang', [ManageAdminCabangController::class, 'createAdminCabang']);
        Route::put('/admin-cabang/{id_user}', [ManageAdminCabangController::class, 'updateAdminCabang']);
        Route::delete('/admin-cabang/{id_user}', [ManageAdminCabangController::class, 'deleteAdminCabang']);
        
        //Manage Cabang, Produk, Bahan Baku, Karyawan, Pengeluaran, Transaksi
        Route::apiResource('/cabang', CabangController::class)->except(['index']);
        Route::apiResource('/produk', ProdukController::class)->except(['getProdukByCabang', 'updateStok']);
        Route::apiResource('/bahan-baku', BahanBakuController::class)->except(['index']);
        Route::apiResource('/karyawan', KaryawanController::class)->except(['getKaryawanByCabang', 'store', 'update', 'destroy']);
        Route::apiResource('/pengeluaran', PengeluaranController::class)->except(['getPengeluaranByCabang', 'store', 'update', 'destroy']);
        Route::apiResource('/transaksi', TransaksiController::class)->except(['update', 'store']);

    });

    // ===================================================================
    // --- RUTE UNTUK ADMIN CABANG & SHARED ---
    // ===================================================================

    //Dashboard
    Route::get('/dashboard/cabang/{id}', [DashboardController::class, 'cabangStats']);
    Route::get('/dashboard/cabang/{id}/chart', [DashboardController::class, 'cabangChart']);
    Route::get('/dashboard/user/activities', [DashboardController::class, 'userActivities']);

    //Reports
    Route::get('/reports/cabang/{id}', [ReportController::class, 'cabangReport']);
    Route::get('/reports/cabang/{id}/products', [ReportController::class, 'productReportPaginated']);
    Route::get('/reports/cabang/{id}/sales/transactions', [ReportController::class, 'salesTransactionsPaginated']);
    Route::get('/reports/cabang/{id}/sales/expenses', [ReportController::class, 'salesExpensesPaginated']);
    Route::get('/reports/cabang/{id}/employees', [ReportController::class, 'employeeReportPaginated']);
    
    //Stocks
    Route::get('/cabang/{id_cabang}/produk', [ProdukController::class, 'getProdukByCabang']);
    Route::put('/stok-cabang/{id_stock_cabang}', [ProdukController::class, 'updateStok']);

    //Karyawan
    Route::get('/cabang/{id_cabang}/karyawan', [KaryawanController::class, 'getKaryawanByCabang']);
    Route::post('/karyawan', [KaryawanController::class, 'store']);
    Route::put('/karyawan/{id_karyawan}', [KaryawanController::class, 'update']);
    Route::delete('/karyawan/{id_karyawan}', [KaryawanController::class, 'destroy']);

    //Kasir
    Route::get('/cabang/{id_cabang}/kasir', [KasirController::class, 'getKasirByCabang']);
    Route::post('/kasir', [KasirController::class, 'store']);
    Route::put('/kasir/{id_user}', [KasirController::class, 'update']);
    Route::delete('/kasir/{id_user}', [KasirController::class, 'destroy']);

    //Pengeluaran
    Route::get('/cabang/{id_cabang}/pengeluaran', [PengeluaranController::class, 'getPengeluaranByCabang']);
    Route::post('/pengeluaran', [PengeluaranController::class, 'store']);
    Route::put('/pengeluaran/{id_pengeluaran}', [PengeluaranController::class, 'update']);
    Route::delete('/pengeluaran/{id_pengeluaran}', [PengeluaranController::class, 'destroy']);

    //Pemesanan
    Route::get('/cabang/{id_cabang}/pemesanan', [PemesananController::class, 'index']);
    Route::post('/pemesanan', [PemesananController::class, 'store']);
    Route::put('/pemesanan/{id_transaksi}', [PemesananController::class, 'update']);
    Route::delete('/pemesanan/{id_transaksi}', [PemesananController::class, 'destroy']);

    //Jenis Pengeluaran
    Route::get('/jenis-pengeluaran', [JenisPengeluaranController::class, 'index']);
    Route::post('/jenis-pengeluaran', [JenisPengeluaranController::class, 'store']);
    Route::put('/jenis-pengeluaran/{id_jenis}', [JenisPengeluaranController::class, 'update']);
    Route::delete('/jenis-pengeluaran/{id_jenis}', [JenisPengeluaranController::class, 'destroy']);

    //Bahan Baku
    Route::get('/bahan-baku', [BahanBakuController::class, 'index']); 

    //Backup database routes
    Route::post('/backup', [BackupController::class, 'createBackup']);
    Route::post('/backup-json', [BackupController::class, 'createBackupSimple']);
    Route::get('/backup-history', [BackupController::class, 'getBackupHistory']);
    
});