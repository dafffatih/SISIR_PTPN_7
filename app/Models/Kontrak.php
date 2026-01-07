<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kontrak extends Model
{
    use HasFactory;

    protected $table = 'kontraks';

    protected $fillable = [
        'nomor_kontrak',
        'loex',
        'nama_pembeli',
        'tgl_kontrak',
        'volume',
        'harga',
        'nilai',
        'inc_ppn',
        'tgl_bayar',
        'unit',
        'mutu',
        'nomor_dosi',
        'tgl_dosi',
        'port',
        'kontrak_sap',
        'dp_sap',
        'so_sap',
        'kode_do',
        'sisa_awal',
        'total_layan',
        'sisa_akhir',
        'jatuh_tempo',
        'origin_file',
    ];

    protected $casts = [
        'tgl_kontrak' => 'date',
        'volume' => 'decimal:2',
        'harga' => 'decimal:2',
        'nilai' => 'decimal:2',
        'total_layan' => 'decimal:2',
        'sisa_akhir' => 'decimal:2',
    ];
}
