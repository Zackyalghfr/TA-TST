<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

// Akun uji untuk Postman: Andi (role user). Password di-hash otomatis oleh cast "hashed".
class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'andi@example.com'],
            [
                'name'     => 'Andi',
                'password' => 'password',
                'role'     => 'user',
            ]
        );
    }
}
