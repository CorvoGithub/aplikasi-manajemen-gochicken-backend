<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UsersModel;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Services\AuditLogService;

class KasirController extends Controller
{
    /**
     * Menampilkan semua data kasir untuk cabang tertentu.
     */
    public function getKasirByCabang($id_cabang)
    {
        $kasir = UsersModel::where('id_cabang', $id_cabang)
            ->where('role', 'kasir')
            ->orderBy('nama', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $kasir,
        ]);
    }

    /**
     * Tambah kasir baru.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_cabang' => 'required|exists:cabang,id_cabang',
            'nama' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Generate default password (email without domain)
        $emailParts = explode('@', $request->email);
        $defaultPassword = $emailParts[0] . '123'; // username123

        $kasir = UsersModel::create([
            'nama' => $request->nama,
            'email' => $request->email,
            'password' => Hash::make($defaultPassword),
            'role' => 'kasir',
            'id_cabang' => $request->id_cabang,
        ]);

        // Log creation - FIX: Use the created kasir's id_user as record_id
        AuditLogService::logCreate(
            'users',
            (string)$kasir->id_user, // Convert to string for record_id
            $kasir->toArray(),
            "Kasir {$kasir->nama} berhasil ditambahkan dengan password default"
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Kasir berhasil ditambahkan.',
            'data' => $kasir,
        ], 201);
    }

    /**
     * Edit data kasir.
     */
    public function update(Request $request, $id_user)
    {
        $kasir = UsersModel::where('id_user', $id_user)
            ->where('role', 'kasir')
            ->first();

        if (!$kasir) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kasir tidak ditemukan.',
            ], 404);
        }

        // Store old data for audit log
        $oldData = $kasir->toArray();

        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email,' . $id_user . ',id_user',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $kasir->nama = $request->nama;
        $kasir->email = $request->email;
        $kasir->save();

        // Log update - FIX: Use string for record_id
        AuditLogService::logUpdate(
            'users',
            (string)$kasir->id_user, // Convert to string for record_id
            $oldData,
            $kasir->toArray(),
            "Data kasir {$kasir->nama} berhasil diupdate"
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Kasir berhasil diupdate.',
            'data' => $kasir,
        ]);
    }

    /**
     * Hapus kasir.
     */
    public function destroy($id_user)
    {
        $kasir = UsersModel::where('id_user', $id_user)
            ->where('role', 'kasir')
            ->first();

        if (!$kasir) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kasir tidak ditemukan.',
            ], 404);
        }

        // Store old data for audit log
        $oldData = $kasir->toArray();

        $kasir->delete();

        // Log deletion - FIX: Use string for record_id
        AuditLogService::logDelete(
            'users',
            (string)$id_user, // Convert to string for record_id
            $oldData,
            "Kasir {$oldData['nama']} berhasil dihapus"
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Kasir berhasil dihapus.',
        ], 200);
    }
}