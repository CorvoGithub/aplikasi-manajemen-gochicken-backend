<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use App\Models\ProdukModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProdukController extends Controller
{
    /**
     * Menampilkan semua data produk.
     */
    public function index()
    {
        $produk = ProdukModel::all();

        return response()->json([
            'status' => 'success',
            'data' => $produk,
        ]);
    }

    /**
     * Tambah produk baru.
     */
    // In App\Http\Controllers\ProdukController.php

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama_produk' => 'required',
            'kategori' => 'required',
            'deskripsi' => 'required',
            'harga' => 'required|numeric',
            'gambar_produk' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Cek dan simpan gambar terlebih dahulu
        $gambarPath = null;
        if ($request->hasFile('gambar_produk')) {
            $gambarPath = $request->file('gambar_produk')->store('produk_images', 'public');
        }

        $produkData = $request->only(['nama_produk', 'kategori', 'deskripsi', 'harga']);
        $produkData['gambar_produk'] = $gambarPath;

        if ($request->filled('id_stock_cabang')) {
            $produkData['id_stock_cabang'] = $request->id_stock_cabang;
        }

        // Buat produk dengan data gambar yang sudah ada
        $produk = ProdukModel::create($produkData);

        return response()->json([
            'status' => 'success',
            'message' => 'Produk berhasil ditambahkan.',
            'data' => $produk,
        ], 201);
    }


    /**
     * Edit data produk.
     */
    public function update(Request $request, $id_produk)
    {
        $produk = ProdukModel::find($id_produk);

        if (!$produk) {
            return response()->json([
                'status' => 'error',
                'message' => 'Produk tidak ditemukan.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nama_produk' => 'required',
            'kategori' => 'required',
            'deskripsi' => 'required',
            'harga' => 'required|numeric',
            'id_stock_cabang' => 'nullable',
            'gambar_produk' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $produk->nama_produk = $request->nama_produk;
        $produk->kategori = $request->kategori;
        $produk->deskripsi = $request->deskripsi;
        $produk->harga = $request->harga;
        $produk->id_stock_cabang = $request->id_stock_cabang;

        // Tambahkan logika untuk menghapus gambar lama jika ada gambar baru
        if ($request->hasFile('gambar_produk')) {
            // Hapus gambar lama jika ada
            if ($produk->gambar_produk && Storage::disk('public')->exists($produk->gambar_produk)) {
                Storage::disk('public')->delete($produk->gambar_produk);
            }
            $produk->gambar_produk = $request->file('gambar_produk')->store('produk_images', 'public');
        }

        $produk->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Produk berhasil diupdate.',
            'data' => $produk,
        ]);
    }

    /**
     * Hapus produk.
     */
    public function destroy($id_produk)
    {
        $produk = ProdukModel::find($id_produk);

        if (!$produk) {
            return response()->json([
                'status' => 'error',
                'message' => 'Produk tidak ditemukan.',
            ], 404);
        }

        $produk->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Produk berhasil dihapus.',
        ], 200);
    }
}
