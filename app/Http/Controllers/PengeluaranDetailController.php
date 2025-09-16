<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DetailPengeluaranModel;
use Illuminate\Support\Facades\Validator;

class DetailPengeluaranController extends Controller
{
    /**
     * Menampilkan semua detail pengeluaran.
     */
    public function index()
    {
        $detailPengeluaran = DetailPengeluaranModel::with(['pengeluaran', 'jenisPengeluaran', 'bahanBaku', 'cabang', 'karyawan'])->get();

        return response()->json([
            'status' => 'success',
            'data' => $detailPengeluaran,
        ]);
    }

    /**
     * Tambah detail pengeluaran baru.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_detail_pengeluaran' => 'required',
            'id_pengeluaran' => 'required',
            'id_bahan_baku' => 'required',
            'id_cabang' => 'required',
            'id_jenis_pengeluaran' => 'required',
            'id_karyawan' => 'required',
            'jumlah_item' => 'required|numeric',
            'harga_satuan' => 'required|numeric',
            'total_harga' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $detail = DetailPengeluaranModel::create($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Detail pengeluaran berhasil ditambahkan.',
            'data' => $detail,
        ], 201);
    }

    /**
     * Update detail pengeluaran.
     */
    public function update(Request $request, $id_detail_pengeluaran)
    {
        $detail = DetailPengeluaranModel::find($id_detail_pengeluaran);

        if (!$detail) {
            return response()->json([
                'status' => 'error',
                'message' => 'Detail pengeluaran tidak ditemukan.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'id_bahan_baku' => 'sometimes|required',
            'id_cabang' => 'sometimes|required',
            'id_jenis_pengeluaran' => 'sometimes|required',
            'id_karyawan' => 'sometimes|required',
            'jumlah_item' => 'sometimes|numeric',
            'harga_satuan' => 'sometimes|numeric',
            'total_harga' => 'sometimes|numeric',
        ]);


        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $detail->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Detail pengeluaran berhasil diupdate.',
            'data' => $detail,
        ]);
    }

    /**
     * Hapus detail pengeluaran.
     */
    public function destroy($id_detail_pengeluaran)
    {
        $detail = DetailPengeluaranModel::find($id_detail_pengeluaran);

        if (!$detail) {
            return response()->json([
                'status' => 'error',
                'message' => 'Detail pengeluaran tidak ditemukan.',
            ], 404);
        }

        $detail->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Detail pengeluaran berhasil dihapus.',
        ], 200);
    }
}
