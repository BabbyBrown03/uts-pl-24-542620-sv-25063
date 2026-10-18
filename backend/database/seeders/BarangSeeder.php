<?php

namespace Database\Seeders;

use App\Models\Barang;
use Illuminate\Database\Seeder;

class BarangSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['kode_barang' => 'BRG-001', 'nama_barang' => 'Laptop Asus VivoBook 14', 'kategori' => 'Elektronik', 'stok' => 12, 'harga' => 8750000],
            ['kode_barang' => 'BRG-002', 'nama_barang' => 'Mouse Logitech M221', 'kategori' => 'Aksesoris', 'stok' => 40, 'harga' => 145000],
            ['kode_barang' => 'BRG-003', 'nama_barang' => 'Keyboard Mechanical Keychron K2', 'kategori' => 'Aksesoris', 'stok' => 18, 'harga' => 1100000],
            ['kode_barang' => 'BRG-004', 'nama_barang' => 'Monitor LG 24 inch IPS', 'kategori' => 'Elektronik', 'stok' => 7, 'harga' => 2350000],
            ['kode_barang' => 'BRG-005', 'nama_barang' => 'Printer Canon LBP2900', 'kategori' => 'Perlengkapan', 'stok' => 5, 'harga' => 3250000],
            ['kode_barang' => 'BRG-006', 'nama_barang' => 'Kabel HDMI 2 meter', 'kategori' => 'Aksesoris', 'stok' => 65, 'harga' => 45000],
            ['kode_barang' => 'BRG-007', 'nama_barang' => 'Flashdisk Sandisk 64GB', 'kategori' => 'Aksesoris', 'stok' => 30, 'harga' => 95000],
            ['kode_barang' => 'BRG-008', 'nama_barang' => 'Kursi Kerja Ergonomis', 'kategori' => 'Furnitur', 'stok' => 15, 'harga' => 1450000],
        ];

        foreach ($data as $row) {
            Barang::updateOrCreate(['kode_barang' => $row['kode_barang']], $row);
        }
    }
}