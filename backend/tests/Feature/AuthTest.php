<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Pengujian endpoint autentikasi: /api/login, /api/logout, /api/me.
 */
class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin Inventaris',
            'email' => 'admin@inventaris.test',
            'password' => Hash::make('password123'),
        ]);
    }

    public function test_admin_bisa_login_dan_mendapat_token(): void
    {
        $this->admin();

        $response = $this->postJson('/api/login', [
            'email' => 'admin@inventaris.test',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['message', 'token', 'token_type', 'user' => ['id', 'name', 'email']]);

        $this->assertNotEmpty($response->json('token'));
        // Password tidak boleh pernah ikut dikembalikan.
        $this->assertArrayNotHasKey('password', $response->json('user'));
    }

    public function test_login_gagal_dengan_password_salah(): void
    {
        $this->admin();

        $response = $this->postJson('/api/login', [
            'email' => 'admin@inventaris.test',
            'password' => 'password-keliru',
        ]);

        // 422 sesuai aturan soal: validasi gagal.
        $response->assertStatus(422)
            ->assertJsonValidationErrors('email');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_login_ditolak_saat_data_tidak_lengkap(): void
    {
        $response = $this->postJson('/api/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_endpoint_terlindungi_menolak_permintaan_tanpa_token(): void
    {
        $this->getJson('/api/barang')->assertStatus(401);
        $this->getJson('/api/me')->assertStatus(401);
    }

    public function test_endpoint_terlindungi_menolak_token_tidak_valid(): void
    {
        $this->withHeader('Authorization', 'Bearer token-ngawur-123')
            ->getJson('/api/barang')
            ->assertStatus(401);
    }

    public function test_logout_mencabut_token(): void
    {
        $user = $this->admin();

        $token = $user->createToken('test-token')->plainTextToken;
        $this->assertDatabaseCount('personal_access_tokens', 1);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/logout');

        $response->assertStatus(200)->assertJson(['message' => 'Logout berhasil.']);

        // Bukti server-side revocation: record token dihapus dari database,
        // sehingga token tersebut tidak lagi berlaku (request berikutnya
        // dengan token ini akan mendapat 401).
        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->assertDatabaseMissing('personal_access_tokens', [
            'name' => 'test-token',
        ]);
    }
}