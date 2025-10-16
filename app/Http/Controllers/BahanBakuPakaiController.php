<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

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
            DB::table('bahan_baku_harian')->insert([
                'tanggal' => $request->tanggal,
                'id_bahan_baku' => $request->id_bahan_baku,
                'jumlah_pakai' => $request->jumlah_pakai,
                'catatan' => $request->catatan,
            ]);

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
     * Hapus data pemakaian bahan baku
     */
    public function destroy($id_pemakaian)
    {
        DB::beginTransaction();
        try {
            $row = DB::table('bahan_baku_harian')->where('id_pemakaian', $id_pemakaian)->first();
            if (!$row) { DB::rollBack(); return response()->json(['status'=>'error','message'=>'Data tidak ditemukan'],404); }

            // kembalikan stok
            DB::table('bahan_baku')->where('id_bahan_baku', $row->id_bahan_baku)->increment('jumlah_stok', $row->jumlah_pakai);

            // hapus record pemakaian
            DB::table('bahan_baku_harian')->where('id_pemakaian', $id_pemakaian)->delete();

            DB::commit();
            return response()->json(['status'=>'success','message'=>'Pemakaian berhasil dihapus dan stok dikembalikan.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status'=>'error','message'=>'Gagal menghapus: '.$e->getMessage()],500);
        }
    }

}
