<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PengeluaranModel;
use App\Models\DetailPengeluaranModel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Exception;

class PengeluaranController extends Controller
{
    /**
     * Get expenses for a specific branch.
     */
    public function getPengeluaranByCabang($id_cabang)
    {
        try {
            $pengeluaran = PengeluaranModel::where('id_cabang', $id_cabang)
                ->with(['jenisPengeluaran', 'details.bahanBaku']) // Eager load relationships
                ->orderBy('tanggal', 'desc')
                ->get();

            return response()->json(['status' => 'success', 'data' => $pengeluaran]);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Gagal mengambil data pengeluaran: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Store a new expense and its details.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_cabang' => 'required|exists:cabang,id_cabang',
            'id_jenis' => 'required|exists:jenis_pengeluaran,id_jenis',
            'tanggal' => 'required|date',
            'jumlah' => 'required|numeric',
            'keterangan' => 'required|string',
            'details' => 'nullable|array',
            'details.*.id_bahan_baku' => 'required_with:details|exists:bahan_baku,id_bahan_baku',
            'details.*.jumlah_item' => 'required_with:details|numeric|min:1',
            'details.*.harga_satuan' => 'required_with:details|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Validasi gagal.', 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $pengeluaran = PengeluaranModel::create([
                'id_cabang' => $request->id_cabang,
                'id_jenis' => $request->id_jenis,
                'tanggal' => $request->tanggal,
                'jumlah' => $request->jumlah,
                'keterangan' => $request->keterangan,
            ]);

            if ($request->has('details') && is_array($request->details)) {
                foreach ($request->details as $detailData) {
                    DetailPengeluaranModel::create([
                        'id_pengeluaran' => $pengeluaran->id_pengeluaran,
                        // ✨ PERBAIKAN: Menambahkan 'id_jenis' yang wajib diisi ke dalam payload detail
                        'id_jenis' => $request->id_jenis,
                        'id_bahan_baku' => $detailData['id_bahan_baku'],
                        'jumlah_item' => $detailData['jumlah_item'],
                        'harga_satuan' => $detailData['harga_satuan'],
                        'total_harga' => $detailData['jumlah_item'] * $detailData['harga_satuan'],
                    ]);
                }
            }

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Pengeluaran berhasil ditambahkan.', 'data' => $pengeluaran], 201);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan server: ' . $e->getMessage()], 500);
        }
    }

    /**
     * ✨ REVISED: Update an existing expense and its details.
     */
    public function update(Request $request, $id_pengeluaran)
    {
        $validator = Validator::make($request->all(), [
            'id_jenis' => 'required|exists:jenis_pengeluaran,id_jenis',
            'tanggal' => 'required|date',
            'jumlah' => 'required|numeric',
            'keterangan' => 'required|string',
            'details' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Validasi gagal.', 'errors' => $validator->errors()], 422);
        }
        
        $pengeluaran = PengeluaranModel::find($id_pengeluaran);
        if (!$pengeluaran) {
            return response()->json(['status' => 'error', 'message' => 'Pengeluaran tidak ditemukan'], 404);
        }
        
        DB::beginTransaction();
        try {
            $pengeluaran->update($request->only(['id_jenis', 'tanggal', 'jumlah', 'keterangan']));

            DetailPengeluaranModel::where('id_pengeluaran', $id_pengeluaran)->delete();

            if ($request->has('details') && is_array($request->details)) {
                foreach ($request->details as $detailData) {
                    DetailPengeluaranModel::create([
                        'id_pengeluaran' => $pengeluaran->id_pengeluaran,
                        // ✨ PERBAIKAN: Juga menambahkan 'id_jenis' saat mengupdate
                        'id_jenis' => $request->id_jenis,
                        'id_bahan_baku' => $detailData['id_bahan_baku'],
                        'jumlah_item' => $detailData['jumlah_item'],
                        'harga_satuan' => $detailData['harga_satuan'],
                        'total_harga' => $detailData['jumlah_item'] * $detailData['harga_satuan'],
                    ]);
                }
            }

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Pengeluaran berhasil diupdate.']);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Gagal mengupdate pengeluaran: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Delete an expense.
     */
    public function destroy($id_pengeluaran)
    {
        $pengeluaran = PengeluaranModel::find($id_pengeluaran);
        if (!$pengeluaran) {
            return response()->json(['status' => 'error', 'message' => 'Pengeluaran tidak ditemukan'], 404);
        }
        // Deleting the main expense should cascade to details if the foreign key is set up correctly
        $pengeluaran->delete();
        return response()->json(['status' => 'success', 'message' => 'Pengeluaran berhasil dihapus.']);
    }
}

