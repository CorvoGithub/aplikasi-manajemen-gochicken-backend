<?php

namespace App\Http\Controllers;

use App\Models\TransaksiModel;
use App\Models\DetailTransaksiModel;
use App\Models\StokCabangModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use Carbon\Carbon;

class AndroidTransaksiController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'kode_transaksi' => 'required|string',
            'id_cabang' => 'required|exists:cabang,id_cabang',
            'nama_pelanggan' => 'required|string|max:255',
            'metode_pembayaran' => 'required|string',
            'status_pembayaran' => 'required|in:OnLoan,Selesai',
            'total_harga' => 'required|numeric',
            'items' => 'required|array|min:1',
            'items.*.id_produk' => 'required|exists:produk,id_produk',
            'items.*.jumlah_produk' => 'required|integer|min:1',
            'items.*.harga_item' => 'required|numeric',
            'items.*.subtotal' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak valid.',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $transaksi = TransaksiModel::create([
                'kode_transaksi' => $request->kode_transaksi,
                'id_cabang' => $request->id_cabang,
                'nama_pelanggan' => $request->nama_pelanggan,
                'tanggal_waktu' => $request->tanggal_waktu ?? now(),
                'metode_pembayaran' => $request->metode_pembayaran,
                'status_transaksi' => $request->status_pembayaran,
                'total_harga' => $request->total_harga,
                'source' => 'android', // ← ADD THIS LINE
            ]);

            foreach ($request->items as $item) {
                DetailTransaksiModel::create([
                    'id_transaksi' => $transaksi->id_transaksi,
                    'id_produk' => $item['id_produk'],
                    'jumlah_produk' => $item['jumlah_produk'],
                    'harga_item' => $item['harga_item'],
                    'subtotal' => $item['subtotal'],
                ]);

                // Update stok
                $stok = StokCabangModel::where('id_cabang', $request->id_cabang)
                                    ->where('id_produk', $item['id_produk'])
                                    ->first();

                if (!$stok || $stok->jumlah_stok < $item['jumlah_produk']) {
                    throw new Exception('Stok tidak mencukupi untuk produk ID: ' . $item['id_produk']);
                }
                $stok->decrement('jumlah_stok', $item['jumlah_produk']);
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Transaksi berhasil dibuat.',
                'data' => $transaksi
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Transaction error: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Gagal membuat transaksi: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getTransaksiByCabang(Request $request, $id_cabang)
    {
        try {
            $query = TransaksiModel::with('detail.produk', 'cabang')
                ->where('id_cabang', $id_cabang)
                ->orderBy('tanggal_waktu', 'desc');

            // Filter 1: Status (semua, OnLoan, Selesai)
            if ($request->has('status') && $request->status !== 'semua') {
                $query->where('status_transaksi', $request->status);
            }

            // Filter 2: Waktu (minggu, bulan, tahun) - dari PemesananPage.jsx
            if ($request->has('filter') && $request->filter !== 'custom') {
                $now = Carbon::now();
                if ($request->filter === 'minggu') {
                    $query->whereBetween('tanggal_waktu', [$now->startOfWeek(), $now->endOfWeek()]);
                } elseif ($request->filter === 'bulan') {
                    $query->whereMonth('tanggal_waktu', $now->month)->whereYear('tanggal_waktu', $now->year);
                } elseif ($request->filter === 'tahun') {
                    $query->whereYear('tanggal_waktu', $now->year);
                }
            }

            // Filter 3: Tanggal Kustom - dari PemesananPage.jsx
            if ($request->has('start_date') && $request->start_date && $request->has('end_date') && $request->end_date) {
                $startDate = Carbon::parse($request->start_date)->startOfDay();
                $endDate = Carbon::parse($request->end_date)->endOfDay();
                $query->whereBetween('tanggal_waktu', [$startDate, $endDate]);
            }

            // Pagination (sesuai dengan PemesananPage.jsx)
            $transaksi = $query->paginate(20);

            return response()->json($transaksi);

        } catch (Exception $e) {
            Log::error('Get Transaksi by Cabang error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil data transaksi: ' . $e->getMessage()
            ], 500);
        }
    }
}