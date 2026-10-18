<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Password di-hash oleh Hash::make, TIDAK pernah disimpan plaintext.
        User::updateOrCreate(
            ['email' => 'admin@inventaris.test'],
            [
                'name' => 'Admin Inventaris',
                'password' => Hash::make('password123'),
            ],
        );

        $this->call(BarangSeeder::class);
    }
}