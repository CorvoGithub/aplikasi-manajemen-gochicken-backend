<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TransaksiModel extends Model
{
    use HasFactory;

    protected $table = 'transaksi';
    protected $primaryKey = 'id_transaksi';
    public $incrementing = true;

    protected $fillable = [
        // 'id_transaksi',
        'kode_transaksi',
        'tanggal_waktu',
        'total_harga',
        'metode_pembayaran',
        'status_transaksi',
        'nama_pelanggan',
        'id_cabang',
    ];

    // Relasi ke User
    public function user()
    {
        return $this->belongsTo(UsersModel::class, 'id_user');
    }

    // Relasi ke Cabang (kalau ada model Cabang)
    public function cabang()
    {
        return $this->belongsTo(CabangModel::class, 'id_cabang');
    }

    // Relasi ke Produk
    public function produk()
    {
        return $this->belongsTo(ProdukModel::class, 'id_produk');
    }

    // Relasi ke Detail Transaksi (kalau ada tabel detail transaksi)
    public function detail()
    {
        return $this->belongsTo(DetailTransaksiModel::class, 'id_detail_transaksi');
    }

    /**
     * ✨ PERBAIKAN: Menambahkan relasi 'details' (plural) yang benar.
     * Ini adalah fungsi baru yang dibutuhkan oleh PemesananController.
     * Tipe relasinya adalah hasMany, yang berarti "satu transaksi memiliki banyak detail".
     * Fungsi `detail()` Anda yang lama tidak akan tersentuh sama sekali.
     */
    public function details()
    {
        return $this->hasMany(DetailTransaksiModel::class, 'id_transaksi', 'id_transaksi');
    }
}
