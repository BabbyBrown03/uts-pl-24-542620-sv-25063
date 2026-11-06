<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    /**
     * GET /api/health - cek kesehatan basis data dan cache.
     *
     * Tidak memerlukan autentikasi supaya Docker Compose &
     * load balancer dapat memantau layanan tanpa token.
     */
    public function check(): JsonResponse
    {
        $databaseHealthy = false;
        $cacheHealthy = false;
        $errors = [];

        // --- Cek basis data ---
        try {
            DB::connection()->getPDO();
            $databaseHealthy = true;
        } catch (\Throwable $e) {
            Log::error('Health check gagal: basis data tidak terhubung', [
                'action' => 'health_check',
                'komponen' => 'database',
                'alasan' => 'koneksi_basis_data_gagal',
                'detail' => 'tidak_dapat_mengakses_basis_data',
            ]);
            $errors['database'] = 'koneksi basis data gagal';
        }

        // --- Cek cache ---
        try {
            Cache::store('file')->put('health_check', 'ok', 1);
            Cache::store('file')->get('health_check');
            $cacheHealthy = true;
        } catch (\Throwable $e) {
            Log::error('Health check gagal: cache tidak dapat ditulis', [
                'action' => 'health_check',
                'komponen' => 'cache',
                'alasan' => 'cache_gagal',
                'detail' => 'tidak_dapat_menulis_cache',
            ]);
            $errors['cache'] = 'cache tidak dapat diakses';
        }

        $healthy = $databaseHealthy && $cacheHealthy;

        return response()->json([
            'status' => $healthy ? 'healthy' : 'unhealthy',
            'components' => [
                'database' => $databaseHealthy ? 'healthy' : 'unhealthy',
                'cache' => $cacheHealthy ? 'healthy' : 'unhealthy',
            ],
            'errors' => $errors,
        ], $healthy ? 200 : 503);
    }
}