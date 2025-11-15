<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\JenisPengeluaranModel;
use Illuminate\Support\Facades\Validator;
use App\Services\AuditLogService;

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
        // The database should handle auto-incrementing the ID.
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

        $jenis_pengeluaran = JenisPengeluaranModel::create([
            'jenis_pengeluaran' => $request->jenis_pengeluaran,
        ]);

        // Log creation
        AuditLogService::logCreate(
            'jenis_pengeluaran',
            (string)$jenis_pengeluaran->id_jenis,
            $jenis_pengeluaran->toArray(),
            "Jenis pengeluaran {$jenis_pengeluaran->jenis_pengeluaran} berhasil ditambahkan"
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Jenis pengeluaran berhasil ditambahkan.',
            'data' => $jenis_pengeluaran,
        ], 201);
    }

    /**
     * Edit data pengeluaran.
     */
    public function update(Request $request, $id_jenis)
    {
        $jenis_pengeluaran = JenisPengeluaranModel::find($id_jenis);

        if (!$jenis_pengeluaran) {
            return response()->json([
                'status' => 'error',
                'message' => 'jenis pengeluaran tidak ditemukan.',
            ], 404);
        }

        // Store old data for audit log
        $oldData = $jenis_pengeluaran->toArray();

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

        // Refresh to get updated data
        $jenis_pengeluaran->refresh();

        // Log update
        AuditLogService::logUpdate(
            'jenis_pengeluaran',
            (string)$jenis_pengeluaran->id_jenis,
            $oldData,
            $jenis_pengeluaran->toArray(),
            "Jenis pengeluaran berhasil diupdate: {$oldData['jenis_pengeluaran']} → {$jenis_pengeluaran->jenis_pengeluaran}"
        );

        return response()->json([
            'status' => 'success',
            'message' => 'jenis pengeluaran berhasil diupdate.',
            'data' => $jenis_pengeluaran,
        ]);
    }

    /**
     * Hapus jenis pengeluaran.
     */
    public function destroy($id_jenis)
    {
        $jenis_pengeluaran = JenisPengeluaranModel::find($id_jenis);

        if (!$jenis_pengeluaran) {
            return response()->json([
                'status' => 'error',
                'message' => 'jenis pengeluaran tidak ditemukan.',
            ], 404);
        }

        // Store old data for audit log
        $oldData = $jenis_pengeluaran->toArray();

        $jenis_pengeluaran->delete();

        // Log deletion
        AuditLogService::logDelete(
            'jenis_pengeluaran',
            (string)$id_jenis,
            $oldData,
            "Jenis pengeluaran {$oldData['jenis_pengeluaran']} berhasil dihapus"
        );

        return response()->json([
            'status' => 'success',
            'message' => 'jenis pengeluaran berhasil dihapus.',
        ], 200);
    }
}