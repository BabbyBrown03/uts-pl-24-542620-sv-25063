<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Barang extends Model
{
    use HasFactory;

    /**
     * Nama tabel mengikuti migration (bukan pluralisasi otomatis "barangs").
     */
    protected $table = 'barang';

    protected $fillable = [
        'kode_barang',
        'nama_barang',
        'kategori',
        'stok',
        'harga',
    ];

    protected $casts = [
        'stok' => 'integer',
        'harga' => 'decimal:2',
    ];
}