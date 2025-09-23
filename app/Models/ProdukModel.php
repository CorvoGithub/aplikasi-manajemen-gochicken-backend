<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProdukModel extends Model
{
    use HasFactory;
    protected $table = 'produk';
    protected $primaryKey = 'id_produk';
    public $incrementing = false;
    protected $fillable = [
        'id_produk',
        'nama_produk',
        'kategori',
        'deskripsi',
        'harga',
        'id_stock_cabang',
        'gambar_produk',
    ];
    protected $appends = ['gambar_produk_url'];

    public function stockCabang()
    {
        return $this->hasMany(StokCabangModel::class, 'id_stock_cabang');
    }

    public function getGambarProdukUrlAttribute()
    {
        return $this->gambar_produk ? asset('storage/' . $this->gambar_produk) : null;
    }
}
