<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BarangRequest extends FormRequest
{
    /**
     * Semua endpoint barang wajib memakai token Sanctum.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Aturan validasi: kode unik, stok & harga tidak boleh negatif.
     */
    public function rules(): array
    {
        $barangId = $this->route('barang')?->id;

        return [
            'kode_barang' => [
                'required',
                'string',
                'max:100',
                // Unik saat membuat, dan mengabaikan diri sendiri saat mengubah.
                Rule::unique('barang', 'kode_barang')->ignore($barangId),
            ],
            'nama_barang' => ['required', 'string', 'max:255'],
            'kategori' => ['required', 'string', 'max:100'],
            'stok' => ['required', 'integer', 'min:0'],
            'harga' => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * Pesan error dalam bahasa Indonesia.
     */
    public function messages(): array
    {
        return [
            'kode_barang.required' => 'Kode barang wajib diisi.',
            'kode_barang.unique' => 'Kode barang sudah digunakan.',
            'nama_barang.required' => 'Nama barang wajib diisi.',
            'kategori.required' => 'Kategori wajib diisi.',
            'stok.required' => 'Stok wajib diisi.',
            'stok.integer' => 'Stok harus berupa bilangan bulat.',
            'stok.min' => 'Stok tidak boleh negatif.',
            'harga.required' => 'Harga wajib diisi.',
            'harga.numeric' => 'Harga harus berupa angka.',
            'harga.min' => 'Harga tidak boleh negatif.',
        ];
    }
}