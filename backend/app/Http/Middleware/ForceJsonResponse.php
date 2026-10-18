<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Memaksa route /api/* diperlakukan sebagai permintaan JSON.
 *
 * Tanpa middleware ini, saat AuthenticationException dilempar pada request
 * yang tidak membawa header "Accept: application/json" (misalnya Postman atau
 * curl yang mengirim wildcard Accept), Laravel akan mencoba me-redirect ke
 * route "login" sehingga status 401 berubah menjadi error 500
 * "Route [login] not defined".
 */
class ForceJsonResponse
{
    public function handle(Request $request, Closure $next)
    {
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}