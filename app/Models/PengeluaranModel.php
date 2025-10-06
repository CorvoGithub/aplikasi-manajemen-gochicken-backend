<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengeluaranModel extends Model
{
    use HasFactory;

    protected $table = 'pengeluaran';
    protected $primaryKey = 'id_pengeluaran';
    public $timestamps = true;

    protected $fillable = [
        'id_cabang',
        'id_jenis',
        'tanggal',
        'jumlah',
        'keterangan',
    ];

    /**
     * ✨ FIX: Defines the relationship to the 'jenis_pengeluaran' table.
     * This allows us to easily get the name of the expense type.
     */
    public function jenisPengeluaran()
    {
        return $this->belongsTo(JenisPengeluaranModel::class, 'id_jenis', 'id_jenis');
    }

    /**
     * ✨ FIX: Defines the relationship to the 'detail_pengeluaran' table.
     * This allows us to get all the detail items for an expense.
     */
    public function details()
    {
        return $this->hasMany(DetailPengeluaranModel::class, 'id_pengeluaran', 'id_pengeluaran');
    }

    /**
     * Relationship to Cabang (Branch)
     */
    public function cabang()
    {
        return $this->belongsTo(CabangModel::class, 'id_cabang', 'id_cabang');
    }
}
