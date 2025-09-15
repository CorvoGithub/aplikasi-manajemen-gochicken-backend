<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PengeluaranModel extends Model
{
    use HasFactory;

    protected $table = 'pengeluaran';
    protected $primaryKey = 'id_pengeluaran';
    public $incrementing = false;

    protected $fillable = [
        'id_pengeluaran',
        'id_detail_pengeluaran',
        'tanggal',
        'jumlah',
        'keterangan',
    ];

    // Relasi ke jenis pengeluaran
    public function jenisPengeluaran()
    {
        return $this->belongsTo(JenisPengeluaranModel::class, 'id_jenis_pengeluaran');
    }

    // Relasi ke detail pengeluaran
    public function detailPengeluaran()
    {
        return $this->hasMany(DetailPengeluaranModel::class, 'id_pengeluaran', 'id_pengeluaran');
    }
}
