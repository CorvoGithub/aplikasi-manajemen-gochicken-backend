<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\JenisPengeluaranModel;
use Illuminate\Support\Facades\Validator;

class JenisPengeluaranController extends Controller
{
    /**
     * Menampilkan semua data pengeluaran.
    */
    public function index()
    {
        $jenis_pengeluaran = JenisPengeluaranModel::all();

        return response()->json([
            'status' => 'success',
            'data' => $jenis_pengeluaran,
        ]);
    }

    /**
     * Tambah pengeluaran baru.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_jenis_pengeluaran' => 'required',
            'jenis_pengeluaran' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $jenis_pengeluaran = JenisPengeluaranModel::create([
            'id_jenis_pengeluaran' => $request->id_jenis_pengeluaran,
            'jenis_pengeluaran' => $request->jenis_pengeluaran,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'jenis pengeluaran berhasil ditambahkan.',
            'data' => $jenis_pengeluaran,
        ], 201);
    }

    /**
     * Edit data pengeluaran.
     */
    public function update(Request $request, $id_jenis_pengeluaran)
    {
        $jenis_pengeluaran = JenisPengeluaranModel::find($id_jenis_pengeluaran);

        if (!$jenis_pengeluaran) {
            return response()->json([
                'status' => 'error',
                'message' => 'jenis pengeluaran tidak ditemukan.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'jenis_pengeluaran' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $jenis_pengeluaran->jenis_pengeluaran = $request->jenis_pengeluaran ?? $jenis_pengeluaran->jenis_pengeluaran;

        $jenis_pengeluaran->save();

        return response()->json([
            'status' => 'success',
            'message' => 'jenis pengeluaran berhasil diupdate.',
            'data' => $jenis_pengeluaran,
        ]);
    }

    /**
     * Hapus jenis pengeluaran.
     */
    public function destroy($id_jenis_pengeluaran)
    {
        $jenis_pengeluaran = JenisPengeluaranModel::find($id_jenis_pengeluaran);

        if (!$jenis_pengeluaran) {
            return response()->json([
                'status' => 'error',
                'message' => 'jenis pengeluaran tidak ditemukan.',
            ], 404);
        }

        $jenis_pengeluaran->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'jenis pengeluaran berhasil dihapus.',
        ], 200);
    }
}
