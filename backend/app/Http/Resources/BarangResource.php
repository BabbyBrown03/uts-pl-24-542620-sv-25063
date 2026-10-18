<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BarangResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'kode_barang' => $this->kode_barang,
            'nama_barang' => $this->nama_barang,
            'kategori' => $this->kategori,
            'stok' => (int) $this->stok,
            'harga' => (float) $this->harga,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}