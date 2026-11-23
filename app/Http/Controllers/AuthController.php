<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

// Bersama: satu AuthController untuk seluruh kelompok (tabel users)
class AuthController extends Controller
{
    use ApiResponse;

    // POST /api/register (terbuka)
    public function register(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name'     => 'required|string|max:100',
                'email'    => 'required|email|max:255|unique:users,email',
                'password' => 'required|string|min:8|confirmed',
            ]);

            if ($validator->fails()) {
                return $this->error('Data tidak valid', 422, $validator->errors());
            }

            $data = $validator->validated();

            // role TIDAK diambil dari input: pendaftar publik selalu "user"
            $user = User::create([
                'name'     => $data['name'],
                'email'    => $data['email'],
                'password' => $data['password'], // di-hash otomatis oleh cast "hashed"
                'role'     => 'user',
            ]);

            $token = auth('api')->login($user);

            return $this->success([
                'user'         => $user->only('id', 'name', 'email', 'role'),
                'access_token' => $token,
                'token_type'   => 'Bearer',
                'expires_in'   => auth('api')->factory()->getTTL() * 60,
            ], 'Registrasi berhasil', 201);
        } catch (QueryException $e) {
            Log::error('Gagal registrasi: ' . $e->getMessage());

            return $this->error('Terjadi kesalahan pada database', 500);
        }
    }

    // POST /api/login (terbuka)
    public function login(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'email'    => 'required|email',
                'password' => 'required|string',
            ]);

            if ($validator->fails()) {
                return $this->error('Data tidak valid', 422, $validator->errors());
            }

            $token = auth('api')->attempt($validator->validated());

            // Pesan sama untuk email salah maupun password salah
            if (!$token) {
                return $this->error('Email atau password salah', 401);
            }

            return $this->success([
                'access_token' => $token,
                'token_type'   => 'Bearer',
                'expires_in'   => auth('api')->factory()->getTTL() * 60,
            ], 'Login berhasil');
        } catch (QueryException $e) {
            Log::error('Gagal login: ' . $e->getMessage());

            return $this->error('Terjadi kesalahan pada database', 500);
        }
    }

    // GET /api/me (auth:api)
    public function me(): JsonResponse
    {
        return $this->success(
            auth('api')->user()->only('id', 'name', 'email', 'role'),
            'Profil pengguna'
        );
    }

    // POST /api/logout (auth:api) - token dimasukkan ke blacklist
    public function logout(): JsonResponse
    {
        try {
            auth('api')->logout();

            return $this->success(null, 'Logout berhasil');
        } catch (\Throwable $e) {
            Log::error('Gagal logout: ' . $e->getMessage());

            return $this->error('Gagal logout', 500);
        }
    }
}
