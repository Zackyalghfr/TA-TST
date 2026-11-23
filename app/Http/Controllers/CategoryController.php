<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class CategoryController extends Controller
{
    // READ user lihat miliknya, admin lihat semua
    public function index(Request $request)
    {
        try {
            $user = $request->user();

            $categories = $user->isAdmin()
                ? Category::with('user:id,name,email')->latest()->get()
                : $user->categories()->latest()->get();

            return response()->json([
                'message' => 'Daftar kategori.',
                'data'    => $categories,
            ]);
        } catch (Throwable $e) {
            return $this->serverError($e, 'Gagal mengambil daftar kategori.');
        }
    }

    // CREATE
    public function store(Request $request)
    {
        
        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string',
        ]);

        try {
            $category = $request->user()->categories()->create($data);

            return response()->json([
                'message' => 'Kategori berhasil ditambahkan.',
                'data'    => $category,
            ], 201);
        } catch (Throwable $e) {
            return $this->serverError($e, 'Gagal menambahkan kategori.');
        }
    }

    // READ
    public function show(Request $request, Category $category)
    {
        // Otorisasi di luar try agar tetap menghasilkan 403
        $this->authorizeOwner($request, $category);

        try {
            return response()->json([
                'message' => 'Detail kategori.',
                'data'    => $category->load('user:id,name,email'),
            ]);
        } catch (Throwable $e) {
            return $this->serverError($e, 'Gagal mengambil detail kategori.');
        }
    }

    // UPDATE
    public function update(Request $request, Category $category)
    {
        $this->authorizeOwner($request, $category);

        $data = $request->validate([
            'name'        => 'sometimes|required|string|max:100',
            'description' => 'nullable|string',
        ]);

        try {
            $category->update($data);

            return response()->json([
                'message' => 'Kategori berhasil diperbarui.',
                'data'    => $category,
            ]);
        } catch (Throwable $e) {
            return $this->serverError($e, 'Gagal memperbarui kategori.');
        }
    }

    // DELETE hanya admin dijaga middleware role:admin di route
    public function destroy(Category $category)
    {
        try {
            $category->delete();

            return response()->json(['message' => 'Kategori berhasil dihapus.']);
        } catch (Throwable $e) {
            return $this->serverError($e, 'Gagal menghapus kategori.');
        }
    }

    // User biasa hanya boleh akses miliknya sendiri, admin boleh semua
    private function authorizeOwner(Request $request, Category $category): void
    {
        $user = $request->user();

        if (! $user->isAdmin() && $category->user_id !== $user->id) {
            abort(403, 'Anda tidak memiliki akses ke kategori ini.');
        }
    }

    // Catat error asli ke log, kirim pesan aman ke client
    private function serverError(Throwable $e, string $message): JsonResponse
    {
        Log::error($message, [
            'error' => $e->getMessage(),
            'file'  => $e->getFile(),
            'line'  => $e->getLine(),
        ]);

        return response()->json(['message' => $message], 500);
    }
}