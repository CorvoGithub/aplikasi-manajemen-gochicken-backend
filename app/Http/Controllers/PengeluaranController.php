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
    public function index()
    {
        try {
            $data = PengeluaranModel::with(['jenisPengeluaran', 'details.bahanBaku'])->get();
            return response()->json(['status' => 'success', 'data' => $data]);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function getPengeluaranByCabang($id_cabang)
    {
        try {
            $pengeluaran = PengeluaranModel::where('id_cabang', $id_cabang)
                ->with(['jenisPengeluaran', 'details.bahanBaku'])
                ->orderBy('tanggal', 'desc')
                ->get();

            return response()->json(['status' => 'success', 'data' => $pengeluaran]);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Gagal mengambil data: ' . $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_cabang' => 'required|exists:cabang,id_cabang',
            'id_jenis' => 'required|exists:jenis_pengeluaran,id_jenis',
            'tanggal' => 'required|date',
            'jumlah' => 'required|numeric|min:0',
            'keterangan' => 'required|string|max:255',
            'details' => 'nullable|array',
            'details.*.id_bahan_baku' => 'required_with:details|exists:bahan_baku,id_bahan_baku',
            'details.*.jumlah_item' => 'required_with:details|numeric|min:1',
            'details.*.harga_satuan' => 'required_with:details|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Hitung jumlah hari dalam bulan dari tanggal pengeluaran
            $jumlah_hari_bulan_ini = cal_days_in_month(
                CAL_GREGORIAN,
                date('m', strtotime($request->tanggal)),
                date('Y', strtotime($request->tanggal))
            );

            // Hitung cicilan harian otomatis
            $cicilan_harian = $request->jumlah / $jumlah_hari_bulan_ini;

            $pengeluaran = PengeluaranModel::create([
                'id_cabang' => $request->id_cabang,
                'id_jenis' => $request->id_jenis,
                'tanggal' => $request->tanggal,
                'jumlah' => $request->jumlah,
                'keterangan' => $request->keterangan,
                'cicilan_harian' => $cicilan_harian, // <── tambahkan ini
            ]);

            if (!empty($request->details)) {
                foreach ($request->details as $detail) {
                    DetailPengeluaranModel::create([
                        'id_pengeluaran' => $pengeluaran->id_pengeluaran,
                        'id_jenis' => $request->id_jenis,
                        'id_bahan_baku' => $detail['id_bahan_baku'],
                        'jumlah_item' => $detail['jumlah_item'],
                        'harga_satuan' => $detail['harga_satuan'],
                        'total_harga' => $detail['jumlah_item'] * $detail['harga_satuan'],
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

    public function update(Request $request, $id_pengeluaran)
    {
        $validator = Validator::make($request->all(), [
            'id_jenis' => 'required|exists:jenis_pengeluaran,id_jenis',
            'tanggal' => 'required|date',
            'jumlah' => 'required|numeric|min:0',
            'keterangan' => 'required|string|max:255',
            'details' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Validasi gagal.', 'errors' => $validator->errors()], 422);
        }

        $pengeluaran = PengeluaranModel::find($id_pengeluaran);
        if (!$pengeluaran) {
            return response()->json(['status' => 'error', 'message' => 'Pengeluaran tidak ditemukan.'], 404);
        }

        DB::beginTransaction();
        try {
             // Rehitung cicilan_harian ketika nominal diubah
            $jumlah_hari_bulan_ini = cal_days_in_month(
                CAL_GREGORIAN,
                date('m', strtotime($request->tanggal)),
                date('Y', strtotime($request->tanggal))
            );

            $cicilan_harian = $request->jumlah / $jumlah_hari_bulan_ini;

            $pengeluaran->update([
                'id_jenis' => $request->id_jenis,
                'tanggal' => $request->tanggal,
                'jumlah' => $request->jumlah,
                'keterangan' => $request->keterangan,
                'cicilan_harian' => $cicilan_harian, // <── update otomatis juga
            ]);

            DetailPengeluaranModel::where('id_pengeluaran', $id_pengeluaran)->delete();

            if (!empty($request->details)) {
                foreach ($request->details as $detail) {
                    DetailPengeluaranModel::create([
                        'id_pengeluaran' => $pengeluaran->id_pengeluaran,
                        'id_jenis' => $request->id_jenis,
                        'id_bahan_baku' => $detail['id_bahan_baku'],
                        'jumlah_item' => $detail['jumlah_item'],
                        'harga_satuan' => $detail['harga_satuan'],
                        'total_harga' => $detail['jumlah_item'] * $detail['harga_satuan'],
                    ]);
                }
            }

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Pengeluaran berhasil diperbarui.']);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Gagal memperbarui: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id_pengeluaran)
    {
        $pengeluaran = PengeluaranModel::find($id_pengeluaran);
        if (!$pengeluaran) {
            return response()->json(['status' => 'error', 'message' => 'Pengeluaran tidak ditemukan.'], 404);
        }

        $pengeluaran->delete();
        return response()->json(['status' => 'success', 'message' => 'Pengeluaran berhasil dihapus.']);
    }
}
