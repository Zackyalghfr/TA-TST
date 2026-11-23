<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ANGGOTA 1 - tabel tasks
// Harus dijalankan SETELAH users dan categories (foreign key).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // nullable: tugas boleh belum punya kategori; jika kategori dihapus, category_id menjadi NULL
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 150);
            $table->text('description')->nullable();
            $table->string('status', 20)->default('pending'); // pending | in_progress | completed
            $table->dateTime('deadline')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
