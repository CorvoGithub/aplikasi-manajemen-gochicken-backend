<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PengeluaranModel;
use Illuminate\Support\Facades\Validator;

class PengeluaranController extends Controller
{
    /**
     * Menampilkan semua data pengeluaran.
    */
    public function index()
    {
        $pengeluaran = PengeluaranModel::all();

        return response()->json([
            'status' => 'success',
            'data' => $pengeluaran,
        ]);
    }

    /**
     * Tambah pengeluaran baru.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_pengeluaran' => 'required',
            'id_detail_pengeluaran' => 'required',
            'tanggal' => 'required',
            'jumlah' => 'required',
            'keterangan' => 'required',

        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $pengeluaran = PengeluaranModel::create([
            'id_pengeluaran' => $request->id_pengeluaran,
            'tanggal' => $request->tanggal,
            'jumlah' => $request->jumlah,
            'keterangan' => $request->keterangan,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'pengeluaran berhasil ditambahkan.',
            'data' => $pengeluaran,
        ], 201);
    }

    /**
     * Edit data pengeluaran.
     */
    public function update(Request $request, $id_pengeluaran)
    {
        $pengeluaran = PengeluaranModel::find($id_pengeluaran);

        if (!$pengeluaran) {
            return response()->json([
                'status' => 'error',
                'message' => 'pengeluaran tidak ditemukan.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'tanggal' => 'required',
            'jumlah' => 'required',
            'keterangan' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $pengeluaran->tanggal = $request->tanggal ?? $pengeluaran->tanggal;
        $pengeluaran->jumlah = $request->jumlah ?? $pengeluaran->jumlah;
        $pengeluaran->keterangan = $request->keterangan ?? $pengeluaran->keterangan;

        $pengeluaran->save();

        return response()->json([
            'status' => 'success',
            'message' => 'pengeluaran berhasil diupdate.',
            'data' => $pengeluaran,
        ]);
    }

    /**
     * Hapus pengeluaran.
     */
    public function destroy($id_pengeluaran)
    {
        $pengeluaran = PengeluaranModel::find($id_pengeluaran);

        if (!$pengeluaran) {
            return response()->json([
                'status' => 'error',
                'message' => 'pengeluaran tidak ditemukan.',
            ], 404);
        }

        $pengeluaran->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'pengeluaran berhasil dihapus.',
        ], 200);
    }
}
