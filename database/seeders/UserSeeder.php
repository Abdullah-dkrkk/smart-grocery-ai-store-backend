<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@smartgrocery.com'],
            [
                'name' => 'Admin',
                'email_verified_at' => now(),
                'password' => bcrypt('admin123'),
                'role' => 'admin',
            ]
        );

        User::firstOrCreate(
            ['email' => 'vendor@smartgrocery.com'],
            [
                'name' => 'Demo Vendor',
                'email_verified_at' => now(),
                'password' => bcrypt('vendor123'),
                'role' => 'vendor',
            ]
        );

        User::firstOrCreate(
            ['email' => 'customer@smartgrocery.com'],
            [
                'name' => 'Demo Customer',
                'email_verified_at' => now(),
                'password' => bcrypt('customer123'),
                'role' => 'customer',
            ]
        );

        User::factory()->customer()->count(10)->create();
        User::factory()->vendor()->count(3)->create();
    }
}
