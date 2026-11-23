<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

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
