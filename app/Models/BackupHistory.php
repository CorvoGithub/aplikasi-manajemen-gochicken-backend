<?php
// app/Models/BackupHistory.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BackupHistory extends Model
{
    use HasFactory;

    protected $table = 'backup_history';

    protected $fillable = [
        'filename',
        'file_type',
        'file_size',
        'backup_type',
        'user_id',
        'user_role',
        'file_path',
        'success',
        'error_message'
    ];

    // No relationships for now to avoid issues
}