<?php

namespace Database\Seeders;

use App\Models\UsersModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class KasirSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        UsersModel::insert([
            [
                'nama'=>'Kasir Cabang Cimahi',
                'email'=>'kasir_cimahi@gmail.com',
                'password'=>null,
                'role'=>'kasir',
                'id_cabang'=>1,
            ],

            [
                'nama'=>'Kasir Cabang Bandung',
                'email'=>'kasir_bandung@gmail.com',
                'password'=>null,
                'role'=>'kasir',
                'id_cabang'=>2,
            ],
        ]);
    }
}