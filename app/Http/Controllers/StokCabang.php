<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StokCabangModel;
use Illuminate\Support\Facades\Validator;
use App\Services\AuditLogService;

class StokCabang extends Controller
{
    /**
     * Tampilkan semua bahan baku
     */
    public function index()
    {
        $stokCabang = StokCabangModel::with(['produk', 'cabang'])->get();

        return response()->json([
            'status' => 'success',
            'data' => $stokCabang,
        ]);
    }

    /**
     * Simpan bahan baku baru
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_stock_cabang' => 'required',
            'id_cabang' => 'required',
            'id_produk' => 'required',
            'jumlah_stok' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $stok = StokCabangModel::create($request->all());

        // Get related data for audit log
        $produk = $stok->produk;
        $cabang = $stok->cabang;

        // Log creation
        AuditLogService::logCreate(
            'stok_cabang',
            $stok->id_stock_cabang,
            [
                'id_stock_cabang' => $stok->id_stock_cabang,
                'id_cabang' => $stok->id_cabang,
                'id_produk' => $stok->id_produk,
                'jumlah_stok' => $stok->jumlah_stok,
                'produk_nama' => $produk->nama_produk,
                'cabang_nama' => $cabang->nama_cabang
            ],
            "Stok cabang berhasil ditambahkan - Produk: {$produk->nama_produk}, Cabang: {$cabang->nama_cabang}, Jumlah: {$stok->jumlah_stok}"
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Stok cabang berhasil ditambahkan.',
            'data' => $stok,
        ], 201);
    }

    /**
     * Update stok cabang
     */
    public function update(Request $request, $id_stock_cabang)
    {
        $stok = StokCabangModel::find($id_stock_cabang);

        if (!$stok) {
            return response()->json([
                'status' => 'error',
                'message' => 'Stok cabang tidak ditemukan.',
            ], 404);
        }

        // Store old data for audit log
        $oldData = $stok->toArray();
        $produk = $stok->produk;
        $cabang = $stok->cabang;

        $validator = Validator::make($request->all(), [
            'id_cabang' => 'required',
            'id_produk' => 'required',
            'jumlah_stok' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $stok->update($request->all());

        // Refresh to get updated data
        $stok->refresh();

        // Log update
        AuditLogService::logUpdate(
            'stok_cabang',
            $stok->id_stock_cabang,
            $oldData,
            $stok->toArray(),
            "Stok cabang diupdate - Produk: {$produk->nama_produk}, Cabang: {$cabang->nama_cabang}, Jumlah: {$oldData['jumlah_stok']} → {$stok->jumlah_stok}"
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Stok cabang berhasil diupdate.',
            'data' => $stok,
        ]);
    }

    /**
     * Hapus stok cabang
     */
    public function destroy($id_stock_cabang)
    {
        $stok = StokCabangModel::find($id_stock_cabang);

        if (!$stok) {
            return response()->json([
                'status' => 'error',
                'message' => 'Stok cabang tidak ditemukan.',
            ], 404);
        }

        // Store old data for audit log
        $oldData = $stok->toArray();
        $produk = $stok->produk;
        $cabang = $stok->cabang;

        // Optional: Cek apakah ada relasi detail pengeluaran
        if ($stok->detailPengeluaran()->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Stok cabang tidak bisa dihapus karena sudah digunakan di pengeluaran.',
            ], 400);
        }

        $stok->delete();

        // Log deletion
        AuditLogService::logDelete(
            'stok_cabang',
            $id_stock_cabang,
            $oldData,
            "Stok cabang berhasil dihapus - Produk: {$produk->nama_produk}, Cabang: {$cabang->nama_cabang}, Jumlah: {$oldData['jumlah_stok']}"
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Stok cabang berhasil dihapus.',
        ]);
    }
}