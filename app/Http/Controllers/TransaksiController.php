<?php

namespace App\Http\Controllers;

use App\Models\TransaksiModel;
use App\Models\DetailTransaksiModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TransaksiController extends Controller
{
    /**
     * Menampilkan semua transaksi dengan detailnya.
     */
    public function index()
    {
        $transaksi = TransaksiModel::with('detail.produk', 'cabang')->get();

        return response()->json([
            'status' => 'success',
            'data' => $transaksi,
        ]);
    }

    /**
     * Simpan transaksi baru dari Android app.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama_pelanggan'     => 'nullable',
            'metode_pembayaran'  => 'required',
            'id_cabang'          => 'required',
            'items'              => 'required',
            'items.*.id_produk'  => 'required',
            'items.*.jumlah'     => 'required',
            'items.*.harga'      => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validasi gagal.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Generate unique ID & kode transaksi
            $idTransaksi   = (string) Str::uuid();
            $kodeTransaksi = 'TRNSK-' . now()->format('d-m-Y-H:i');

            $totalHarga = 0;
            foreach ($request->items as $item) {
                $totalHarga += $item['jumlah'] * $item['harga'];
            }

            // Simpan header transaksi
            $transaksi = TransaksiModel::create([
                'id_transaksi'     => $idTransaksi,
                'kode_transaksi'   => $kodeTransaksi,
                'tanggal_waktu'    => now(),
                'total_harga'      => $totalHarga,
                'metode_pembayaran'=> $request->metode_pembayaran,
                'nama_pelanggan'   => $request->nama_pelanggan,
                'id_cabang'        => $request->id_cabang,
            ]);

            // Simpan detail transaksi
            foreach ($request->items as $item) {
                DetailTransaksiModel::create([
                    'id_transaksi'    => $transaksi->id_transaksi,
                    'id_produk'       => $item['id_produk'],
                    'jumlah_produk'   => $item['jumlah'],
                    'harga_item'      => $item['harga'],
                    'subtotal'        => $item['jumlah'] * $item['harga'],
                ]);
            }


            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Transaksi berhasil disimpan.',
                'data'    => $transaksi->load('detail.produk'),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal menyimpan transaksi.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Menampilkan detail transaksi tertentu.
     */
    public function show($id_transaksi)
    {
        $transaksi = TransaksiModel::with('detail.produk', 'cabang')->find($id_transaksi);

        if (!$transaksi) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Transaksi tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data'   => $transaksi,
        ]);
    }

    /**
     * Hapus transaksi beserta detailnya.
     */
    public function destroy($id_transaksi)
    {
        $transaksi = TransaksiModel::find($id_transaksi);

        if (!$transaksi) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Transaksi tidak ditemukan.',
            ], 404);
        }

        DB::beginTransaction();
        try {
            DetailTransaksiModel::where('id_transaksi', $id_transaksi)->delete();
            $transaksi->delete();

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Transaksi berhasil dihapus.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal menghapus transaksi.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
