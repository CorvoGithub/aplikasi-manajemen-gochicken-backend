<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    protected $table = 'audit_logs';

    protected $fillable = [
        'table_name',
        'action',
        'old_data',
        'new_data',
        'record_id',
        'user_id',
        'user_role',
        'ip_address',
        'user_agent',
        'description'
    ];

    protected $casts = [
        'old_data' => 'array',
        'new_data' => 'array',
    ];

    // Relationship to User using id_user as foreign key
    public function user()
    {
        return $this->belongsTo(UsersModel::class, 'user_id', 'id_user');
    }

    // Relationship to Cabang through User
    public function cabang()
    {
        return $this->hasOneThrough(
            CabangModel::class,
            UsersModel::class,
            'id_user', // Foreign key on users table
            'id_cabang', // Foreign key on cabang table
            'user_id', // Local key on audit_logs table
            'id_cabang' // Local key on users table
        );
    }
}