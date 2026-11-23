<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

// ANGGOTA 1 - CRUD tabel tasks
class TaskController extends Controller
{
    use ApiResponse;

    // Kolom yang dipilih (tidak memakai SELECT *)
    private const COLUMNS = ['id', 'user_id', 'category_id', 'title', 'description', 'status', 'deadline'];

    // User biasa hanya melihat tugas miliknya; admin melihat semua
    private function scoped(): Builder
    {
        return Task::query()->when(!$this->isAdmin(), function ($query) {
            $query->where('user_id', $this->authUser()->id);
        });
    }

    // GET /api/tasks?status=&category_id=&user_id=(admin)
    public function index(Request $request): JsonResponse
    {
        try {
            $tasks = $this->scoped()
                ->with(['category:id,name', 'user:id,name'])
                ->select(self::COLUMNS)
                ->when($request->query('status'), function ($query, $status) {
                    $query->where('status', $status);
                })
                ->when($request->query('category_id'), function ($query, $categoryId) {
                    $query->where('category_id', $categoryId);
                })
                ->when($this->isAdmin() ? $request->query('user_id') : null, function ($query, $userId) {
                    $query->where('user_id', $userId);
                })
                ->latest('id')
                ->get();

            return $this->success($tasks, 'Daftar tugas');
        } catch (QueryException $e) {
            Log::error('Gagal membaca daftar tugas: ' . $e->getMessage());

            return $this->error('Terjadi kesalahan pada database', 500);
        }
    }

    // GET /api/tasks/{id} - detail tugas beserta kategori, catatan, dan jadwal
    public function show(string $id): JsonResponse
    {
        try {
            $task = $this->scoped()
                ->with([
                    'category:id,name',
                    'user:id,name',
                    'notes:id,task_id,title,content',
                    'schedules:id,task_id,schedule_date,start_time,end_time',
                ])
                ->select(self::COLUMNS)
                ->find($id);

            if (!$task) {
                return $this->error('Tugas tidak ditemukan', 404);
            }

            return $this->success($task, 'Detail tugas');
        } catch (QueryException $e) {
            Log::error('Gagal membaca detail tugas: ' . $e->getMessage());

            return $this->error('Terjadi kesalahan pada database', 500);
        }
    }

    // POST /api/tasks
    public function store(Request $request): JsonResponse
    {
        try {
            $user = $this->authUser();

            $validator = Validator::make($request->all(), [
                // kategori harus milik pengguna sendiri
                'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where('user_id', $user->id)],
                'title'       => 'required|string|max:150',
                'description' => 'nullable|string',
                'status'      => ['sometimes', 'required', Rule::in(Task::STATUSES)],
                'deadline'    => 'nullable|date',
            ]);

            if ($validator->fails()) {
                return $this->error('Data tidak valid', 422, $validator->errors());
            }

            // user_id diisi dari token, bukan dari input klien
            $task = $user->tasks()->create($validator->validated());

            return $this->success($task, 'Tugas berhasil ditambahkan', 201);
        } catch (QueryException $e) {
            Log::error('Gagal menyimpan tugas: ' . $e->getMessage());

            return $this->error('Terjadi kesalahan pada database', 500);
        }
    }

    // PUT/PATCH /api/tasks/{id}
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $task = $this->scoped()->find($id);

            if (!$task) {
                return $this->error('Tugas tidak ditemukan', 404);
            }

            $validator = Validator::make($request->all(), [
                'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where('user_id', $task->user_id)],
                'title'       => 'sometimes|required|string|max:150',
                'description' => 'nullable|string',
                'status'      => ['sometimes', 'required', Rule::in(Task::STATUSES)],
                'deadline'    => 'nullable|date',
            ]);

            if ($validator->fails()) {
                return $this->error('Data tidak valid', 422, $validator->errors());
            }

            $task->update($validator->validated());

            return $this->success($task, 'Tugas berhasil diubah');
        } catch (QueryException $e) {
            Log::error('Gagal mengubah tugas: ' . $e->getMessage());

            return $this->error('Terjadi kesalahan pada database', 500);
        }
    }

    // DELETE /api/tasks/{id} (hanya admin, dijaga middleware role:admin di route)
    public function destroy(string $id): JsonResponse
    {
        try {
            $deleted = $this->scoped()->whereKey($id)->delete();

            if ($deleted === 0) {
                return $this->error('Tugas tidak ditemukan', 404);
            }

            return $this->success(null, 'Tugas berhasil dihapus');
        } catch (QueryException $e) {
            Log::error('Gagal menghapus tugas: ' . $e->getMessage());

            return $this->error('Terjadi kesalahan pada database', 500);
        }
    }
}
