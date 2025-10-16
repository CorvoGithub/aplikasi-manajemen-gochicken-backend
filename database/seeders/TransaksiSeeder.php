<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TransaksiSeeder extends Seeder
{
    public function run(): void
    {
        $today = Carbon::today();
        $todayStr = $today->format('dmY'); // DDMMYYYY

        // === TRANSAKSI HARI INI (10 transaksi) ===
        $transaksiHariIni = [
            [
                'id_transaksi' => 1,
                'kode_transaksi' => "TRNSK-{$todayStr}-0900",
                'tanggal_waktu' => $today->copy()->setTime(9, 0, 0),
                'total_harga' => 15000,
                'metode_pembayaran' => 'Cash',
                'status_transaksi' => 'Selesai',
                'nama_pelanggan' => null,
                'id_cabang' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_transaksi' => 2,
                'kode_transaksi' => "TRNSK-{$todayStr}-1430",
                'tanggal_waktu' => $today->copy()->setTime(14, 30, 0),
                'total_harga' => 12000,
                'metode_pembayaran' => 'Transfer',
                'status_transaksi' => 'Selesai',
                'nama_pelanggan' => null,
                'id_cabang' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_transaksi' => 3,
                'kode_transaksi' => "TRNSK-{$todayStr}-2015",
                'tanggal_waktu' => $today->copy()->setTime(20, 15, 0),
                'total_harga' => 18000,
                'metode_pembayaran' => 'E-Wallet',
                'status_transaksi' => 'Selesai',
                'nama_pelanggan' => null,
                'id_cabang' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_transaksi' => 4,
                'kode_transaksi' => "TRNSK-{$todayStr}-1005",
                'tanggal_waktu' => $today->copy()->setTime(10, 5, 0),
                'total_harga' => 9000,
                'metode_pembayaran' => 'Cash',
                'status_transaksi' => 'Selesai',
                'nama_pelanggan' => null,
                'id_cabang' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_transaksi' => 5,
                'kode_transaksi' => "TRNSK-{$todayStr}-1715",
                'tanggal_waktu' => $today->copy()->setTime(17, 15, 0),
                'total_harga' => 10000,
                'metode_pembayaran' => 'Transfer',
                'status_transaksi' => 'Selesai',
                'nama_pelanggan' => null,
                'id_cabang' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_transaksi' => 6,
                'kode_transaksi' => "TRNSK-{$todayStr}-0830",
                'tanggal_waktu' => $today->copy()->setTime(8, 30, 0),
                'total_harga' => 7000,
                'metode_pembayaran' => 'E-Wallet',
                'status_transaksi' => 'Selesai',
                'nama_pelanggan' => null,
                'id_cabang' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_transaksi' => 7,
                'kode_transaksi' => "TRNSK-{$todayStr}-1930",
                'tanggal_waktu' => $today->copy()->setTime(19, 30, 0),
                'total_harga' => 6000,
                'metode_pembayaran' => 'Cash',
                'status_transaksi' => 'Selesai',
                'nama_pelanggan' => null,
                'id_cabang' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_transaksi' => 8,
                'kode_transaksi' => "TRNSK-{$todayStr}-1120",
                'tanggal_waktu' => $today->copy()->setTime(11, 20, 0),
                'total_harga' => 120000,
                'metode_pembayaran' => 'Transfer',
                'status_transaksi' => 'Selesai',
                'nama_pelanggan' => 'Vertin',
                'id_cabang' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_transaksi' => 9,
                'kode_transaksi' => "TRNSK-{$todayStr}-1423",
                'tanggal_waktu' => $today->copy()->setTime(14, 23, 0),
                'total_harga' => 150000,
                'metode_pembayaran' => 'E-Wallet',
                'status_transaksi' => 'Selesai',
                'nama_pelanggan' => 'Aleph',
                'id_cabang' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_transaksi' => 10,
                'kode_transaksi' => "TRNSK-{$todayStr}-2135",
                'tanggal_waktu' => $today->copy()->setTime(21, 35, 0),
                'total_harga' => 200000,
                'metode_pembayaran' => 'Cash',
                'status_transaksi' => 'OnLoan',
                'nama_pelanggan' => 'Lucy',
                'id_cabang' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        // === DETAIL TRANSAKSI HARI INI ===
        $detailHariIni = [
            // Transaksi 1: Kopi Hitam (15000)
            ['id_detail' => 1, 'id_transaksi' => 1, 'id_produk' => 3, 'jumlah_produk' => 1, 'harga_item' => 15000, 'subtotal' => 15000],

            // Transaksi 2: Kopi Susu (12000)
            ['id_detail' => 2, 'id_transaksi' => 2, 'id_produk' => 2, 'jumlah_produk' => 1, 'harga_item' => 12000, 'subtotal' => 12000],

            // Transaksi 3: Kopi Hitam (15000) + Donat (6000)
            ['id_detail' => 3, 'id_transaksi' => 3, 'id_produk' => 3, 'jumlah_produk' => 1, 'harga_item' => 15000, 'subtotal' => 15000],
            ['id_detail' => 4, 'id_transaksi' => 3, 'id_produk' => 7, 'jumlah_produk' => 1, 'harga_item' => 6000, 'subtotal' => 6000],

            // Transaksi 4: Matcha Latte (9000)
            ['id_detail' => 5, 'id_transaksi' => 4, 'id_produk' => 4, 'jumlah_produk' => 1, 'harga_item' => 9000, 'subtotal' => 9000],

            // Transaksi 5: Kopi (10000)
            ['id_detail' => 6, 'id_transaksi' => 5, 'id_produk' => 1, 'jumlah_produk' => 1, 'harga_item' => 10000, 'subtotal' => 10000],

            // Transaksi 6: Croissant (7000)
            ['id_detail' => 7, 'id_transaksi' => 6, 'id_produk' => 6, 'jumlah_produk' => 1, 'harga_item' => 7000, 'subtotal' => 7000],

            // Transaksi 7: Donat (6000)
            ['id_detail' => 8, 'id_transaksi' => 7, 'id_produk' => 7, 'jumlah_produk' => 1, 'harga_item' => 6000, 'subtotal' => 6000],

            // Transaksi 8 (Vertin): 8x Kopi Hitam (15000 each)
            ['id_detail' => 9, 'id_transaksi' => 8, 'id_produk' => 3, 'jumlah_produk' => 8, 'harga_item' => 15000, 'subtotal' => 120000],

            // Transaksi 9 (Aleph): 10x Kopi Hitam (15000 each)
            ['id_detail' => 10, 'id_transaksi' => 9, 'id_produk' => 3, 'jumlah_produk' => 10, 'harga_item' => 15000, 'subtotal' => 150000],

            // Transaksi 10 (Lucy - OnLoan): 10x Kopi Susu (12000) + 10x Matcha (9000) + 5x Croissant (7000)
            ['id_detail' => 11, 'id_transaksi' => 10, 'id_produk' => 2, 'jumlah_produk' => 10, 'harga_item' => 12000, 'subtotal' => 120000],
            ['id_detail' => 12, 'id_transaksi' => 10, 'id_produk' => 4, 'jumlah_produk' => 5, 'harga_item' => 9000, 'subtotal' => 45000],
            ['id_detail' => 13, 'id_transaksi' => 10, 'id_produk' => 6, 'jumlah_produk' => 5, 'harga_item' => 7000, 'subtotal' => 35000],
        ];

        // Insert transaksi hari ini
        DB::table('transaksi')->insert($transaksiHariIni);

        // Insert detail transaksi hari ini
        foreach ($detailHariIni as &$detail) {
            $detail['created_at'] = now();
            $detail['updated_at'] = now();
        }
        DB::table('detail_transaksi')->insert($detailHariIni);

        // === DUMMY DATA (Januari–Agustus) ===
        $startMonth = Carbon::create(2025, 1, 1);
        $produkHarga = [
            1 => 10000, 2 => 12000, 3 => 15000, 4 => 9000,
            5 => 5000, 6 => 7000, 7 => 6000,
        ];

        $detailCounter = 50;
        $transaksiCounter = 11;

        for ($m = 0; $m < 8; $m++) {
            $bulan = $startMonth->copy()->addMonths($m);

            for ($w = 0; $w < 4; $w++) {
                $senin = $bulan->copy()->startOfMonth()->addWeeks($w)->next(Carbon::MONDAY);

                foreach ([1, 2] as $cabang) {
                    $produkId = rand(1, 7);
                    $jumlah = rand(1, 3);
                    $harga = $produkHarga[$produkId];
                    $subtotal = $harga * $jumlah;

                    $timeStr = $senin->format('dmY') . '-' . str_pad(rand(800, 2000), 4, '0', STR_PAD_LEFT);

                    // Insert transaksi
                    DB::table('transaksi')->insert([
                        'id_transaksi' => $transaksiCounter,
                        'kode_transaksi' => "TRNSK-{$timeStr}",
                        'tanggal_waktu' => $senin->copy()->setTime(rand(8, 20), rand(0, 59), 0),
                        'total_harga' => $subtotal,
                        'metode_pembayaran' => ['Cash', 'Transfer', 'E-Wallet'][array_rand([0,1,2])],
                        'status_transaksi' => ['Selesai', 'OnLoan'][array_rand([0,1])],
                        'nama_pelanggan' => null,
                        'id_cabang' => $cabang,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    // Insert detail transaksi (FIXED: id_detail)
                    DB::table('detail_transaksi')->insert([
                        'id_detail' => $detailCounter,
                        'id_transaksi' => $transaksiCounter,
                        'id_produk' => $produkId,
                        'jumlah_produk' => $jumlah,
                        'harga_item' => $harga,
                        'subtotal' => $subtotal,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $detailCounter++;
                    $transaksiCounter++;
                }
            }
        }
    }
}
