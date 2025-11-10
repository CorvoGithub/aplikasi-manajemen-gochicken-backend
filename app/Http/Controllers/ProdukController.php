<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use App\Models\ProdukModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\StokCabangModel;
use App\Services\AuditLogService;

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

        // Log creation
        AuditLogService::logCreate(
            'produk',
            $produk->id_produk,
            $produk->toArray(),
            "Produk {$produk->nama_produk} berhasil ditambahkan dengan harga Rp " . number_format($produk->harga, 0, ',', '.')
        );

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

        // Store old data for audit log
        $oldData = $produk->toArray();

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

        // Refresh to get updated data
        $produk->refresh();

        // Log update
        AuditLogService::logUpdate(
            'produk',
            $produk->id_produk,
            $oldData,
            $produk->toArray(),
            "Produk {$produk->nama_produk} berhasil diupdate - Harga: Rp " . number_format($oldData['harga'], 0, ',', '.') . " → Rp " . number_format($produk->harga, 0, ',', '.')
        );

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

        // Store old data for audit log
        $oldData = $produk->toArray();

        $produk->delete();

        // Log deletion
        AuditLogService::logDelete(
            'produk',
            $id_produk,
            $oldData,
            "Produk {$oldData['nama_produk']} berhasil dihapus"
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Produk berhasil dihapus.',
        ], 200);
    }

    public function getProdukByCabang($id_cabang)
    {
        try {
            $produkStok = ProdukModel::select(
                'produk.id_produk',
                'produk.nama_produk',
                'produk.kategori',
                'produk.harga',
                'produk.gambar_produk',
                'stok_cabang.jumlah_stok',
                'stok_cabang.id_stock_cabang'
            )
            ->join('stok_cabang', 'produk.id_produk', '=', 'stok_cabang.id_produk')
            ->where('stok_cabang.id_cabang', $id_cabang)
            ->orderBy('produk.nama_produk', 'asc')
            ->get();

            // Append the full URL for the image
            $produkStok->each(function ($item) {
                if ($item->gambar_produk) {
                    $item->gambar_url = url(Storage::url($item->gambar_produk));
                }
            });

            return response()->json([
                'status' => 'success',
                'data' => $produkStok,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil data produk cabang.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateStok(Request $request, $id_stock_cabang)
    {
        $validator = Validator::make($request->all(), [
            'jumlah' => 'required|integer', // e.g., +1 or -1
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }
        
        try {
            $stok = StokCabangModel::findOrFail($id_stock_cabang);

            // Store old data for audit log
            $oldStok = $stok->jumlah_stok;
            $produk = $stok->produk;

            // Use a transaction to ensure data integrity
            DB::transaction(function () use ($stok, $request) {
                $newStok = $stok->jumlah_stok + $request->jumlah;
                
                // Prevent stock from going below zero
                if ($newStok < 0) {
                    throw new \Exception("Stok tidak boleh kurang dari nol.");
                }
                
                $stok->jumlah_stok = $newStok;
                $stok->save();
            });

            // Refresh to get updated data
            $stok->refresh();

            // Log stock update
            AuditLogService::logUpdate(
                'stok_cabang',
                $stok->id_stock_cabang,
                ['jumlah_stok' => $oldStok],
                ['jumlah_stok' => $stok->jumlah_stok],
                "Stok produk {$produk->nama_produk} diupdate: {$oldStok} → {$stok->jumlah_stok} (Perubahan: {$request->jumlah})"
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Stok berhasil diupdate.',
                'data' => $stok,
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
             return response()->json([
                'status' => 'error',
                'message' => 'Data stok tidak ditemukan.',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400); // Bad request (e.g., trying to make stock negative)
        }
    }
}