<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;


Route::get('/ping', function () {
    return ['message' => 'API Laravel berjalan'];
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);


Route::middleware('auth:api')->group(function () {

    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // ---- ANGGOTA 1: tasks -------------------------------------------------
    Route::get('/tasks', [TaskController::class, 'index']);
    Route::get('/tasks/{id}', [TaskController::class, 'show'])->whereNumber('id');
    Route::post('/tasks', [TaskController::class, 'store']);
    Route::match(['put', 'patch'], '/tasks/{id}', [TaskController::class, 'update'])->whereNumber('id');

    // ---- ANGGOTA 2: categories -------------------------------------------
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{id}', [CategoryController::class, 'show'])->whereNumber('id');
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::match(['put', 'patch'], '/categories/{id}', [CategoryController::class, 'update'])->whereNumber('id');

    // ---- ANGGOTA 3: notes --------------------------------------------------
    Route::get('/notes', [NoteController::class, 'index']);
    Route::get('/notes/{id}', [NoteController::class, 'show'])->whereNumber('id');
    Route::post('/notes', [NoteController::class, 'store']);
    Route::match(['put', 'patch'], '/notes/{id}', [NoteController::class, 'update'])->whereNumber('id');

    // ---- ANGGOTA 4: schedules ---------------------------------------------
    Route::get('/schedules', [ScheduleController::class, 'index']);
    Route::get('/schedules/{id}', [ScheduleController::class, 'show'])->whereNumber('id');
    Route::post('/schedules', [ScheduleController::class, 'store']);
    Route::match(['put', 'patch'], '/schedules/{id}', [ScheduleController::class, 'update'])->whereNumber('id');

    // ---- DELETE: hanya admin (di dalam grup auth:api, jadi 401 diperiksa lebih dulu dari 403)
    Route::middleware('role:admin')->group(function () {
        Route::delete('/tasks/{id}', [TaskController::class, 'destroy'])->whereNumber('id');
        Route::delete('/categories/{id}', [CategoryController::class, 'destroy'])->whereNumber('id');
        Route::delete('/notes/{id}', [NoteController::class, 'destroy'])->whereNumber('id');
        Route::delete('/schedules/{id}', [ScheduleController::class, 'destroy'])->whereNumber('id');
    });
});
