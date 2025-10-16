<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TransaksiModel;
use App\Models\DetailTransaksiModel;
use App\Models\StokCabangModel;
use App\Models\ProdukModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Exception;

class PemesananController extends Controller
{
    public function index(Request $request, $id_cabang)
    {
        $query = TransaksiModel::where('id_cabang', $id_cabang)
            ->whereNotNull('nama_pelanggan')
            ->with(['details.produk']);

        if ($request->has('status') && $request->status !== 'semua') {
            $query->where('status_transaksi', $request->status);
        }

        $filter = $request->query('filter');
        if ($filter === 'minggu') {
            $query->whereBetween('tanggal_waktu', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
        } elseif ($filter === 'bulan') {
            $query->whereMonth('tanggal_waktu', Carbon::now()->month)->whereYear('tanggal_waktu', Carbon::now()->year);
        } elseif ($filter === 'tahun') {
            $query->whereYear('tanggal_waktu', Carbon::now()->year);
        } elseif ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('tanggal_waktu', [$request->start_date, Carbon::parse($request->end_date)->endOfDay()]);
        }

        $pemesanan = $query->orderBy('tanggal_waktu', 'desc')->paginate(10);
        return response()->json($pemesanan);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_cabang' => 'required|exists:cabang,id_cabang',
            'nama_pelanggan' => 'required|string|max:255',
            'metode_pembayaran' => 'required|string',
            'status_transaksi' => 'required|in:OnLoan,Selesai',
            'details' => 'required|array|min:1',
            'details.*.id_produk' => 'required|exists:produk,id_produk',
            'details.*.jumlah_produk' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Data tidak valid.', 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $totalHarga = 0;
            foreach ($request->details as $item) {
                $produk = ProdukModel::find($item['id_produk']);
                if (!$produk) throw new Exception('Produk tidak ditemukan.');
                $totalHarga += $produk->harga * $item['jumlah_produk'];
            }
            
            // ✨ PERBAIKAN: Membuat format kode transaksi yang benar
            $today = Carbon::now();
            $kodeTransaksi = 'TRNSK-' . $today->format('dmY-Hi');

            $transaksi = TransaksiModel::create([
                'id_cabang' => $request->id_cabang,
                'nama_pelanggan' => $request->nama_pelanggan,
                'tanggal_waktu' => $today,
                'metode_pembayaran' => $request->metode_pembayaran,
                'status_transaksi' => $request->status_transaksi,
                'total_harga' => $totalHarga,
                'kode_transaksi' => $kodeTransaksi,
            ]);

            foreach ($request->details as $item) {
                $produk = ProdukModel::find($item['id_produk']);
                DetailTransaksiModel::create([
                    'id_transaksi' => $transaksi->id_transaksi,
                    'id_produk' => $item['id_produk'],
                    'jumlah_produk' => $item['jumlah_produk'],
                    'harga_item' => $produk->harga,
                    'subtotal' => $item['jumlah_produk'] * $produk->harga,
                ]);

                $stok = StokCabangModel::where('id_cabang', $request->id_cabang)->where('id_produk', $item['id_produk'])->first();
                if (!$stok || $stok->jumlah_stok < $item['jumlah_produk']) {
                    throw new Exception('Stok untuk produk "' . $produk->nama_produk . '" tidak mencukupi.');
                }
                $stok->decrement('jumlah_stok', $item['jumlah_produk']);
            }

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Pemesanan berhasil dibuat.'], 201);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Gagal membuat pesanan: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id_transaksi) {
        $validator = Validator::make($request->all(), [ 'status_transaksi' => 'required|in:OnLoan,Selesai', ]);
        if ($validator->fails()) { return response()->json(['status' => 'error', 'message' => 'Data tidak valid.'], 422); }
        $transaksi = TransaksiModel::find($id_transaksi);
        if (!$transaksi) { return response()->json(['status' => 'error', 'message' => 'Transaksi tidak ditemukan.'], 404); }
        $transaksi->status_transaksi = $request->status_transaksi;
        $transaksi->save();
        return response()->json(['status' => 'success', 'message' => 'Status pemesanan berhasil diupdate.']);
    }

    public function destroy($id_transaksi) {
        $transaksi = TransaksiModel::with('details')->find($id_transaksi);
        if (!$transaksi) { return response()->json(['status' => 'error', 'message' => 'Transaksi tidak ditemukan.'], 404); }
        DB::beginTransaction();
        try {
            foreach ($transaksi->details as $item) {
                $stok = StokCabangModel::where('id_cabang', $transaksi->id_cabang)->where('id_produk', $item->id_produk)->first();
                if ($stok) { $stok->increment('jumlah_stok', $item->jumlah_produk); }
            }
            $transaksi->details()->delete();
            $transaksi->delete();
            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Pemesanan berhasil dihapus.']);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Gagal menghapus pesanan: ' . $e->getMessage()], 500);
        }
    }
}

