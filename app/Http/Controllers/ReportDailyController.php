<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportDailyController extends Controller
{
    // ✅ GET laporan harian lengkap
    public function getDailyReport(Request $request)
    {
        $tanggal = $request->query('tanggal', date('Y-m-d'));

        // === PENJUALAN ===
        $penjualan = DB::table('detail_transaksi')
            ->join('transaksi', 'detail_transaksi.id_transaksi', '=', 'transaksi.id_transaksi')
            ->join('produk', 'detail_transaksi.id_produk', '=', 'produk.id_produk')
            ->select(
                'produk.nama_produk as produk',
                DB::raw('SUM(detail_transaksi.jumlah_produk) as jumlah_produk'),
                DB::raw('AVG(detail_transaksi.harga_item) as harga_item'),
                DB::raw('SUM(detail_transaksi.jumlah_produk * detail_transaksi.harga_item) as total_penjualan_produk')
            )
            ->whereDate('transaksi.tanggal_waktu', $tanggal)
            ->where('transaksi.status_transaksi', 'Selesai')
            ->groupBy('produk.nama_produk')
            ->get();

        $total_penjualan = $penjualan->sum('total_penjualan_produk');

        // === BAHAN BAKU ===
        $bahan_baku = DB::table('bahan_baku_harian')
            ->join('bahan_baku', 'bahan_baku_harian.id_bahan_baku', '=', 'bahan_baku.id_bahan_baku')
            ->select(
                'bahan_baku.nama_bahan',
                'bahan_baku.satuan',
                'bahan_baku.harga_satuan',
                'bahan_baku_harian.jumlah_pakai',
                DB::raw('(bahan_baku.harga_satuan * bahan_baku_harian.jumlah_pakai) as modal_produk')
            )
            ->whereDate('bahan_baku_harian.tanggal', $tanggal)
            ->get();


        $total_modal = $bahan_baku->sum('modal_produk');

        // === PENGELUARAN ===
        $pengeluaran = DB::table('pengeluaran')->select('keterangan', 'jumlah', 'cicilan_harian')->get();
        $pengeluaran_harian = $pengeluaran->sum('cicilan_harian');
        $total_pengeluaran_bulanan = $pengeluaran->sum('jumlah');

        // === ONLOAN ===
        $onloan = DB::table('transaksi')
            ->whereDate('tanggal_waktu', $tanggal)
            ->where('status_transaksi', 'OnLoan')
            ->sum('total_harga');

        // === LABA & NETT ===
        $laba_harian = $total_penjualan - ($total_modal + $pengeluaran_harian);
        $nett_income = $laba_harian - 100000; // misalnya potongan tetap (opsional)

        // === WARNING ===
        $peringatan = null;
        if (($total_penjualan + $onloan) < ($total_modal + $pengeluaran_harian)) {
            $peringatan = "⚠️ Pendapatan hari ini lebih kecil dari total pengeluaran dan modal";
        }

        return response()->json([
            'tanggal' => $tanggal,
            'penjualan' => [
                'detail' => $penjualan,
                'total_penjualan' => $total_penjualan
            ],
            'bahan_baku' => [
                'detail' => $bahan_baku,
                'total_modal_bahan_baku' => $total_modal
            ],
            'pengeluaran' => [
                'cicilan_harian' => $pengeluaran_harian,
                'total_pengeluaran_bulanan' => $total_pengeluaran_bulanan
            ],
            'onloan' => $onloan,
            'penjualan_harian' => $total_penjualan,
            'modal_bahan_baku' => $total_modal,
            'pengeluaran_harian' => $pengeluaran_harian,
            'laba_harian' => $laba_harian,
            'nett_income' => $nett_income,
            'peringatan' => $peringatan
        ]);
    }

    // ✅ UPDATE STATUS ORDER dari halaman laporan
    public function updateOrderStatus(Request $request, $id)
    {
        $status = $request->input('status_order');

        DB::table('transaksi')->where('id', $id)->update([
            'status_transaksi' => $status
        ]);

        return response()->json(['message' => 'Status order berhasil diperbarui']);
    }
}
