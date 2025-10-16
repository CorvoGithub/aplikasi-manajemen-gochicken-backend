<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class JenisPengeluaranModel extends Model
{
    use HasFactory;

    protected $table = 'jenis_pengeluaran';
    protected $primaryKey = 'id_jenis';
    public $incrementing = false;

    protected $fillable = [
        'id_jenis',
        'jenis_pengeluaran',
    ];

    // Relasi: satu jenis pengeluaran bisa dipakai banyak pengeluaran
    public function pengeluaran()
    {
        return $this->hasMany(PengeluaranModel::class, 'id_jenis');
    }

    // Relasi: satu jenis pengeluaran bisa muncul di banyak detail pengeluaran
    public function detailPengeluaran()
    {
        return $this->hasMany(DetailPengeluaranModel::class, 'id_jenis');
    }
}
