<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

/**
 * Satu bentuk response standar untuk SELURUH anggota kelompok.
 *
 *  Sukses : { "success": true,  "message": "...", "data": ... }
 *  Gagal  : { "success": false, "message": "...", "errors": ... }
 */
trait ApiResponse
{
    protected function success(
        mixed $data = null,
        string $message = 'Berhasil',
        int $status = 200
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $status);
    }

    protected function error(
        string $message,
        int $status,
        mixed $errors = null
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors'  => $errors,
        ], $status);
    }
}
