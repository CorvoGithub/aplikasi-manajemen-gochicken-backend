<?php

namespace Database\Seeders;

use App\Models\StokCabangModel;
use Illuminate\Database\Seeder;

class StokCabangSeeder extends Seeder
{
    public function run(): void
    {
        $data = [];

        // 2 cabang, masing-masing punya semua produk (1-7)
        for ($cabangId = 1; $cabangId <= 2; $cabangId++) {
            for ($produkId = 1; $produkId <= 7; $produkId++) {
                $data[] = [
                    'id_cabang' => $cabangId,
                    'id_produk' => $produkId,
                    'jumlah_stok' => rand(10, 50),
                ];
            }
        }

        StokCabangModel::insert($data);
    }
}
