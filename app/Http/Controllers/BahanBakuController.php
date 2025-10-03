<?php

namespace App\Http\Controllers;

use App\Models\BahanBakuModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BahanBakuController extends Controller
{
    /**
     * Tampilkan semua bahan baku
     */
    public function index()
    {
        $bahanBaku = BahanBakuModel::all();

        return response()->json([
            'status' => 'success',
            'data' => $bahanBaku,
        ]);
    }

    /**
     * Simpan bahan baku baru
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama_bahan' => 'required',
            'harga_satuan' => 'required',
            'satuan' => 'required',
            'jumlah_stok' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $bahan = BahanBakuModel::create($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Bahan baku berhasil ditambahkan.',
            'data' => $bahan,
        ], 201);
    }

    /**
     * Update bahan baku
     */
    public function update(Request $request, $id_bahan_baku)
    {
        $bahan = BahanBakuModel::find($id_bahan_baku);

        if (!$bahan) {
            return response()->json([
                'status' => 'error',
                'message' => 'Bahan baku tidak ditemukan.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nama_bahan' => 'required',
            'satuan' => 'required',
            'harga_satuan' => 'required',
            'jumlah_stok' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $bahan->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Bahan baku berhasil diupdate.',
            'data' => $bahan,
        ]);
    }

    /**
     * Hapus bahan baku
     */
    public function destroy($id_bahan_baku)
    {
        $bahan = BahanBakuModel::find($id_bahan_baku);

        if (!$bahan) {
            return response()->json([
                'status' => 'error',
                'message' => 'Bahan baku tidak ditemukan.',
            ], 404);
        }

        // Optional: Cek apakah ada relasi detail pengeluaran
        if ($bahan->detailPengeluaran()->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Bahan baku tidak bisa dihapus karena sudah digunakan di pengeluaran.',
            ], 400);
        }

        $bahan->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Bahan baku berhasil dihapus.',
        ]);
    }
}
