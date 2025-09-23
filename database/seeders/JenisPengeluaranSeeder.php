<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class JenisPengeluaranSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('jenis_pengeluaran')->insert([
            ['jenis_pengeluaran' => 'Gaji karyawan'],
            ['jenis_pengeluaran' => 'Pembelian bahan baku'],
            ['jenis_pengeluaran' => 'Sewa tempat'],
            ['jenis_pengeluaran' => 'Listrik & air'],
            ['jenis_pengeluaran' => 'Gas / Minyak goreng'],
            ['jenis_pengeluaran' => 'Perawatan peralatan'],
            ['jenis_pengeluaran' => 'Promosi / Marketing'],
            ['jenis_pengeluaran' => 'Transportasi & distribusi'],
            ['jenis_pengeluaran' => 'Pajak / Retribusi'],
            ['jenis_pengeluaran' => 'Lain-lain'],
        ]);
    }
}
