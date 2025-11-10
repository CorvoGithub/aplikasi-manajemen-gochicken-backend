<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Services\AuditLogService;

class BahanBakuPakaiController extends Controller
{
    /**
     * Tampilkan pemakaian bahan baku harian
     */
    public function index(Request $request)
    {
        $tanggal = $request->query('tanggal', date('Y-m-d'));

        $pemakaian = DB::table('bahan_baku_harian')
            ->join('bahan_baku', 'bahan_baku_harian.id_bahan_baku', '=', 'bahan_baku.id_bahan_baku')
            ->whereDate('bahan_baku_harian.tanggal', $tanggal)
            ->select(
                'bahan_baku_harian.id_pemakaian',
                'bahan_baku.nama_bahan',
                'bahan_baku.satuan',
                'bahan_baku.harga_satuan',
                'bahan_baku_harian.jumlah_pakai',
                DB::raw('(bahan_baku.harga_satuan * bahan_baku_harian.jumlah_pakai) as total_modal'),
                'bahan_baku_harian.catatan'
            )
            ->get();

        return response()->json([
            'status' => 'success',
            'tanggal' => $tanggal,
            'data' => $pemakaian
        ]);
    }

    /**
     * Tambah pemakaian bahan baku harian
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tanggal' => 'required|date',
            'id_bahan_baku' => 'required|exists:bahan_baku,id_bahan_baku',
            'jumlah_pakai' => 'required|numeric|min:0.01',
            'catatan' => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Ambil baris bahan baku dengan lock untuk mencegah race condition
            $bahanBaku = DB::table('bahan_baku')
                ->where('id_bahan_baku', $request->id_bahan_baku)
                ->lockForUpdate()
                ->first();

            if (!$bahanBaku) {
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => 'Bahan baku tidak ditemukan.'], 404);
            }

            // Ganti ->jumlah ke ->stok (pastikan kolom DB bernama 'stok')
            $stokSkrg = (float) $bahanBaku->jumlah_stok;
            $pakai = (float) $request->jumlah_pakai;

            if ($stokSkrg < $pakai) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Stok bahan baku tidak mencukupi. Stok saat ini: ' . $stokSkrg,
                ], 400);
            }

            // Kurangi stok
            DB::table('bahan_baku')
                ->where('id_bahan_baku', $request->id_bahan_baku)
                ->update(['jumlah_stok' => $stokSkrg - $pakai]);

            // Simpan pemakaian harian
            $idPemakaian = DB::table('bahan_baku_harian')->insertGetId([
                'tanggal' => $request->tanggal,
                'id_bahan_baku' => $request->id_bahan_baku,
                'jumlah_pakai' => $request->jumlah_pakai,
                'catatan' => $request->catatan,
            ]);

            // Get the created record for audit log
            $pemakaianData = DB::table('bahan_baku_harian')
                ->where('id_pemakaian', $idPemakaian)
                ->first();

            // Log creation
            AuditLogService::logCreate(
                'bahan_baku_harian',
                $idPemakaian,
                [
                    'id_pemakaian' => $idPemakaian,
                    'tanggal' => $request->tanggal,
                    'id_bahan_baku' => $request->id_bahan_baku,
                    'jumlah_pakai' => $request->jumlah_pakai,
                    'catatan' => $request->catatan,
                    'bahan_baku_nama' => $bahanBaku->nama_bahan,
                    'stok_sebelum' => $stokSkrg,
                    'stok_sesudah' => $stokSkrg - $pakai
                ],
                "Pemakaian bahan baku {$bahanBaku->nama_baku} sejumlah {$request->jumlah_pakai} {$bahanBaku->satuan} berhasil dicatat"
            );

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Pemakaian bahan baku berhasil disimpan dan stok diperbarui.',
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menyimpan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update pemakaian bahan baku
     */
    public function update(Request $request, $id_pemakaian)
    {
        $validator = Validator::make($request->all(), [
            'tanggal' => 'required|date',
            'id_bahan_baku' => 'required|exists:bahan_baku,id_bahan_baku',
            'jumlah_pakai' => 'required|numeric|min:0.01',
            'catatan' => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Get old data for audit log
            $oldPemakaian = DB::table('bahan_baku_harian')
                ->where('id_pemakaian', $id_pemakaian)
                ->first();

            if (!$oldPemakaian) {
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => 'Data pemakaian tidak ditemukan.'], 404);
            }

            $bahanBaku = DB::table('bahan_baku')
                ->where('id_bahan_baku', $request->id_bahan_baku)
                ->lockForUpdate()
                ->first();

            if (!$bahanBaku) {
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => 'Bahan baku tidak ditemukan.'], 404);
            }

            // Kembalikan stok lama
            DB::table('bahan_baku')
                ->where('id_bahan_baku', $oldPemakaian->id_bahan_baku)
                ->increment('jumlah_stok', $oldPemakaian->jumlah_pakai);

            // Kurangi stok baru
            $stokSkrg = (float) $bahanBaku->jumlah_stok;
            $pakai = (float) $request->jumlah_pakai;

            if ($stokSkrg < $pakai) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Stok bahan baku tidak mencukupi. Stok saat ini: ' . $stokSkrg,
                ], 400);
            }

            DB::table('bahan_baku')
                ->where('id_bahan_baku', $request->id_bahan_baku)
                ->update(['jumlah_stok' => $stokSkrg - $pakai]);

            // Update pemakaian
            DB::table('bahan_baku_harian')
                ->where('id_pemakaian', $id_pemakaian)
                ->update([
                    'tanggal' => $request->tanggal,
                    'id_bahan_baku' => $request->id_bahan_baku,
                    'jumlah_pakai' => $request->jumlah_pakai,
                    'catatan' => $request->catatan,
                ]);

            // Get updated data for audit log
            $newPemakaian = DB::table('bahan_baku_harian')
                ->where('id_pemakaian', $id_pemakaian)
                ->first();

            // Log update
            AuditLogService::logUpdate(
                'bahan_baku_harian',
                $id_pemakaian,
                (array) $oldPemakaian,
                (array) $newPemakaian,
                "Pemakaian bahan baku diupdate - Jumlah: {$oldPemakaian->jumlah_pakai} → {$request->jumlah_pakai}"
            );

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Pemakaian bahan baku berhasil diupdate.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengupdate: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Hapus data pemakaian bahan baku
     */
    public function destroy($id_pemakaian)
    {
        DB::beginTransaction();
        try {
            $row = DB::table('bahan_baku_harian')->where('id_pemakaian', $id_pemakaian)->first();
            if (!$row) { 
                DB::rollBack(); 
                return response()->json(['status'=>'error','message'=>'Data tidak ditemukan'],404); 
            }

            // Get bahan baku info for audit log
            $bahanBaku = DB::table('bahan_baku')->where('id_bahan_baku', $row->id_bahan_baku)->first();

            // Store old data for audit log
            $oldData = (array) $row;

            // kembalikan stok
            DB::table('bahan_baku')->where('id_bahan_baku', $row->id_bahan_baku)->increment('jumlah_stok', $row->jumlah_pakai);

            // hapus record pemakaian
            DB::table('bahan_baku_harian')->where('id_pemakaian', $id_pemakaian)->delete();

            // Log deletion
            AuditLogService::logDelete(
                'bahan_baku_harian',
                $id_pemakaian,
                $oldData,
                "Pemakaian bahan baku {$bahanBaku->nama_bahan} sejumlah {$row->jumlah_pakai} {$bahanBaku->satuan} berhasil dihapus"
            );

            DB::commit();
            return response()->json(['status'=>'success','message'=>'Pemakaian berhasil dihapus dan stok dikembalikan.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status'=>'error','message'=>'Gagal menghapus: '.$e->getMessage()],500);
        }
    }
}