<?php

namespace Database\Seeders;

use App\Models\ProdukModel;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProdukSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ProdukModel::insert([
            [
                'nama_produk' => 'Ayam Goreng',
                'kategori' => 'Makanan',
                'deskripsi' => 'Ayam goreng crispy dengan bumbu spesial',
                'harga' => 8000,
                'id_stock_cabang' => 1,
                'gambar_produk' => 'ayam_goreng.jpg',
            ],
            [
                'nama_produk' => 'Nasi',
                'kategori' => 'Makanan',
                'deskripsi' => 'Nasi putih',
                'harga' => 3000,
                'id_stock_cabang' => 2,
                'gambar_produk' => 'nasi.jpg',
            ],
        ]);
    }
}
