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
            'id_bahan_baku' => 'required',
            'jumlah_pakai' => 'required',
            'catatan' => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::table('bahan_baku_harian')->insert([
            'tanggal' => $request->tanggal,
            'id_bahan_baku' => $request->id_bahan_baku,
            'jumlah_pakai' => $request->jumlah_pakai,
            'catatan' => $request->catatan,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Pemakaian bahan baku berhasil disimpan.',
        ], 201);
    }

    /**
     * Update data pemakaian bahan baku
     */
    public function update(Request $request, $id_pemakaian)
    {
        $validator = Validator::make($request->all(), [
            'jumlah_pakai' => 'required',
            'catatan' => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::table('bahan_baku_harian')
            ->where('id_pemakaian', $id_pemakaian)
            ->update([
                'jumlah_pakai' => $request->jumlah_pakai,
                'catatan' => $request->catatan,
            ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Pemakaian bahan baku berhasil diupdate.',
        ]);
    }

    /**
     * Hapus data pemakaian bahan baku
     */
    public function destroy($id_pemakaian)
    {
        DB::table('bahan_baku_harian')->where('id_pemakaian', $id_pemakaian)->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Pemakaian bahan baku berhasil dihapus.',
        ]);
    }
}
