<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BahanBakuController;
use App\Http\Controllers\CabangController;
use App\Http\Controllers\KaryawanController;
use App\Http\Controllers\ManageAdminCabangController;
use App\Http\Controllers\PengeluaranController;
use App\Http\Controllers\ProdukController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TransaksiController;
use App\Http\Controllers\ReportController;
use App\Models\BahanBakuModel;


// Public routes for authentication
Route::post('/super-admin/login', [AuthController::class, 'loginSuperAdmin']);
Route::post('/admin-cabang/login', [AuthController::class, 'loginAdminCabang']);
Route::get('/cabang', [CabangController::class, 'index']);

// Routes protected by Sanctum middleware
Route::middleware('auth:sanctum')->group(function () {

    // ==== Current authenticated user ====
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // ==== Logout route ====
    Route::post('/logout', [AuthController::class, 'logout']);

    // ==== Get data for dashboard as Super Admin ====
    Route::get('/dashboard', [DashboardController::class, 'globalStats']);
    Route::get('/dashboard/chart', [DashboardController::class, 'globalChart']);
    Route::get('/dashboard/activities', [DashboardController::class, 'globalActivities']);


    // ==== Get data for dashboard as Admin Cabang ====
    Route::get('/dashboard', [DashboardController::class, 'globalStats']);
    Route::get('/dashboard/cabang/{id}', [DashboardController::class, 'cabangStats']);
    Route::get('/dashboard/cabang/{id}/chart', [DashboardController::class, 'cabangChart']);
    Route::get('/dashboard/user/activities', [DashboardController::class, 'userActivities']);

    // ==== Get data for Reports as Admin Cabang (UNPAGINATED) ====
    Route::get('/reports/cabang/{id}', [ReportController::class, 'cabangReport']);
    // Route::get('/reports/cabang/{id}/products', [ReportController::class, 'productReport']);
    // Route::get('/reports/cabang/{id}/sales', [ReportController::class, 'salesReport']);
    // Route::get('/reports/cabang/{id}/employees', [ReportController::class, 'employeeReport']);

    // ==== Get data for Reports as Admin Cabang (PAGINATED) ====
    Route::get('/reports/cabang/{id}/products', [ReportController::class, 'productReportPaginated']);
    Route::get('/reports/cabang/{id}/sales/transactions', [ReportController::class, 'salesTransactionsPaginated']);
    Route::get('/reports/cabang/{id}/sales/expenses', [ReportController::class, 'salesExpensesPaginated']);
    Route::get('/reports/cabang/{id}/employees', [ReportController::class, 'employeeReportPaginated']);

    // ==== Get data for Reports as Super Admin (ALL CABANG) ====
    Route::get('/reports/all', [ReportController::class, 'allCabangReport']);


    // ==== Routes that can only be accessed by Super Admin ====
    Route::middleware('role:super admin')->group(function () {
        // Cabang Management API
        Route::post('/cabang', [CabangController::class, 'store']);
        Route::put('/cabang/{id_cabang}', [CabangController::class, 'update']);
        Route::delete('/cabang/{id_cabang}', [CabangController::class, 'destroy']);

        // Admin Cabang Management API
        Route::get('/admin-cabang', [ManageAdminCabangController::class, 'listAdmin']);
        Route::get('/cabang-without-admin', [ManageAdminCabangController::class, 'getCabangWithoutAdmin']);
        Route::post('/create-admin-cabang', [ManageAdminCabangController::class, 'createAdminCabang']);
        Route::put('/admin-cabang/{id_user}', [ManageAdminCabangController::class, 'updateAdminCabang']);
        Route::delete('/admin-cabang/{id_user}', [ManageAdminCabangController::class, 'deleteAdminCabang']);

        // Produk Management API
        Route::get('/produk', [ProdukController::class, 'index']);
        Route::post('/produk', [ProdukController::class, 'store']);
        Route::put('/produk/{id_produk}', [ProdukController::class, 'update']);
        Route::delete('/produk/{id_produk}', [ProdukController::class, 'destroy']);

        // Bahan Baku Management API
        Route::get('/bahan-baku', [BahanBakuController::class, 'index']);
        Route::post('/bahan-baku', [BahanBakuController::class, 'store']);
        Route::put('/bahan-baku/{id_bahan_baku}', [BahanBakuController::class, 'update']);
        Route::delete('/bahan-baku/{id_bahan_baku}', [BahanBakuController::class, 'destroy']);

        // Transaksi Management API
        Route::get('/transaksi', [TransaksiController::class, 'index']);
        Route::post('/transaksi', [TransaksiController::class, 'store']);
        Route::get('/transaksi/{id_transaksi}', [TransaksiController::class, 'show']);
        Route::delete('/transaksi/{id_transaksi}', [TransaksiController::class, 'destroy']);

        // Karyawan Management API
        Route::get('/karyawan', [KaryawanController::class, 'index']);
        Route::post('/karyawan', [KaryawanController::class, 'store']);
        Route::put('/karyawan/{id_karyawan}', [KaryawanController::class, 'update']);
        Route::delete('/karyawan/{id_karyawan}', [KaryawanController::class, 'destroy']);

        // Karyawan Management API
        Route::get('/pengeluaran', [PengeluaranController::class, 'index']);
        Route::post('/pengeluaran', [PengeluaranController::class, 'store']);
        Route::put('/pengeluaran/{id_pengeluaran}', [PengeluaranController::class, 'update']);
        Route::delete('/pengeluaran/{id_pengeluaran}', [PengeluaranController::class, 'destroy']);
    });

});
