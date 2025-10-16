<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Fetch all reports for the dashboard of a specific branch.
     */
    public function cabangReport(Request $request, $id)
    {
        $filter = $request->query('filter', 'bulan'); // default 'bulan'

        // 1. DATA FOR SALES TREND CHART
        $salesTrend = DB::table('transaksi')
            ->where('id_cabang', $id)
            ->when($filter === 'minggu', function ($q) {
                $q->whereBetween('tanggal_waktu', [now()->startOfWeek(Carbon::SUNDAY), now()->endOfWeek(Carbon::SATURDAY)])
                    ->select(
                        DB::raw('DAYNAME(tanggal_waktu) as period'),
                        DB::raw('SUM(total_harga) as total_pendapatan'),
                        DB::raw('COUNT(id_transaksi) as jumlah_transaksi')
                    )
                    ->groupBy('period')
                    ->orderBy(DB::raw('DAYOFWEEK(tanggal_waktu)'));
            })
            ->when($filter === 'bulan', function ($q) {
                $q->whereMonth('tanggal_waktu', now()->month)->whereYear('tanggal_waktu', now()->year)
                    ->select(
                        DB::raw("CONCAT('Minggu ', WEEK(tanggal_waktu, 5) - WEEK(DATE_FORMAT(tanggal_waktu, '%Y-%m-01'), 5) + 1) as period"),
                        DB::raw('SUM(total_harga) as total_pendapatan'),
                        DB::raw('COUNT(id_transaksi) as jumlah_transaksi')
                    )
                    ->groupBy('period')
                    ->orderBy('period');
            })
            ->when($filter === 'tahun', function ($q) {
                $q->whereYear('tanggal_waktu', now()->year)
                    ->select(
                        DB::raw('MONTHNAME(tanggal_waktu) as period'),
                        DB::raw('SUM(total_harga) as total_pendapatan'),
                        DB::raw('COUNT(id_transaksi) as jumlah_transaksi')
                    )
                    ->groupBy('period')
                    ->orderBy(DB::raw('MONTH(tanggal_waktu)'));
            })
            ->get();

        // 2. DATA FOR TOP 5 PRODUCTS CHART
        $topProducts = DB::table('detail_transaksi')
            ->join('transaksi', 'detail_transaksi.id_transaksi', '=', 'transaksi.id_transaksi')
            ->join('produk', 'detail_transaksi.id_produk', '=', 'produk.id_produk')
            ->where('transaksi.id_cabang', $id)
            ->select('produk.nama_produk', DB::raw('SUM(detail_transaksi.jumlah_produk) as total_terjual'))
            ->groupBy('produk.nama_produk')
            ->orderByDesc('total_terjual')
            ->limit(5)
            ->get();

        // 3. DATA FOR SUMMARY CARDS
        $totalPendapatan = DB::table('transaksi')->where('id_cabang', $id)->sum('total_harga');
        $totalTransaksi = DB::table('transaksi')->where('id_cabang', $id)->count();
        $avgTransaksi = $totalTransaksi > 0 ? $totalPendapatan / $totalTransaksi : 0;

        $busiestDay = DB::table('transaksi')
            ->where('id_cabang', $id)
            ->select(DB::raw('DAYNAME(tanggal_waktu) as day'), DB::raw('COUNT(*) as count'))
            ->groupBy('day')
            ->orderByDesc('count')
            ->first();

        return response()->json([
            'status' => 'success',
            'data' => [
                'salesTrend' => $salesTrend,
                'topProducts' => $topProducts,
                'summary' => [
                    'totalPendapatan' => (float)$totalPendapatan,
                    'totalTransaksi' => (int)$totalTransaksi,
                    'avgTransaksi' => (float)$avgTransaksi,
                    'produkTerlaris' => $topProducts->first() ? $topProducts->first()->nama_produk : 'N/A',
                    'hariTersibuk' => $busiestDay ? $busiestDay->day : 'N/A',
                ]
            ]
        ]);
    }

    /**
     * Fetch all reports across all branches for Super Admin dashboard.
     */
    public function allCabangReport(Request $request)
    {
        $filter = $request->query('filter', 'bulan'); // default bulan

        // 1. SALES TREND (gabungan semua cabang)
        $salesTrend = DB::table('transaksi')
            ->when($filter === 'minggu', function ($q) {
                $q->whereBetween('tanggal_waktu', [now()->startOfWeek(Carbon::SUNDAY), now()->endOfWeek(Carbon::SATURDAY)])
                    ->select(
                        DB::raw('DAYNAME(tanggal_waktu) as period'),
                        DB::raw('SUM(total_harga) as total_pendapatan'),
                        DB::raw('COUNT(id_transaksi) as jumlah_transaksi')
                    )
                    ->groupBy('period')
                    ->orderBy(DB::raw('DAYOFWEEK(tanggal_waktu)'));
            })
            ->when($filter === 'bulan', function ($q) {
                $q->whereMonth('tanggal_waktu', now()->month)->whereYear('tanggal_waktu', now()->year)
                    ->select(
                        DB::raw("CONCAT('Minggu ', WEEK(tanggal_waktu, 5) - WEEK(DATE_FORMAT(tanggal_waktu, '%Y-%m-01'), 5) + 1) as period"),
                        DB::raw('SUM(total_harga) as total_pendapatan'),
                        DB::raw('COUNT(id_transaksi) as jumlah_transaksi')
                    )
                    ->groupBy('period')
                    ->orderBy('period');
            })
            ->when($filter === 'tahun', function ($q) {
                $q->whereYear('tanggal_waktu', now()->year)
                    ->select(
                        DB::raw('MONTHNAME(tanggal_waktu) as period'),
                        DB::raw('SUM(total_harga) as total_pendapatan'),
                        DB::raw('COUNT(id_transaksi) as jumlah_transaksi')
                    )
                    ->groupBy('period')
                    ->orderBy(DB::raw('MONTH(tanggal_waktu)'));
            })
            ->get();

        // 2. TOP 5 PRODUCTS (gabungan semua cabang)
        $topProducts = DB::table('detail_transaksi')
            ->join('transaksi', 'detail_transaksi.id_transaksi', '=', 'transaksi.id_transaksi')
            ->join('produk', 'detail_transaksi.id_produk', '=', 'produk.id_produk')
            ->select('produk.nama_produk', DB::raw('SUM(detail_transaksi.jumlah_produk) as total_terjual'))
            ->groupBy('produk.nama_produk')
            ->orderByDesc('total_terjual')
            ->limit(5)
            ->get();

        // 3. SUMMARY (gabungan semua cabang)
        $totalPendapatan = DB::table('transaksi')->sum('total_harga');
        $totalTransaksi = DB::table('transaksi')->count();
        $avgTransaksi = $totalTransaksi > 0 ? $totalPendapatan / $totalTransaksi : 0;

        $busiestDay = DB::table('transaksi')
            ->select(DB::raw('DAYNAME(tanggal_waktu) as day'), DB::raw('COUNT(*) as count'))
            ->groupBy('day')
            ->orderByDesc('count')
            ->first();

        return response()->json([
            'status' => 'success',
            'data' => [
                'salesTrend' => $salesTrend,
                'topProducts' => $topProducts,
                'summary' => [
                    'totalPendapatan' => (float)$totalPendapatan,
                    'totalTransaksi' => (int)$totalTransaksi,
                    'avgTransaksi' => (float)$avgTransaksi,
                    'produkTerlaris' => $topProducts->first() ? $topProducts->first()->nama_produk : 'N/A',
                    'hariTersibuk' => $busiestDay ? $busiestDay->day : 'N/A',
                ]
            ]
        ]);
    }

    public function productReportSuperAdmin(Request $request)
    {
        $products = DB::table('produk as p')
            ->join('stok_cabang as sc', 'p.id_produk', '=', 'sc.id_produk')
            ->join('cabang as c', 'sc.id_cabang', '=', 'c.id_cabang')
            ->select(
                'p.nama_produk',
                'p.kategori',
                'c.nama_cabang as cabang',
                'p.harga',
                'sc.jumlah_stok'
            )
            ->select('p.nama_produk', 'p.kategori', 'c.nama_cabang as cabang', 'p.harga', 'sc.jumlah_stok')
            ->orderBy('p.nama_produk')
            ->paginate($request->get('limit', 10));

        return response()->json($products);
    }

    /**
     * Fetch paginated list of sales transactions for super admin across all branches.
     */
    public function salesTransactionsSuperAdmin(Request $request)
    {
        $transaksi = DB::table('transaksi')
            ->select('kode_transaksi', 'tanggal_waktu', 'metode_pembayaran', 'total_harga')
            ->orderByDesc('tanggal_waktu')
            ->paginate($request->get('limit', 10));

        return response()->json($transaksi);
    }

    /**
     * Fetch paginated list of expenses for super admin across all branches.
     */
    public function salesExpensesSuperAdmin(Request $request)
    {
        $pengeluaran = DB::table('pengeluaran as p')
            ->join('jenis_pengeluaran as jp', 'p.id_jenis', '=', 'jp.id_jenis')
            ->select('p.tanggal', 'jp.jenis_pengeluaran as jenis', 'p.jumlah', 'p.keterangan')
            ->orderByDesc('p.tanggal')
            ->paginate($request->get('limit', 10));

        return response()->json($pengeluaran);
    }

    public function employeeReportSuperAdmin(Request $request)
    {
        $karyawan = DB::table('karyawan')
            ->select('nama_karyawan', 'alamat', 'telepon', 'gaji')
            ->orderBy('nama_karyawan')
            ->paginate($request->get('limit', 10));

        return response()->json($karyawan);
    }


    //----------------------------------------------------------------------------------
    // All new paginated report methods below
    //----------------------------------------------------------------------------------

    /**
     * Fetch paginated list of products for a specific branch.
     */
    public function productReportPaginated(Request $request, $id)
    {
        $products = DB::table('produk as p')
            ->join('stok_cabang as sc', 'p.id_produk', '=', 'sc.id_produk')

            ->where('sc.id_cabang', $id)
            ->select('p.nama_produk', 'p.kategori', 'p.harga', 'sc.jumlah_stok')
            ->orderBy('p.nama_produk')
            ->paginate($request->get('limit', 10));

        return response()->json($products);
    }

    /**
     * Fetch paginated list of employees for a specific branch.
     */
    public function employeeReportPaginated(Request $request, $id)
    {
        $karyawan = DB::table('karyawan')
            ->where('id_cabang', $id)
            ->select('nama_karyawan', 'alamat', 'telepon', 'gaji')
            ->orderBy('nama_karyawan')
            ->paginate($request->get('limit', 10));

        return response()->json($karyawan);
    }

    /**
     * Fetch paginated list of sales transactions for a specific branch.
     */
    public function salesTransactionsPaginated(Request $request, $id)
    {
        $transaksi = DB::table('transaksi')
            ->where('id_cabang', $id)
            ->select('kode_transaksi', 'tanggal_waktu', 'metode_pembayaran', 'total_harga')
            ->orderByDesc('tanggal_waktu')
            ->paginate($request->get('limit', 10));

        return response()->json($transaksi);
    }

    /**
     * Fetch paginated list of expenses for a specific branch.
     */
    public function salesExpensesPaginated(Request $request, $id)
    {
        $pengeluaran = DB::table('pengeluaran as p')
            ->join('jenis_pengeluaran as jp', 'p.id_jenis', '=', 'jp.id_jenis')
            ->where('id_cabang', $id)
            ->select('p.tanggal', 'jp.jenis_pengeluaran as jenis', 'p.jumlah', 'p.keterangan')
            ->orderByDesc('p.tanggal')
            ->paginate($request->get('limit', 10));

        return response()->json($pengeluaran);
    }
}
