<?php

namespace Tests\Feature;

use App\Models\Barang;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pengujian CRUD barang: 200 / 201 / 401 / 404 / 422.
 */
class BarangApiTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(): string
    {
        $user = User::create([
            'name' => 'Admin Inventaris',
            'email' => 'admin@inventaris.test',
            'password' => bcrypt('password123'),
        ]);

        return $user->createToken('test-token')->plainTextToken;
    }

    private function authed(string $method, string $uri, array $data = [])
    {
        return $this->withHeader('Authorization', 'Bearer '.$this->tokenFor())
            ->json($method, $uri, $data);
    }

    public function test_menampilkan_semua_barang(): void
    {
        Barang::create([
            'kode_barang' => 'BRG-001',
            'nama_barang' => 'Laptop',
            'kategori' => 'Elektronik',
            'stok' => 5,
            'harga' => 7500000,
        ]);

        $response = $this->authed('GET', '/api/barang');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.kode_barang', 'BRG-001');
    }

    public function test_menampilkan_detail_satu_barang(): void
    {
        $barang = Barang::create([
            'kode_barang' => 'BRG-002',
            'nama_barang' => 'Mouse',
            'kategori' => 'Aksesoris',
            'stok' => 10,
            'harga' => 145000,
        ]);

        $this->authed('GET', '/api/barang/'.$barang->id)
            ->assertStatus(200)
            ->assertJsonPath('data.nama_barang', 'Mouse')
            ->assertJsonPath('data.stok', 10);
    }

    public function test_menambah_barang_mengembalikan_201(): void
    {
        $response = $this->authed('POST', '/api/barang', [
            'kode_barang' => 'BRG-003',
            'nama_barang' => 'Keyboard',
            'kategori' => 'Aksesoris',
            'stok' => 8,
            'harga' => 450000,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.kode_barang', 'BRG-003');

        $this->assertDatabaseHas('barang', ['kode_barang' => 'BRG-003']);
    }

    public function test_mengubah_barang_mengembalikan_200(): void
    {
        $barang = Barang::create([
            'kode_barang' => 'BRG-004',
            'nama_barang' => 'Monitor',
            'kategori' => 'Elektronik',
            'stok' => 3,
            'harga' => 2000000,
        ]);

        $this->authed('PUT', '/api/barang/'.$barang->id, [
            'kode_barang' => 'BRG-004',
            'nama_barang' => 'Monitor LG',
            'kategori' => 'Elektronik',
            'stok' => 6,
            'harga' => 2350000,
        ])->assertStatus(200)->assertJsonPath('data.nama_barang', 'Monitor LG');

        $this->assertDatabaseHas('barang', ['id' => $barang->id, 'stok' => 6]);
    }

    public function test_menghapus_barang(): void
    {
        $barang = Barang::create([
            'kode_barang' => 'BRG-005',
            'nama_barang' => 'Printer',
            'kategori' => 'Perlengkapan',
            'stok' => 1,
            'harga' => 3000000,
        ]);

        $this->authed('DELETE', '/api/barang/'.$barang->id)->assertStatus(200);

        $this->assertDatabaseMissing('barang', ['id' => $barang->id]);
    }

    public function test_barang_tidak_ada_mengembalikan_404(): void
    {
        $this->authed('GET', '/api/barang/9999')
            ->assertStatus(404)
            ->assertExactJson(['message' => 'Data yang diminta tidak ditemukan.']);
    }

    public function test_endpoint_tidak_ada_mengembalikan_404(): void
    {
        $this->authed('GET', '/api/tidak-ada')
            ->assertStatus(404)
            ->assertExactJson(['message' => 'Endpoint tidak ditemukan.']);
    }

    public function test_validasi_gagal_mengembalikan_422(): void
    {
        // Stok & harga negatif, kode kosong, dan kode duplikat.
        Barang::create([
            'kode_barang' => 'BRG-006',
            'nama_barang' => 'Kabel',
            'kategori' => 'Aksesoris',
            'stok' => 4,
            'harga' => 45000,
        ]);

        $response = $this->authed('POST', '/api/barang', [
            'kode_barang' => 'BRG-006',
            'nama_barang' => '',
            'kategori' => '',
            'stok' => -1,
            'harga' => -500,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['kode_barang', 'nama_barang', 'kategori', 'stok', 'harga']);

        $this->assertSame(1, Barang::count());
    }

    public function test_crud_membutuhkan_token(): void
    {
        $barang = Barang::create([
            'kode_barang' => 'BRG-007',
            'nama_barang' => 'Flashdisk',
            'kategori' => 'Aksesoris',
            'stok' => 9,
            'harga' => 95000,
        ]);

        $this->getJson('/api/barang')->assertStatus(401);
        $this->getJson('/api/barang/'.$barang->id)->assertStatus(401);
        $this->postJson('/api/barang', [])->assertStatus(401);
        $this->putJson('/api/barang/'.$barang->id, [])->assertStatus(401);
        $this->deleteJson('/api/barang/'.$barang->id)->assertStatus(401);

        // Tidak ada data yang berubah karena semua request ditolak.
        $this->assertDatabaseHas('barang', ['id' => $barang->id, 'stok' => 9]);
    }
}