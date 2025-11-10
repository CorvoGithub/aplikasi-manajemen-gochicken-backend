<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportDailyController extends Controller
{
    // ===================================================================
    //              --- FUNGSI UNTUK SUPER ADMIN ---
    // ===================================================================

    public function getDailyReport(Request $request)
    {
        try {
            $tanggal = $request->query('tanggal', date('Y-m-d'));

            // Pastikan format tanggal valid
            $date = Carbon::parse($tanggal)->format('Y-m-d');

            // === DEBUG: CEK STATUS TRANSAKSI YANG ADA ===
            $status_check = DB::table('transaksi')
                ->select('status_transaksi', DB::raw('COUNT(*) as count'))
                ->whereRaw('DATE(tanggal_waktu) = ?', [$date])
                ->groupBy('status_transaksi')
                ->get();

            // === DEBUG: CEK DETAIL TRANSAKSI ===
            $detail_check = DB::table('detail_transaksi')
                ->join('transaksi', 'detail_transaksi.id_transaksi', '=', 'transaksi.id_transaksi')
                ->whereRaw('DATE(transaksi.tanggal_waktu) = ?', [$date])
                ->count();

            // === PENJUALAN - UPDATED TO INCLUDE tanggal_waktu ===
            $penjualan = DB::table('detail_transaksi')
                ->join('transaksi', 'detail_transaksi.id_transaksi', '=', 'transaksi.id_transaksi')
                ->join('produk', 'detail_transaksi.id_produk', '=', 'produk.id_produk')
                ->join('cabang', 'transaksi.id_cabang', '=', 'cabang.id_cabang')
                ->select(
                    'produk.nama_produk as produk',
                    'cabang.nama_cabang',
                    'transaksi.tanggal_waktu', // ADDED: Include transaction date
                    DB::raw('SUM(detail_transaksi.jumlah_produk) as jumlah_produk'),
                    DB::raw('AVG(detail_transaksi.harga_item) as harga_item'),
                    DB::raw('SUM(detail_transaksi.jumlah_produk * detail_transaksi.harga_item) as total_penjualan_produk')
                )
                ->whereRaw('DATE(transaksi.tanggal_waktu) = ?', [$date])
                ->whereRaw('LOWER(TRIM(transaksi.status_transaksi)) = ?', ['selesai'])
                ->groupBy('produk.nama_produk', 'produk.id_produk', 'cabang.nama_cabang', 'transaksi.tanggal_waktu')
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
                ->whereRaw('DATE(bahan_baku_harian.tanggal) = ?', [$date])
                ->get();

            $total_modal = $bahan_baku->sum('modal_produk');

            // === PENGELUARAN ===
            $pengeluaran = DB::table('pengeluaran')
                ->join('jenis_pengeluaran', 'pengeluaran.id_jenis', '=', 'jenis_pengeluaran.id_jenis')
                ->join('cabang', 'pengeluaran.id_cabang', '=', 'cabang.id_cabang')
                ->select(
                    'pengeluaran.id_pengeluaran',
                    'pengeluaran.tanggal',
                    'pengeluaran.jumlah',
                    'pengeluaran.cicilan_harian',
                    'pengeluaran.keterangan',
                    'jenis_pengeluaran.jenis_pengeluaran as jenis',
                    'cabang.nama_cabang'
                )
                ->where(function ($query) use ($date) {
                    $query
                        // tampilkan pengeluaran di tanggal itu
                        ->whereDate('pengeluaran.tanggal', $date)
                        // atau cicilan yang masih aktif di bulan yang sama
                        ->orWhere(function ($sub) use ($date) {
                            $sub->whereMonth('pengeluaran.tanggal', Carbon::parse($date)->month)
                                ->whereYear('pengeluaran.tanggal', Carbon::parse($date)->year);
                        });
                })
                ->get();

            $pengeluaran_harian = 0;
            $pengeluaran_detail = [];

            foreach ($pengeluaran as $item) {
                $tanggalMulai = Carbon::parse($item->tanggal);
                $tanggalSekarang = Carbon::parse($date);

                // hitung berapa hari dalam bulan tsb
                $daysInMonth = $tanggalMulai->daysInMonth;
                $tanggalAkhir = $tanggalMulai->copy()->addDays($daysInMonth - 1);

                // jika tanggal laporan masih dalam masa cicilan
                if ($tanggalSekarang->between($tanggalMulai, $tanggalAkhir)) {
                    $harian = $item->cicilan_harian ?: 0;
                    $pengeluaran_harian += $harian;
                } else {
                    $harian = 0;
                }

                $pengeluaran_detail[] = [
                    'id_pengeluaran' => $item->id_pengeluaran,
                    'jenis' => $item->jenis,
                    'keterangan' => $item->keterangan,
                    'jumlah' => $item->jumlah,
                    'cicilan_harian' => $harian,
                    'tanggal' => $item->tanggal,
                    'nama_cabang' => $item->nama_cabang,
                ];
            }

            $total_pengeluaran_bulanan = collect($pengeluaran_detail)->sum('jumlah');

            // === ONLOAN ===
            $onloan = DB::table('transaksi')
                ->whereRaw('DATE(tanggal_waktu) = ?', [$date])
                ->whereRaw('LOWER(TRIM(status_transaksi)) = ?', ['onloan'])
                ->sum('total_harga');

            // === LABA & NETT ===
            $laba_harian = $total_penjualan - $total_modal;
            $nett_income = $laba_harian - $pengeluaran_harian;

            // === WARNING ===
            $peringatan = null;
            if (($total_penjualan + $onloan) < ($total_modal + $pengeluaran_harian)) {
                $peringatan = "⚠️ Pendapatan hari ini lebih kecil dari total pengeluaran dan modal";
            }

            // === DEBUG INFO (hapus setelah testing) ===
            $debug = [
                'query_date' => $date,
                'penjualan_count' => $penjualan->count(),
                'total_transaksi' => DB::table('transaksi')
                    ->whereRaw('DATE(tanggal_waktu) = ?', [$date])
                    ->count(),
                'status_breakdown' => $status_check,
                'detail_transaksi_count' => $detail_check,
                'sample_transaksi' => DB::table('transaksi')
                    ->select('id_transaksi', 'status_transaksi', 'tanggal_waktu', 'total_harga')
                    ->whereRaw('DATE(tanggal_waktu) = ?', [$date])
                    ->limit(3)
                    ->get(),
                'raw_sql_penjualan' => DB::table('detail_transaksi')
                    ->join('transaksi', 'detail_transaksi.id_transaksi', '=', 'transaksi.id_transaksi')
                    ->join('produk', 'detail_transaksi.id_produk', '=', 'produk.id_produk')
                    ->join('cabang', 'transaksi.id_cabang', '=', 'cabang.id_cabang')
                    ->whereRaw('DATE(transaksi.tanggal_waktu) = ?', [$date])
                    ->whereRaw('LOWER(TRIM(transaksi.status_transaksi)) = ?', ['selesai'])
                    ->toSql(),
            ];

            return response()->json([
                'tanggal' => $date,
                'penjualan' => [
                    'detail' => $penjualan,
                    'total_penjualan' => $total_penjualan
                ],
                'bahan_baku' => [
                    'detail' => $bahan_baku,
                    'total_modal_bahan_baku' => $total_modal
                ],
                'pengeluaran' => [
                    'detail' => $pengeluaran_detail,
                    'cicilan_harian' => $pengeluaran_harian,
                    'total_pengeluaran_bulanan' => $total_pengeluaran_bulanan
                ],
                'onloan' => $onloan,
                'penjualan_harian' => $total_penjualan,
                'modal_bahan_baku' => $total_modal,
                'pengeluaran_harian' => $pengeluaran_harian,
                'laba_harian' => $laba_harian,
                'nett_income' => $nett_income,
                'peringatan' => $peringatan,
                'debug' => $debug // Hapus setelah testing
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Terjadi kesalahan saat mengambil data',
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ], 500);
        }
    }

    // ✅ UPDATE STATUS ORDER dari halaman laporan
    public function updateOrderStatus(Request $request, $id)
    {
        try {
            $status = $request->input('status_transaksi');

            $updated = DB::table('transaksi')
                ->where('id_transaksi', $id)
                ->update([
                    'status_transaksi' => $status,
                    'updated_at' => now()
                ]);

            if ($updated) {
                return response()->json([
                    'success' => true,
                    'message' => 'Status order berhasil diperbarui'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan'
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ✅ HAPUS PENGELUARAN (FORCE DELETE - allows deletion even if active)
    public function deletePengeluaran($id)
    {
        try {
            // Cek apakah pengeluaran ada
            $pengeluaran = DB::table('pengeluaran')
                ->where('id_pengeluaran', $id)
                ->first();

            if (!$pengeluaran) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data pengeluaran tidak ditemukan'
                ], 404);
            }

            $deleted = DB::table('pengeluaran')
                ->where('id_pengeluaran', $id)
                ->delete();

            if ($deleted) {
                return response()->json([
                    'success' => true,
                    'message' => 'Pengeluaran berhasil dihapus'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus pengeluaran'
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus pengeluaran',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}