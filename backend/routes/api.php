<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BarangController;
use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

// Endpoint publik: hanya login.
Route::post('/login', [AuthController::class, 'login']);

// Endpoint publik untuk pemantauan layanan (Docker healthcheck).
Route::get('/health', [HealthController::class, 'check']);

// Seluruh endpoint lain dilindungi token Sanctum.
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // CRUD barang
    Route::apiResource('barang', BarangController::class);
});