# TA TST – Task Manager API (Laravel 13 + JWT + RBAC)

REST API manajemen tugas mahasiswa. Tabel bersama: `users`. Tiap anggota memegang satu tabel.

| Anggota | Tabel | Model / Controller | Migration |
|---|---|---|---|
| 1 | `tasks` | `Task` / `TaskController` | `2026_11_23_032500_create_tasks_table` |
| 2 | `categories` | `Category` / `CategoryController` | `2026_11_23_032406_create_categories_table` |
| 3 | `notes` | `Note` / `NoteController` | `2026_11_23_032600_create_notes_table` |
| 4 | `schedules` | `Schedule` / `ScheduleController` | `2026_11_23_032700_create_schedules_table` |
| Bersama | `users` | `User` / `AuthController` / `ApiResponse` / `RoleMiddleware` | bawaan Laravel + `add_role_to_users_table` |

ERD: `docs/ERD.png` (sumber `docs/ERD.mmd`). Postman: `docs/TA-TST.postman_collection.json` dan environment `docs/TA-TST.postman_environment.json`.
Daftar endpoint lengkap, kredensial uji, dan nama database ada di `INFO_KELOMPOK4.txt`.

## Setup (sekali per mesin)

Package JWT (`php-open-source-saver/jwt-auth`) dan `config/jwt.php` sudah ada di proyek, jadi cukup:

```bash
composer install
copy .env.example .env               # lewati jika file .env sudah ada (di ZIP pengumpulan sudah disertakan)
php artisan key:generate
php artisan jwt:secret
```

Opsional (kerapian): Sanctum tidak dipakai karena materi memakai JWT. Jika ingin dibersihkan, jalankan
`composer remove laravel/sanctum`, lalu hapus `config/sanctum.php` dan migration `create_personal_access_tokens_table`.

Buat database `ta_tst` di phpMyAdmin (utf8mb4_unicode_ci), lalu:

```bash
php artisan config:clear
php artisan migrate:fresh --seed      # seed: admin@gmail.com / password, andi@example.com / password
php artisan route:list --path=api
php artisan migrate:status
php artisan serve                     # http://127.0.0.1:8000
```

## Endpoint

Semua response memakai `ApiResponse`: `{success, message, data}` atau `{success:false, message, errors}`.

| Method | Path | Akses |
|---|---|---|
| GET | `/api/ping` | terbuka |
| POST | `/api/register`, `/api/login` | terbuka |
| GET | `/api/me`, POST `/api/logout` | login |
| GET, POST | `/api/{tasks,categories,notes,schedules}` | login |
| GET, PUT/PATCH | `/api/{tasks,categories,notes,schedules}/{id}` | login |
| DELETE | `/api/{tasks,categories,notes,schedules}/{id}` | **admin saja** |

Filter: `tasks?status=&category_id=&user_id=(admin)`, `categories?search=`, `notes?task_id=`, `schedules?task_id=&schedule_date=`.

## Matriks hak akses (RBAC)

| Aksi | admin | user |
|---|---|---|
| GET / POST / PUT / PATCH | ✓ (semua data) | ✓ (hanya data miliknya) |
| DELETE | ✓ | ✗ (403) |

Data milik user lain dijawab **404** (keberadaannya tidak dibocorkan). `user_id` selalu diisi dari token, bukan dari input.
Ingin user boleh menghapus data miliknya sendiri? Pindahkan route DELETE keluar dari grup `role:admin` di `routes/api.php`; `scoped()` di tiap controller sudah membatasi ke data miliknya.

## Status code

200 sukses · 201 dibuat · 401 token tidak ada/salah/kedaluwarsa · 403 role kurang · 404 tidak ditemukan · 405 method salah · 422 validasi gagal · 500 error database (detail hanya di `storage/logs/laravel.log`).

## Bukti untuk tugas

* **route:list / migrate:status / struktur tabel**: `php artisan route:list --path=api`, `php artisan migrate:status`, `php artisan db:table tasks` (dst.).
* **Jumlah query**: saat `APP_DEBUG=true` tiap response punya header `X-Query-Count` (tab *Headers* di Postman). Angka sudah termasuk 1 query user dari token. Untuk membandingkan, hapus sementara `->with(...)` di `index()` lalu kirim ulang.
* **500**: matikan MySQL, jalankan folder 7 di Postman, lalu tunjukkan `laravel.log`.
* **Collection**: import collection dan environment `TA-TST Local`, pilih environment itu, lalu jalankan folder 0 sampai 6 (Collection Runner): 0 Health Check, 1 Authentication, 2 Tasks, 3 Categories, 4 Notes, 5 Schedules, 6 Authorization dan Ownership. Variabel `admin_token`, `user_token`, dan id data terisi otomatis sebagai environment variable. Folder 7 hanya uji manual untuk response 500.
