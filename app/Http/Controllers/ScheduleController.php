<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

// ANGGOTA 4 - CRUD tabel schedules
class ScheduleController extends Controller
{
    use ApiResponse;

    // Kolom yang dipilih (tidak memakai SELECT *)
    private const COLUMNS = ['id', 'user_id', 'task_id', 'schedule_date', 'start_time', 'end_time'];

    // User biasa hanya melihat jadwal miliknya; admin melihat semua
    private function scoped(): Builder
    {
        return Schedule::query()->when(!$this->isAdmin(), function ($query) {
            $query->where('user_id', $this->authUser()->id);
        });
    }

    // GET /api/schedules?task_id=&schedule_date=YYYY-MM-DD
    public function index(Request $request): JsonResponse
    {
        try {
            $schedules = $this->scoped()
                ->with(['task:id,title,status,deadline', 'user:id,name'])
                ->select(self::COLUMNS)
                ->when($request->query('task_id'), function ($query, $taskId) {
                    $query->where('task_id', $taskId);
                })
                ->when($request->query('schedule_date'), function ($query, $date) {
                    $query->whereDate('schedule_date', $date);
                })
                ->orderBy('schedule_date')
                ->orderBy('start_time')
                ->get();

            return $this->success($schedules, 'Daftar jadwal');
        } catch (QueryException $e) {
            Log::error('Gagal membaca daftar jadwal: ' . $e->getMessage());

            return $this->error('Terjadi kesalahan pada database', 500);
        }
    }

    // GET /api/schedules/{id}
    public function show(string $id): JsonResponse
    {
        try {
            $schedule = $this->scoped()
                ->with(['task:id,title,status,deadline', 'user:id,name'])
                ->select(self::COLUMNS)
                ->find($id);

            if (!$schedule) {
                return $this->error('Jadwal tidak ditemukan', 404);
            }

            return $this->success($schedule, 'Detail jadwal');
        } catch (QueryException $e) {
            Log::error('Gagal membaca detail jadwal: ' . $e->getMessage());

            return $this->error('Terjadi kesalahan pada database', 500);
        }
    }

    // POST /api/schedules
    public function store(Request $request): JsonResponse
    {
        try {
            $user = $this->authUser();

            $validator = Validator::make($request->all(), [
                // tugas harus milik pengguna sendiri
                'task_id'       => ['required', 'integer', Rule::exists('tasks', 'id')->where('user_id', $user->id)],
                'schedule_date' => 'required|date_format:Y-m-d',
                'start_time'    => 'required|date_format:H:i,H:i:s',
                'end_time'      => 'required|date_format:H:i,H:i:s|after:start_time',
            ]);

            if ($validator->fails()) {
                return $this->error('Data tidak valid', 422, $validator->errors());
            }

            // user_id diisi dari token, bukan dari input klien
            $schedule = $user->schedules()->create($validator->validated());

            return $this->success($schedule, 'Jadwal berhasil ditambahkan', 201);
        } catch (QueryException $e) {
            Log::error('Gagal menyimpan jadwal: ' . $e->getMessage());

            return $this->error('Terjadi kesalahan pada database', 500);
        }
    }

    // PUT/PATCH /api/schedules/{id}
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $schedule = $this->scoped()->find($id);

            if (!$schedule) {
                return $this->error('Jadwal tidak ditemukan', 404);
            }

            $validator = Validator::make($request->all(), [
                'task_id'       => ['sometimes', 'required', 'integer', Rule::exists('tasks', 'id')->where('user_id', $schedule->user_id)],
                'schedule_date' => 'sometimes|required|date_format:Y-m-d',
                'start_time'    => 'sometimes|required|date_format:H:i,H:i:s',
                'end_time'      => 'sometimes|required|date_format:H:i,H:i:s',
            ]);

            if ($validator->fails()) {
                return $this->error('Data tidak valid', 422, $validator->errors());
            }

            $data = $validator->validated();

            // Jam selesai harus setelah jam mulai, dihitung dari nilai baru atau nilai lama
            $start = strtotime($data['start_time'] ?? $schedule->start_time);
            $end   = strtotime($data['end_time'] ?? $schedule->end_time);

            if ($end <= $start) {
                return $this->error('Data tidak valid', 422, [
                    'end_time' => ['Jam selesai harus setelah jam mulai.'],
                ]);
            }

            $schedule->update($data);

            return $this->success($schedule, 'Jadwal berhasil diubah');
        } catch (QueryException $e) {
            Log::error('Gagal mengubah jadwal: ' . $e->getMessage());

            return $this->error('Terjadi kesalahan pada database', 500);
        }
    }

    // DELETE /api/schedules/{id} (hanya admin, dijaga middleware role:admin di route)
    public function destroy(string $id): JsonResponse
    {
        try {
            $deleted = $this->scoped()->whereKey($id)->delete();

            if ($deleted === 0) {
                return $this->error('Jadwal tidak ditemukan', 404);
            }

            return $this->success(null, 'Jadwal berhasil dihapus');
        } catch (QueryException $e) {
            Log::error('Gagal menghapus jadwal: ' . $e->getMessage());

            return $this->error('Terjadi kesalahan pada database', 500);
        }
    }
}
