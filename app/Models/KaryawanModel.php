<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class KaryawanModel extends Model
{
    use HasFactory;

    protected $table = 'karyawan';
    protected $primaryKey = 'id_karyawan';
    
    // This is the fix. 'id_karyawan' is an auto-incrementing key.
    public $incrementing = true;
    
    protected $fillable = [
        // 'id_karyawan' should not be fillable as it's auto-incremented
        'id_cabang',
        'nama_karyawan',
        'alamat',
        'telepon',
        'gaji',
    ];

    public function cabang()
    {
        return $this->belongsTo(CabangModel::class, 'id_cabang');
    }

    public function detailPengeluaran()
    {
        return $this->hasMany(DetailPengeluaranModel::class, 'id_karyawan');
    }
}