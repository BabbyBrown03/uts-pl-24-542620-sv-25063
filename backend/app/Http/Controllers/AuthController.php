<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * POST /api/login - validasi kredensial lalu kembalikan token Sanctum.
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $user = User::where('email', $credentials['email'])->first();

        // Pesan sengaja disamakan agar tidak membocorkan email mana yang terdaftar.
        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        // Cabut token lama lalu buat token baru agar satu device = satu token aktif.
        $user->tokens()->delete();
        $token = $user->createToken('inventaris-token')->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil.',
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ], 200);
    }

    /**
     * POST /api/logout - cabut token yang sedang digunakan.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout berhasil.',
        ], 200);
    }

    /**
     * GET /api/me - cek token masih valid (dipakai Postman & frontend).
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Token valid.',
            'data' => $request->user()->only(['id', 'name', 'email']),
        ], 200);
    }
}