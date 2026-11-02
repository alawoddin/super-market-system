<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Seed the single administrator account. Runs safely again: an existing admin keeps their password.
     */
    public function run(): void
    {
        User::query()->firstOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('123'),
                'email_verified_at' => now(),
                'phone' => null,
                'address' => null,
                'photo' => null,
            ]
        );
    }
}