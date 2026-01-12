<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    // 1. Definisikan nama tabel secara eksplisit
    protected $table = 'settings';

    // 2. Pastikan fillable sudah benar
    protected $fillable = [
        'key', 
        'value'
    ];
}