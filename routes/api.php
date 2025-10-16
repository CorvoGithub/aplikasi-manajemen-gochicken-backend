<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BahanBakuController;
use App\Http\Controllers\CabangController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\JenisPengeluaranController;
use App\Http\Controllers\KaryawanController;
use App\Http\Controllers\ManageAdminCabangController;
use App\Http\Controllers\PengeluaranController;
use App\Http\Controllers\ProdukController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TransaksiController;
use App\Http\Controllers\PemesananController;

// Rute publik untuk autentikasi
Route::post('/super-admin/login', [AuthController::class, 'loginSuperAdmin']);
Route::post('/admin-cabang/login', [AuthController::class, 'loginAdminCabang']);
Route::get('/cabang', [CabangController::class, 'index']);
Route::post('/kasir/login', [AuthController::class, 'loginKasir']);

// Rute yang dilindungi oleh middleware Sanctum
Route::middleware('auth:sanctum')->group(function () {
    // Pengguna yang sedang terautentikasi
    Route::get('/user', fn(Request $request) => $request->user());
    Route::post('/logout', [AuthController::class, 'logout']);

    // --- DASHBOARD & LAPORAN (UNTUK ADMIN CABANG) ---
    Route::get('/dashboard/cabang/{id}', [DashboardController::class, 'cabangStats']);
    Route::get('/dashboard/cabang/{id}/chart', [DashboardController::class, 'cabangChart']);
    Route::get('/dashboard/user/activities', [DashboardController::class, 'userActivities']);
    Route::get('/reports/cabang/{id}', [ReportController::class, 'cabangReport']);
    Route::get('/reports/cabang/{id}/products', [ReportController::class, 'productReportPaginated']);
    Route::get('/reports/cabang/{id}/sales/transactions', [ReportController::class, 'salesTransactionsPaginated']);
    Route::get('/reports/cabang/{id}/sales/expenses', [ReportController::class, 'salesExpensesPaginated']);
    Route::get('/reports/cabang/{id}/employees', [ReportController::class, 'employeeReportPaginated']);

    // --- RUTE FUNGSIONAL UNTUK ADMIN CABANG ---
    // Produk & Stok
    Route::get('/cabang/{id_cabang}/produk', [ProdukController::class, 'getProdukByCabang']);
    Route::put('/stok-cabang/{id_stock_cabang}', [ProdukController::class, 'updateStok']);

    // Karyawan
    Route::get('/cabang/{id_cabang}/karyawan', [KaryawanController::class, 'getKaryawanByCabang']);
    Route::post('/karyawan', [KaryawanController::class, 'store']);
    Route::put('/karyawan/{id_karyawan}', [KaryawanController::class, 'update']);
    Route::delete('/karyawan/{id_karyawan}', [KaryawanController::class, 'destroy']);
    
    // Pengeluaran
    Route::get('/cabang/{id_cabang}/pengeluaran', [PengeluaranController::class, 'getPengeluaranByCabang']);
    Route::post('/pengeluaran', [PengeluaranController::class, 'store']);
    Route::put('/pengeluaran/{id_pengeluaran}', [PengeluaranController::class, 'update']);
    Route::delete('/pengeluaran/{id_pengeluaran}', [PengeluaranController::class, 'destroy']);

    // Pemesanan
    Route::get('/cabang/{id_cabang}/pemesanan', [PemesananController::class, 'index']);
    Route::post('/pemesanan', [PemesananController::class, 'store']);
    Route::put('/pemesanan/{id_transaksi}', [PemesananController::class, 'update']);
    Route::delete('/pemesanan/{id_transaksi}', [PemesananController::class, 'destroy']);

    // --- SUMBER DAYA BERSAMA (Dibutuhkan oleh Admin Cabang untuk form) ---
    Route::get('/jenis-pengeluaran', [JenisPengeluaranController::class, 'index']);
    Route::post('/jenis-pengeluaran', [JenisPengeluaranController::class, 'store']);
    Route::get('/bahan-baku', [BahanBakuController::class, 'index']); 

    // --- HANYA UNTUK SUPER ADMIN ---
    Route::middleware('role:super admin')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'globalStats']);
        
        // Manajemen Resource
        Route::apiResource('/cabang', CabangController::class)->except(['index']);
        Route::apiResource('/produk', ProdukController::class)->except(['getProdukByCabang', 'updateStok']);
        Route::apiResource('/bahan-baku', BahanBakuController::class)->except(['index']);
        Route::apiResource('/karyawan', KaryawanController::class)->except(['getKaryawanByCabang', 'store', 'update', 'destroy']);
        Route::apiResource('/pengeluaran', PengeluaranController::class)->except(['getPengeluaranByCabang', 'store', 'update', 'destroy']);
        Route::apiResource('/transaksi', TransaksiController::class)->except(['update', 'store']); // 'store' sekarang ditangani oleh kasir mobile

        // Manajemen Admin Cabang
        Route::get('/admin-cabang', [ManageAdminCabangController::class, 'listAdmin']);
        Route::get('/cabang-without-admin', [ManageAdminCabangController::class, 'getCabangWithoutAdmin']);
        Route::post('/create-admin-cabang', [ManageAdminCabangController::class, 'createAdminCabang']);
        Route::put('/admin-cabang/{id_user}', [ManageAdminCabangController::class, 'updateAdminCabang']);
        Route::delete('/admin-cabang/{id_user}', [ManageAdminCabangController::class, 'deleteAdminCabang']);
    });
});

