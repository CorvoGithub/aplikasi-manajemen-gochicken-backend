<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StokCabangModel;
use Illuminate\Support\Facades\Validator;

class StokCabang extends Controller
{
    /**
     * Tampilkan semua bahan baku
     */
    public function index()
    {
        $stokCabang = StokCabangModel::all();

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

        // Optional: Cek apakah ada relasi detail pengeluaran
        if ($stok->detailPengeluaran()->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Stok cabang tidak bisa dihapus karena sudah digunakan di pengeluaran.',
            ], 400);
        }

        $stok->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Stok cabang berhasil dihapus.',
        ]);
    }
}
