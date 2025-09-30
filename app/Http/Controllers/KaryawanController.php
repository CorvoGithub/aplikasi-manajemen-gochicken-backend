<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\KaryawanModel;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class KaryawanController extends Controller
{
    /**
     * Menampilkan semua data karyawan.
     */
    public function index()
    {
        $karyawan = KaryawanModel::with('cabang')->get();

        return response()->json([
            'status' => 'success',
            'data' => $karyawan,
        ]);
    }

    /**
     * Tambah cabang baru.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_cabang' => 'required',
            'nama_karyawan' => 'required',
            'alamat' => 'required',
            'telepon' => 'required',
            'gaji' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $karyawan = KaryawanModel::create([
            'id_karyawan' => $request->id_karyawan,
            'id_cabang' => $request->id_cabang,
            'nama_karyawan' => $request->nama_karyawan,
            'alamat' => $request->alamat,
            'telepon' => $request->telepon,
            'gaji' => $request->gaji,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Karyawan berhasil ditambahkan.',
            'data' => $karyawan,
        ], 201);
    }

    /**
     * Edit data cabang.
     */
    public function update(Request $request, $id_karyawan)
    {
        $karyawan = KaryawanModel::find($id_karyawan);

        if (!$karyawan) {
            return response()->json([
                'status' => 'error',
                'message' => 'Karyawan tidak ditemukan.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nama_karyawan' => 'required|string|max:255',
            'alamat' => 'required|string|max:255',
            'telepon' => 'required|string|max:20',
            'gaji' => 'required',
            'id_cabang' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $karyawan->nama_karyawan = $request->nama_karyawan ?? $karyawan->nama_karyawan;
        $karyawan->alamat = $request->alamat ?? $karyawan->alamat;
        $karyawan->telepon = $request->telepon ?? $karyawan->telepon;
        $karyawan->gaji = $request->gaji ?? $karyawan->gaji;
        $karyawan->id_cabang = $request->id_cabang ?? $karyawan->id_cabang;

        // Hash password jika ada perubahan
        if ($request->filled('password_karyawan')) {
            $karyawan->password_karyawan = Hash::make($request->password_karyawan);
        }

        $karyawan->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Karyawan berhasil diupdate.',
            'data' => $karyawan,
        ]);
    }

    /**
     * Hapus cabang.
     */
    public function destroy($id_karyawan)
    {
        $karyawan = KaryawanModel::find($id_karyawan);

        if (!$karyawan) {
            return response()->json([
                'status' => 'error',
                'message' => 'Karyawan tidak ditemukan.',
            ], 404);
        }

        $karyawan->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Karyawan berhasil dihapus.',
        ], 200);
    }
}
