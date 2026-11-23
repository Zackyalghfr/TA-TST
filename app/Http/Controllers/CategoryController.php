<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    use ApiResponse;

    private const COLUMNS = ['id', 'user_id', 'name', 'description'];

    private function scoped(): Builder
    {
        return Category::query()->when(!$this->isAdmin(), function ($query) {
            $query->where('user_id', $this->authUser()->id);
        });
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $categories = $this->scoped()
                ->select(self::COLUMNS) 
                ->with('user:id,name')
                ->withCount('tasks')
                ->when($request->query('search'), function ($query, $search) {
                    $query->where('name', 'like', '%' . $search . '%');
                })
                ->latest('id')
                ->get();

            return $this->success($categories, 'Daftar kategori');
        } catch (QueryException $e) {
            Log::error('Gagal membaca daftar kategori: ' . $e->getMessage());

            return $this->error('Terjadi kesalahan pada database', 500);
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $category = $this->scoped()
                ->with([
                    'user:id,name',
                    'tasks:id,category_id,title,status,deadline',
                ])
                ->select(self::COLUMNS)
                ->find($id);

            if (!$category) {
                return $this->error('Kategori tidak ditemukan', 404);
            }

            return $this->success($category, 'Detail kategori');
        } catch (QueryException $e) {
            Log::error('Gagal membaca detail kategori: ' . $e->getMessage());

            return $this->error('Terjadi kesalahan pada database', 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $user = $this->authUser();

            $validator = Validator::make($request->all(), [
                'name'        => ['required', 'string', 'max:100', Rule::unique('categories', 'name')->where('user_id', $user->id)],
                'description' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return $this->error('Data tidak valid', 422, $validator->errors());
            }

            $category = $user->categories()->create($validator->validated());

            return $this->success($category, 'Kategori berhasil ditambahkan', 201);
        } catch (QueryException $e) {
            Log::error('Gagal menyimpan kategori: ' . $e->getMessage());

            return $this->error('Terjadi kesalahan pada database', 500);
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $category = $this->scoped()->find($id);

            if (!$category) {
                return $this->error('Kategori tidak ditemukan', 404);
            }

            $validator = Validator::make($request->all(), [
                'name'        => [
                    'sometimes', 'required', 'string', 'max:100',
                    Rule::unique('categories', 'name')->where('user_id', $category->user_id)->ignore($category->id),
                ],
                'description' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return $this->error('Data tidak valid', 422, $validator->errors());
            }

            $category->update($validator->validated());

            return $this->success($category, 'Kategori berhasil diubah');
        } catch (QueryException $e) {
            Log::error('Gagal mengubah kategori: ' . $e->getMessage());

            return $this->error('Terjadi kesalahan pada database', 500);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $deleted = $this->scoped()->whereKey($id)->delete();

            if ($deleted === 0) {
                return $this->error('Kategori tidak ditemukan', 404);
            }

            return $this->success(null, 'Kategori berhasil dihapus');
        } catch (QueryException $e) {
            Log::error('Gagal menghapus kategori: ' . $e->getMessage());

            return $this->error('Terjadi kesalahan pada database', 500);
        }
    }
}
