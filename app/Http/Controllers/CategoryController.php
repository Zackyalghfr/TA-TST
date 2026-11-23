<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    // READ user lihat miliknya, admin lihat semua
    public function index(Request $request)
    {
        $user = $request->user();

        $categories = $user->isAdmin()
            ? Category::with('user:id,name,email')->latest()->get()
            : $user->categories()->latest()->get();

        return response()->json([
            'message' => 'Daftar kategori.',
            'data'    => $categories,
        ]);
    }

    // CREATE
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'color'       => 'nullable|regex:/^#[0-9A-Fa-f]{6}$/',
            'description' => 'nullable|string',
        ]);

        $category = $request->user()->categories()->create($data);

        return response()->json([
            'message' => 'Kategori berhasil ditambahkan.',
            'data'    => $category,
        ], 201);
    }

    // READ 
    public function show(Request $request, Category $category)
    {
        $this->authorizeOwner($request, $category);

        return response()->json([
            'message' => 'Detail kategori.',
            'data'    => $category->load('user:id,name,email'),
        ]);
    }

    // UPDATE
    public function update(Request $request, Category $category)
    {
        $this->authorizeOwner($request, $category);

        $data = $request->validate([
            'name'        => 'sometimes|required|string|max:100',
            'color'       => 'sometimes|required|regex:/^#[0-9A-Fa-f]{6}$/',
            'description' => 'nullable|string',
        ]);

        $category->update($data);

        return response()->json([
            'message' => 'Kategori berhasil diperbarui.',
            'data'    => $category,
        ]);
    }

    //  hanya admin (dijaga middleware role:admin di route)
    public function destroy(Category $category)
    {
        $category->delete();

        return response()->json(['message' => 'Kategori berhasil dihapus.']);
    }

    // User biasa hanya boleh akses miliknya sendiri, admin boleh semua
    private function authorizeOwner(Request $request, Category $category): void
    {
        $user = $request->user();

        if (! $user->isAdmin() && $category->user_id !== $user->id) {
            abort(403, 'Anda tidak memiliki akses ke kategori ini.');
        }
    }
}