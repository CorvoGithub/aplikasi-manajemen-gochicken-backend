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
use App\Services\AuditLogService;

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
            $detailProduk = [];
            
            foreach ($request->details as $item) {
                $produk = ProdukModel::find($item['id_produk']);
                if (!$produk) throw new Exception('Produk tidak ditemukan.');
                $totalHarga += $produk->harga * $item['jumlah_produk'];
                $detailProduk[] = [
                    'produk' => $produk->nama_produk,
                    'jumlah' => $item['jumlah_produk'],
                    'harga' => $produk->harga
                ];
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

            $stokChanges = [];
            
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
                
                $stokSebelum = $stok->jumlah_stok;
                $stok->decrement('jumlah_stok', $item['jumlah_produk']);
                $stokSesudah = $stok->jumlah_stok;
                
                $stokChanges[] = [
                    'produk' => $produk->nama_produk,
                    'stok_sebelum' => $stokSebelum,
                    'stok_sesudah' => $stokSesudah,
                    'jumlah_dipakai' => $item['jumlah_produk']
                ];
            }

            // Log creation
            AuditLogService::logCreate(
                'transaksi',
                (string)$transaksi->id_transaksi,
                [
                    'id_transaksi' => $transaksi->id_transaksi,
                    'kode_transaksi' => $transaksi->kode_transaksi,
                    'id_cabang' => $transaksi->id_cabang,
                    'nama_pelanggan' => $transaksi->nama_pelanggan,
                    'total_harga' => $transaksi->total_harga,
                    'status_transaksi' => $transaksi->status_transaksi,
                    'metode_pembayaran' => $transaksi->metode_pembayaran,
                    'detail_produk' => $detailProduk,
                    'stok_changes' => $stokChanges
                ],
                "Pemesanan baru dibuat untuk pelanggan {$transaksi->nama_pelanggan} dengan total Rp " . number_format($transaksi->total_harga, 0, ',', '.')
            );

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Pemesanan berhasil dibuat.'], 201);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Gagal membuat pesanan: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id_transaksi) {
        $validator = Validator::make($request->all(), [ 
            'status_transaksi' => 'required|in:OnLoan,Selesai', 
        ]);
        
        if ($validator->fails()) { 
            return response()->json(['status' => 'error', 'message' => 'Data tidak valid.'], 422); 
        }
        
        $transaksi = TransaksiModel::find($id_transaksi);
        
        if (!$transaksi) { 
            return response()->json(['status' => 'error', 'message' => 'Transaksi tidak ditemukan.'], 404); 
        }

        // Store old data for audit log
        $oldStatus = $transaksi->status_transaksi;
        
        $transaksi->status_transaksi = $request->status_transaksi;
        $transaksi->save();

        // Log update
        AuditLogService::logUpdate(
            'transaksi',
            (string)$transaksi->id_transaksi,
            ['status_transaksi' => $oldStatus],
            ['status_transaksi' => $transaksi->status_transaksi],
            "Status pemesanan {$transaksi->kode_transaksi} diupdate: {$oldStatus} → {$transaksi->status_transaksi}"
        );

        return response()->json(['status' => 'success', 'message' => 'Status pemesanan berhasil diupdate.']);
    }

    public function destroy($id_transaksi) {
        $transaksi = TransaksiModel::with('details')->find($id_transaksi);
        
        if (!$transaksi) { 
            return response()->json(['status' => 'error', 'message' => 'Transaksi tidak ditemukan.'], 404); 
        }
        
        DB::beginTransaction();
        try {
            // Store old data for audit log
            $oldData = $transaksi->toArray();
            $stokChanges = [];
            
            foreach ($transaksi->details as $item) {
                $stok = StokCabangModel::where('id_cabang', $transaksi->id_cabang)->where('id_produk', $item->id_produk)->first();
                if ($stok) { 
                    $stokSebelum = $stok->jumlah_stok;
                    $stok->increment('jumlah_stok', $item->jumlah_produk);
                    $stokSesudah = $stok->jumlah_stok;
                    
                    $stokChanges[] = [
                        'produk_id' => $item->id_produk,
                        'stok_sebelum' => $stokSebelum,
                        'stok_sesudah' => $stokSesudah,
                        'jumlah_dikembalikan' => $item->jumlah_produk
                    ];
                }
            }
            
            $transaksi->details()->delete();
            $transaksi->delete();

            // Log deletion
            AuditLogService::logDelete(
                'transaksi',
                (string)$id_transaksi,
                $oldData,
                "Pemesanan {$oldData['kode_transaksi']} untuk pelanggan {$oldData['nama_pelanggan']} berhasil dihapus. Stok dikembalikan."
            );

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Pemesanan berhasil dihapus.']);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Gagal menghapus pesanan: ' . $e->getMessage()], 500);
        }
    }
}