<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProdukModel extends Model
{
    use HasFactory;

    protected $table = 'produk';
    protected $primaryKey = 'id_produk';
    
    // This is the main fix. 'id_produk' is an auto-incrementing key, so this must be true.
    public $incrementing = true;
    
    protected $fillable = [
        'nama_produk',
        'kategori',
        'deskripsi',
        'harga',
        'id_stock_cabang', // <-- This is the column name for the foreign key
        'gambar_produk',
    ];
    protected $appends = ['gambar_produk_url'];

    // This relationship is incorrect. 'id_stock_cabang' is the foreign key on this model,
    // so it should belong to StokCabangModel.
    public function stockCabang()
    {
        return $this->belongsTo(StokCabangModel::class, 'id_stock_cabang');
    }

    public function getGambarProdukUrlAttribute()
    {
        return $this->gambar_produk ? asset('storage/' . $this->gambar_produk) : null;
    }
}
