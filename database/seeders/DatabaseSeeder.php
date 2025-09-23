<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            ProdukSeeder::class,
            StokCabangSeeder::class,
            KaryawanSeeder::class,
            CabangSeeder::class,
            AdminCabangSeeder::class,
            SuperAdminSeeder::class,
            TransaksiSeeder::class,
            PengeluaranSeeder::class,
            JenisPengeluaranSeeder::class
            // DetailTransaksiSeeder::class
        ]);
    }
}
