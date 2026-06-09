<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ArticleSeeder::class,
        ]);

        // Run UserSeeder if users table is empty
        if (\App\Models\User::count() === 0) {
            $this->call([
                UserSeeder::class,
            ]);
        }
    }
}
