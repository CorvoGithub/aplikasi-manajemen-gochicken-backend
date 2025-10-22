<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use App\Models\KaryawanModel;
use App\Models\ProdukModel;
use App\Models\PengeluaranModel;
use App\Models\CabangModel;

class DashboardController extends Controller
{

    /**
     * Fungsi helper untuk menerapkan filter waktu ke query.
     */
    private function applyTimeFilter($query, $filter, $dateColumn)
    {
        if ($filter === 'minggu') {
            return $query->whereBetween($dateColumn, [now()->startOfWeek(Carbon::SUNDAY), now()->endOfWeek(Carbon::SATURDAY)]);
        }
        if ($filter === 'bulan') {
            return $query->whereMonth($dateColumn, now()->month)->whereYear($dateColumn, now()->year);
        }
        if ($filter === 'tahun') {
            return $query->whereYear($dateColumn, now()->year);
        }
        return $query; // Jika filter 'all-time' atau tidak valid, tidak ada filter yang ditambahkan.
    }

    // ===================================================================
    // --- FUNGSI UNTUK SUPER ADMIN ---
    // ===================================================================

    /**
     * Menyediakan statistik global statis untuk kartu ringkasan Super Admin.
     */
    // In DashboardController.php, update the globalStats method:

    public function globalStats()
    {
        // Existing calculations
        $revenueMonth = DB::table('transaksi')
            ->whereYear('tanggal_waktu', now()->year)
            ->whereMonth('tanggal_waktu', now()->month)
            ->sum('total_harga');
        
        $transactionsToday = DB::table('transaksi')
            ->whereDate('tanggal_waktu', Carbon::today())
            ->count();
        
        $totalProduk = DB::table('produk')->count();
        $totalCabang = CabangModel::count();
        
        // NEW: Calculate available products across all branches
        $produkTersedia = DB::table('stok_cabang')
            ->where('jumlah_stok', '>', 0)
            ->distinct('id_produk')
            ->count('id_produk');

        return response()->json([
            'status' => 'success',
            'data' => [
                'revenue_month' => (float) $revenueMonth,
                'transactions_today' => (int) $transactionsToday,
                'total_produk' => (int) $totalProduk,
                'total_cabang' => (int) $totalCabang,
                'produk_tersedia' => (int) $produkTersedia, // Add this line
            ],
        ]);
    }
    
    /**
     * ✨ FUNGSI BARU: Mengambil ringkasan harian untuk semua cabang.
     */
    public function dailyBranchSummaries()
    {
        $cabangList = CabangModel::all();
        $summaries = [];

        $totalPendapatanGlobal = 0;
        $totalPengeluaranGlobal = 0;

        foreach ($cabangList as $cabang) {
            $pendapatan = DB::table('transaksi')->where('id_cabang', $cabang->id_cabang)->whereDate('tanggal_waktu', Carbon::today())->sum('total_harga');
            $pengeluaran = DB::table('pengeluaran')->where('id_cabang', $cabang->id_cabang)->whereDate('tanggal', Carbon::today())->sum('jumlah');
            
            $summaries[] = [
                'id_cabang' => $cabang->id_cabang,
                'nama_cabang' => $cabang->nama_cabang,
                'pendapatan_hari_ini' => (float) $pendapatan,
                'pengeluaran_hari_ini' => (float) $pengeluaran,
                'estimasi_laba' => (float) ($pendapatan - $pengeluaran),
            ];
            
            $totalPendapatanGlobal += $pendapatan;
            $totalPengeluaranGlobal += $pengeluaran;
        }

        array_unshift($summaries, [
            'id_cabang' => 'all',
            'nama_cabang' => 'Semua Cabang',
            'pendapatan_hari_ini' => (float) $totalPendapatanGlobal,
            'pengeluaran_hari_ini' => (float) $totalPengeluaranGlobal,
            'estimasi_laba' => (float) ($totalPendapatanGlobal - $totalPengeluaranGlobal),
        ]);

        return response()->json(['status' => 'success', 'data' => $summaries]);
    }
    
    public function globalChart(Request $request)
    {
        $filter = $request->query('filter', 'tahun');
        
        $pendapatan = DB::table('transaksi')
            ->selectRaw("DATE(tanggal_waktu) as tanggal, SUM(total_harga) as total")
            ->when($filter !== 'all-time', fn($q) => $this->applyTimeFilter($q, $filter, 'tanggal_waktu'))
            ->groupBy('tanggal')->orderBy('tanggal')->get();
        
        $pengeluaran = DB::table('pengeluaran')
             ->selectRaw("DATE(tanggal) as tanggal, SUM(jumlah) as total")
             ->when($filter !== 'all-time', fn($q) => $this->applyTimeFilter($q, $filter, 'tanggal'))
             ->groupBy('tanggal')->orderBy('tanggal')->get();

        return response()->json([
            'status' => 'success',
            'data' => [ 'pendapatan' => $pendapatan, 'pengeluaran' => $pengeluaran ]
        ]);
    }

    public function revenueBreakdown(Request $request)
    {
        $cabangId = $request->query('cabang');
        
        $query = DB::table('transaksi')
            ->whereDate('tanggal_waktu', Carbon::today())
            ->where('status_transaksi', 'Selesai');

        if ($cabangId && $cabangId !== 'all') {
            $query->where('id_cabang', $cabangId);
        }

        $revenueBreakdown = $query
            ->select('metode_pembayaran', DB::raw('SUM(total_harga) as total'))
            ->groupBy('metode_pembayaran')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $revenueBreakdown
        ]);
    }
    
    // ===================================================================
    // --- FUNGSI UNTUK ADMIN CABANG (TIDAK DIUBAH) ---
    // ===================================================================

    // Statistik per-cabang
    public function cabangStats($id)
    {
        // Data for top summary cards
        $totalProduk = DB::table('stok_cabang')->where('id_cabang', $id)->count();
        $tersedia = DB::table('stok_cabang')->where('id_cabang', $id)->where('jumlah_stok', '>', 0)->count();
        $todayCount = DB::table('transaksi')->whereDate('tanggal_waktu', Carbon::today())->where('id_cabang', $id)->count();
        $revenueMonth = DB::table('transaksi')->whereYear('tanggal_waktu', Carbon::now()->year)->whereMonth('tanggal_waktu', Carbon::now()->month)->where('id_cabang', $id)->sum('total_harga');
        
        $top = DB::table('detail_transaksi')
            ->join('transaksi', 'detail_transaksi.id_transaksi', '=', 'transaksi.id_transaksi')
            ->where('transaksi.id_cabang', $id)
            ->select('detail_transaksi.id_produk', DB::raw('SUM(detail_transaksi.jumlah_produk) as total_qty'))
            ->groupBy('detail_transaksi.id_produk')
            ->orderByDesc('total_qty')
            ->first();

        $topProductName = null;
        if ($top) {
            $prod = DB::table('produk')->where('id_produk', $top->id_produk)->first();
            $topProductName = $prod ? $prod->nama_produk : null;
        }

        // Data for Daily Financial Summary widget
        $pendapatanHariIni = DB::table('transaksi')
            ->where('id_cabang', $id)
            ->whereDate('tanggal_waktu', Carbon::today())
            ->sum('total_harga');

        $pengeluaranHariIni = DB::table('pengeluaran')
            ->where('id_cabang', $id)
            ->whereDate('tanggal', Carbon::today())
            ->sum('jumlah');
            
        $estimasiLaba = $pendapatanHariIni - $pengeluaranHariIni;
        
        // ✨ NEW: Query for revenue breakdown by payment method for today
        $revenueBreakdown = DB::table('transaksi')
            ->where('id_cabang', $id)
            ->whereDate('tanggal_waktu', Carbon::today())
            ->select('metode_pembayaran', DB::raw('SUM(total_harga) as total'))
            ->groupBy('metode_pembayaran')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                // Old data for top cards
                'total_produk' => (int) $totalProduk,
                'produk_tersedia' => (int) $tersedia,
                'transactions_today' => (int) $todayCount,
                'revenue_month' => (float) $revenueMonth,
                'top_product' => $topProductName,
                // Data for daily summary
                'pendapatan_hari_ini' => (float) $pendapatanHariIni,
                'pengeluaran_hari_ini' => (float) $pengeluaranHariIni,
                'estimasi_laba' => (float) $estimasiLaba,
                // ✨ New data added to the response
                'revenue_breakdown' => $revenueBreakdown,
            ],
        ]);
    }


    public function cabangChart(Request $request, $id)
    {
        $filter = $request->query('filter', 'tahun'); // Default changed to 'tahun' as requested

        // Query pendapatan (dari transaksi)
        $pendapatan = DB::table('transaksi')
            ->selectRaw("DATE(tanggal_waktu) as tanggal, SUM(total_harga) as total")
            ->where('id_cabang', $id)
            ->when($filter === 'minggu', function ($q) {
                $q->whereBetween('tanggal_waktu', [
                    now()->startOfWeek(Carbon::SUNDAY), now()->endOfWeek(Carbon::SATURDAY)
                ]);
            })
            ->when($filter === 'bulan', function ($q) {
                $q->whereYear('tanggal_waktu', now()->year)
                    ->whereMonth('tanggal_waktu', now()->month);
            })
            ->when($filter === 'tahun', function ($q) {
                $q->whereYear('tanggal_waktu', now()->year);
            })
            ->groupBy('tanggal')
            ->orderBy('tanggal')
            ->get();

        // Query pengeluaran
        $pengeluaran = DB::table('pengeluaran')
            ->selectRaw("DATE(tanggal) as tanggal, SUM(jumlah) as total")
            ->where('id_cabang', $id)
            ->when($filter === 'minggu', function ($q) {
                $q->whereBetween('tanggal', [
                    now()->startOfWeek(Carbon::SUNDAY), now()->endOfWeek(Carbon::SATURDAY)
                ]);
            })
            ->when($filter === 'bulan', function ($q) {
                $q->whereYear('tanggal', now()->year)
                    ->whereMonth('tanggal', now()->month);
            })
            ->when($filter === 'tahun', function ($q) {
                $q->whereYear('tanggal', now()->year);
            })
            ->groupBy('tanggal')
            ->orderBy('tanggal')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'pendapatan' => $pendapatan,
                'pengeluaran' => $pengeluaran,
            ]
        ]);
    }

    public function userActivities(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'admin cabang') {
            return response()->json(['status' => 'success', 'data' => [] ]);
        }

        $cabangId = $user->id_cabang;

        // --- Fetch Activities from Different Tables ---

        // Fetch recent karyawan (employees) added by this admin
        $karyawanActivities = KaryawanModel::where('id_cabang', $cabangId)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($item) {
                return [
                    'description' => "Tambah Karyawan baru: {$item->nama_karyawan}",
                    'timestamp' => Carbon::parse($item->created_at)->toISOString(),
                    'type' => 'add'
                ];
            });

        $pengeluaranActivities = PengeluaranModel::where('id_cabang', $cabangId)
        ->orderBy('created_at', 'desc')
        ->take(5)
        ->get()
        ->map(function ($item) {
            return [
                'description' => "Pengeluaran Rp " . number_format($item->jumlah, 0, ',', '.') . " untuk {$item->keterangan}",
                'timestamp' => Carbon::parse($item->created_at)->toISOString(),
                'type' => 'expense'
            ];
        });

        // Fetch recent produk (products) added/updated by this admin's branch
        $produkActivities = ProdukModel::where('id_stock_cabang', $cabangId)
        ->orderBy('created_at', 'desc')
        ->take(5)
        ->get()
        ->map(function ($item) {
            return [
                'description' => "Tambah/Update produk: {$item->nama_produk}",
                'timestamp' => Carbon::parse($item->created_at)->toISOString(),
                'type' => 'update'
            ];
        });
        
        $produkActivities = ProdukModel::where('id_stock_cabang', $cabangId) // This logic might need adjustment based on your schema
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($item) {
                return [
                    'description' => "Tambah/Update produk: {$item->nama_produk}",
                    'timestamp' => Carbon::parse($item->created_at)->toISOString(),
                    'type' => 'update'
                ];
            });

        $allActivities = collect([])->concat($karyawanActivities)
                                      ->concat($pengeluaranActivities)
                                      ->concat($produkActivities);

        $recentActivities = $allActivities->sortByDesc('timestamp')->take(10)->values()->all();

        return response()->json([
            'status' => 'success',
            'data' => $recentActivities
        ]);
    }
}
