<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BahanBakuModel extends Model
{
    use HasFactory;

    protected $table = 'bahan_baku';
    protected $primaryKey = 'id_bahan_baku';
    public $incrementing = false;

    protected $fillable = [
        'id_bahan_baku',
        'nama_bahan',
        'satuan',
        'harga_satuan',
        'jumlah_stok',
    ];

    // Relasi: satu bahan baku bisa muncul di banyak detail pengeluaran
    public function detailPengeluaran()
    {
        return $this->hasMany(DetailPengeluaranModel::class, 'id_bahan_baku', 'id_bahan_baku');
    }
}
