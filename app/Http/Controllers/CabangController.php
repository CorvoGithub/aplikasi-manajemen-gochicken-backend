<?php

namespace App\Http\Controllers;

use App\Models\CabangModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Services\AuditLogService;

class CabangController extends Controller
{
    /**
     * Menampilkan semua data cabang.
     */
    public function index()
    {
        $cabang = CabangModel::all()->toArray();

        return response()->json([
            'status' => 'success',
            'data' => $cabang,
        ]);
    }

    /**
     * Tambah cabang baru.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama_cabang' => 'required',
            'alamat' => 'required',
            'telepon' => 'required',
            'password_cabang' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $cabang = CabangModel::create([
            'nama_cabang' => $request->nama_cabang,
            'alamat' => $request->alamat,
            'telepon' => $request->telepon,
            'password_cabang' => Hash::make($request->password_cabang),
        ]);

        // Log creation
        AuditLogService::logCreate(
            'cabang',
            (string)$cabang->id_cabang,
            $cabang->toArray(),
            "Cabang {$cabang->nama_cabang} berhasil ditambahkan"
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Cabang berhasil ditambahkan.',
            'data' => $cabang,
        ], 201);
    }

    /**
     * Edit data cabang.
     */
    public function update(Request $request, $id_cabang)
    {
        $cabang = CabangModel::find($id_cabang);

        if (!$cabang) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cabang tidak ditemukan.',
            ], 404);
        }

        // Store old data for audit log
        $oldData = $cabang->toArray();

        $validator = Validator::make($request->all(), [
            'nama_cabang' => 'required',
            'alamat' => 'required',
            'telepon' => 'required',
            'password_cabang' => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $cabang->nama_cabang = $request->nama_cabang ?? $cabang->nama_cabang;
        $cabang->alamat = $request->alamat ?? $cabang->alamat;
        $cabang->telepon = $request->telepon ?? $cabang->telepon;

        // Hash password jika ada perubahan
        if ($request->filled('password_cabang')) {
            $cabang->password_cabang = Hash::make($request->password_cabang);
        }

        $cabang->save();

        // Refresh to get updated data
        $cabang->refresh();

        // Log update
        AuditLogService::logUpdate(
            'cabang',
            (string)$cabang->id_cabang,
            $oldData,
            $cabang->toArray(),
            "Data cabang {$cabang->nama_cabang} berhasil diupdate"
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Cabang berhasil diupdate.',
            'data' => $cabang,
        ]);
    }

    /**
     * Hapus cabang.
     */
    public function destroy($id_cabang)
    {
        $cabang = CabangModel::find($id_cabang);

        if (!$cabang) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cabang tidak ditemukan.',
            ], 404);
        }

        // Store old data for audit log
        $oldData = $cabang->toArray();

        $cabang->delete();

        // Log deletion
        AuditLogService::logDelete(
            'cabang',
            (string)$id_cabang,
            $oldData,
            "Cabang {$oldData['nama_cabang']} berhasil dihapus"
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Cabang berhasil dihapus.',
        ], 200);
    }
}