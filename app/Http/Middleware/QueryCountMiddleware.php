<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Alat bantu bukti "jumlah query" (tugas pekan 11).
 * Saat APP_DEBUG=true, setiap response API diberi header X-Query-Count
 * (lihat di tab Headers pada Postman). Tidak aktif di produksi (APP_DEBUG=false).
 * Jumlah sudah termasuk 1 query untuk mengambil user dari token JWT.
 */
class QueryCountMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!config('app.debug')) {
            return $next($request);
        }

        DB::enableQueryLog();

        $response = $next($request);

        $response->headers->set('X-Query-Count', (string) count(DB::getQueryLog()));

        return $response;
    }
}
