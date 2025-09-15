<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CabangModel extends Model
{
    use HasFactory;
    protected $table = 'cabang';
    protected $primaryKey = 'id_cabang';
    public $incrementing = false;
    protected $fillable = [
        'id_cabang',
        'nama_cabang',
        'alamat',
        'telepon',
        'password_cabang',
    ];

    protected $hidden = [
        'password_cabang',
    ];

    public function user()
    {
        return $this->hasMany('App\Models\UsersModel', 'id_cabang');
    }

    public function karyawan()
    {
        return $this->hasMany('App\Models\KaryawanModel', 'id_cabang');
    }

    public function detailPengeluaran()
    {
        return $this->hasMany(DetailPengeluaranModel::class, 'id_cabang');
    }
}
