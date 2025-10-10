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

        $data = [
            // === ORIGINAL DATA (10 transaksi) ===
            [
                'kode_transaksi'    => "TRNSK-{$todayStr}-0900",
                'tanggal_waktu'     => $today->copy()->setTime(9, 0, 0),
                'total_harga'       => 15000,
                'metode_pembayaran' => 'Cash',
                'status_transaksi'  => 'Selesai',
                'nama_pelanggan'    => null,
                'id_cabang'         => 1,
                'created_at'        => now(),
                'updated_at'        => now(),
            ],
            [
                'kode_transaksi'    => "TRNSK-{$todayStr}-1430",
                'tanggal_waktu'     => $today->copy()->setTime(14, 30, 0),
                'total_harga'       => 12000,
                'metode_pembayaran' => 'Transfer',
                'status_transaksi'  => 'Selesai',
                'nama_pelanggan'    => null,
                'id_cabang'         => 1,
                'created_at'        => now(),
                'updated_at'        => now(),
            ],
            [
                'kode_transaksi'    => "TRNSK-{$todayStr}-2015",
                'tanggal_waktu'     => $today->copy()->setTime(20, 15, 0),
                'total_harga'       => 18000,
                'metode_pembayaran' => 'E-Wallet',
                'status_transaksi'  => 'Selesai',
                'nama_pelanggan'    => null,
                'id_cabang'         => 2,
                'created_at'        => now(),
                'updated_at'        => now(),
            ],
            [
                'kode_transaksi'    => "TRNSK-{$todayStr}-1005",
                'tanggal_waktu'     => $today->copy()->setTime(10, 5, 0),
                'total_harga'       => 9000,
                'metode_pembayaran' => 'Cash',
                'status_transaksi'  => 'Selesai',
                'nama_pelanggan'    => null,
                'id_cabang'         => 2,
                'created_at'        => now(),
                'updated_at'        => now(),
            ],
            [
                'kode_transaksi'    => "TRNSK-{$todayStr}-1715",
                'tanggal_waktu'     => $today->copy()->setTime(17, 15, 0),
                'total_harga'       => 10000,
                'metode_pembayaran' => 'Transfer',
                'status_transaksi'  => 'Selesai',
                'nama_pelanggan'    => null,
                'id_cabang'         => 1,
                'created_at'        => now(),
                'updated_at'        => now(),
            ],
            [
                'kode_transaksi'    => "TRNSK-{$todayStr}-0830",
                'tanggal_waktu'     => $today->copy()->setTime(8, 30, 0),
                'total_harga'       => 7000,
                'metode_pembayaran' => 'E-Wallet',
                'status_transaksi'  => 'Selesai',
                'nama_pelanggan'    => null,
                'id_cabang'         => 2,
                'created_at'        => now(),
                'updated_at'        => now(),
            ],
            [
                'kode_transaksi'    => "TRNSK-{$todayStr}-1930",
                'tanggal_waktu'     => $today->copy()->setTime(19, 30, 0),
                'total_harga'       => 6000,
                'metode_pembayaran' => 'Cash',
                'status_transaksi'  => 'Selesai',
                'nama_pelanggan'    => null,
                'id_cabang'         => 1,
                'created_at'        => now(),
                'updated_at'        => now(),
            ],

            // 3 transaksi besar (Vertin, Aleph, Lucy)
            [
                'kode_transaksi'    => "TRNSK-{$todayStr}-1120",
                'tanggal_waktu'     => $today->copy()->setTime(11, 20, 0),
                'total_harga'       => 120000,
                'metode_pembayaran' => 'Transfer',
                'status_transaksi'  => 'Selesai',
                'nama_pelanggan'    => 'Vertin',
                'id_cabang'         => 2,
                'created_at'        => now(),
                'updated_at'        => now(),
            ],
            [
                'kode_transaksi'    => "TRNSK-{$todayStr}-1423",
                'tanggal_waktu'     => $today->copy()->setTime(14, 23, 0),
                'total_harga'       => 150000,
                'metode_pembayaran' => 'E-Wallet',
                'status_transaksi'  => 'Selesai',
                'nama_pelanggan'    => 'Aleph',
                'id_cabang'         => 1,
                'created_at'        => now(),
                'updated_at'        => now(),
            ],
            [
                'kode_transaksi'    => "TRNSK-{$todayStr}-2135",
                'tanggal_waktu'     => $today->copy()->setTime(21, 35, 0),
                'total_harga'       => 200000,
                'metode_pembayaran' => 'Cash',
                'status_transaksi'  => 'OnLoan',
                'nama_pelanggan'    => 'Lucy',
                'id_cabang'         => 1,
                'created_at'        => now(),
                'updated_at'        => now(),
            ],
        ];

        // === DUMMY DATA (Januari–Agustus, tiap Senin, tiap cabang) ===
        $startMonth = Carbon::create(2025, 1, 1);
        $produkHarga = [
            1 => 10000,
            2 => 12000,
            3 => 15000,
            4 => 9000,
            5 => 5000,
            6 => 7000,
            7 => 6000,
        ];

        $detailCounter = 50; // id_detail mulai dari 50 biar ga tabrakan
        $transaksiCounter = 11; // lanjut setelah transaksi ke-10

        for ($m = 0; $m < 8; $m++) { // Januari–Agustus
            $bulan = $startMonth->copy()->addMonths($m);

            // Loop tiap minggu → ambil hari Senin
            for ($w = 0; $w < 4; $w++) {
                $senin = $bulan->copy()->startOfMonth()->addWeeks($w)->next(Carbon::MONDAY);

                foreach ([1, 2] as $cabang) {
                    $produkId = rand(1, 7);
                    $jumlah = rand(1, 3);
                    $harga = $produkHarga[$produkId];
                    $subtotal = $harga * $jumlah;

                    $timeStr = $senin->format('dmY') . '-' . str_pad(rand(800, 2000), 4, '0', STR_PAD_LEFT);

                    $data[] = [
                        'kode_transaksi'    => "TRNSK-{$timeStr}",
                        'tanggal_waktu'     => $senin->copy()->setTime(rand(8, 20), rand(0, 59), 0),
                        'total_harga'       => $subtotal,
                        'metode_pembayaran' => ['Cash', 'Transfer', 'E-Wallet'][array_rand([0,1,2])],
                        'status_transaksi'  => ['Selesai', 'OnLoan'][array_rand([0,1])],
                        'nama_pelanggan'    => null,
                        'id_cabang'         => $cabang,
                        'created_at'        => now(),
                        'updated_at'        => now(),
                    ];

                    // Sinkron detail transaksi (nanti kita masukin ke DetailTransaksiSeeder)
                    DB::table('detail_transaksi')->insert([
                        'id_detail'      => $detailCounter,
                        'id_transaksi'   => $transaksiCounter,
                        'id_produk'      => $produkId,
                        'jumlah_produk'  => $jumlah,
                        'harga_item'     => $harga,
                        'subtotal'       => $subtotal,
                        'created_at'     => now(),
                        'updated_at'     => now(),
                    ]);

                    $detailCounter++;
                    $transaksiCounter++;
                }
            }
        }

        DB::table('transaksi')->insert($data);
    }
}
