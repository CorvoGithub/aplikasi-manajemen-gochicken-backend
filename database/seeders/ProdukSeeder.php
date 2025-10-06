<?php

namespace Database\Seeders;

use App\Models\ProdukModel;
use Illuminate\Database\Seeder;

class ProdukSeeder extends Seeder
{
    public function run(): void
    {
        ProdukModel::insert([
            [
                'nama_produk' => 'Ayam Goreng Paha Bawah',
                'deskripsi' => 'Paha bawah ayam goreng crispy bumbu spesial',
                'harga' => 10000,
                'kategori' => 'Ayam Goreng',
                'id_stock_cabang' => 1,
                'gambar_produk' => 'ayam_paha_bawah.png',
            ],
            [
                'nama_produk' => 'Ayam Goreng Paha Atas',
                'deskripsi' => 'Paha atas ayam goreng crispy bumbu spesial',
                'harga' => 12000,
                'kategori' => 'Ayam Goreng',
                'id_stock_cabang' => 2,
                'gambar_produk' => 'ayam_paha_atas.png',
            ],
            [
                'nama_produk' => 'Ayam Goreng Dada',
                'deskripsi' => 'Dada ayam goreng crispy bumbu spesial',
                'harga' => 15000,
                'kategori' => 'Ayam Goreng',
                'id_stock_cabang' => 3,
                'gambar_produk' => 'ayam_dada.png',
            ],
            [
                'nama_produk' => 'Ayam Goreng Sayap',
                'deskripsi' => 'Sayap ayam goreng crispy bumbu spesial',
                'harga' => 9000,
                'kategori' => 'Ayam Goreng',
                'id_stock_cabang' => 4,
                'gambar_produk' => 'ayam_sayap.png',
            ],
            [
                'nama_produk' => 'Nasi Putih',
                'deskripsi' => 'Nasi putih pulen',
                'harga' => 5000,
                'kategori' => 'Nasi',
                'id_stock_cabang' => 5,
                'gambar_produk' => 'nasi.jpg',
            ],
            [
                'nama_produk' => 'Kulit Crispy',
                'deskripsi' => 'Kulit ayam goreng crispy renyah',
                'harga' => 7000,
                'kategori' => 'Add On',
                'id_stock_cabang' => 6,
                'gambar_produk' => 'kulit.png',
            ],
            [
                'nama_produk' => 'Usus Crispy',
                'deskripsi' => 'Usus ayam goreng crispy',
                'harga' => 6000,
                'kategori' => 'Add On',
                'id_stock_cabang' => 7,
                'gambar_produk' => 'usus.png',
            ],
        ]);
    }
}
