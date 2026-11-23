<?php

namespace App\Http\Middleware;

use App\Traits\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * RBAC: dipakai sebagai ->middleware('role:admin') atau ->middleware('role:admin,user').
 * Harus dipasang SETELAH auth:api supaya token tidak valid = 401 lebih dulu, role kurang = 403.
 */
class RoleMiddleware
{
    use ApiResponse;

    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = auth('api')->user();

        if (!$user) {
            return $this->error('Token tidak valid atau tidak ada', 401);
        }

        // Role dibaca dari database (bukan dari payload token) sehingga perubahan role langsung berlaku
        if (!in_array($user->role, $roles, true)) {
            return $this->error('Anda tidak memiliki akses ke sumber daya ini', 403);
        }

        return $next($request);
    }
}
