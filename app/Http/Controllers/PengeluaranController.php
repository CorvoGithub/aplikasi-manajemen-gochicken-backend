<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PengeluaranModel;
use App\Models\DetailPengeluaranModel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Exception;
use App\Services\AuditLogService;

class PengeluaranController extends Controller
{
    public function index()
    {
        try {
            $data = PengeluaranModel::with(['jenisPengeluaran', 'details.bahanBaku', 'cabang'])->get();
            return response()->json(['status' => 'success', 'data' => $data]);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function getPengeluaranByCabang($id_cabang)
    {
        try {
            $pengeluaran = PengeluaranModel::where('id_cabang', $id_cabang)
                ->with(['jenisPengeluaran', 'details.bahanBaku', 'cabang'])
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
            'id_jenis' => 'required',
            'tanggal' => 'required|date',
            'jumlah' => 'required|numeric|min:0',
            'keterangan' => 'required|string|max:255',
            'is_cicilan_harian' => 'nullable|boolean',
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
            $cicilan_harian = null;

            // 🔹 Hitung cicilan harian hanya jika pengeluaran bertipe cicilan
            if ($request->boolean('is_cicilan_harian')) {
                $jumlah_hari_bulan_ini = cal_days_in_month(
                    CAL_GREGORIAN,
                    date('m', strtotime($request->tanggal)),
                    date('Y', strtotime($request->tanggal))
                );

                if ($jumlah_hari_bulan_ini > 0) {
                    $cicilan_harian = $request->jumlah / $jumlah_hari_bulan_ini;
                }
            }

            // Get jenis pengeluaran name
            $jenisPengeluaran = DB::table('jenis_pengeluaran')->where('id_jenis', $request->id_jenis)->first();

            // 🔹 Tetap simpan JUMLAH TOTAL ke kolom jumlah, cicilan ke kolom cicilan_harian
            $pengeluaran = PengeluaranModel::create([
                'id_cabang' => $request->id_cabang,
                'id_jenis' => $request->id_jenis,
                'tanggal' => $request->tanggal,
                'jumlah' => $request->jumlah, // total pengeluaran (tetap)
                'keterangan' => $request->keterangan,
                'cicilan_harian' => $cicilan_harian, // nilai per hari (jika ada)
            ]);

            $stokChanges = [];
            $detailsData = [];

            // 🔹 Simpan detail jika ada
            if (!empty($request->details)) {
                foreach ($request->details as $detail) {
                    $detailRecord = DetailPengeluaranModel::create([
                        'id_pengeluaran' => $pengeluaran->id_pengeluaran,
                        'id_jenis' => $request->id_jenis,
                        'id_bahan_baku' => $detail['id_bahan_baku'],
                        'jumlah_item' => $detail['jumlah_item'],
                        'harga_satuan' => $detail['harga_satuan'],
                        'total_harga' => $detail['jumlah_item'] * $detail['harga_satuan'],
                    ]);

                    $detailsData[] = $detailRecord->toArray();

                    // 🔹 Update stok jika jenis pengeluaran = pembelian bahan baku
                    $jenis = DB::table('jenis_pengeluaran')->where('id_jenis', $request->id_jenis)->first();
                    if ($jenis && strtolower($jenis->jenis_pengeluaran) === 'pembelian bahan baku') {
                        $bahanBaku = DB::table('bahan_baku')->where('id_bahan_baku', $detail['id_bahan_baku'])->first();
                        $stokSebelum = $bahanBaku->jumlah_stok;
                        DB::table('bahan_baku')->where('id_bahan_baku', $detail['id_bahan_baku'])
                            ->increment('jumlah_stok', $detail['jumlah_item']);
                        $stokSesudah = $stokSebelum + $detail['jumlah_item'];
                        
                        $stokChanges[] = [
                            'bahan_baku' => $bahanBaku->nama_bahan,
                            'stok_sebelum' => $stokSebelum,
                            'stok_sesudah' => $stokSesudah,
                            'jumlah_ditambahkan' => $detail['jumlah_item']
                        ];
                    }
                }
            }

            // Log creation
            AuditLogService::logCreate(
                'pengeluaran',
                (string)$pengeluaran->id_pengeluaran,
                [
                    'id_pengeluaran' => $pengeluaran->id_pengeluaran,
                    'id_cabang' => $pengeluaran->id_cabang,
                    'jenis_pengeluaran' => $jenisPengeluaran->jenis_pengeluaran,
                    'tanggal' => $pengeluaran->tanggal,
                    'jumlah' => $pengeluaran->jumlah,
                    'keterangan' => $pengeluaran->keterangan,
                    'cicilan_harian' => $pengeluaran->cicilan_harian,
                    'details' => $detailsData,
                    'stok_changes' => $stokChanges
                ],
                "Pengeluaran {$jenisPengeluaran->jenis_pengeluaran} sebesar Rp " . number_format($pengeluaran->jumlah, 0, ',', '.') . " berhasil ditambahkan"
            );

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Pengeluaran berhasil ditambahkan.',
                'data' => $pengeluaran
            ], 201);

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
            'is_cicilan_harian' => 'nullable|boolean',
            'details' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Validasi gagal. Pastikan keterangan pengeluaran sesuai syarat.', 'errors' => $validator->errors()], 422);
        }

        $pengeluaran = PengeluaranModel::find($id_pengeluaran);
        if (!$pengeluaran) {
            return response()->json(['status' => 'error', 'message' => 'Pengeluaran tidak ditemukan.'], 404);
        }

        DB::beginTransaction();
        try {
            // Store old data for audit log
            $oldData = $pengeluaran->toArray();
            $oldDetails = DetailPengeluaranModel::where('id_pengeluaran', $id_pengeluaran)->get()->toArray();

            $cicilan_harian = null;
            if ($request->boolean('is_cicilan_harian')) {
                $jumlah_hari_bulan_ini = cal_days_in_month(
                    CAL_GREGORIAN,
                    date('m', strtotime($request->tanggal)),
                    date('Y', strtotime($request->tanggal))
                );
                $cicilan_harian = $request->jumlah / $jumlah_hari_bulan_ini;
            }

            // Get jenis pengeluaran names
            $oldJenis = DB::table('jenis_pengeluaran')->where('id_jenis', $oldData['id_jenis'])->first();
            $newJenis = DB::table('jenis_pengeluaran')->where('id_jenis', $request->id_jenis)->first();

            // 🔹 Update data utama (jumlah tetap jumlah pengeluaran, bukan cicilan)
            $pengeluaran->update([
                'id_jenis' => $request->id_jenis,
                'tanggal' => $request->tanggal,
                'jumlah' => $request->jumlah, // tetap jumlah total
                'keterangan' => $request->keterangan,
                'cicilan_harian' => $cicilan_harian, // update cicilan harian (jika ada)
            ]);

            $stokChanges = [];
            $newDetails = [];

            // 🔁 Revert & update stok + detail seperti sebelumnya...
            DetailPengeluaranModel::where('id_pengeluaran', $id_pengeluaran)->delete();

            if (!empty($request->details)) {
                foreach ($request->details as $detail) {
                    $detailRecord = DetailPengeluaranModel::create([
                        'id_pengeluaran' => $pengeluaran->id_pengeluaran,
                        'id_jenis' => $request->id_jenis,
                        'id_bahan_baku' => $detail['id_bahan_baku'],
                        'jumlah_item' => $detail['jumlah_item'],
                        'harga_satuan' => $detail['harga_satuan'],
                        'total_harga' => $detail['jumlah_item'] * $detail['harga_satuan'],
                    ]);

                    $newDetails[] = $detailRecord->toArray();

                    $jenisBaru = DB::table('jenis_pengeluaran')->where('id_jenis', $request->id_jenis)->first();
                    if ($jenisBaru && strtolower($jenisBaru->jenis_pengeluaran) === 'pembelian bahan baku') {
                        $bahanBaku = DB::table('bahan_baku')->where('id_bahan_baku', $detail['id_bahan_baku'])->first();
                        $stokSebelum = $bahanBaku->jumlah_stok;
                        DB::table('bahan_baku')->where('id_bahan_baku', $detail['id_bahan_baku'])
                            ->increment('jumlah_stok', $detail['jumlah_item']);
                        $stokSesudah = $stokSebelum + $detail['jumlah_item'];
                        
                        $stokChanges[] = [
                            'bahan_baku' => $bahanBaku->nama_bahan,
                            'stok_sebelum' => $stokSebelum,
                            'stok_sesudah' => $stokSesudah,
                            'jumlah_ditambahkan' => $detail['jumlah_item']
                        ];
                    }
                }
            }

            // Log update
            AuditLogService::logUpdate(
                'pengeluaran',
                (string)$pengeluaran->id_pengeluaran,
                $oldData,
                $pengeluaran->toArray(),
                "Pengeluaran diupdate - Jenis: {$oldJenis->jenis_pengeluaran} → {$newJenis->jenis_pengeluaran}, Jumlah: Rp " . number_format($oldData['jumlah'], 0, ',', '.') . " → Rp " . number_format($pengeluaran->jumlah, 0, ',', '.')
            );

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
            return response()->json([
                'status' => 'error',
                'message' => 'Data pengeluaran tidak ditemukan.'
            ], 404);
        }

        DB::beginTransaction();
        try {
            // Store old data for audit log
            $oldData = $pengeluaran->toArray();
            $oldDetails = DetailPengeluaranModel::where('id_pengeluaran', $id_pengeluaran)->get()->toArray();
            $jenis = DB::table('jenis_pengeluaran')->where('id_jenis', $pengeluaran->id_jenis)->first();

            $stokChanges = [];

            // 🧩 Cek apakah pengeluaran ini adalah "Pembelian bahan baku"
            if ($jenis && strtolower($jenis->jenis_pengeluaran) === 'pembelian bahan baku') {
                // Ambil semua detail pengeluaran terkait
                $details = DB::table('detail_pengeluaran')->where('id_pengeluaran', $pengeluaran->id_pengeluaran)->get();

                // Kurangi stok bahan baku sesuai jumlah_item dari tiap detail
                foreach ($details as $detail) {
                    $bahanBaku = DB::table('bahan_baku')->where('id_bahan_baku', $detail->id_bahan_baku)->first();
                    $stokSebelum = $bahanBaku->jumlah_stok;
                    DB::table('bahan_baku')
                        ->where('id_bahan_baku', $detail->id_bahan_baku)
                        ->decrement('jumlah_stok', $detail->jumlah_item);
                    $stokSesudah = $stokSebelum - $detail->jumlah_item;
                    
                    $stokChanges[] = [
                        'bahan_baku' => $bahanBaku->nama_bahan,
                        'stok_sebelum' => $stokSebelum,
                        'stok_sesudah' => $stokSesudah,
                        'jumlah_dikurangi' => $detail->jumlah_item
                    ];
                }
            }

            // Hapus detail pengeluaran
            DB::table('detail_pengeluaran')->where('id_pengeluaran', $pengeluaran->id_pengeluaran)->delete();

            // Hapus pengeluaran utama
            $pengeluaran->delete();

            // Log deletion
            AuditLogService::logDelete(
                'pengeluaran',
                (string)$id_pengeluaran,
                $oldData,
                "Pengeluaran {$jenis->jenis_pengeluaran} sebesar Rp " . number_format($oldData['jumlah'], 0, ',', '.') . " berhasil dihapus"
            );

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Data pengeluaran berhasil dihapus dan stok telah diperbarui.'
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghapus data: ' . $e->getMessage()
            ], 500);
        }
    }
}