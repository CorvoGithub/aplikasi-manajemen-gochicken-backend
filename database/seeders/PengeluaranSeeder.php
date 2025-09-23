<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PengeluaranSeeder extends Seeder
{
    public function run(): void
    {
        $jenisMap = [
            'Gaji karyawan' => 1,
            'Pembelian bahan baku' => 2,
            'Sewa tempat' => 3,
            'Listrik & air' => 4,
            'Gas / Minyak goreng' => 5,
            'Perawatan peralatan' => 6,
            'Promosi / Marketing' => 7,
            'Transportasi & distribusi' => 8,
            'Pajak / Retribusi' => 9,
            'Lain-lain' => 10,
        ];

        $data = [];
        $start = Carbon::create(2025, 1, 1);

        // loop 9 bulan
        for ($i = 0; $i < 9; $i++) {
            $bulan = $start->copy()->addMonths($i);

            foreach ([1, 2] as $cabangId) {
                // pengeluaran tetap
                $data[] = [
                    'id_cabang' => $cabangId,
                    'id_jenis' => $jenisMap['Gaji karyawan'],
                    'tanggal' => $bulan->copy()->day(1),
                    'jumlah' => 5000000,
                    'keterangan' => 'Gaji karyawan bulan ' . $bulan->format('F Y'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $data[] = [
                    'id_cabang' => $cabangId,
                    'id_jenis' => $jenisMap['Sewa tempat'],
                    'tanggal' => $bulan->copy()->day(1),
                    'jumlah' => 3000000,
                    'keterangan' => 'Sewa tempat bulan ' . $bulan->format('F Y'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $data[] = [
                    'id_cabang' => $cabangId,
                    'id_jenis' => $jenisMap['Pembelian bahan baku'],
                    'tanggal' => $bulan->copy()->day(rand(2, 5)),
                    'jumlah' => rand(3000000, 6000000),
                    'keterangan' => 'Pembelian bahan baku bulan ' . $bulan->format('F Y'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                // pengeluaran tambahan acak (1–3 jenis per bulan)
                $optional = collect(array_slice($jenisMap, 3))->random(rand(1, 3));
                foreach ($optional as $jenis => $idJenis) {
                    $data[] = [
                        'id_cabang' => $cabangId,
                        'id_jenis' => $idJenis,
                        'tanggal' => $bulan->copy()->day(rand(6, 25)),
                        'jumlah' => rand(500000, 2000000),
                        'keterangan' => $jenis . ' bulan ' . $bulan->format('F Y'),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        }

        DB::table('pengeluaran')->insert($data);
    }
}
