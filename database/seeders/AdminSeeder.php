<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'budi@gmail.com'],
            [
                'name'     => 'Budi',
                'password' => 'password',
                'role'     => 'admin',
            ]
        );
    }
}