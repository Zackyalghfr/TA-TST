<?php

namespace App\Http\Controllers;

use App\Models\User;

abstract class Controller
{
    /**
     * Pengguna yang sedang login (dari token JWT, guard "api").
     */
    protected function authUser(): User
    {
        return auth('api')->user();
    }

    /**
     * Admin boleh melihat data semua pengguna; user biasa hanya miliknya sendiri.
     */
    protected function isAdmin(): bool
    {
        return $this->authUser()->role === 'admin';
    }
}
