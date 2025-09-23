<?php

namespace App\Http\Controllers;

use App\Models\UsersModel;
use App\Models\CabangModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ManageAdminCabangController extends Controller
{
    /**
     * Tampilkan daftar admin cabang yang ada.
     */
    public function listAdmin()
    {
        $admin = UsersModel::where('role', 'admin cabang')
            ->with('cabang')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $admin,
        ]);
    }

    /**
     * Buat akun admin cabang baru.
     */
    public function createAdminCabang(Request $request)
    {
        // 1. Validasi data yang masuk
        $validator = Validator::make($request->all(), [
            // 'id_user' => 'required',
            'nama' => 'required',
            'email' => 'required',
            'password' => 'required',
            'id_cabang' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data tidak valid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        // 2. Pastikan cabang yang dipilih belum memiliki admin cabang
        // Menggunakan findOrFail untuk memastikan cabang ada
        $cabang = CabangModel::findOrFail($request->id_cabang);

        if ($cabang->user()->where('role', 'admin cabang')->exists()) {
            throw ValidationException::withMessages([
                'id_cabang' => ['Cabang ini sudah memiliki admin cabang.'],
            ]);
        }

        // 3. Simpan data user baru dengan password pribadi yang di-hash
        $user = UsersModel::create([
            'id_user' => $request->id_user,
            'nama' => $request->nama,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'admin cabang',
            'id_cabang' => $request->id_cabang,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Akun admin cabang berhasil dibuat.',
            'data' => $user,
        ], 201); // 201 Created
    }

    public function getCabangWithoutAdmin() {
        // Logika untuk mendapatkan cabang yang belum memiliki admin
        $cabangWithAdmin = UsersModel::where('role', 'admin cabang')
                                    ->pluck('id_cabang');

        $cabangWithoutAdmin = CabangModel::whereNotIn('id_cabang', $cabangWithAdmin)
                                        ->get();

        return response()->json([
            'status' => 'success',
            'data' => $cabangWithoutAdmin,
        ]);
    }

    public function updateAdminCabang($id_user, Request $request) {
        // isi logika update
        return response()->json(['message' => 'Update logic for admin cabang.']);
    }

    public function deleteAdminCabang($id_user) {
        $admin = UsersModel::find($id_user);

        if (!$admin) {
            return response()->json([
                'status' => 'error',
                'message' => 'Admin tidak ditemukan.',
            ], 404);
        }

        $admin->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Admin berhasil dihapus.',
        ], 200);
    }
}
