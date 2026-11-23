<?php

use App\Traits\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
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
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);

        // Header X-Query-Count (hanya aktif jika APP_DEBUG=true) untuk bukti jumlah query
        $middleware->api(append: [
            \App\Http\Middleware\QueryCountMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Semua error untuk /api/* memakai bentuk standar: success, message, errors.
        $format = new class {
            use ApiResponse;

            public function make(string $message, int $status)
            {
                return $this->error($message, $status);
            }
        };

        // 401: token tidak ada / salah / kedaluwarsa
        $exceptions->render(function (AuthenticationException $e, Request $request) use ($format) {
            if ($request->is('api/*')) {
                return $format->make('Token tidak valid atau tidak ada', 401);
            }
        });

        // 404: URL tidak dikenal atau id bukan angka (whereNumber)
        $exceptions->render(function (NotFoundHttpException $e, Request $request) use ($format) {
            if ($request->is('api/*')) {
                return $format->make('Endpoint tidak ditemukan', 404);
            }
        });

        // 500: error database yang terjadi di luar Controller (mis. saat guard JWT mencari user).
        // Detail teknis hanya masuk log (storage/logs/laravel.log), klien menerima pesan umum.
        $exceptions->render(function (QueryException $e, Request $request) use ($format) {
            if ($request->is('api/*')) {
                return $format->make('Terjadi kesalahan pada database', 500);
            }
        });

        // 405: method tidak diizinkan pada URL tersebut
        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) use ($format) {
            if ($request->is('api/*')) {
                return $format->make('Method tidak diizinkan', 405);
            }
        });
    })->create();
