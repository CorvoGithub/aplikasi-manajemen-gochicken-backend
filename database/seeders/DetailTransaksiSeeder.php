<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DetailTransaksiSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            // 7 transaksi kecil (NULL pelanggan, 1–5 item)
            ['id_detail' => 10, 'id_transaksi' => 1,  'id_produk' => 1, 'jumlah_produk' => 1,  'harga_item' => 15000, 'subtotal' => 15000, 'created_at' => now(), 'updated_at' => now()],
            ['id_detail' => 12, 'id_transaksi' => 2,  'id_produk' => 2, 'jumlah_produk' => 1,  'harga_item' => 12000, 'subtotal' => 12000, 'created_at' => now(), 'updated_at' => now()],
            ['id_detail' => 15, 'id_transaksi' => 3,  'id_produk' => 3, 'jumlah_produk' => 2,  'harga_item' => 9000,  'subtotal' => 18000, 'created_at' => now(), 'updated_at' => now()],
            ['id_detail' => 20, 'id_transaksi' => 4,  'id_produk' => 4, 'jumlah_produk' => 1,  'harga_item' => 9000,  'subtotal' => 9000,  'created_at' => now(), 'updated_at' => now()],
            ['id_detail' => 25, 'id_transaksi' => 5,  'id_produk' => 5, 'jumlah_produk' => 2,  'harga_item' => 5000,  'subtotal' => 10000, 'created_at' => now(), 'updated_at' => now()],
            ['id_detail' => 30, 'id_transaksi' => 6,  'id_produk' => 6, 'jumlah_produk' => 1,  'harga_item' => 7000,  'subtotal' => 7000,  'created_at' => now(), 'updated_at' => now()],
            ['id_detail' => 32, 'id_transaksi' => 7,  'id_produk' => 7, 'jumlah_produk' => 1,  'harga_item' => 6000,  'subtotal' => 6000,  'created_at' => now(), 'updated_at' => now()],

            // 3 transaksi besar (Vertin, Aleph, Lucy → banyak item)
            ['id_detail' => 35, 'id_transaksi' => 8,  'id_produk' => 1, 'jumlah_produk' => 12, 'harga_item' => 10000, 'subtotal' => 120000, 'created_at' => now(), 'updated_at' => now()],
            ['id_detail' => 40, 'id_transaksi' => 9,  'id_produk' => 3, 'jumlah_produk' => 10, 'harga_item' => 15000, 'subtotal' => 150000, 'created_at' => now(), 'updated_at' => now()],
            ['id_detail' => 45, 'id_transaksi' => 10, 'id_produk' => 5, 'jumlah_produk' => 40, 'harga_item' => 5000,  'subtotal' => 200000, 'created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('detail_transaksi')->insert($data);
    }
}
