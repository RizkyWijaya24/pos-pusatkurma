<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Admin User
        User::updateOrCreate(
            ['email' => 'admin@pusatkurma.com'],
            [
                'name' => 'Admin Pusat Kurma',
                'password' => Hash::make('AdminSecurePass123!'),
                'role' => 'admin',
            ]
        );

        // 2. Kasir User
        User::updateOrCreate(
            ['email' => 'kasir@pusatkurma.com'],
            [
                'name' => 'Kasir Pusat Kurma',
                'password' => Hash::make('KasirSecurePass123!'),
                'role' => 'kasir',
            ]
        );

        // 3. Owner User
        User::updateOrCreate(
            ['email' => 'owner@pusatkurma.com'],
            [
                'name' => 'Owner Pusat Kurma',
                'password' => Hash::make('OwnerSecurePass123!'),
                'role' => 'owner',
            ]
        );
    }
}
