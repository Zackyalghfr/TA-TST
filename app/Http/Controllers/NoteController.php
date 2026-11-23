<?php

namespace App\Http\Controllers;

use App\Models\Note;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

// ANGGOTA 3 - CRUD tabel notes
class NoteController extends Controller
{
    use ApiResponse;

    // Kolom yang dipilih (tidak memakai SELECT *)
    private const COLUMNS = ['id', 'user_id', 'task_id', 'title', 'content'];

    // User biasa hanya melihat catatan miliknya; admin melihat semua
    private function scoped(): Builder
    {
        return Note::query()->when(!$this->isAdmin(), function ($query) {
            $query->where('user_id', $this->authUser()->id);
        });
    }

    // GET /api/notes?task_id=
    public function index(Request $request): JsonResponse
    {
        try {
            $notes = $this->scoped()
                ->with(['task:id,title,status', 'user:id,name'])
                ->select(self::COLUMNS)
                ->when($request->query('task_id'), function ($query, $taskId) {
                    $query->where('task_id', $taskId);
                })
                ->latest('id')
                ->get();

            return $this->success($notes, 'Daftar catatan');
        } catch (QueryException $e) {
            Log::error('Gagal membaca daftar catatan: ' . $e->getMessage());

            return $this->error('Terjadi kesalahan pada database', 500);
        }
    }

    // GET /api/notes/{id}
    public function show(string $id): JsonResponse
    {
        try {
            $note = $this->scoped()
                ->with(['task:id,title,status', 'user:id,name'])
                ->select(self::COLUMNS)
                ->find($id);

            if (!$note) {
                return $this->error('Catatan tidak ditemukan', 404);
            }

            return $this->success($note, 'Detail catatan');
        } catch (QueryException $e) {
            Log::error('Gagal membaca detail catatan: ' . $e->getMessage());

            return $this->error('Terjadi kesalahan pada database', 500);
        }
    }

    // POST /api/notes
    public function store(Request $request): JsonResponse
    {
        try {
            $user = $this->authUser();

            $validator = Validator::make($request->all(), [
                // tugas harus milik pengguna sendiri
                'task_id' => ['required', 'integer', Rule::exists('tasks', 'id')->where('user_id', $user->id)],
                'title'   => 'required|string|max:150',
                'content' => 'required|string',
            ]);

            if ($validator->fails()) {
                return $this->error('Data tidak valid', 422, $validator->errors());
            }

            // user_id diisi dari token, bukan dari input klien
            $note = $user->notes()->create($validator->validated());

            return $this->success($note, 'Catatan berhasil ditambahkan', 201);
        } catch (QueryException $e) {
            Log::error('Gagal menyimpan catatan: ' . $e->getMessage());

            return $this->error('Terjadi kesalahan pada database', 500);
        }
    }

    // PUT/PATCH /api/notes/{id}
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $note = $this->scoped()->find($id);

            if (!$note) {
                return $this->error('Catatan tidak ditemukan', 404);
            }

            $validator = Validator::make($request->all(), [
                'task_id' => ['sometimes', 'required', 'integer', Rule::exists('tasks', 'id')->where('user_id', $note->user_id)],
                'title'   => 'sometimes|required|string|max:150',
                'content' => 'sometimes|required|string',
            ]);

            if ($validator->fails()) {
                return $this->error('Data tidak valid', 422, $validator->errors());
            }

            $note->update($validator->validated());

            return $this->success($note, 'Catatan berhasil diubah');
        } catch (QueryException $e) {
            Log::error('Gagal mengubah catatan: ' . $e->getMessage());

            return $this->error('Terjadi kesalahan pada database', 500);
        }
    }

    // DELETE /api/notes/{id} (hanya admin, dijaga middleware role:admin di route)
    public function destroy(string $id): JsonResponse
    {
        try {
            $deleted = $this->scoped()->whereKey($id)->delete();

            if ($deleted === 0) {
                return $this->error('Catatan tidak ditemukan', 404);
            }

            return $this->success(null, 'Catatan berhasil dihapus');
        } catch (QueryException $e) {
            Log::error('Gagal menghapus catatan: ' . $e->getMessage());

            return $this->error('Terjadi kesalahan pada database', 500);
        }
    }
}
