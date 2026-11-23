<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminSeeder::class, // admin@gmail.com / password (role admin)
            UserSeeder::class,  // andi@example.com / password (role user)
        ]);
    }
}
