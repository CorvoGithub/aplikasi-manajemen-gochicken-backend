<?php

namespace Database\Seeders;

use App\Models\BahanBakuModel;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BahanBakuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        BahanBakuModel::insert([
            [
                'nama_bahan'=>'Ayam',
                'harga_satuan'=>20000,
                'jumlah_stok'=>15,
            ],

            [
                'nama_bahan'=>'Tepung',
                'harga_satuan'=>22000,
                'jumlah_stok'=>5,
            ],
        ]);
    }
}
