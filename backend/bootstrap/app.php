<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Semua route /api/* diperlakukan sebagai permintaan JSON supaya
        // error (401/404/422) selalu dibalas JSON, bukan redirect HTML.
        $middleware->api(prepend: [
            \App\Http\Middleware\ForceJsonResponse::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Pastikan SEMUA error pada route /api/* selalu dibalas JSON,
        // termasuk 404 saat route model binding tidak menemukan data.
        $exceptions->shouldRenderJsonWhen(
            fn ($request, Throwable $e) => $request->is('api/*') || $request->expectsJson()
        );

        // Pesan error generik agar detail internal (nama class, stack trace)
        // tidak pernah diekspos ke client, apa pun nilai APP_DEBUG.
        $exceptions->render(function (Throwable $e, $request) {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            return match (true) {
                // Route model binding gagal => resource tidak ditemukan.
                $e instanceof ModelNotFoundException,
                $e->getPrevious() instanceof ModelNotFoundException => response()->json(
                    ['message' => 'Data yang diminta tidak ditemukan.'], 404
                ),
                $e instanceof NotFoundHttpException => response()->json(
                    ['message' => 'Endpoint tidak ditemukan.'], 404
                ),
                $e instanceof MethodNotAllowedHttpException => response()->json(
                    ['message' => 'Metode HTTP tidak diizinkan.'], 405
                ),
                default => null,
            };
        });
    })->create();